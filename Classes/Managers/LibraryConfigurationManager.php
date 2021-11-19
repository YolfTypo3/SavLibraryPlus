<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace YolfTypo3\SavLibraryPlus\Managers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Exception;

/**
 * General configuration manager
 *
 * @package SavLibraryPlus
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
     * The Css path
     *
     * @var string
     */
    public static $cssRootPath = 'Resources/Public/Css';

    /**
     * The styles path (for compatiblity with previously generated extensions)
     *
     * @var string
     */
    public static $stylesRootPath = 'Resources/Public/Styles';

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
     * @return boolean
     */
    public function initialize()
    {
        // Checks if the extension is under maintenance
        if ($this->checkIfExtensionIsUnderMaintenance() === true)
            return false;

        // Sets the library configuration
        if ($this->setLibraryConfiguration() === false)
            return false;

        // Checks the compatibility
        if ($this->checkCompatibility() === false)
            return false;

        // Adds the cascading style sheets
        self::addCascadingStyleSheets();

        // Injects the form configuration in its manager
        $formConfiguration = $this->getFormConfiguration();
        if ($formConfiguration === null) {
            return false;
        }
        FormConfigurationManager::injectFormConfiguration($formConfiguration);

        return true;
    }

    /**
     * Checks if the extension is under maintenance.
     *
     * @return boolean
     */
    protected function checkIfExtensionIsUnderMaintenance()
    {
        // Checks if a global maintenance is requested
        $extensionKey = AbstractController::LIBRARY_NAME;
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $maintenanceAllowedUsers = explode(',', $extensionConfiguration->get($extensionKey, 'maintenanceAllowedUsers'));
        if ($extensionConfiguration->get($extensionKey, 'maintenance')) {
            FlashMessages::addError('error.underMaintenance');
            $userUid = $this->getTypoScriptConfiguration()->fe_user->user['uid'];
            if (empty($userUid) || in_array($userUid, $maintenanceAllowedUsers) === false) {
                return true;
            }
        }

        // Checks if a maintenance of the extension is requested
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();
            if ($extensionConfiguration->get($extensionKey, 'maintenance')) {
            FlashMessages::addError('error.underMaintenance');
            $userUid = $this->getTypoScriptConfiguration()->fe_user->user['uid'];
            if (empty($userUid) || in_array($userUid, $maintenanceAllowedUsers) === false) {
                return true;
            }
        }
        return false;
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

        $fileName = self::$libraryRootPath . '/' . GeneralUtility::underscoredToUpperCamelCase(AbstractController::LIBRARY_NAME) . '.xml';

        if (file_exists(ExtensionManagementUtility::extPath($extensionKey) . $fileName) === false) {
            return FlashMessages::addError('error.unknownConfigurationFile', []);
        } else {
            // Sets the configuration
            $filePathSanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
            $fileName = $filePathSanitizer->sanitize('EXT:' . $extensionKey . '/' . $fileName);
            $this->libraryConfiguration = GeneralUtility::xml2array(file_get_contents($fileName), 'sav_library_plus_pi');
            return true;
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
            return substr(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['iconRootPath']), strlen(Environment::getPublicPath() . '/')) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in the iconRootPath defined by the extension configuration in TS
        $fileNameWithExtension = self::getFileNameWithExtension($extensionTypoScriptConfiguration['iconRootPath'] . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['iconRootPath']), strlen(Environment::getPublicPath() . '/')) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in the iconRootPath defined by the library configuration in TS
        $fileNameWithExtension = self::getFileNameWithExtension($libraryTypoScriptConfiguration['iconRootPath'] . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['iconRootPath']), strlen(Environment::getPublicPath() . '/')) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in Resources/Icons folder of the extension
        $fileNameWithExtension = self::getFileNameWithExtension(ExtensionManagementUtility::extPath(ExtensionConfigurationManager::getExtensionKey()) . self::$iconRootPath . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            $extensionWebPath = AbstractController::getExtensionWebPath(ExtensionConfigurationManager::getExtensionKey());
            return $extensionWebPath . self::$iconRootPath . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in Resources/Icons folder of the SAV Library Plus extension
        $fileNameWithExtension = self::getFileNameWithExtension(ExtensionManagementUtility::extPath(AbstractController::LIBRARY_NAME) . self::$iconRootPath . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            $extensionWebPath = AbstractController::getExtensionWebPath(AbstractController::LIBRARY_NAME);
            return $extensionWebPath . self::$iconRootPath . '/' . $fileNameWithExtension;
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
            return substr(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['imageRootPath']), strlen(Environment::getPublicPath() . '/')) . '/';
        } elseif (is_file(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['imageRootPath'] . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['imageRootPath']), strlen(Environment::getPublicPath() . '/')) . '/';
        } elseif (is_file(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['imageRootPath'] . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['imageRootPath']), strlen(Environment::getPublicPath() . '/')) . '/';
        } elseif (is_file(ExtensionManagementUtility::extPath(ExtensionConfigurationManager::getExtensionKey()) . self::$imageRootPath . '/' . $fileName)) {
            $extensionWebPath = AbstractController::getExtensionWebPath(ExtensionConfigurationManager::getExtensionKey());
            return $extensionWebPath . self::$imageRootPath . '/';
        } else {
            $extensionWebPath = AbstractController::getExtensionWebPath(AbstractController::LIBRARY_NAME);
            return $extensionWebPath . self::$imageRootPath . '/';
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
     * @return void
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
     * @return void
     */
    protected static function addLibraryCascadingStyleSheet()
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        if (empty($typoScriptConfiguration['stylesheet'])) {
            $extensionWebPath = AbstractController::getExtensionWebPath($extensionKey);
            $cascadingStyleSheet = $extensionWebPath . self::$cssRootPath . '/' . $extensionKey . '.css';
            AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
        } else {
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($typoScriptConfiguration['stylesheet']);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(Environment::getPublicPath() . '/'));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                ]));
            }
        }
    }

    /**
     * Adds the extension css file if any
     * The css file should be extension.css in the "Styles" directory
     * where "extension" is the extension key
     *
     * @return void
     */
    protected static function addExtensionCascadingStyleSheet()
    {
        $extensionKey = ExtensionConfigurationManager::getExtensionKey();
        $typoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        if (empty($typoScriptConfiguration['stylesheet']) === false) {
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($typoScriptConfiguration['stylesheet']);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(Environment::getPublicPath() . '/'));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                ]));
            }
        } elseif (is_file(ExtensionManagementUtility::extPath($extensionKey) . self::$cssRootPath . '/' . $extensionKey . '.css')) {
            $extensionWebPath = AbstractController::getExtensionWebPath($extensionKey);
            $cascadingStyleSheet = $extensionWebPath . self::$cssRootPath . '/' . $extensionKey . '.css';
            AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
        } elseif (is_file(ExtensionManagementUtility::extPath($extensionKey) . self::$stylesRootPath . '/' . $extensionKey . '.css')) {
            $extensionWebPath = AbstractController::getExtensionWebPath($extensionKey);
            $cascadingStyleSheet = $extensionWebPath . self::$stylesRootPath . '/' . $extensionKey . '.css';
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
        $libraryVersion = [];
        preg_match('/^([0-9])\./', ExtensionManagementUtility::getExtensionVersion(AbstractController::LIBRARY_NAME), $libraryVersion);

        $extensionVersion = [];
        preg_match('/^([0-9])\./', $this->libraryConfiguration['general']['version'], $extensionVersion);

        if ($libraryVersion[1] != $extensionVersion[1]) {
            return FlashMessages::addError('error.incorrectVersion');
        } else {
            return true;
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
     * @return string or null if the form identifier is empty
     */
    public function getFormConfiguration()
    {
        $formIdentifier = $this->getController()
            ->getExtensionConfigurationManager()
            ->getFormIdentifier();
        if (empty($formIdentifier)) {
            FlashMessages::addError('fatal.noFormSelectedInFlexform');
            return null;
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
        if ($viewsWithCondition === null) {
            $getViewIdentifierFunction = 'get' . $viewType . 'Identifier';
            $viewIdentifier = FormConfigurationManager::$getViewIdentifierFunction();
            return $viewIdentifier;
        } else {
            foreach ($viewsWithCondition as $viewWithConditionKey => $viewWithCondition) {
                $viewWithConditionConfiguration = $viewWithCondition['config'];

                if (empty($viewWithConditionConfiguration['cutif']) === false || empty($viewWithConditionConfiguration['showif']) === false) {
                    // Builds a field configuration manager
                    $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                    $fieldConfigurationManager->injectController($this->getController());
                    $fieldConfigurationManager->injectKickstarterFieldConfiguration($viewWithConditionConfiguration);

                    // Checks the cutif condition
                    if ($fieldConfigurationManager->cutIf() === false) {
                        return $viewWithConditionKey;
                    }
                }
            }
            // If no false condition was found, return the default view
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
     * @return mixed The configuration or false if the key is not found
     */
    public static function searchFieldConfiguration(&$viewConfiguration, $fieldKey)
    {
        foreach ($viewConfiguration as $itemKey => $item) {
            if ($itemKey == $fieldKey) {
                return $item['config'];
            } elseif (isset($item['config']['subform'])) {
                $fieldConfiguration = self::searchFieldConfiguration($item['config']['subform'], $fieldKey);
                if ($fieldConfiguration != false) {
                    return $fieldConfiguration;
                }
            } elseif (isset($item['fields'])) {
                $fieldConfiguration = self::searchFieldConfiguration($item['fields'], $fieldKey);
                if ($fieldConfiguration != false) {
                    return $fieldConfiguration;
                }
            }
        }
        return false;
    }

    /**
     * Searchs for the basic field configuration (fieldType, tableName, fieldName) in the library configuration views
     *
     * @param string $fieldKey
     *            the key to search
     * @param array $configuration
     *            The configuration in which the search is performed
     *
     * @return mixed The configuration or false if the key is not found
     */
    public function searchBasicFieldConfiguration($fieldKey, $configuration = null)
    {
        if ($configuration === null) {
            $configuration = $this->libraryConfiguration['views'];
        }
        foreach ($configuration as $itemKey => $item) {
            if ($itemKey == $fieldKey) {
                $basicFieldConfiguration = [
                    'fieldType' => $item['config']['fieldType'],
                    'tableName' => $item['config']['tableName'],
                    'fieldName' => $item['config']['fieldName']
                ];
                if ($item['config']['fieldType'] === 'ShowOnly') {
                    $basicFieldConfiguration = array_merge($basicFieldConfiguration, [
                        'renderType' => $item['config']['renderType']
                    ]);
                }
                return $basicFieldConfiguration;
            } elseif (isset($item['config']['subform'])) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item['config']['subform']);
                if ($basicFieldConfiguration != false) {
                    return $basicFieldConfiguration;
                }
            } elseif (isset($item['fields'])) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item['fields']);
                if ($basicFieldConfiguration != false) {
                    return $basicFieldConfiguration;
                }
            } elseif (is_int($itemKey)) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item);
                if ($basicFieldConfiguration != false) {
                    return $basicFieldConfiguration;
                }
            }
        }
        return false;
    }

    /**
     * Gets the default date format from the library TypoScript configuration if any.
     *
     * @return string
     */
    public static function getTypoScriptConfiguration()
    {
        $libraryPluginName = 'tx_' . str_replace('_', '', AbstractController::LIBRARY_NAME) . '.';
        $typoScriptConfiguration = self::getTypoScriptFrontendController()->tmpl->setup['plugin.'][$libraryPluginName];
        if (is_array($typoScriptConfiguration)) {
            return $typoScriptConfiguration;
        } else {
            return null;
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
        if (is_array($format) && empty($format['date']) === false) {
            return $format['date'];
        } else {
            return null;
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
        if (is_array($format) && empty($format['dateTime']) === false) {
            return $format['dateTime'];
        } else {
            return null;
        }
    }

    /**
     * Sets the view configuration files from the TypoScript configuration
     *
     * @return void
     */
    public function setViewConfigurationFilesFromTypoScriptConfiguration()
    {
        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the TypoScript configuration
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($typoScriptConfiguration === null) {
            return;
        }

        // Sets the template root path if any
        $templateRootPath = $typoScriptConfiguration['templateRootPath'];
        if (empty($templateRootPath) === false) {
            $viewer->setTemplateRootPath($templateRootPath);
        }

        // Sets the partial root path if any
        $viewType = lcfirst($viewer->getViewType()) . '.';
        if (is_array($typoScriptConfiguration[$viewType])) {
            $partialRootPath = $typoScriptConfiguration[$viewType]['partialRootPath'];
        } else {
            $partialRootPath = $typoScriptConfiguration['partialRootPath'];
        }
        if (empty($partialRootPath) === false) {
            $viewer->setPartialRootPath($partialRootPath);
        }

        // Sets the layout root path if any
        $layoutRootPath = $typoScriptConfiguration['layoutRootPath'];
        if (empty($layoutRootPath) === false) {
            $viewer->setLayoutRootPath($layoutRootPath);
        }
    }

    /**
     * Sets the link configuration for the view from the TypoScript configuration
     *
     * @return void
     */
    public function setViewLinkConfigurationFromTypoScriptConfiguration()
    {
        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the library TypoScript configuration
        $libraryTypoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($libraryTypoScriptConfiguration === null) {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $libraryTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === false) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the view type
        $viewType = lcfirst($viewer->getViewType()) . '.';

        // Gets the view TypoScript configuration
        if (is_array($libraryTypoScriptConfiguration[$viewType])) {
            $viewTypoScriptConfiguration = $libraryTypoScriptConfiguration[$viewType];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $viewTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === false) {
            $viewer->setLinkConfiguration($linkConfiguration);
        }
    }
}
