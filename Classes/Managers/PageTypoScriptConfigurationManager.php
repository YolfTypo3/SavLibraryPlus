<?php
namespace YolfTypo3\SavLibraryPlus\Managers;

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

use YolfTypo3\SavLibraryPlus\Managers\FormConfigurationManager;

/**
 * Page Typoscript configuration manager
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class PageTypoScriptConfigurationManager extends AbstractManager
{

    /**
     * Gets the page TypoScript configuration.
     *
     * @return array or null
     */
    protected function getTypoScriptConfiguration()
    {
        // Gets the page TypoScript configuration
        $pageTypoScriptConfiguration = $GLOBALS['TSFE']->getPagesTSconfig();
        if (is_array($pageTypoScriptConfiguration) === FALSE) {
            return NULL;
        }

        // Gets the plugin TypoScript configuration
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
        $pluginTypoScriptConfiguration = $pageTypoScriptConfiguration[$extensionConfigurationManager->getTSconfigPluginName() . '_pi1.'];
        if (is_array($pluginTypoScriptConfiguration) === FALSE) {
            return NULL;
        }

        // Gets the plugin TypoScript configuration
        $formTypoScriptConfiguration = $pluginTypoScriptConfiguration[FormConfigurationManager::getFormTitle() . '.'];
        if (is_array($formTypoScriptConfiguration) === FALSE) {
            return NULL;
        }

        return $formTypoScriptConfiguration;
    }

    /**
     * Sets the view configuration files from the page TypoScript configuration
     *
     * @return none
     */
    public function setViewConfigurationFilesFromPageTypoScriptConfiguration()
    {
        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === NULL) {
            return;
        }

        // Gets the TypoScript configuration
        $typoScriptConfiguration = $this->getTypoScriptConfiguration();

        if ($typoScriptConfiguration === NULL) {
            return;
        }

        // Sets the template root path if any
        $templateRootPath = $typoScriptConfiguration['templateRootPath'];
        if (empty($templateRootPath) === FALSE) {
            $viewer->setTemplateRootPath($templateRootPath);
        }

        // Sets the partial root path if any
        $viewType = lcfirst($viewer->getViewType()) . '.';
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
    public function setViewLinkConfigurationFromPageTypoScriptConfiguration()
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
        $viewType = lcfirst($viewer->getViewType()) . '.';

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
     * Gets the view configuration field from the page TypoScript configuration
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getViewConfigurationFieldFromPageTypoScriptConfiguration($fieldName)
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

        // Gets the view page TypoScript configuration
        $viewType = lcfirst($viewer->getViewType()) . '.';
        $viewTypoScriptConfiguration = $typoScriptConfiguration[$viewType];
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
}

?>