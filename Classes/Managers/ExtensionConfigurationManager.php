<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Extension configuration manager
 *
 * @package SavLibraryPlus
 */
final class ExtensionConfigurationManager extends AbstractManager
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
    protected string $formNameHashAlgorithm = 'crc32';

    /**
     * The extension configuration
     *
     * @var array
     */
    protected array $extensionConfiguration;

    /**
     * This is the incoming array by name $this->prefixId merged between POST and GET, POST taking precedence.
     * Eg. if the class name is 'tx_myext'
     * then the content of this array will be whatever comes into &tx_myext[...]=...
     *
     * @var array
     */
    protected array $piVars = [];
    
    /**
     * Initializes the manager
     * 
     * @param array $typoScriptConfiguration
     *
     * @return void
     */
    public function initialize(array $configuration): bool
    {
        // Gets the extension configuration from the flexform
        $extensionConfigurationFromFlexform = [];
        $this->pi_initPIflexForm();
        
        $pi_flexform = $this->controller->getContentObjectRendererDataAttribute('pi_flexform');
        if (! isset($pi_flexform['data'])) {
            return FlashMessages::addError('error.incorrectExtensionConfiguration', [
                $this->controller->getExtensionKey()
            ]);
        }
       
        foreach ($pi_flexform['data'] as $sheetKey => $sheet) {
            foreach ($sheet['lDEF'] as $attributeKey => $attribute) {
                $extensionConfigurationFromFlexform[$attributeKey] = $this->pi_getFFvalue($pi_flexform, $attributeKey, $sheetKey);
            }
        }

        // Merges the TypoScript configuration with the configuration from the flexform
        $this->extensionConfiguration = array_merge($configuration, $extensionConfigurationFromFlexform);

        // Adds the form name hash algorithm
        $formNameHashAlgorithm = $this->getExtensionConfigurationItem('formNameHashAlgo');
        if (empty($formNameHashAlgorithm) === false) {
            $this->formNameHashAlgorithm = $formNameHashAlgorithm;
        }
      
        // Sets the piVars
        $this->piVars = $this->getRequestPostOverGetParameterWithPrefix($this->controller->getExtensionPrefixId());

        return true;
    }
    
    /**
     * Gets TS Config plugin name.
     *
     * @return string
     */
    public function getTSconfigPluginName(): string
    {
        return 'tx_' . str_replace('_', '', $this->controller->getExtensionKey());
    }
    
    /**
     * Gets the piVars.
     *
     * @return array
     */
    public function getPiVars(): array
    {
        return $this->piVars;
    }

    /**
     * Gets extension configuration item
     *
     * @param $itemKey string
     *
     * @return mixed
     */
    public function getExtensionConfigurationItem(string $itemKey): mixed
    {
        return $this->extensionConfiguration[$itemKey] ?? null;
    }

    /**
     * Gets the form identifier.
     *
     * @return string
     */
    public function getFormIdentifier(): string
    {
        return $this->getExtensionConfigurationItem('formId');
    }

    /**
     * Gets the maxItems.
     *
     * @return string
     */
    public function getMaxItems(): string
    {
        return $this->getExtensionConfigurationItem('maxItems');
    }

    /**
     * Gets the form name hash algorithm.
     *
     * @return string
     */
    public function getFormNameHashAlgorithm(): string
    {
        return $this->formNameHashAlgorithm;
    }

    /**
     * Gets the storage page.
     *
     * @return string
     */
    public function getStoragePage(): string
    {
        // Gets the storage page from the plugin
        $storagePage = $this->getExtensionConfigurationItem('storagePage');
        
        return $storagePage ?? '';
    }

    /**
     * Gets the flag "noFilterShowAll".
     *
     * @return string
     */
    public function getShowAllIfNoFilter(): string
    {
        return $this->getExtensionConfigurationItem('noFilterShowAll');
    }

    /**
     * Gets the flag "showNoAvailableInformation".
     *
     * @return string
     */
    public function getShowNoAvailableInformation(): string
    {
        return $this->getExtensionConfigurationItem('showNoAvailableInformation');
    }

    /**
     * Gets the field "permanentFilter".
     *
     * @return string
     */
    public function getPermanentFilter(): string
    {
        return $this->getExtensionConfigurationItem('permanentFilter');
    }

    /**
     * Gets the flag "inputIsAllowed".
     *
     * @return string
     */
    public function getInputIsAllowed(): string
    {
        return $this->getExtensionConfigurationItem('inputIsAllowed');
    }

    /**
     * Gets the flag "noNewButton".
     *
     * @return string
     */
    public function getNoNewButton(): string
    {
        return $this->getExtensionConfigurationItem('noNewButton');
    }

    /**
     * Gets the flag "noEditButton".
     *
     * @return string
     */
    public function getNoEditButton(): string
    {
        return $this->getExtensionConfigurationItem('noEditButton');
    }

    /**
     * Gets the flag "noDeleteButton".
     *
     * @return string
     */
    public function getNoDeleteButton(): string
    {
        return $this->getExtensionConfigurationItem('noDeleteButton');
    }

    /**
     * Gets the flag "deleteButtonOnlyForCruser".
     *
     * @return string
     */
    public function getDeleteButtonOnlyForCreationUser(): string
    {
        return $this->getExtensionConfigurationItem('deleteButtonOnlyForCreationUser');
    }

    /**
     * Gets the field "inputStartDate".
     *
     * @return int
     */
    public function getInputStartDate(): int
    {
        return (int) $this->getExtensionConfigurationItem('inputStartDate');
    }

    /**
     * Gets the field "inputStopDate".
     *
     * @return int
     */
    public function getInputEndDate(): int
    {
        return (int) $this->getExtensionConfigurationItem('inputEndDate');
    }

    /**
     * Gets the field "dateUserRestriction".
     *
     * @return int
     */
    public function getDateUserRestriction(): int
    {
        return (int) $this->getExtensionConfigurationItem('dateUserRestriction');
    }

    /**
     * Gets the field "allowedGroups".
     *
     * @return string
     */
    public function getAllowedGroups(): string
    {
        return $this->getExtensionConfigurationItem('allowedGroups');
    }

    /**
     * Gets the field "maxPages" (maximum number of pages to display in the browser).
     *
     * @return int
     */
    public function getMaxPages(): int
    {
        if ($this->getExtensionConfigurationItem('maxPages')) {
            return intval($this->getExtensionConfigurationItem('maxPages'));
        } else {
            return 10;
        }
    }

    /**
     * Gets the field "inputAdminField".
     *
     * @return string
     */
    public function getInputAdminField(): string
    {
        return $this->getExtensionConfigurationItem('inputAdminField');
    }

    /**
     * Gets the field "allowQueryProperty".
     *
     * @return string
     */
    public function getAllowQueryProperty(): string
    {
        return $this->getExtensionConfigurationItem('allowQueryProperty');
    }

    /**
     * Gets the field "allowExec".
     *
     * @return string
     */
    public function getAllowExec(): string
    {
        return $this->getExtensionConfigurationItem('allowExec');
    }

    /**
     * Gets the help page for the list view.
     *
     * @return string
     */
    public function getHelpPageForListView(): string
    {
        return $this->getExtensionConfigurationItem('helpPageListView');
    }

    /**
     * Gets the help page for the single view.
     *
     * @return string
     */
    public function getHelpPageForSingleView(): string
    {
        return $this->getExtensionConfigurationItem('helpPageSingleView');
    }

    /**
     * Gets the help page for the edit view.
     *
     * @return string
     */
    public function getHelpPageForEditView(): string
    {
        return $this->getExtensionConfigurationItem('helpPageEditView');
    }

    /**
     * Gets the default date format from the extension TypoScript configuration if any.
     *
     * @return string|null
     */
    public function getDefaultDateFormat(): ?string
    {
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        if ($extensionTypoScriptConfiguration !== null) {
            // Gets the TypoScript associated with the form name if any
            $formTitleKey = $this->controller->getLibraryConfigurationManager()->getFormTitle() . '.';
            if (is_array($extensionTypoScriptConfiguration[$formTitleKey] ?? null)) {
                $format = $extensionTypoScriptConfiguration[$formTitleKey]['dateFormat.'] ?? null;
            } else {
                $format = $extensionTypoScriptConfiguration['dateFormat.'] ?? null;
            }
            // Processes the format
            if (is_array($format) && empty($format['date']) === false) {
                return $format['date'];
            }
        }
        return null;
    }

    /**
     * Gets the default dateTime format from the extension TypoScript configuration if any.
     *
     * @return string|null
     */
    public function getDefaultDateTimeFormat(): ?string
    {
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        if ($extensionTypoScriptConfiguration !== null) {
            // Gets the TypoScript associated with the form name if any
            $formTitleKey = $this->controller->getLibraryConfigurationManager()->getFormTitle() . '.';
            if (is_array($extensionTypoScriptConfiguration[$formTitleKey] ?? null)) {
                $format = $extensionTypoScriptConfiguration[$formTitleKey]['dateFormat.'];
            } else {
                $format = $extensionTypoScriptConfiguration['dateFormat.'] ?? null;
            }
            // Processes the format
            if (is_array($format) && empty($format['dateTime']) === false) {
                return $format['dateTime'];
            }
        }
        return null;
    }

    /**
     * Sets the view configuration files from the page TypoScript configuration
     *
     * @return void
     */
    public function setViewConfigurationFilesFromTypoScriptConfiguration(): void
    {
        // Gets the viewer
        $viewer = $this->controller->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the extension TypoScript configuration
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        if ($extensionTypoScriptConfiguration === null) {
            return;
        }

        // Gets the form title key
        $formTitleKey = $this->controller->getLibraryConfigurationManager()->getFormTitle() . '.';

        // Initializes the TypoScript configuration
        $typoScriptConfiguration = $extensionTypoScriptConfiguration;

        // Sets the template root path if any
        if (is_array($extensionTypoScriptConfiguration[$formTitleKey] ?? null)) {
            $typoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey];
        }

        $templateRootPath = $typoScriptConfiguration['templateRootPath'] ?? null;
        if (! empty($templateRootPath)) {
            $viewer->setTemplateRootPath($templateRootPath);
        }

        // Sets the partial root path if any
        if (is_array($extensionTypoScriptConfiguration[$formTitleKey] ?? null)) {
            $typoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey];
        }
        $viewType = lcfirst($viewer->getViewType()) . '.';
        if (is_array($typoScriptConfiguration[$viewType] ?? null)) {
            $partialRootPath = $typoScriptConfiguration[$viewType]['partialRootPath'] ?? null;
        } else {
            $partialRootPath = $typoScriptConfiguration['partialRootPath'] ?? null;
        }
        if (! empty($partialRootPath)) {
            $viewer->setPartialRootPath($partialRootPath);
        }

        // Sets the layout root path if any
        if (is_array($extensionTypoScriptConfiguration[$formTitleKey] ?? null)) {
            $typoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey];
        }
        $layoutRootPath = $typoScriptConfiguration['layoutRootPath'] ?? null;
        if (! empty($layoutRootPath)) {
            $viewer->setLayoutRootPath($layoutRootPath);
        }
    }

    /**
     * Gets the view configuration field from the page TypoScript configuration
     *
     * @param string $fieldName
     *
     * @return array|null
     */
    public function getViewConfigurationFieldFromTypoScriptConfiguration(string $fieldName): ?array
    {
        // Gets the TypoScript configuration
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        if ($extensionTypoScriptConfiguration === null) {
            return null;
        }

        // Gets the viewer
        $viewer = $this->controller->getViewer();
        if ($viewer === null) {
            return null;
        }

        // Gets the plugin TypoScript configuration
        $formTitleKey = $this->controller->getLibraryConfigurationManager()->getFormTitle() . '.';
        $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey] ?? null;
        if (! is_array($formTypoScriptConfiguration)) {
            return null;
        }

        // Gets the view page TypoScript configuration
        $viewType = lcfirst($viewer->getViewType()) . '.';

        $viewTypoScriptConfiguration = $formTypoScriptConfiguration[$viewType] ?? null;
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
        $querier = $this->controller->getQuerier();
        if ($querier !== null) {
            $isMainTableField = $querier->getQueryConfigurationManager()->getMainTable() == $fieldNameParts[0];
        } else {
            $isMainTableField = false;
        }

        // Builds the view field attributes configuration
        if ($isMainTableField && is_array($viewConfigurationFields[$fieldNameWithDot] ?? null)) {
            $viewConfigurationFieldAttributes = $viewConfigurationFields[$fieldNameWithDot];
        } elseif (is_array($viewConfigurationFields[$tableNameWithDot][$fieldNameWithDot] ?? null)) {
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

    /**
     * Sets the link configuration for the view from the TypoScript configuration
     *
     * @return void
     */
    public function setViewLinkConfigurationFromTypoScriptConfiguration(): void
    {
        // Gets the viewer
        $viewer = $this->controller->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the extension TypoScript configuration
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        if ($extensionTypoScriptConfiguration === null) {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $extensionTypoScriptConfiguration['link.'] ?? null;
        if (! empty($linkConfiguration)) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the form title
        $formTitleKey = $this->controller->getLibraryConfigurationManager()->getFormTitle() . '.';

        // Gets the form TypoScript configuration
        if (is_array($extensionTypoScriptConfiguration[$formTitleKey] ?? null)) {
            $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $formTypoScriptConfiguration['link.'] ?? null;
        if (! empty($linkConfiguration)) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the view type
        $viewType = lcfirst($viewer->getViewType()) . '.';

        // Gets the view TypoScript configuration
        if (is_array($extensionTypoScriptConfiguration[$formTitleKey][$viewType] ?? null)) {
            $viewTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey][$viewType];
        } elseif (is_array($extensionTypoScriptConfiguration[$viewType] ?? null)) {
            $viewTypoScriptConfiguration = $extensionTypoScriptConfiguration[$viewType];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $viewTypoScriptConfiguration['link.'] ?? null;
        if (! empty($linkConfiguration)) {
            $viewer->setLinkConfiguration($linkConfiguration);
        }
    }

    /**
     * Checks if the plugin type is USER.
     *
     * @return bool
     */
    public function isUserPlugin(): bool
    {
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();
        return ($contentObjectRenderer->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER);
    }

    /**
     * Checks if a cHash is required.
     *
     * @return bool
     */
    public function isCacheHashRequired(): bool
    {
        return $this->isUserPlugin();
    }
    
    /**
     * Converts $this->cObj->data['pi_flexform'] from XML string to flexForm array.
     *
     * @param string $field Field name to convert
     *
     * @return void
     */
    public function pi_initPIflexForm(string $field = 'pi_flexform'): void
    {
        // Converting flexform data into array
        $fieldData = $this->controller->getContentObjectRendererDataAttribute($field);
        if (!is_array($fieldData) && $fieldData) {
            $this->controller->setContentObjectRendererDataAttribute($field, GeneralUtility::xml2array((string)$fieldData));
            if (!is_array($this->controller->getContentObjectRendererDataAttribute($field))) {
                $this->controller->setContentObjectRendererDataAttribute($field, []);
            }
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     *
     * @return string|null The content.
     */
    public function pi_getFFvalue(array $T3FlexForm_array, string $fieldName, string $sheet = 'sDEF', string $lang = 'lDEF', string $value = 'vDEF'): ?string
    {
        $sheetArray = $T3FlexForm_array['data'][$sheet][$lang] ?? '';
        if (is_array($sheetArray)) {
            return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }
        return null;
    }
    
    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensional array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array and return element number X (whether this is right behavior is not settled yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     * 
     * @return mixed The value, typ. string.
     * @internal
     * @see pi_getFFvalue()
     */
    public function pi_getFFvalueFromSheetArray(array $sheetArray, array $fieldNameArr, string $value): mixed
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }
                        $c++;
                    }
                }
            } elseif (isset($tempArr[$v])) {
                $tempArr = $tempArr[$v];
            }
        }
        return $tempArr[$value] ?? '';
    }
    
    /**
     * Returns the global arrays $_GET and $_POST merged with $_POST taking precedence.
     *
     * @param string $parameter Key (variable name) from GET or POST vars
     * 
     * @return array Returns the GET vars merged recursively onto the POST vars.
     */
    protected function getRequestPostOverGetParameterWithPrefix(string $parameter): array
    {
        $postParameter = isset($_POST[$parameter]) && is_array($_POST[$parameter]) ? $_POST[$parameter] : [];
        $getParameter = isset($_GET[$parameter]) && is_array($_GET[$parameter]) ? $_GET[$parameter] : [];
        $mergedParameters = $getParameter;
        ArrayUtility::mergeRecursiveWithOverrule($mergedParameters, $postParameter);
        return $mergedParameters;
    }
    
    /**
     * Link a string to some page.
     * Like pi_getPageLink() but takes a string as first parameter which will in turn be wrapped with the URL including target attribute
     * Simple example: $this->pi_linkToPage('My link', 123) to get something like <a href="index.php?id=123&type=1">My link</a>
     *
     * @param string $str The content string to wrap in <a> tags
     * @param int $id Page id
     * @param string $target Target value to use. Affects the &type-value of the URL, defaults to current.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @return string The input string wrapped in <a> tags with the URL and target set.
     * @see pi_getPageLink()
     * @see ContentObjectRenderer::typoLink()
     */
    public function pi_linkToPage(string $str, int $id, string $target = '', array $urlParameters = []): string
    {
        $conf = [
            'parameter' => $id,
        ];
        if ($target) {
            $conf['target'] = $target;
            $conf['extTarget'] = $target;
            $conf['fileTarget'] = $target;
        }
        if (is_array($urlParameters)) {
            if (!empty($urlParameters)) {
                $conf['additionalParams'] = HttpUtility::buildQueryString($urlParameters, '&');
            }
        } else {
            $conf['additionalParams'] = $urlParameters;
        }
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();
        
        return $contentObjectRenderer->typoLink((string)$str, $conf);
    }
}
