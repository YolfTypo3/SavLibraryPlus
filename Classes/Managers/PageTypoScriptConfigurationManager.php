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

/**
 * Page Typoscript configuration manager
 *
 * @package SavLibraryPlus
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
        $pageTypoScriptConfiguration = self::getTypoScriptFrontendController()->getPagesTSconfig();
        if (! is_array($pageTypoScriptConfiguration)) {
            return null;
        }

        // Gets the plugin TypoScript configuration
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
        $pluginTypoScriptConfiguration = $pageTypoScriptConfiguration[$extensionConfigurationManager->getTSconfigPluginName() . '_pi1.'] ?? null;
        if (! is_array($pluginTypoScriptConfiguration)) {
            return null;
        }

        // Gets the plugin TypoScript configuration
        $formTypoScriptConfiguration = $pluginTypoScriptConfiguration[FormConfigurationManager::getFormTitle() . '.'] ?? null;
        if (! is_array($formTypoScriptConfiguration)) {
            return null;
        }

        return $formTypoScriptConfiguration;
    }

    /**
     * Sets the view configuration files from the page TypoScript configuration
     *
     * @return void
     */
    public function setViewConfigurationFilesFromPageTypoScriptConfiguration()
    {
        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the TypoScript configuration
        $typoScriptConfiguration = $this->getTypoScriptConfiguration();

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
    public function setViewLinkConfigurationFromPageTypoScriptConfiguration()
    {
        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the extension TypoScript configuration
        $extensionTypoScriptConfiguration = $this->getTypoScriptConfiguration();
        if ($extensionTypoScriptConfiguration === null) {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $extensionTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === false) {
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
        if (empty($linkConfiguration) === false) {
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
        if (empty($linkConfiguration) === false) {
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
        if ($typoScriptConfiguration === null) {
            return;
        }

        // Gets the viewer
        $viewer = $this->getController()->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the view page TypoScript configuration
        $viewType = lcfirst($viewer->getViewType()) . '.';
        $viewTypoScriptConfiguration = $typoScriptConfiguration[$viewType];
        if ($viewTypoScriptConfiguration === null) {
            return null;
        }

        // Processes the view configuration fields
        $viewConfigurationFields = $viewTypoScriptConfiguration['fields.'];

        // Processes the field name
        $fieldNameParts = explode('.', $fieldName);
        $tableNameWithDot = $fieldNameParts[0] . '.';
        $fieldNameWithDot = $fieldNameParts[1] . '.';

        // Checks if the field is in the main table
        $querier = $this->getController()->getQuerier();
        if ($querier !== null) {
            $isMainTableField = $querier->getQueryConfigurationManager()->getMainTable() == $fieldNameParts[0];
        } else {
            $isMainTableField = false;
        }

        // Builds the view field attributes configuration
        if ($isMainTableField && is_array($viewConfigurationFields[$fieldNameWithDot])) {
            $viewConfigurationFieldAttributes = $viewConfigurationFields[$fieldNameWithDot];
        } elseif (is_array($viewConfigurationFields[$tableNameWithDot][$fieldNameWithDot])) {
            $viewConfigurationFieldAttributes = $viewConfigurationFields[$tableNameWithDot][$fieldNameWithDot];
        } else {
            return null;
        }

        // Processes the field attributes
        $fieldAttributes = [];
        foreach ($viewConfigurationFieldAttributes as $viewConfigurationFieldAttributeKey => $viewConfigurationFieldAttribute) {
            $fieldAttributes[strtolower($viewConfigurationFieldAttributeKey)] = $viewConfigurationFieldAttribute;
        }
        return $fieldAttributes;
    }
}
