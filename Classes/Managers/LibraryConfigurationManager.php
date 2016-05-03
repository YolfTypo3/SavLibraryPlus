<?php
namespace SAV\SavLibraryPlus\Managers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy <yolf.typo3@orange.fr>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use SAV\SavLibraryPlus\Managers\FormConfigurationManager;
use SAV\SavLibraryPlus\Managers\AdditionalHeaderManager;
use SAV\SavLibraryPlus\Managers\FieldConfigurationManager;
use SAV\SavLibraryPlus\Controller\FlashMessages;

/**
 * General configuration manager
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class LibraryConfigurationManager extends AbstractManager
{

    /**
     * The icons path
     *
     * @var string
     */
    public static $iconRootPath = 'Resources/Public/Icons';

    /**
     * The images path
     *
     * @var string
     */
    public static $imageRootPath = 'Resources/Public/Images';

    /**
     * The styles path
     *
     * @var string
     */
    public static $stylesRootPath = 'Resources/Public/Styles';

    /**
     * The private styles path (for compatibility with previous generated extensions)
     *
     * @var string
     */
    public static $stylesPrivateRootPath = 'Resources/Private/Styles';

    /**
     * JavaScript root path
     *
     * @var string
     */
    public static $javaScriptRootPath = 'Resources/Public/JavaScript';

    /**
     * The language path
     *
     * @var string
     */
    protected static $languageRootPath = 'Resources/Private/Language';

    /**
     * The flexforms path
     *
     * @var string
     */
    protected static $libraryRootPath = 'Configuration/Library';

    /**
     * Allowed icon file name extensions
     *
     * @var string
     */
    protected static $allowedIconFileNameExtensions = '.gif,.png,.jpg,.jpeg';

    /**
     * The library configuration
     *
     * @var array
     */
    private $libraryConfiguration;

    /**
     * The images directory
     *
     * @var string
     */
    private $imagesDirectory;

    /**
     * Initializes the configuration
     *
     * @return none
     */
    public function initialize()
    {
        // Checks if the extension is under maintenance
        if ($this->checkIfExtensionIsUnderMaintenance() === TRUE)
            return FALSE;

            // Sets the library configuration
        if ($this->setLibraryConfiguration() === FALSE)
            return FALSE;

            // Checks the compatibility
        if ($this->checkCompatibility() === FALSE)
            return FALSE;

            // Adds the cascading style sheets
        self::addCascadingStyleSheets();

        // Injects the form configuration in its manager
        $formConfiguration = $this->getFormConfiguration();
        if ($formConfiguration === NULL) {
            return FALSE;
        }
        FormConfigurationManager::injectFormConfiguration($formConfiguration);

        return TRUE;
    }

    /**
     * Checks if the extension is under maintenance.
     *
     * @return boolean
     */
    protected function checkIfExtensionIsUnderMaintenance()
    {
        // Checks if a global maintenance is requested
        $unserializedConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][AbstractController::LIBRARY_NAME]);
        $maintenanceAllowedUsers = explode(',', $unserializedConfiguration['maintenanceAllowedUsers']);
        if ($unserializedConfiguration['maintenance']) {
            FlashMessages::addError('error.underMaintenance');
            if (in_array($GLOBALS['TSFE']->fe_user->user['uid'], $maintenanceAllowedUsers) === FALSE) {
                return TRUE;
            }
        }

        // Checks if a maintenance of the extension is requested
        $unserializedConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey()]);
        if ($unserializedConfiguration['maintenance']) {
            FlashMessages::addError('error.underMaintenance');
            if (in_array($GLOBALS['TSFE']->fe_user->user['uid'], $maintenanceAllowedUsers) === FALSE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Sets the library configuration
     *
     * @return boolean
     */
    protected function setLibraryConfiguration()
    {
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        $extensionPrefixId = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionPrefixId();
        $fileName = self::$libraryRootPath . '/' . GeneralUtility::underscoredToUpperCamelCase(AbstractController::LIBRARY_NAME) . '.xml';

        if (file_exists(ExtensionManagementUtility::extPath($extensionKey) . $fileName) === FALSE) {
            return FlashMessages::addError('error.unknownConfigurationFile', array());
        } else {
            // Sets the configuration
            $this->libraryConfiguration = GeneralUtility::xml2array($this->getController()
                ->getExtensionConfigurationManager()
                ->getExtensionContentObject()
                ->fileResource('EXT:' . $extensionKey . '/' . $fileName), 'sav_library_plus_pi');
            return TRUE;
        }
    }

    /**
     * Gets the icon path
     *
     * @param string $fileName
     *            The file name without extension
     *
     * @return string
     */
    public static function getIconPath($fileName)
    {
        // The icon directory is taken from the configuration in TS if set,
        // else from the Resources/Icons folder in the extension if it exists,
        // else from the default Resources/Icons in the SAV Library Plus extension if it exists
        // File name extension is added from allowed files name extensions.
        $libraryTypoScriptConfiguration = self::getTypoScriptConfiguration();
        $extensionTypoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[FormConfigurationManager::getFormTitle() . '.'];

        // Checks if the file name is in the iconRootPath defined by the form configuration in TS
        $fileNameWithExtension = self::getFileNameWithExtension($formTypoScriptConfiguration['iconRootPath'] . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['iconRootPath']), strlen(PATH_site)) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in the iconRootPath defined by the extension configuration in TS
        $fileNameWithExtension = self::getFileNameWithExtension($extensionTypoScriptConfiguration['iconRootPath'] . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['iconRootPath']), strlen(PATH_site)) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in the iconRootPath defined by the library configuration in TS
        $fileNameWithExtension = self::getFileNameWithExtension($libraryTypoScriptConfiguration['iconRootPath'] . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['iconRootPath']), strlen(PATH_site)) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in Resources/Icons folder of the extension
        $fileNameWithExtension = self::getFileNameWithExtension(ExtensionManagementUtility::siteRelPath(ExtensionConfigurationManager::getExtensionKey()) . self::$iconRootPath . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return ExtensionManagementUtility::siteRelPath(ExtensionConfigurationManager::getExtensionKey()) . self::$iconRootPath . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in Resources/Icons folder of the SAV Library Plus extension
        $fileNameWithExtension = self::getFileNameWithExtension(ExtensionManagementUtility::siteRelPath(AbstractController::LIBRARY_NAME) . self::$iconRootPath . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return ExtensionManagementUtility::siteRelPath(AbstractController::LIBRARY_NAME) . self::$iconRootPath  . '/' . $fileNameWithExtension;
        }

        return '';
    }

    /**
     * *
     * Gets the icon file name with its extension by checking if it exists in the given path.
     *
     * @param string $path
     *            The file path
     * @param string $fileName
     *            The file name without extension
     *
     * @return string The file name with extension
     */
    protected static function getFileNameWithExtension($path, $fileName)
    {
        $iconFileNameExtensions = explode(',', self::$allowedIconFileNameExtensions);
        foreach ($iconFileNameExtensions as $iconFileNameExtension) {
            if (preg_match('/^[^\.]+\.\w+$/', $fileName) == 0) {
                $fileNameWithExtension = $fileName . $iconFileNameExtension;
            } else {
                $fileNameWithExtension = $fileName;
            }
            if (is_file(GeneralUtility::getFileAbsFileName($path . $fileNameWithExtension))) {
                return $fileNameWithExtension;
            }
        }
        return '';
    }

    /**
     * Gets the images directory
     *
     * @return boolean
     */
    public static function getImageRootPath($fileName)
    {
        // The images directory is taken from the configuration in TS if set,
        // else from the Resources/Images folder in the extension if it exists,
        // else from the default Resources/Images in the library.
        $libraryTypoScriptConfiguration = self::getTypoScriptConfiguration();
        $extensionTypoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[FormConfigurationManager::getFormTitle() . '.'];
        if (is_file(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['imageRootPath'] . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['imageRootPath']), strlen(PATH_site)) . '/';
        } elseif (is_file(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['imageRootPath'] . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['imageRootPath']), strlen(PATH_site)) . '/';
        } elseif (is_file(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['imageRootPath'] . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['imageRootPath']), strlen(PATH_site)) . '/';
        } elseif (is_file(ExtensionManagementUtility::siteRelPath(ExtensionConfigurationManager::getExtensionKey()) . self::$imageRootPath  . '/' .$fileName)) {
            return ExtensionManagementUtility::siteRelPath(ExtensionConfigurationManager::getExtensionKey()) . self::$imageRootPath . '/';
        } else {
            return ExtensionManagementUtility::siteRelPath(AbstractController::LIBRARY_NAME) . self::$imageRootPath . '/';
        }
    }

    /**
     * Gets the language path
     *
     * @return string The language path
     */
    public function getLanguagePath()
    {
        return self::$languageRootPath . '/';
    }

    /**
     * Adds the css files
     *
     * @return none
     */
    public static function addCascadingStyleSheets()
    {
        // Adds the library cascading style sheet
        self::addLibraryCascadingStyleSheet();

        // Adds the extension cascading style sheet
        self::addExtensionCascadingStyleSheet();
    }

    /**
     * Adds the library css file
     * - from the stylesheet TypoScript configuration if any
     * - else from the default css file which is in the "Styles" directory of the SAV Library Plus
     *
     * @return none
     */
    protected static function addLibraryCascadingStyleSheet()
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        if (empty($typoScriptConfiguration['stylesheet'])) {
            $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($extensionKey) . self::$stylesRootPath . '/' . $extensionKey . '.css';
            AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
        } else {
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($typoScriptConfiguration['stylesheet']);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(PATH_site));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new \SAV\SavLibraryPlus\Exception(FlashMessages::translate('error.fileDoesNotExist', array(
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                )));
            }
        }
    }

    /**
     * Adds the extension css file if any
     * The css file should be extension.css in the "Styles" directory
     * where "extension" is the extension key
     *
     * @return none
     */
    protected static function addExtensionCascadingStyleSheet()
    {
        $extensionKey = ExtensionConfigurationManager::getExtensionKey();
        $typoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        if (empty($typoScriptConfiguration['stylesheet']) === FALSE) {
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($typoScriptConfiguration['stylesheet']);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(PATH_site));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new \SAV\SavLibraryPlus\Exception(FlashMessages::translate('error.fileDoesNotExist', array(
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                )));
            }
        } elseif (is_file(ExtensionManagementUtility::extPath($extensionKey) . self::$stylesRootPath . '/' . $extensionKey . '.css')) {
            $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($extensionKey) . self::$stylesRootPath . '/' . $extensionKey . '.css';
            AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
        } elseif (is_file(ExtensionManagementUtility::extPath($extensionKey) . self::$stylesPrivateRootPath . '/' . $extensionKey . '.css')) {
            $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($extensionKey) . self::$stylesPrivateRootPath . '/' . $extensionKey . '.css';
            AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
        }
    }

    /**
     * Checks the compatibility between the extension version and the library version.
     * Versions are under the format x.y.z. Compatibility is satisfied if x's are the same
     *
     * @return boolean
     */
    protected function checkCompatibility()
    {

        // Checks the compatibility between the extension version and the library version.
        // Versions are under the format x.y.z. Compatibility is satisfied if x's are the same
        preg_match('/^([0-9])\./', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][AbstractController::LIBRARY_NAME]['version'], $libraryVersion);

        preg_match('/^([0-9])\./', $this->libraryConfiguration['general']['version'], $extensionVersion);

        if ($libraryVersion[1] != $extensionVersion[1]) {
            return FlashMessages::addError('error.incorrectVersion');
        } else {
            return TRUE;
        }
    }

    /**
     * Gets the library configuration.
     *
     * @return array
     */
    public function getLibraryConfiguration()
    {
        return $this->libraryConfiguration;
    }

    /**
     * Gets a field in the general configuration.
     *
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public function getGeneralConfigurationField($fieldName)
    {
        return $this->libraryConfiguration['general'][$fieldName];
    }

    /**
     * Gets a field in the general configuration.
     *
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public function isOverridedTableForLocalization($tableName)
    {
        return isset($this->libraryConfiguration['general']['overridedTablesForLocalization']) && isset($this->libraryConfiguration['general']['overridedTablesForLocalization'][$tableName]) && $this->libraryConfiguration['general']['overridedTablesForLocalization'][$tableName];
    }

    /**
     * Gets the form configuration.
     *
     * @return string or NULL if the form identifier is empty
     */
    public function getFormConfiguration()
    {
        $formIdentifier = $this->getController()
            ->getExtensionConfigurationManager()
            ->getFormIdentifier();
        if (empty($formIdentifier)) {
            FlashMessages::addError('fatal.noFormSelectedInFlexform');
            return NULL;
        }
        return $this->libraryConfiguration['forms'][$formIdentifier];
    }

    /**
     * Gets the view identifier.
     *
     * @param string $viewType
     *            - the type of the view
     *
     * @return integer
     */
    public function getViewIdentifier($viewType)
    {
        $viewsWithCondition = FormConfigurationManager::getViewsWithCondition($viewType);
        if ($viewsWithCondition === NULL) {
            $getViewIdentifierFunction = 'get' . $viewType . 'Identifier';
            $viewIdentifier = FormConfigurationManager::$getViewIdentifierFunction();
            return $viewIdentifier;
        } else {
            foreach ($viewsWithCondition as $viewWithConditionKey => $viewWithCondition) {
                $viewWithConditionConfiguration = $viewWithCondition['config'];

                if (empty($viewWithConditionConfiguration['cutif']) === FALSE || empty($viewWithConditionConfiguration['showif']) === FALSE) {
                    // Builds a field configuration manager
                    $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                    $fieldConfigurationManager->injectController($this->getController());
                    $fieldConfigurationManager->injectKickstarterFieldConfiguration($viewWithConditionConfiguration);

                    // Checks the cutif condition
                    if ($fieldConfigurationManager->cutIf() === FALSE) {
                        return $viewWithConditionKey;
                    }
                }
            }
            // If no FALSE condition was found, return the default view
            $getViewIdentifierFunction = 'get' . $viewType . 'Identifier';
            $viewIdentifier = FormConfigurationManager::$getViewIdentifierFunction();
            return $viewIdentifier;
        }
    }

    /**
     * Gets the view configuration.
     *
     * @param string $viewIdentifier
     *            - the view identifier
     *
     * @return string
     */
    public function getViewConfiguration($viewIdentifier)
    {
        return $this->libraryConfiguration['views'][$viewIdentifier];
    }

    /**
     * Gets the list view template configuration.
     *
     * @return string
     */
    public function getListViewTemplateConfiguration()
    {
        $listViewIdentifier = FormConfigurationManager::getListViewIdentifier();
        return $this->libraryConfiguration['templates'][$listViewIdentifier];
    }

    /**
     * Gets the special view template configuration.
     *
     * @return string
     */
    public function getSpecialViewTemplateConfiguration()
    {
        $specialViewIdentifier = FormConfigurationManager::getSpecialViewIdentifier();
        return $this->libraryConfiguration['templates'][$specialViewIdentifier];
    }

    /**
     * Gets the form view template configuration.
     *
     * @return string
     */
    public function getFormViewTemplateConfiguration()
    {
        $formViewIdentifier = FormConfigurationManager::getFormViewIdentifier();
        return $this->libraryConfiguration['templates'][$formViewIdentifier];
    }

    /**
     * Gets the query configuration.
     *
     * @return string
     */
    public function getQueryConfiguration()
    {
        $queryIdentifier = FormConfigurationManager::getQueryIdentifier();
        return $this->libraryConfiguration['queries'][$queryIdentifier];
    }

    /**
     * Searchs for a field configuration in a view configuration
     *
     * @param array $viewConfiguration
     *            The view configuration
     * @param string $fieldKey
     *            the key to search
     *
     * @return mixed The configuration or FALSE if the key is not found
     */
    public static function searchFieldConfiguration(&$viewConfiguration, $fieldKey)
    {
        foreach ($viewConfiguration as $itemKey => $item) {
            if ($itemKey == $fieldKey) {
                return $item['config'];
            } elseif (isset($item['config']['subform'])) {
                $fieldConfiguration = self::searchFieldConfiguration($item['config']['subform'], $fieldKey);
                if ($fieldConfiguration != FALSE) {
                    return $fieldConfiguration;
                }
            } elseif (isset($item['fields'])) {
                $fieldConfiguration = self::searchFieldConfiguration($item['fields'], $fieldKey);
                if ($fieldConfiguration != FALSE) {
                    return $fieldConfiguration;
                }
            }
        }
        return FALSE;
    }

    /**
     * Searchs for the basic field configuration (fieldType, tableName, fieldName) in the library configuration views
     *
     * @param string $fieldKey
     *            the key to search
     * @param array $configuration
     *            The configuration in which the search is performed
     *
     * @return mixed The configuration or FALSE if the key is not found
     */
    public function searchBasicFieldConfiguration($fieldKey, $configuration = NULL)
    {
        if ($configuration === NULL) {
            $configuration = $this->libraryConfiguration['views'];
        }
        foreach ($configuration as $itemKey => $item) {
            if ($itemKey == $fieldKey) {
                $basicFieldConfiguration = array(
                    'fieldType' => $item['config']['fieldType'],
                    'tableName' => $item['config']['tableName'],
                    'fieldName' => $item['config']['fieldName']
                );
                if ($item['config']['fieldType'] === 'ShowOnly') {
                    $basicFieldConfiguration = array_merge($basicFieldConfiguration, array(
                        'renderType' => $item['config']['renderType']
                    ));
                }
                return $basicFieldConfiguration;
            } elseif (isset($item['config']['subform'])) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item['config']['subform']);
                if ($basicFieldConfiguration != FALSE) {
                    return $basicFieldConfiguration;
                }
            } elseif (isset($item['fields'])) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item['fields']);
                if ($basicFieldConfiguration != FALSE) {
                    return $basicFieldConfiguration;
                }
            } elseif (is_int($itemKey)) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item);
                if ($basicFieldConfiguration != FALSE) {
                    return $basicFieldConfiguration;
                }
            }
        }
        return FALSE;
    }

    /**
     * Gets the default date format from the library TypoScript configuration if any.
     *
     * @return string
     */
    public static function getTypoScriptConfiguration()
    {
        $libraryPluginName = 'tx_' . str_replace('_', '', AbstractController::LIBRARY_NAME) . '.';
        $typoScriptConfiguration = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$libraryPluginName];
        if (is_array($typoScriptConfiguration)) {
            return $typoScriptConfiguration;
        } else {
            return NULL;
        }
    }

    /**
     * Gets the default date format from the library TypoScript configuration if any.
     *
     * @return string
     */
    public static function getDefaultDateFormat()
    {
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        $format = $typoScriptConfiguration['format.'];
        if (is_array($format) && empty($format['date']) === FALSE) {
            return $format['date'];
        } else {
            return NULL;
        }
    }

    /**
     * Gets the default dateTime format from the library TypoScript configuration if any.
     *
     * @return string
     */
    public static function getDefaultDateTimeFormat()
    {
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        $format = $typoScriptConfiguration['format.'];
        if (is_array($format) && empty($format['dateTime']) === FALSE) {
            return $format['dateTime'];
        } else {
            return NULL;
        }
    }

    /**
     * Sets the view configuration files from the TypoScript configuration
     *
     * @return none
     */
    public function setViewConfigurationFilesFromTypoScriptConfiguration()
    {

        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === NULL) {
            return;
        }

        // Gets the TypoScript configuration
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($typoScriptConfiguration === NULL) {
            return;
        }

        // Sets the template root path if any
        $templateRootPath = $typoScriptConfiguration['templateRootPath'];
        if (empty($templateRootPath) === FALSE) {
            $viewer->setTemplateRootPath($templateRootPath);
        }

        // Sets the partial root path if any
        $viewType = GeneralUtility::lcfirst($viewer->getViewType()) . '.';
        if (is_array($typoScriptConfiguration[$viewType])) {
            $partialRootPath = $typoScriptConfiguration[$viewType]['partialRootPath'];
        } else {
            $partialRootPath = $typoScriptConfiguration['partialRootPath'];
        }
        if (empty($partialRootPath) === FALSE) {
            $viewer->setPartialRootPath($partialRootPath);
        }

        // Sets the layout root path if any
        $layoutRootPath = $typoScriptConfiguration['layoutRootPath'];
        if (empty($layoutRootPath) === FALSE) {
            $viewer->setLayoutRootPath($layoutRootPath);
        }
    }

    /**
     * Sets the link configuration for the view from the TypoScript configuration
     *
     * @return none
     */
    public function setViewLinkConfigurationFromTypoScriptConfiguration()
    {

        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === NULL) {
            return;
        }

        // Gets the library TypoScript configuration
        $libraryTypoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($libraryTypoScriptConfiguration === NULL) {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $libraryTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === FALSE) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the view type
        $viewType = GeneralUtility::lcfirst($viewer->getViewType()) . '.';

        // Gets the view TypoScript configuration
        if (is_array($libraryTypoScriptConfiguration[$viewType])) {
            $viewTypoScriptConfiguration = $libraryTypoScriptConfiguration[$viewType];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $viewTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === FALSE) {
            $viewer->setLinkConfiguration($linkConfiguration);
        }
    }
}

?>