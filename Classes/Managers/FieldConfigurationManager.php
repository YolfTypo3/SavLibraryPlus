<?php
namespace YolfTypo3\SavLibraryPlus\Managers;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier;
use YolfTypo3\SavLibraryPlus\ItemViewers\General\StringItemViewer;
use YolfTypo3\SavLibraryPlus\Viewers\EditViewer;

/**
 * Field configuration manager.
 *
 * @package SavLibraryPlus
 */
class FieldConfigurationManager extends AbstractManager
{
    /**
     * Pattern for the cutter
     */
    const CUT_IF_PATTERN = '/
    (?:
      (?:
        \s+
        (?P<connector>[\|&]|or|and|OR|AND)
        \s+
      )?
      (?P<expression>
        (?P<lparenthesis>\s*?\(\s*?)?
        (?:
        	false | true |
	        (?:\#{3})?
		        (?P<lhs>(?:(?:\w+\.)+)?\w+)
		        \s*(?P<operator>=|!=|>=|<=|>|<|isnot|is)\s*
		        (?P<rhs>[-\w]+|\#{3}[^\#]+\#{3})
	        (?:\#{3})?
        )
        (?P<rparenthesis>\s*?\)\s*?)?
      )
    )
  /x';

    /**
     * The table name
     *
     * @var string
     *
     */
    protected $tableName;

    /**
     * The field configuration from the Kickstarter
     *
     * @var array
     *
     */
    protected $kickstarterFieldConfiguration;

    /**
     * Flac for the cutter
     *
     * @var boolean
     *
     */
    protected $cutFlag;

    /**
     * Flag telling that the fusion of fields is in progress
     *
     * @var boolean
     *
     */
    protected $fusionInProgress = false;

    /**
     * Flag telling that the fusion of fields is pending
     *
     * @var boolean
     *
     */
    protected $fusionBeginPending = false;

    /**
     * The local querier
     *
     * @var \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier
     */
    protected $querier = null;

    /**
     * The stack for the cutter
     *
     * @var array
     */
    protected $cutterStack = [];

    /**
     * Injects the local querier
     *
     * @param \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier $querier
     *
     * @return void
     */
    public function injectQuerier($querier)
    {
        $this->querier = $querier;
    }

    /**
     * Gets the querier
     *
     * @return \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier
     */
    public function getQuerier()
    {
        if ($this->querier === null) {
            return $this->getController()->getQuerier();
        } else {
            return $this->querier;
        }
    }

    /**
     * Injects the kickstarter field configuration
     *
     * @param array $kickstarterFieldConfiguration
     *
     * @return void
     */
    public function injectKickstarterFieldConfiguration(&$kickstarterFieldConfiguration)
    {
        $this->kickstarterFieldConfiguration = $kickstarterFieldConfiguration;
        $this->setFullFieldName();
    }

    /**
     * Builds the full field name
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function buildFullFieldName($fieldName)
    {
        $fieldNameParts = explode('.', $fieldName);
        if (count($fieldNameParts) == 1) {
            // The tableName is assumed by default
            $fieldName = $this->kickstarterFieldConfiguration['tableName'] . '.' . $fieldName;
        }
        return $fieldName;
    }

    /**
     * Sets the full field name
     *
     * @return void
     */
    public function setFullFieldName()
    {
        $this->fullFieldName = $this->buildFullFieldName($this->kickstarterFieldConfiguration['fieldName']);
    }

    /**
     * Gets the full field name
     *
     * @return string
     */
    public function getFullFieldName()
    {
        return $this->fullFieldName;
    }

    /**
     * Gets the table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->kickstarterFieldConfiguration['tableName'];
    }

    /**
     * Gets the fieldName name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->kickstarterFieldConfiguration['fieldName'];
    }

    /**
     * Builds the fields configuration for a folder.
     *
     * @param array $folder
     *            array (the folder)
     * @param boolean $flatten
     * @param boolean $flattenAll
     *
     * @return array
     */
    public function getFolderFieldsConfiguration($folder, $flatten = false, $flattenAll = false)
    {
        $folderFieldsConfiguration = [];

        if (is_array($folder['fields'])) {
            foreach ($folder['fields'] as $fieldId => $kickstarterFieldConfiguration) {

                // Injects the kickstarter configuration
                $this->injectKickstarterFieldConfiguration($kickstarterFieldConfiguration['config']);

                // Builds full field name
                $fullFieldName = $this->getFullFieldName();

                // Gets the configuration
                $fieldConfiguration = $this->getFieldConfiguration();

                // If it is a subform, gets the configuration for each subform field
                if (isset($fieldConfiguration['subform']) && $flatten === true) {
                    foreach ($fieldConfiguration['subform'] as $subformFolderKey => $subformFolder) {
                        $subfromFolderFieldsConfiguration = $this->getFolderFieldsConfiguration($subformFolder, $flatten);
                        foreach ($subfromFolderFieldsConfiguration as $subfromFolderFieldConfigurationKey => $subfromFolderFieldConfiguration) {
                            $subfromFolderFieldsConfiguration[$subfromFolderFieldConfigurationKey]['parentTableName'] = $fieldConfiguration['tableName'];
                            $subfromFolderFieldsConfiguration[$subfromFolderFieldConfigurationKey]['parentFieldName'] = $fieldConfiguration['fieldName'];
                            if ($flattenAll === true) {
                                $folderFieldsConfiguration = array_merge($folderFieldsConfiguration, [
                                        $subfromFolderFieldConfigurationKey => $subfromFolderFieldConfiguration
                                    ]
                                );
                            }
                        }
                        $fieldConfiguration['subform'] = $subfromFolderFieldsConfiguration;
                    }
                }

                $folderFieldsConfiguration = array_merge($folderFieldsConfiguration, [
                        $fieldId => $fieldConfiguration
                    ]
                );
            }
        }

        return $folderFieldsConfiguration;
    }

    /**
     * Builds the configuration for the selected field.
     *
     * @return array
     */
    public function getFieldConfiguration()
    {
        // Sets table name and field name
        $tableName = $this->kickstarterFieldConfiguration['tableName'];
        $fieldName = $this->kickstarterFieldConfiguration['fieldName'];
        $fullFieldName = $tableName . '.' . $fieldName;

        // Injects the uid of the foreign table in the special markers in case of a subform item
        if ($this->kickstarterFieldConfiguration['subformItem']) {
            if ($this->getQuerier() instanceof UpdateQuerier) {
                $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);
                $this->getQuerier()->injectSpecialMarkers(['###uidForeignTable###' => $this->getQuerier()->getPostVariableKey($cryptedFullFieldName)]);
            } else {
                if ($this->getController()->getViewer() != null && $this->getController()->getViewer()->isNewView()) {
                    $this->getQuerier()->injectSpecialMarkers(['###uidForeignTable###' => 0]);
                } else {
                    $this->getQuerier()->injectSpecialMarkers(['###uidForeignTable###' => $this->getQuerier()->getFieldValueFromCurrentRow('uid')]);
                }
            }
        }

        // Intializes the field configuration
        $fieldConfiguration = [];

        // Adds the TCA config field
        $fieldConfiguration = array_merge($fieldConfiguration, TcaConfigurationManager::getTcaConfigField($tableName, $fieldName));

        // Adds the configuration from the kickstarter
        $fieldConfiguration = array_merge($fieldConfiguration, $this->kickstarterFieldConfiguration);

        // Adds the configuration from the extension TypoScript configuration
        $viewConfigurationFieldFromTypoScriptConfiguration = $this->getController()
            ->getExtensionConfigurationManager()
            ->getViewConfigurationFieldFromTypoScriptConfiguration($fullFieldName);
        if (is_array($viewConfigurationFieldFromTypoScriptConfiguration)) {
            $fieldConfiguration = array_merge($fieldConfiguration, $viewConfigurationFieldFromTypoScriptConfiguration);
        }

        // Adds the configuration from the page TypoScript configuration
        $viewConfigurationFieldFromPageTypoScriptConfiguration = $this->getController()
            ->getPageTypoScriptConfigurationManager()
            ->getViewConfigurationFieldFromPageTypoScriptConfiguration($fullFieldName);
        if (is_array($viewConfigurationFieldFromPageTypoScriptConfiguration)) {
            $fieldConfiguration = array_merge($fieldConfiguration, $viewConfigurationFieldFromPageTypoScriptConfiguration);
        }

        // Adds the label
        $fieldConfiguration['label'] = $this->getLabel();

        // Adds the value
        $fieldConfiguration['value'] = $this->getValue();

        // Adds the required attribute
        $viewer = $this->getController()->getViewer();
        if (($viewer instanceof EditViewer || $this->getQuerier() instanceof UpdateQuerier) && $this->kickstarterFieldConfiguration['requiredif']) {
            $fieldConfiguration['required'] = ($this->processFieldCondition($this->kickstarterFieldConfiguration['requiredif']) ? '1' : '0');
        } else {
            $fieldConfiguration['required'] = $fieldConfiguration['required'] || preg_match('/required/', $fieldConfiguration['eval']) > 0;
        }

        // Adds special attributes
        $querier = $this->getQuerier();
        if (! empty($querier)) {
            // Adds the uid
            $fieldConfiguration['uid'] = $querier->getFieldValueFromCurrentRow('uid');
            // Adds field-based attributes
            $fieldBasedAttribute = $fieldConfiguration['fieldlink'];
            if (! empty($fieldBasedAttribute)) {
                $fieldConfiguration['link'] = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($fieldBasedAttribute));
            }
            $fieldBasedAttribute = $fieldConfiguration['fieldmessage'];
            if (! empty($fieldBasedAttribute)) {
                $fieldConfiguration['message'] = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($fieldBasedAttribute));
            }
        }

        // Adds the default class label
        $fieldConfiguration['classLabel'] = $this->getClassLabel();

        // Adds the style for the label if any
        if ($this->kickstarterFieldConfiguration['stylelabel']) {
            $fieldConfiguration['styleLabel'] = $this->kickstarterFieldConfiguration['stylelabel'];
        }

        // Adds the default class value
        $fieldConfiguration['classValue'] = $this->getClassValue();

        // Adds the style for the value if any
        if ($this->kickstarterFieldConfiguration['stylevalue']) {
            $fieldConfiguration['styleValue'] = $this->kickstarterFieldConfiguration['stylevalue'];
        }

        // Adds the default class Field
        $fieldConfiguration['classField'] = $this->getClassField();

        // Adds the default class Item
        $fieldConfiguration['classItem'] = $this->getClassItem();

        // Adds the error flag if there was at least an error during the update
        $fieldConfiguration['error'] = $this->getErrorFlag();

        // Adds the cutters (fusion and field)
        $this->setCutFlag();
        $fieldConfiguration['cutDivItemBegin'] = $this->getCutDivItemBegin();
        $fieldConfiguration['cutDivItemInner'] = $this->getCutDivItemInner();
        $fieldConfiguration['cutDivItemEnd'] = $this->getCutDivItemEnd();
        $fieldConfiguration['cutLabel'] = $this->getCutLabel();

        // Gets the value from the TypoScript stdwrap property, if any
        if ($this->kickstarterFieldConfiguration['stdwrapvalue']) {
            $fieldConfiguration['value'] = $this->getValueFromTypoScriptStdwrap($fieldConfiguration['value']);
        }

        // Gets the value from a TypoScript object, if any
        if ($this->kickstarterFieldConfiguration['tsobject']) {
            $fieldConfiguration['value'] = $this->getValueFromTypoScriptObject();
        }

        // Adds the item wrapper if the viewer exists
        $viewer = $this->getController()->getViewer();
        if (! empty($viewer)) {
            if ($this->kickstarterFieldConfiguration['wrapitemifnotcut'] && ! $fieldConfiguration['cutDivItemInner']) {
                $this->kickstarterFieldConfiguration['wrapitem'] = $this->kickstarterFieldConfiguration['wrapitemifnotcut'];
            }
            $fieldConfiguration['wrapItem'] = $querier->parseLocalizationTags($this->kickstarterFieldConfiguration['wrapitem']);
            $fieldConfiguration['wrapItem'] = $querier->parseFieldTags($fieldConfiguration['wrapItem']);

            $fieldConfiguration['wrapInnerItem'] = $querier->parseLocalizationTags($this->kickstarterFieldConfiguration['wrapinneritem']);
            $fieldConfiguration['wrapInnerItem'] = $querier->parseFieldTags($fieldConfiguration['wrapInnerItem']);

            $fieldConfiguration['wrapValue'] = $querier->parseLocalizationTags($this->kickstarterFieldConfiguration['wrapvalue']);
            $fieldConfiguration['wrapValue'] = $querier->parseFieldTags($fieldConfiguration['wrapValue']);
        }

        // Processes edit attribute and condition if any
        if ($viewer instanceof EditViewer && $this->kickstarterFieldConfiguration['editif']) {
            $fieldConfiguration['edit'] = ($this->processFieldCondition($this->kickstarterFieldConfiguration['editif']) ? '1' : '0');
        }

        // Adds the TODO if any
        if (! empty($this->kickstarterFieldConfiguration['todo'])) {
            FlashMessages::addError(
                'error.todo',
                [
                    $fullFieldName,
                    $this->kickstarterFieldConfiguration['todo']
                ]
            );
        }

        return $fieldConfiguration;
    }

    /**
     * Builds the label.
     *
     * @return string
     */
    protected function getLabel()
    {
        $label = $this->kickstarterFieldConfiguration['label'];
        if (empty($this->kickstarterFieldConfiguration['label'])) {
            $tableName = $this->kickstarterFieldConfiguration['tableName'];
            $fieldName = $this->kickstarterFieldConfiguration['fieldName'];
            // Tries to find the label in the extension resource locallang_db file
            $labelKey = 'LLL:EXT:' . ExtensionConfigurationManager::getExtensionKey() . '/Resources/Private/Language/locallang_db.xml:' . $tableName . '.' . $fieldName;
            $label = self::getTypoScriptFrontendController()->sL($labelKey);
            if (empty($label)) {
                // tries to find the table from the TCA
                $label = TcaConfigurationManager::getTcaFieldLabel($tableName, $fieldName);
            }
        }
        return $label;
    }

    /**
     * Builds the value content.
     *
     * @return string
     */
    protected function getValue()
    {
        // Gets the querier
        $querier = $this->getQuerier();

        // Gets the value directly from the kickstarter (specific and rare case)
        if (! empty($this->kickstarterFieldConfiguration['value']) || $this->kickstarterFieldConfiguration['value'] === '0') {
            if (empty($this->kickstarterFieldConfiguration['valueif']) ||
                (! empty($this->kickstarterFieldConfiguration['valueif']) && $this->processFieldCondition($this->kickstarterFieldConfiguration['valueif']))) {
                $value = $this->kickstarterFieldConfiguration['value'];
                if (! empty($querier)) {
                    $value = $querier->parseLocalizationTags($value);
                    $value = $querier->parseFieldTags($value);
                }
                return $value;
            }
        }

        // Gets the value in the session for search
        if (! empty($this->kickstarterFieldConfiguration['search'])) {
            $tagInSession = SessionManager::getFieldFromSession('tagInSession');
            if ($tagInSession !== null) {
                return $tagInSession[$this->kickstarterFieldConfiguration['fieldName']];
            }
        }

        // Gets the value from the fieldname
        if (! empty($querier)) {
            // Checks if an alias attribute is set
            if (! empty($this->kickstarterFieldConfiguration['alias'])) {
                $fieldName = $this->buildFullFieldName($this->kickstarterFieldConfiguration['alias']);
            } elseif ($querier->fieldExistsInCurrentRow($this->kickstarterFieldConfiguration['fieldName'])) {
                $fieldName = $this->kickstarterFieldConfiguration['fieldName'];
            } else {
                $fieldName = $this->getFullFieldName();
            }

            // Gets the value
            if ($querier->errorDuringUpdate() === true) {
                $value = $querier->getFieldValueFromProcessedPostVariables($fieldName);
            } else {
                $value = $querier->getFieldValueFromCurrentRow($fieldName);
            }

            // Special processing if reqValue attribute is set
            if ($this->kickstarterFieldConfiguration['reqvalue'] ) {
                if (empty($this->kickstarterFieldConfiguration['reqvalueif']) ||
                    (! empty($this->kickstarterFieldConfiguration['reqvalueif']) && $this->processFieldCondition($this->kickstarterFieldConfiguration['reqvalueif']))) {
                    $viewerCondition = ($this->getController()->getviewer() !== null && $this->getController()
                        ->getViewer()
                        ->isNewView() === false) || $this->kickstarterFieldConfiguration['renderreqvalue'];
                    if ($viewerCondition === true || ($this->kickstarterFieldConfiguration['fieldType'] =='ShowOnly' && $this->kickstarterFieldConfiguration['edit'] == 0)) {
                     $value = $this->getValueFromRequest();
                    } else {
                        // Processes the reqValue only for additional markers
                        $this->getValueFromRequest();
                    }
                }
            }

            // Special processing for rendering the field in a marker
            if ($this->kickstarterFieldConfiguration['renderfieldinmarker'] ) {
                // Creates the item viewer
                $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\General\\' . $this->kickstarterFieldConfiguration['fieldType'] . 'ItemViewer';
                $itemViewer = GeneralUtility::makeInstance($className);
                $itemViewer->injectController($this->getController());
                $itemViewer->injectItemConfiguration($this->kickstarterFieldConfiguration);
                $value = $itemViewer->render();
                $querier->injectAdditionalMarkers([
                    '###' . $this->kickstarterFieldConfiguration['renderfieldinmarker'] .'###' => $value
                ]);

            }
        }

        return $value;
    }

    /**
     * Builds the value content.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function getValueFromTypoScriptStdwrap($value)
    {
        // The value is wrapped using the stdWrap TypoScript
        $querier = $this->getQuerier();
        if (! empty($querier)) {
            $configuration = $querier->parseLocalizationTags($this->kickstarterFieldConfiguration['stdwrapvalue']);
            $configuration = $querier->parseFieldTags($configuration);
        } else {
            $configuration = $this->kickstarterFieldConfiguration['stdwrapvalue'];
        }

        $TSparser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $TSparser->parse($configuration);
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();
        $value = $contentObject->stdWrap($value, $TSparser->setup);

        return $value;
    }

    /**
     * Builds the value content.
     *
     * @return string
     */
    protected function getValueFromTypoScriptObject()
    {
        // Checks if the typoscript properties exist
        if (empty($this->kickstarterFieldConfiguration['tsproperties'])) {
            FlashMessages::addError(
                'error.noAttributeInField',
                [
                    'tsProperties',
                    $this->kickstarterFieldConfiguration['fieldName']
                ]
            );
            return '';
        }

        // The value is generated from TypoScript
        $querier = $this->getQuerier();
        if (! empty($querier)) {
            $configuration = $querier->parseLocalizationTags($this->kickstarterFieldConfiguration['tsproperties']);
            $configuration = $querier->parseFieldTags($configuration);
        } else {
            $configuration = $this->kickstarterFieldConfiguration['tsproperties'];
        }
        $TSparser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $TSparser->parse($configuration);

        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();
        $value = $contentObject->cObjGetSingle($this->kickstarterFieldConfiguration['tsobject'], $TSparser->setup);

        return $value;
    }

    /**
     * Builds the value content from a request.
     *
     * @return string
     */
    protected function getValueFromRequest()
    {
        // Gets the querier
        $querier = $this->getQuerier();

        // Gets the query
        $query = $this->kickstarterFieldConfiguration['reqvalue'];

        // Processes localization tags
        $query = $querier->parseLocalizationTags($query);
        $query = $querier->parseFieldTags($query);

        // Checks if the query is a select query
        if (! $querier->isSelectQuery($query)) {
            FlashMessages::addError(
                'error.onlySelectQueryAllowed',
                [
                    $this->kickstarterFieldConfiguration['fieldName']
                ]
            );
            return '';
        }

        // Executes the query
        $resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($query);
        if ($resource === false) {
            FlashMessages::addError(
                'error.incorrectQueryInReqValue',
                [
                    $this->kickstarterFieldConfiguration['fieldName']
                ]
            );
        }

        // Sets the separator
        $separator = $this->kickstarterFieldConfiguration['separator'];
        if (empty($separator)) {
            $separator = '<br />';
        }

        // Creates an item viewer for the processing of the func attribute
        $itemViewer = GeneralUtility::makeInstance(StringItemViewer::class);
        $itemViewer->injectController($this->getController());
        $itemViewer->injectItemConfiguration($this->kickstarterFieldConfiguration);

        // Processes the rows
        $value = '';
        while (($row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($resource))) {
            // Checks if the field value is in the row
            if (array_key_exists('value', $row)) {
                $valueFromRow = $row['value'];
                $itemViewer->injectItemConfigurationAttribute($row);
                unset($row['value']);
                // Injects each field as additional markers
                foreach ($row as $fieldKey => $field) {
                    $querier->injectAdditionalMarkers([
                            '###' . $fieldKey . '###' => $field
                        ]
                    );
                }
                $valueFromRow = $itemViewer->processFuncAttribute($valueFromRow);

                $value .= ($value ? $separator : '') . $valueFromRow;
            } else {
                FlashMessages::addError(
                    'error.aliasValueMissingInReqValue',
                    [
                        $this->kickstarterFieldConfiguration['fieldName']
                    ]
                );
                return '';
            }
        }

        return $value;
    }

    /**
     * Builds the class for the label.
     *
     * @return string
     */
    protected function getClassLabel()
    {
        if (empty($this->kickstarterFieldConfiguration['classlabel'])) {
            return 'label';
        } else {
            return 'label ' . $this->kickstarterFieldConfiguration['classlabel'];
        }
    }

    /**
     * Builds the class for the value.
     *
     * @return string
     */
    protected function getClassValue()
    {
        if (empty($this->kickstarterFieldConfiguration['classvalue'])) {
            $class = 'value';
        } else {
            $querier = $this->getQuerier();
            if (! empty($querier)) {
                $class = 'value ' . $querier->parseFieldTags($this->kickstarterFieldConfiguration['classvalue']);
            } else {
                $class = 'value ' . $this->kickstarterFieldConfiguration['classvalue'];
            }
        }

        return $class;
    }

    /**
     * Builds the class for the field.
     *
     * @return string
     */
    protected function getClassField()
    {
        // Adds subform if the type is a RelationManyToManyAsSubform
        if ($this->kickstarterFieldConfiguration['fieldType'] == 'RelationManyToManyAsSubform') {
            $class = 'subform ';
        } else {
            $class = 'field ';
        }

        if (! empty($this->kickstarterFieldConfiguration['classfield'])) {
            $class = $class . $this->kickstarterFieldConfiguration['classfield'];
        }

        return $class;
    }

    /**
     * Builds the class for the item.
     *
     * @return string
     */
    protected function getClassItem()
    {
        if (empty($this->kickstarterFieldConfiguration['classitem'])) {
            $class = 'item';
        } else {
            $class = 'item ' . $this->kickstarterFieldConfiguration['classitem'];
        }

        return $class;
    }

    /**
     * Builds the error flag if any during the update.
     *
     * @return boolean
     */
    protected function getErrorFlag()
    {
        $querier = $this->getQuerier();
        if (empty($querier)) {
            return false;
        } elseif ($querier->errorDuringUpdate() === true) {
            $fieldName = $this->getFullFieldName();
            $errorCode = $querier->getFieldErrorCodeFromProcessedPostVariables($fieldName);
            return $errorCode != UpdateQuerier::ERROR_NONE;
        } else {
            return false;
        }
    }

    /**
     * <DIV class="label"> cutter: checks if the label must be cut
     * Returns true if the <DIV> must be cut.
     *
     * @return boolean
     */
    protected function getCutLabel()
    {
        // Cuts the label if the type is a RelationManyToManyAsSubform an cutLabel is not equal to zero
        if ($this->kickstarterFieldConfiguration['fieldType'] == 'RelationManyToManyAsSubform') {
            $cut = true;
        } elseif ($this->kickstarterFieldConfiguration['cutlabel']) {
            $cut = true;
        } else {
            $cut = false;
        }

        return $cut;
    }

    /**
     * <DIV class="item"> cutter: checks if the beginning of the <DIV> must be cut
     * Returns true if the <DIV> must be cut.
     *
     * @return boolean
     */
    protected function getCutDivItemBegin()
    {
        $fusionBegin = ($this->kickstarterFieldConfiguration['fusion'] == 'begin');

        if ($fusionBegin) {
            $this->fusionBeginPending = true;
        }

        $cut = (($this->fusionInProgress && ! $fusionBegin) || ($this->getCutFlag() && ! $this->fusionInProgress));

        if ($this->fusionBeginPending && ! $cut) {
            $this->fusionInProgress = true;
            $this->fusionBeginPending = false;
        }

        return $cut;
    }

    /**
     * <DIV class="item"> cutter: checks if the endt of the <DIV> must be cut
     * Returns true if the <DIV> must be cut.
     *
     * @return boolean
     */
    protected function getCutDivItemEnd()
    {
        $fusionEnd = ($this->kickstarterFieldConfiguration['fusion'] == 'end');

        $cut = (($this->fusionInProgress && ! $fusionEnd) || ($this->getCutFlag() && ! $this->fusionInProgress));
        if ($fusionEnd) {
            $this->fusionInProgress = false;
            $this->fusionBeginPending = false;
        }
        return $cut;
    }

    /**
     * <DIV class="item"> cutter: checks if the inner content of the <DIV> must be cut
     * Returns true if the <DIV> must be cut.
     *
     * @return boolean
     */
    protected function getCutDivItemInner()
    {
        $cut = ($this->getCutFlag());
        return $cut;
    }

    /**
     * Gets the cut flag.
     * If true the content must be cut.
     *
     * @return boolean
     */
    protected function getCutFlag()
    {
        return $this->cutFlag;
    }

    /**
     * Sets the cut flag
     *
     * @return void
     */
    protected function setCutFlag()
    {
        $this->cutFlag = $this->cutIfEmpty() | $this->cutIf();
    }

    /**
     * Content cutter: checks if the content is empty
     * Returns true if the content must be cut.
     *
     * @return boolean
     */
    protected function cutIfEmpty()
    {
        if ($this->kickstarterFieldConfiguration['cutifnull'] || $this->kickstarterFieldConfiguration['cutifempty']) {
            $value = $this->getValue();
            return empty($value);
        } else {
            return false;
        }
    }

    /**
     * Content cutter: checks if the content is empty
     * Returns true if the content must be cut.
     *
     * @return boolean
     */
    public function cutIf()
    {
        if ($this->kickstarterFieldConfiguration['cutif']) {
            return $this->processFieldCondition($this->kickstarterFieldConfiguration['cutif']);
        } elseif ($this->kickstarterFieldConfiguration['showif']) {
            return ! $this->processFieldCondition($this->kickstarterFieldConfiguration['showif']);
        } else {
            return false;
        }
    }

    /**
     * Processes a field condition
     *
     * @param string $fieldCondition
     *
     * @return boolean True if the field condition is satisfied
     */
    public function processFieldCondition($fieldCondition)
    {
        // Initializes the result
        $result = null;

        // Gets the querier
        $querier = $this->getQuerier();

        // Matches the pattern
        $matches = [];
        preg_match_all(self::CUT_IF_PATTERN, $fieldCondition, $matches);

        // Processes the expressions
        foreach ($matches['expression'] as $matchKey => $match) {

            // Processes the left hand side
            $lhs = $matches['lhs'][$matchKey];
            $isGroupCondition = false;

            switch ($lhs) {
                case 'group':
                    $isGroupCondition = true;
                    if (empty($querier) === false && $querier->rowsNotEmpty()) {
                        $fullFieldName = $querier->buildFullFieldName('usergroup');
                        if ($querier->fieldExistsInCurrentRow($fullFieldName) === true) {
                            $lhsValue = $querier->getFieldValueFromCurrentRow($fullFieldName);
                        } else {
                            return FlashMessages::addError(
                                'error.unknownFieldName',
                                [
                                    $fullFieldName
                                ]
                            );
                        }
                    } else {
                        return false;
                    }
                    break;
                case 'usergroup':
                    $isGroupCondition = true;
                    $lhsValue = self::getTypoScriptFrontendController()->fe_user->user['usergroup'];
                    break;
                case '':
                    break;
                default:
                    // Gets the value
                    if (! empty($querier)) {
                        $fullFieldName = $querier->buildFullFieldName($lhs);
                        if ($querier instanceof UpdateQuerier) {
                            $postVariable = $querier->getPostVariable(AbstractController::cryptTag($fullFieldName));
                            if($querier->getController()->getDebug() && $postVariable === null) {
                                return FlashMessages::addError(
                                    'error.unknownFieldName',
                                    [
                                        $fullFieldName
                                    ]
                                );
                            }
                            $lhsValue = $postVariable;
                        } elseif ($querier->rowsNotEmpty()) {
                            if ($querier->fieldExistsInCurrentRow($fullFieldName) === true) {
                                $lhsValue = $querier->getFieldValueFromCurrentRow($fullFieldName);
                            } else {
                                return FlashMessages::addError(
                                    'error.unknownFieldName',
                                    [
                                        $fullFieldName
                                    ]
                                );
                            }
                        }
                    } else {
                        return false;
                    }
            }

            // Processes the right hand side
            $rhs = $matches['rhs'][$matchKey];
            switch ($rhs) {
                case 'EMPTY':
                    $condition = empty($lhsValue);
                    break;
                case 'NEW':
                    $condition = ($this->getController()->getViewer()->isNewView() && $lhsValue === null);
                    break;
                case '###user###':
                    $rhsValue = self::getTypoScriptFrontendController()->fe_user->user['uid'];
                    break;
                case '###cruser###':
                    $viewer = $this->getController()->getViewer();
                    // Skips the condition if it is a new view since cruser_id will be set when saved
                    if (empty($viewer) === false && $viewer->isNewView() === true) {
                        continue;
                    } else {
                        $rhsValue = self::getTypoScriptFrontendController()->fe_user->user['uid'];
                    }
                    break;
                case '###time()###':
                case '###now()###':
                    $rhsValue = time();
                    break;
                case '':
                    // Processes directly the expression
                    switch ($matches['expression'][$matchKey]) {
                        case 'false':
                        case 'false':
                            $condition = 0;
                            break;
                        case 'true':
                        case 'true':
                            $condition = 1;
                            break;
                        default:
                            $condition = 1;
                    }
                    break;
                default:
                    if ($isGroupCondition !== true) {
                        $rhsValue = $rhs;
                    } else {
                        $rows = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTgetRows(
                            /* SELECT */	'uid',
                            /* FROM   */	'fe_groups',
        	 		        /* WHERE  */	'title="' . $rhs . '"'
                        );
                        $rhsValue = $rows[0]['uid'];
                    }
                    break;
            }

            // Processes the condition
            $operator = $matches['operator'][$matchKey];
            switch ($operator) {
                case '=':
                    if ($isGroupCondition !== true) {
                        $condition = ($lhsValue == $rhsValue);
                    } else {
                        $condition = (in_array($rhsValue, explode(',', $lhsValue)) === true);
                    }
                    break;
                case '!=':
                    if ($isGroupCondition !== true) {
                        $condition = ($lhsValue != $rhsValue);
                    } else {
                        $condition = (in_array($rhsValue, explode(',', $lhsValue)) === false);
                    }
                    break;
                case '>=':
                    if ($isGroupCondition !== true) {
                        $condition = $lhsValue >= $rhsValue;
                    } else {
                        return FlashMessages::addError(
                            'error.operatorNotAllowed',
                            [
                                $operator
                            ]
                        );
                    }
                    break;
                case '<=':
                    if ($isGroupCondition !== true) {
                        $condition = $lhsValue <= $rhsValue;
                    } else {
                        return FlashMessages::addError(
                            'error.operatorNotAllowed',
                            [
                                $operator
                            ]
                        );
                    }
                    break;
                case '>':
                    if ($isGroupCondition !== true) {
                        $condition = $lhsValue > $rhsValue;
                    } else {
                        return FlashMessages::addError(
                            'error.operatorNotAllowed',
                            [
                                $operator
                            ]
                        );
                    }
                    break;
                case '<':
                    if ($isGroupCondition !== true) {
                        $condition = $lhsValue < $rhsValue;
                    } else {
                        return FlashMessages::addError(
                            'error.operatorNotAllowed',
                            [
                                $operator
                            ]
                        );
                    }
                    break;
                case 'isnot':
                    $condition = ! $condition;
                    break;
            }

            // Processes the connector
            $connector = $matches['connector'][$matchKey];

            // Pushes the operator and the result in case of a left parenthesis
            if ($matches['lparenthesis'][$matchKey]) {
                array_push($this->cutterStack, ['connector' => $connector, 'result' => $result]);
                $result = null;
                $connector = '';
            }

            switch ($connector) {
                case '|':
                case 'or':
                case 'OR':
                    $result = ($result === null ? $condition : $result || $condition);
                    break;
                case '&':
                case 'and':
                case 'AND':
                    $result = ($result === null ? $condition : $result && $condition);
                    break;
                case '':
                    $result = $condition;
                    break;
            }
            /*
            debug(
            [
                'lhs' => $lhs,
                'lhsValue' => $lhsValue,
                'operator' => $operator,
                'rhs' => $rhs,
                'rhsValue' => $rhsValue,
                'connector' => $connector,
                'result'    => $result,
            ]
                );
            */

            // Pops the operator and the result in case of a right parenthesis
            if ($matches['rparenthesis'][$matchKey]) {
                $stackValue = array_pop($this->cutterStack);
                switch ($stackValue['connector']) {
                    case '|':
                    case 'or':
                    case 'OR':
                        $result = $result || $stackValue['result'];
                        break;
                    case '&':
                    case 'and':
                    case 'AND':
                        $result = $result && $stackValue['result'];
                        break;
                    case '':
                        $result = $stackValue['result'];
                        break;
                }

            }
        }

        return $result;
    }
}
