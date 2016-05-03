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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use SAV\SavLibraryPlus\Controller\FlashMessages;
use SAV\SavLibraryPlus\Managers\FormConfigurationManager;


/**
 * Extension configuration manager
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExtensionConfigurationManager extends AbstractManager
{

    /**
     * Constants associated with the flag showNoAvailableInformation
     */
    const SHOW_MESSAGE = 0;

    const DO_NOT_SHOW_MESSAGE = 1;

    const DO_NOT_SHOW_EXTENSION = 2;

    /**
     * The form name hash algorithm
     *
     * @var string
     */
    protected static $formNameHashAlgorithm = 'crc32';

    /**
     * The extension class
     *
     * @var \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
     */
    private $extension;

    /**
     * The TypoScript configuration
     *
     * @var array
     */
    private static $typoScriptConfiguration;

    /**
     * The extension configuration
     *
     * @var array
     */
    private $extensionConfiguration;

    /**
     * The content object
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    private static $extensionContentObject;

    /**
     * The extension key
     *
     * @var string
     */
    private static $extensionKey;

    /**
     * Post-processing after controller injection
     *
     * @return none
     */
    protected function postProcessingAfterControllerInjection()
    {}

    /**
     * Injects the extension
     *
     * @param array $extensionConfiguration
     *
     * @return none
     */
    public function injectExtension($extension)
    {
        $this->extension = $extension;

        // Sets the extension content object as a static variable
        self::$extensionContentObject = $this->extension->cObj;

        // Sets the extension key
        self::$extensionKey = $this->extension->extKey;

        // Initialisation
        $this->extension->pi_setPiVarDefaults();
        $this->extension->pi_loadLL();
    }

    /**
     * Injects the TypoScript configuration
     *
     * @param array $typoScriptConfiguration
     *
     * @return none
     */
    public function injectTypoScriptConfiguration($typoScriptConfiguration)
    {
        self::$typoScriptConfiguration = $typoScriptConfiguration;

        // Sets the extension configuration
        $this->setExtensionConfiguration();
    }

    /**
     * Gets the extension.
     *
     * @return tslib_pibase
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Gets the extension key.
     *
     * @return string
     */
    public static function getExtensionKey()
    {
        return self::$extensionKey;
    }

    /**
     * Gets the extension Name, i.e. extension key converted to upercamelcase.
     *
     * @return string
     */
    public static function getExtensionName()
    {
        return GeneralUtility::underscoredToUpperCamelCase(self::$extensionKey);
    }

    /**
     * Gets the extension prefix id.
     *
     * @return string
     */
    public function getExtensionPrefixId()
    {
        return $this->extension->prefixId;
    }

    /**
     * Gets the extension content object.
     *
     * @return tslib_cObj
     */
    public static function getExtensionContentObject()
    {
        return self::$extensionContentObject;
    }

    /**
     * Gets the TypoScript configuration.
     *
     * @return array
     */
    public static function getTypoScriptConfiguration()
    {
        if (is_array(self::$typoScriptConfiguration)) {
            return self::$typoScriptConfiguration;
        } else {
            return NULL;
        }
    }

    /**
     * Gets the content identifier.
     *
     * @return integer
     */
    public function getContentIdentifier()
    {
        return self::getExtensionContentObject()->data['uid'];
    }

    /**
     * Gets TS Config plugin name.
     *
     * @return string
     */
    public function getTSconfigPluginName()
    {
        return 'tx_' . str_replace('_', '', self::getExtensionKey());
    }

    /**
     * Sets the extension configuration
     *
     * @return boolean
     */
    public function setExtensionConfiguration()
    {
        // Gets the extension configuration from the flexform
        $extensionConfigurationFromFlexform = array();
        $this->getExtension()->pi_initPIflexForm();
        if (! isset(self::getExtensionContentObject()->data['pi_flexform']['data'])) {
            return FlashMessages::addError('error.incorrectExtensionConfiguration', array(
                self::getExtensionKey()
            ));
        }

        foreach (self::getExtensionContentObject()->data['pi_flexform']['data'] as $sheetKey => $sheet) {
            foreach ($sheet['lDEF'] as $attributeKey => $attribute) {
                $extensionConfigurationFromFlexform[$attributeKey] = $this->getExtension()->pi_getFFvalue(self::getExtensionContentObject()->data['pi_flexform'], $attributeKey, $sheetKey);
            }
        }

        // Merges the TypoScript configuration with the configuration from the flexform
        $this->extensionConfiguration = array_merge(self::$typoScriptConfiguration, $extensionConfigurationFromFlexform);

        // Adds the form name hash algorithm
        $formNameHashAlgorithm = $this->getExtensionConfigurationItem('formNameHashAlgo');
        if (empty($formNameHashAlgorithm) === FALSE) {
            self::$formNameHashAlgorithm = $formNameHashAlgorithm;
        }

        return TRUE;
    }

    /**
     * Gets extension configuration item
     *
     * @param $itemKey string
     *
     * @return mixed
     */
    public function getExtensionConfigurationItem($itemKey)
    {
        return $this->extensionConfiguration[$itemKey];
    }

    /**
     * Gets the form identifier.
     *
     * @return string
     */
    public function getFormIdentifier()
    {
        return $this->getExtensionConfigurationItem('formId');
    }

    /**
     * Gets the maxItems.
     *
     * @return string
     */
    public function getMaxItems()
    {
        return $this->getExtensionConfigurationItem('maxItems');
    }

    /**
     * Gets the form name hash algorithm.
     *
     * @return string
     */
    public static function getFormNameHashAlgorithm()
    {
        return self::$formNameHashAlgorithm;
    }

    /**
     * Gets the storage page.
     *
     * @return string
     */
    public function getStoragePage()
    {
        return $this->getExtensionConfigurationItem('storagePage');
    }

    /**
     * Gets the flag "noFilterShowAll".
     *
     * @return boolean
     */
    public function getShowAllIfNoFilter()
    {
        return $this->getExtensionConfigurationItem('noFilterShowAll');
    }

    /**
     * Gets the flag "showNoAvailableInformation".
     *
     * @return integer
     */
    public function getShowNoAvailableInformation()
    {
        return $this->getExtensionConfigurationItem('showNoAvailableInformation');
    }

    /**
     * Gets the flag "permanentFilter".
     *
     * @return boolean
     */
    public function getPermanentFilter()
    {
        return $this->getExtensionConfigurationItem('permanentFilter');
    }

    /**
     * Gets the flag "inputIsAllowed".
     *
     * @return string
     */
    public function getInputIsAllowed()
    {
        return $this->getExtensionConfigurationItem('inputIsAllowed');
    }

    /**
     * Gets the flag "noNewButton".
     *
     * @return string
     */
    public function getNoNewButton()
    {
        return $this->getExtensionConfigurationItem('noNewButton');
    }

    /**
     * Gets the flag "noEditButton".
     *
     * @return string
     */
    public function getNoEditButton()
    {
        return $this->getExtensionConfigurationItem('noEditButton');
    }

    /**
     * Gets the flag "noDeleteButton".
     *
     * @return string
     */
    public function getNoDeleteButton()
    {
        return $this->getExtensionConfigurationItem('noDeleteButton');
    }

    /**
     * Gets the flag "deleteButtonOnlyForCruser".
     *
     * @return string
     */
    public function getDeleteButtonOnlyForCreationUser()
    {
        return $this->getExtensionConfigurationItem('deleteButtonOnlyForCreationUser');
    }

    /**
     * Gets the field "inputStartDate".
     *
     * @return integer
     */
    public function getInputStartDate()
    {
        return $this->getExtensionConfigurationItem('inputStartDate');
    }

    /**
     * Gets the field "inputStopDate".
     *
     * @return integer
     */
    public function getInputEndDate()
    {
        return $this->getExtensionConfigurationItem('inputEndDate');
    }

    /**
     * Gets the field "dateUserRestriction".
     *
     * @return integer
     */
    public function getDateUserRestriction()
    {
        return $this->getExtensionConfigurationItem('dateUserRestriction');
    }

    /**
     * Gets the field "allowedGroups".
     *
     * @return integer
     */
    public function getAllowedGroups()
    {
        return $this->getExtensionConfigurationItem('allowedGroups');
    }

    /**
     * Gets the field "maxPages" (maximum number of pages to display in the browser).
     *
     * @return integer
     */
    public function getMaxPages()
    {
        if ($this->getExtensionConfigurationItem('maxPages')) {
            return $this->getExtensionConfigurationItem('maxPages');
        } else {
            return 10;
        }
    }

    /**
     * Gets the field "inputAdminField".
     *
     * @return integer
     */
    public function getInputAdminField()
    {
        return $this->getExtensionConfigurationItem('inputAdminField');
    }

    /**
     * Gets the field "allowQueryProperty".
     *
     * @return boolean
     */
    public function getAllowQueryProperty()
    {
        return $this->getExtensionConfigurationItem('allowQueryProperty');
    }

    /**
     * Gets the field "allowExec".
     *
     * @return boolean
     */
    public function getAllowExec()
    {
        return $this->getExtensionConfigurationItem('allowExec');
    }

    /**
     * Gets the help page for the list view.
     *
     * @return integer
     */
    public function getHelpPageForListView()
    {
        return $this->getExtensionConfigurationItem('helpPageListView');
    }

    /**
     * Gets the help page for the single view.
     *
     * @return integer
     */
    public function getHelpPageForSingleView()
    {
        return $this->getExtensionConfigurationItem('helpPageSingleView');
    }

    /**
     * Gets the help page for the edit view.
     *
     * @return integer
     */
    public function getHelpPageForEditView()
    {
        return $this->getExtensionConfigurationItem('helpPageEditView');
    }

    /**
     * Gets the default date format from the extension TypoScript configuration if any.
     *
     * @return string
     */
    public static function getDefaultDateFormat()
    {
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($typoScriptConfiguration !== NULL) {
            // Gets the TypoScript associated with the form name if any
            $formTitle = FormConfigurationManager::getFormTitle() . '.';
            if (is_array($typoScriptConfiguration[$formTitle])) {
                $format = $typoScriptConfiguration[$formTitle]['format.'];
            } else {
                $format = $typoScriptConfiguration['format.'];
            }
            // Processes the format
            if (is_array($format) && empty($format['date']) === FALSE) {
                return $format['date'];
            }
        }
        return NULL;
    }

    /**
     * Gets the default dateTime format from the extension TypoScript configuration if any.
     *
     * @return string
     */
    public static function getDefaultDateTimeFormat()
    {
        $typoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($typoScriptConfiguration !== NULL) {
            // Gets the TypoScript associated with the form name if any
            $formTitle = FormConfigurationManager::getFormTitle() . '.';
            if (is_array($typoScriptConfiguration[$formTitle])) {
                $format = $typoScriptConfiguration[$formTitle]['format.'];
            } else {
                $format = $typoScriptConfiguration['format.'];
            }
            // Processes the format
            if (is_array($format) && empty($format['dateTime']) === FALSE) {
                return $format['dateTime'];
            }
        }
        return NULL;
    }

    /**
     * Sets the view configuration files from the page TypoScript configuration
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

        // Gets the extension TypoScript configuration
        $extensionTypoScriptConfiguration = self::getTypoScriptConfiguration();
        if ($extensionTypoScriptConfiguration === NULL) {
            return;
        }

        // Gets the form title
        $formTitle = FormConfigurationManager::getFormTitle() . '.';

        // Initializes the TypoScript configuration
        $typoScriptConfiguration = $extensionTypoScriptConfiguration;

        // Sets the template root path if any
        if (is_array($extensionTypoScriptConfiguration[$formTitle])) {
            $typoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitle];
        }

        $templateRootPath = $typoScriptConfiguration['templateRootPath'];
        if (empty($templateRootPath) === FALSE) {
            $viewer->setTemplateRootPath($templateRootPath);
        }

        // Sets the partial root path if any
        if (is_array($extensionTypoScriptConfiguration[$formTitle])) {
            $typoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitle];
        }
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
        if (is_array($extensionTypoScriptConfiguration[$formTitle])) {
            $typoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitle];
        }
        $layoutRootPath = $typoScriptConfiguration['layoutRootPath'];
        if (empty($layoutRootPath) === FALSE) {
            $viewer->setLayoutRootPath($layoutRootPath);
        }
    }

    /**
     * Gets the view configuration field from the page TypoScript configuration
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getViewConfigurationFieldFromTypoScriptConfiguration($fieldName)
    {
        // Gets the TypoScript configuration
        $typoScriptConfiguration = $this->getTypoScriptConfiguration();
        if ($typoScriptConfiguration === NULL) {
            return;
        }

        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === NULL) {
            return;
        }

        // Gets the plugin TypoScript configuration
        $formTypoScriptConfiguration = $typoScriptConfiguration[FormConfigurationManager::getFormTitle() . '.'];
        if (is_array($formTypoScriptConfiguration) === FALSE) {
            return NULL;
        }

        // Gets the view page TypoScript configuration
        $viewType = GeneralUtility::lcfirst($viewer->getViewType()) . '.';

        $viewTypoScriptConfiguration = $formTypoScriptConfiguration[$viewType];
        if ($viewTypoScriptConfiguration === NULL) {
            return NULL;
        }

        // Processes the view configuration fields
        $viewConfigurationFields = $viewTypoScriptConfiguration['fields.'];

        // Processes the field name
        $fieldNameParts = explode('.', $fieldName);
        $tableNameWithDot = $fieldNameParts[0] . '.';
        $fieldNameWithDot = $fieldNameParts[1] . '.';

        // Checks if the field is in the main table
        $querier = $this->getController()->getQuerier();
        if ($querier !== NULL) {
            $isMainTableField = $querier->getQueryConfigurationManager()->getMainTable() == $fieldNameParts[0];
        } else {
            $isMainTableField = FALSE;
        }

        // Builds the view field attributes configuration
        if ($isMainTableField && is_array($viewConfigurationFields[$fieldNameWithDot])) {
            $viewConfigurationFieldAttributes = $viewConfigurationFields[$fieldNameWithDot];
        } elseif (is_array($viewConfigurationFields[$tableNameWithDot][$fieldNameWithDot])) {
            $viewConfigurationFieldAttributes = $viewConfigurationFields[$tableNameWithDot][$fieldNameWithDot];
        } else {
            return NULL;
        }

        // Processes the field attributes
        $fieldAttributes = array();
        foreach ($viewConfigurationFieldAttributes as $viewConfigurationFieldAttributeKey => $viewConfigurationFieldAttribute) {
            $fieldAttributes[strtolower($viewConfigurationFieldAttributeKey)] = $viewConfigurationFieldAttribute;
        }

        return $fieldAttributes;
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

        // Gets the extension TypoScript configuration
        $extensionTypoScriptConfiguration = $this->getTypoScriptConfiguration();
        if ($extensionTypoScriptConfiguration === NULL) {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $extensionTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === FALSE) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the form title
        $formTitle = FormConfigurationManager::getFormTitle() . '.';

        // Gets the form TypoScript configuration
        if (is_array($extensionTypoScriptConfiguration[$formTitle])) {
            $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitle];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $formTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === FALSE) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the view type
        $viewType = GeneralUtility::lcfirst($viewer->getViewType()) . '.';

        // Gets the view TypoScript configuration
        if (is_array($extensionTypoScriptConfiguration[$formTitle][$viewType])) {
            $viewTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitle][$viewType];
        } elseif (is_array($extensionTypoScriptConfiguration[$viewType])) {
            $viewTypoScriptConfiguration = $extensionTypoScriptConfiguration[$viewType];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $viewTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === FALSE) {
            $viewer->setLinkConfiguration($linkConfiguration);
        }
    }

    /**
     * Checks if the plugin type is USER.
     *
     * @return boolean
     */
    public static function isUserPlugin()
    {
        $contentObject = self::getExtensionContentObject();
        return ($contentObject->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER);
    }

    /**
     * Checks if a cHash is required.
     *
     * @return boolean
     */
    public static function isCacheHashRequired()
    {
        return self::isUserPlugin();
    }
}
?>