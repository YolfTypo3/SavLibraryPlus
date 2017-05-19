<?php
namespace SAV\SavLibraryPlus\Queriers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Mail\MailMessage;
use SAV\SavLibraryPlus\Controller\FlashMessages;
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\UriManager;
use SAV\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * Default update Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class UpdateQuerier extends AbstractQuerier
{

    // Error constants
    const ERROR_NONE = 0;

    const ERROR_FIELD_REQUIRED = 1;

    // Line feed
    const LF = "\n";

    /**
     * The POST variables
     *
     * @var array
     */
    protected $postVariables;

    /**
     * The processed POST variables
     *
     * @var array
     */
    public $processedPostVariables;

    /**
     * If TRUE, the value is not updated nor inserted
     *
     * @var boolean
     */
    public static $doNotAddValueToUpdateOrInsert = FALSE;

    /**
     * If TRUE, then no data are updated nor inserted
     *
     * @var boolean
     */
    public static $doNotUpdateOrInsert = FALSE;

    /**
     * If TRUE, the no data are updated or inserted
     *
     * @var boolean
     */
    protected $newRecord = FALSE;

    /**
     * The error code
     *
     * @var boolean
     */
    public static $errorCode;

    /**
     * The field configuration
     *
     * @var array
     */
    protected $fieldConfiguration;

    /**
     * The post processing list
     *
     * @var array
     */
    protected $postProcessingList;

    /**
     * Querier which is used to retreive data
     *
     * @var string
     */
    protected $editQuerierClassName = 'SAV\\SavLibraryPlus\\Queriers\\EditSelectQuerier';

    /**
     * Searches recursively a configuration if an aray, given a key
     *
     * @param array $arrayToSearchIn
     * @param string $key
     * @return array or FALSE
     */
    public function searchConfiguration($arrayToSearchIn, $key)
    {
        foreach ($arrayToSearchIn as $itemKey => $item) {
            if ($itemKey == $key) {
                return $item;
            } elseif (isset($item['subform'])) {
                $configuration = $this->searchConfiguration($item['subform'], $key);
                if ($configuration != FALSE) {
                    return $configuration;
                }
            }
        }
        return FALSE;
    }

    /**
     * Gets an attribute in the field configuration
     *
     * @param string $attributeKey
     *
     * @return mixed
     */
    protected function getFieldConfigurationAttribute($attributeKey)
    {
        return $this->fieldConfiguration[$attributeKey];
    }

    /**
     * Checks if a field exists in the post variable
     *
     * @param string $cryptedFullFieldName
     *
     * @return mixed
     */
    protected function fieldExistsInPostVariable($cryptedFullFieldName)
    {
        return array_key_exists($cryptedFullFieldName, $this->postVariables);
    }

    /**
     * Gets the current value of a post variable field
     *
     * @param string $cryptedFullFieldName
     *
     * @return mixed
     */
    protected function getPostVariable($cryptedFullFieldName)
    {
        return current($this->postVariables[$cryptedFullFieldName]);
    }

    /**
     * Gets processed post variable
     *
     * @param string $fullFieldName
     * @param integer $uid
     *
     * @return mixed
     */
    public function getProcessedPostVariable($fullFieldName, $uid)
    {
        return $this->processedPostVariables[$fullFieldName][$uid];
    }

    /**
     * Returns TRUE if there is at least one error during update
     *
     * @return boolean
     */
    public function errorDuringUpdate()
    {
        return self::$doNotUpdateOrInsert;
    }

    /**
     * Returns TRUE if the record is a new one
     *
     * @return boolean
     */
    public function isNewRecord()
    {
        return $this->newRecord;
    }

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {
        // Checks if the user is authenticated
        if ($this->getController()
            ->getUserManager()
            ->userIsAuthenticated() === FALSE) {
            return FlashMessages::addError('fatal.notAuthenticated');
        }

        // Gets the POST variables
        $this->postVariables = $this->getController()
            ->getUriManager()
            ->getPostVariables();

        if ($this->postVariables === NULL) {
            return;
        }
        unset($this->postVariables['formAction']);

        // Gets the library configuration manager
        $libraryConfigurationManager = $this->getController()->getLibraryConfigurationManager();

        // Gets the view configuration
        $viewConfiguration = $libraryConfigurationManager->getViewConfiguration(UriManager::getViewId());

        // Gets the active folder key
        $activeFolderKey = UriManager::getFolderKey();
        if ($activeFolderKey === NULL || empty($viewConfiguration[$activeFolderKey])) {
            reset($viewConfiguration);
            $activeFolderKey = key($viewConfiguration);
        }

        // Sets the active folder
        $activeFolder = $viewConfiguration[$activeFolderKey];

        // Creates the field configuration manager
        $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
        $fieldConfigurationManager->injectController($this->getController());

        // Gets the fields configuration for the folder
        $folderFieldsConfiguration = $fieldConfigurationManager->getFolderFieldsConfiguration($activeFolder, TRUE);

        // Processes the fields
        $variablesToUpdate = array();
        foreach ($this->postVariables as $postVariableKey => $postVariable) {
            foreach ($postVariable as $uid => $value) {

                // Sets the new record flag
                $this->newRecord = ($uid === 0);

                // Sets the field configuration
                $this->fieldConfiguration = $this->searchConfiguration($folderFieldsConfiguration, $postVariableKey);
                $tableName = $this->fieldConfiguration['tableName'];
                $fieldName = $this->fieldConfiguration['fieldName'];
                $fieldType = $this->fieldConfiguration['fieldType'];

                // Adds the cryted full field name
                $this->fieldConfiguration['cryptedFullFieldName'] = $postVariableKey;

                // Adds the uid to the configuration
                $this->fieldConfiguration['uid'] = $uid;

                // Resets the error code
                self::$errorCode = self::ERROR_NONE;

                // Makes pre-processings.
                self::$doNotAddValueToUpdateOrInsert = FALSE;
                $value = $this->preProcessor($value);

                // Sets the processed Post variables to retrieve for error processing if any
                $fullFieldName = $tableName . '.' . $fieldName;
                $this->processedPostVariables[$fullFieldName][$uid] = array(
                    'value' => $value,
                    'errorCode' => self::$errorCode
                );

                // Adds the variables
                if (self::$doNotAddValueToUpdateOrInsert === FALSE) {
                    $variablesToUpdateOrInsert[$tableName][$uid][$fieldName] = $value;
                }
            }
        }

        // Checks if error exists
        if (self::$doNotUpdateOrInsert === TRUE) {
            FlashMessages::addError('error.dataNotSaved');
            return FALSE;
        } else {
            // No error, inserts or updates the data
            if (empty($variablesToUpdateOrInsert) === FALSE) {
                foreach ($variablesToUpdateOrInsert as $tableName => $variableToUpdateOrInsert) {
                    if (empty($tableName) === FALSE) {
                        foreach ($variableToUpdateOrInsert as $uid => $fields) {
                            if ($uid > 0) {
                                // Updates the fields
                                $this->updateFields($tableName, $fields, $uid);
                            } else {
                                // Inserts the fields
                                $this->insertFields($tableName, $fields);
                            }
                        }
                    }
                }
            }

            // Post-processing
            if (empty($this->postProcessingList) === FALSE) {
                foreach ($this->postProcessingList as $postProcessingItem) {
                    $this->fieldConfiguration = $postProcessingItem['fieldConfiguration'];
                    $method = $postProcessingItem['method'];
                    $value = $postProcessingItem['value'];
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * Pre-processor which calls the method according to the type
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessor($value)
    {
        // Builds the field type
        $fieldType = $this->getFieldConfigurationAttribute('fieldType');
        if ($fieldType == 'ShowOnly') {
            $renderType = $this->getFieldConfigurationAttribute('renderType');
            $fieldType = (empty($renderType) ? 'String' : $renderType);
        }

        // Calls the verification method for the type if it exists
        $verifierMethod = 'verifierFor' . $fieldType;
        if (method_exists($this, $verifierMethod) && $this->$verifierMethod($value) !== TRUE) {
            self::$doNotAddValueToUpdateOrInsert = TRUE;
            self::$doNotUpdateOrInsert = TRUE;
            return $value;
        }

        // Calls the pre-processing method if it exists
        $preProcessorMethod = 'preProcessorFor' . $fieldType;
        if (method_exists($this, $preProcessorMethod)) {
            $newValue = $this->$preProcessorMethod($value);
        } else {
            $newValue = $value;
        }

        // Checks if a required field is not empty
        if ($this->isRequired() && empty($newValue)) {
            self::$doNotUpdateOrInsert = TRUE;
            self::$errorCode = self::ERROR_FIELD_REQUIRED;
            FlashMessages::addError('error.fieldRequired', array(
                $this->fieldConfiguration['label']
            ));
        }

        // Sets a post-processor for query attribute if any
        if ($this->getFieldConfigurationAttribute('query')) {
            // Sets a post processor
            $this->postProcessingList[] = array(
                'method' => 'postProcessorToExecuteQuery',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            );
        }

        // Sets a post-processor for the rtf if any
        if ($this->getFieldConfigurationAttribute('generatertf')) {
            // Sets a post processor
            $this->postProcessingList[] = array(
                'method' => 'postProcessorToGenerateRTF',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            );
        }

        // Sets a post-processor for the email if any
        if ($this->getFieldConfigurationAttribute('mail')) {
            // Sets a post processor
            $this->postProcessingList[] = array(
                'method' => 'postProcessorToSendEmail',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            );

            // Gets the row before processing
            $this->rows['before'] = $this->getCurrentRowInEditView();
        }

        // Calls the verifier if it exists
        $verifierMethod = $this->getFieldConfigurationAttribute('verifier');
        if (! empty($verifierMethod)) {
            if (! method_exists($this, $verifierMethod)) {
                self::$doNotAddValueToUpdateOrInsert = TRUE;
                self::$doNotUpdateOrInsert = TRUE;
                FlashMessages::addError('error.verifierUnknown');
            } elseif ($this->$verifierMethod($newValue) !== TRUE) {
                self::$doNotAddValueToUpdateOrInsert = TRUE;
                self::$doNotUpdateOrInsert = TRUE;
            }
        }

        return $newValue;
    }

    /**
     * Pre-processor for Checkboxes
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForCheckboxes($value)
    {
        $power = 1;
        $newValue = 0;
        foreach ($value as $checked) {
            if ($checked) {
                $newValue += $power;
            }
            $power = $power << 1;
        }
        return $newValue;
    }

    /**
     * Pre-processor for Date
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForDate($value)
    {
        return $this->date2timestamp($value);
    }

    /**
     * Pre-processor for DateTime
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForDateTime($value)
    {
        return $this->date2timestamp($value);
    }

    /**
     * Pre-processor for Files
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForFiles($value)
    {
        // Gets the uploaded files
        $uploadedFiles = $this->uploadFiles();

        // Builds the new value
        foreach ($value as $itemKey => $item) {
            if (isset($uploadedFiles[$itemKey])) {
                $newValue[$itemKey] = $uploadedFiles[$itemKey];
            } else {
                $newValue[$itemKey] = $item;
            }
        }

        return implode(',', $newValue);
    }

    /**
     * Pre-processor for RelationManyToManyAsDoubleSelectorbox
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForRelationManyToManyAsDoubleSelectorbox($value)
    {
        if ($this->getFieldConfigurationAttribute('MM')) {
            $fullFieldName = $this->getFieldConfigurationAttribute('MM') . '.uid_foreign';
            $uid = $this->getFieldConfigurationAttribute('uid');
            $this->processedPostVariables[$fullFieldName][$uid] = array(
                'value' => $value,
                'errorCode' => self::$errorCode
            );

            $this->postProcessingList[] = array(
                'method' => 'postProcessorForRelationManyToManyAsDoubleSelectorbox',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            );

            // The value is replaced by the number of relations
            if (count($value) == 1 && empty($value[0])) {
                $value = 0;
            } else {
                $value = count($value);
            }
        } else {
            if (is_array($value)) {
                // Comma list
                $value = implode(',', $value);
            } else {
                $value = '';
            }
        }

        return $value;
    }

    /**
     * Pre-processor for RelationManyToManyAsSubform
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForRelationManyToManyAsSubform($value)
    {
        // Sets a post processor
        $this->postProcessingList[] = array(
            'method' => 'postProcessorForRelationManyToManyAsSubform',
            'value' => $value,
            'fieldConfiguration' => $this->fieldConfiguration
        );

        return $value;
    }

    /**
     * Pre-processor for String
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForString($value)
    {
        return htmlspecialchars($value);
    }

    /**
     * Pre-processor for Text
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForText($value)
    {
        return htmlspecialchars($value);
    }

    /**
     * Pre-processor for Text
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForRichTextEditor($value)
    {
        $content = html_entity_decode($value, ENT_QUOTES, $GLOBALS['TSFE']->renderCharset);
        $rteTSConfig = BackendUtility::getPagesTSconfig(0);
        $processedRteConfiguration = BackendUtility::RTEsetup($rteTSConfig['RTE.'], '', '');
        $parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
        $parseHTML->init();
        // Checks if the method setRelPath exists because it was removed in TYPO3 8
        if(method_exists($parseHTML, 'setRelPath')) {
            $parseHTML->setRelPath('');
        }
        $specConfParts = BackendUtility::getSpecConfParts('richtext[]:rte_transform[mode=ts_css]');
        $content = $parseHTML->RTE_transform($content, $specConfParts, 'db', $processedRteConfiguration);

        return $content;
    }

    /**
     * Gets the uid for post processors
     *
     * @return integer
     */
    protected function getUidForPostProcessor()
    {
        // Gets the uid
        $tableName = $this->getFieldConfigurationAttribute('tableName');
        if ($this->getFieldConfigurationAttribute('uid') > 0) {
            $uid = $this->getFieldConfigurationAttribute('uid');
        } else {
            // Gets the last inserted uid
            $uid = $this->newInsertedUid[$tableName];
        }
        return $uid;
    }

    /**
     * Post-processor for RelationManyToManyAsDoubleSelectorbox
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function postProcessorForRelationManyToManyAsDoubleSelectorbox($value)
    {
        // Gets the uid
        $uid = $this->getUidForPostProcessor();

        // Deletes existing fields in the MM table
        $this->deleteRecordsInRelationManyToMany($this->getFieldConfigurationAttribute('MM'), $uid);

        // Inserts the new fields
        foreach ($value as $itemKey => $item) {
            if ($item != 0) {
                $this->insertFieldsInRelationManyToMany($this->getFieldConfigurationAttribute('MM'),
                    array(
                        'uid_local' => $uid,
                        'uid_foreign' => $item,
                        'sorting' => $itemKey + 1
                    ) // The order of the selector is assumed
                );
            }
        }
    }

    /**
     * Post-processor for RelationManyToManyAsSubform
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return boolean
     */
    protected function postProcessorForRelationManyToManyAsSubform($value)
    {
        // Checks if a new record was inserted in the foreign table
        $foreignTableName = $this->getFieldConfigurationAttribute('foreign_table');

        if (isset($this->newInsertedUid[$foreignTableName])) {
            // Sets the uid_foreign field with the inserted record
            $uidForeign = $this->newInsertedUid[$foreignTableName];

            $uid = $this->getFieldConfigurationAttribute('uid');
            if (empty($uid)) {
                // Sets the uid_local field with the inserted record in source table
                $sourceTableName = $this->getFieldConfigurationAttribute('tableName');
                $uidLocal = $this->newInsertedUid[$sourceTableName];
            } else {
                // Sets the uid_local field with the uid
                $uidLocal = $this->getFieldConfigurationAttribute('uid');
            }

            $noRelation = $this->getFieldConfigurationAttribute('norelation');
            if (empty($noRelation)) {

                // Insert the new relation in the MM table
                $rowsCount = $this->getRowsCountInRelationManyToMany($this->getFieldConfigurationAttribute('MM'), $uidLocal);
                $this->insertFieldsInRelationManyToMany($this->getFieldConfigurationAttribute('MM'), array(
                    'uid_local' => $uidLocal,
                    'uid_foreign' => $uidForeign,
                    'sorting' => $rowsCount + 1
                ));
            }

            // Sets the count
            $itemCount = $rowsCount + 1;
            $this->resource = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                /* TABLE   */	$this->getFieldConfigurationAttribute('tableName'),
                /* WHERE   */ 'uid=' . intval($uidLocal),
                /* FIELDS  */	array(
                $this->getFieldConfigurationAttribute('fieldName') => $itemCount
            ));
        }

        return TRUE;
    }

    /**
     * Post-processor for sending email.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    protected function postProcessorToSendEmail($value)
    {
        // Gets the key of the email button if it was hit
        $formAction = $this->getController()
            ->getUriManager()
            ->getFormActionFromPostVariables();
        if (isset($formAction['saveAndSendMail'])) {
            $sendMailFieldKey = key($formAction['saveAndSendMail']);
        }

        // Checks if the mail can be sent
        $mailCanBeSent = FALSE;
        if ($this->getFieldConfigurationAttribute('mailauto')) {
            // Mail is sent if a field has changed
            // Gets the current row in the edit view after insert or update
            $this->rows['after'] = $this->getCurrentRowInEditView();
            foreach ($this->rows['after'] as $fieldKey => $field) {
                if (array_key_exists(AbstractController::cryptTag($fieldKey), $this->postVariables) && $field != $this->rows['before'][$fieldKey]) {
                    $mailCanBeSent = TRUE;
                }
            }
        } elseif ($this->getFieldConfigurationAttribute('mailalways')) {
            $mailCanBeSent = TRUE;
        }

        // Processes additional conditions
        $mailIfFieldSetTo = $this->getFieldConfigurationAttribute('mailiffieldsetto');
        if (! empty($mailIfFieldSetTo)) {
            $fieldForCheckMail = $this->getFieldConfigurationAttribute('fieldforcheckmail');
            if (empty($fieldForCheckMail)) {
                $tableName = $this->getFieldConfigurationAttribute('tableName');
                $fieldName = $this->getFieldConfigurationAttribute('fieldName');
                $fullFieldName = $tableName . '.' . $fieldName;
            } else {
                $fullFieldName = $this->buildFullFieldName($fieldForCheckMail);
                $this->rows['after'] = $this->getCurrentRowInEditView();
                $value = $this->rows['after'][$fullFieldName];
            }
            $mailIfFieldSetToArray = explode(',', $mailIfFieldSetTo);
            if (empty($this->rows['before'][$fullFieldName]) && in_array($value, $mailIfFieldSetToArray)) {
                $mailCanBeSent = TRUE;
            } else {
                $mailCanBeSent = FALSE;
            }
        } elseif (empty($value) && $sendMailFieldKey == $this->getFieldConfigurationAttribute('cryptedFullFieldName')) {
            // A checkbox with an email button was hit
            $mailCanBeSent = TRUE;
        } else {
            $fieldForCheckMail = $this->getFieldConfigurationAttribute('fieldforcheckmail');
            if (! empty($fieldForCheckMail)) {
                $fullFieldName = $this->buildFullFieldName($fieldForCheckMail);
                $mailIf = $this->getFieldConfigurationAttribute('mailif');
                if (! empty($mailIf)) {
                    // Creates the querier
                    $querier = GeneralUtility::makeInstance($this->editQuerierClassName);
                    $querier->injectController($this->getController());
                    $querier->injectQueryConfiguration();
                    if ($this->isSubformField()) {
                        $additionalPartToWhereClause = $this->buildAdditionalPartToWhereClause();
                        $querier->getQueryConfigurationManager()->setAdditionalPartToWhereClause($additionalPartToWhereClause);
                    }
                    $querier->injectAdditionalMarkers($this->additionalMarkers);
                    $querier->processQuery();

                    // Creates the field configuration manager
                    $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                    $fieldConfigurationManager->injectController($this->getController());
                    $fieldConfigurationManager->injectQuerier($querier);
                    $mailCanBeSent = $fieldConfigurationManager->processFieldCondition($mailIf);
                } else {
                    if (empty($this->rows['after'][$fullFieldName])) {
                        $mailCanBeSent = FALSE;
                    }
                }
            }
        }

        // Send the email
        if ($mailCanBeSent === TRUE) {
            $mailSuccesFlag = ($this->sendEmail() > 0 ? 1 : 0);

            // Updates the fields if needed
            if ($mailSuccesFlag) {
                $update = FALSE;
                // Checkbox with an email button
                if ($sendMailFieldKey == $this->getFieldConfigurationAttribute('cryptedFullFieldName')) {
                    $fields = array(
                        $this->getFieldConfigurationAttribute('fieldName') => $mailSuccesFlag
                    );
                    $update = TRUE;
                }

                // Attribute fieldToSetAfterMailSent is used
                if ($this->getFieldConfigurationAttribute('fieldtosetaftermailsent')) {
                    $fields = array(
                        $this->getFieldConfigurationAttribute('fieldtosetaftermailsent') => $mailSuccesFlag
                    );
                    $update = TRUE;
                }

                if ($update === TRUE) {
                    $tableName = $this->getFieldConfigurationAttribute('tableName');
                    $uid = $this->getUidForPostProcessor();
                    $this->updateFields($tableName, $fields, $uid);
                }
            }
        }

        return FALSE;
    }

    /**
     * Post-processor for generating RTF.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    protected function postProcessorToGenerateRtf($value)
    {
        // Gets the key of the generate rtf button if it was hit
        $formAction = $this->getController()
            ->getUriManager()
            ->getFormActionFromPostVariables();
        if (isset($formAction['saveAndGenerateRtf'])) {
            $generateRtfFieldKey = key($formAction['saveAndGenerateRtf']);
        }

        if ($generateRtfFieldKey == $this->getFieldConfigurationAttribute('cryptedFullFieldName') || $this->getFieldConfigurationAttribute('generatertfonsave')) {
            // Creates the querier
            $querier = GeneralUtility::makeInstance($this->editQuerierClassName);
            $querier->injectController($this->getController());
            $querier->injectQueryConfiguration();
            if ($this->isSubformField()) {
                $additionalPartToWhereClause = $this->buildAdditionalPartToWhereClause();
                $querier->getQueryConfigurationManager()->setAdditionalPartToWhereClause($additionalPartToWhereClause);
            }
            $querier->injectAdditionalMarkers($this->additionalMarkers);
            $querier->processQuery();

            // Checks if there is a condition for the generation
            $generateCondition = $this->getFieldConfigurationAttribute('generatertfif');
            if (! empty($generateCondition)) {
                $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                $fieldConfigurationManager->injectController($this->getController());
                $fieldConfigurationManager->injectQuerier($querier);
                if (! $fieldConfigurationManager->processFieldCondition($generateCondition)) {
                    return TRUE;
                }
            }

            // Checks if there exists replacement strings for fields
            foreach ($this->fieldConfiguration as $fieldKey => $field) {
                if (preg_match('/^(?<tableName>[^\.]+)\.(?<fieldName>.+)$/', $fieldKey, $matchFieldKey) && preg_match('/^(?<source>[^-]+)->(?<destination>.+)$/', $field, $matchField)) {
                    // Defines the replacement
                    switch (trim($matchField['source'])) {
                        case 'NL':
                            $source = chr(10);
                            $destination = $matchField['destination'];
                            break;
                        default:
                            $source = $matchField['source'];
                            $destination = $matchField['destination'];
                            break;
                    }
                    // Gets the ful field name
                    $fullFieldName = $matchFieldKey[0];
                    // Replaces the row only for the parsing
                    $querier->setFieldValueFromCurrentRow($fullFieldName, str_replace($source, $destination, $querier->getFieldValueFromCurrentRow($fullFieldName)));
                }
            }

            // Gets the template
            $templateRtf = $querier->parseFieldTags($this->getFieldConfigurationAttribute('templatertf'));
            if (empty($templateRtf)) {
                return FlashMessages::addError('error.incorrectRTFTemplateFileConfig');
            }

            // Checks the rtf extension
            $pathParts = pathinfo($templateRtf);
            if ($pathParts['extension'] != 'rtf') {
                return FlashMessages::addError('error.incorrectRTFTemplateFileExtension');
            }

            // Reads the file template
            $file = @file_get_contents(PATH_site . $templateRtf);
            if (empty($file)) {
                return FlashMessages::addError('error.incorrectRTFTemplateFileName');
            }

            // Cleans the file content
            $file = preg_replace('/(###[^\r\n#]*)[\r\n]*([^#]*###)/m', '$1$2', $file);
            preg_match_all('/###([^#]+)###/', $file, $matches);
            foreach ($matches[0] as $matchKey => $match) {
                $match = preg_replace('/\\\\[^\s]+ /', '', $match);
                $file = str_replace($matches[0][$matchKey], $match, $file);
            }

            // Parses the file content
            $file = $querier->parseFieldTags($file);

            // Gets the file name for saving the file
            $saveFileRtf = $querier->parseFieldTags($this->getFieldConfigurationAttribute('savefilertf'));

            // Creates the directories if necessary
            $pathParts = pathinfo($saveFileRtf);
            $directories = explode('/', $pathParts['dirname']);
            $path = PATH_site;
            foreach ($directories as $directory) {
                $path .= $directory;
                if (! is_dir($path)) {
                    if (! mkdir($path)) {
                        return FlashMessages::addError('error.mkdirIncorrect');
                    }
                }
                $path .= '/';
            }

            // Gets the charset of the back end
            $defaultCharset = 'utf-8';
            $encoding = ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $defaultCharset);
            $file = mb_convert_encoding($file, 'Windows-1252', $encoding);

            // Saves the file
            file_put_contents($path . $pathParts['basename'], $file);

            // Updates the record
            $fields = array(
                $this->getFieldConfigurationAttribute('fieldName') => $pathParts['basename']
            );
            $tableName = $this->getFieldConfigurationAttribute('tableName');
            $uid = $this->getUidForPostProcessor();
            $this->updateFields($tableName, $fields, $uid);
        }
        return TRUE;
    }

    /**
     * Post-processor for excuting query.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    protected function postProcessorToExecuteQuery($value)
    {
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        // Checks if query are allowed
        if (! $extensionConfigurationManager->getAllowQueryProperty()) {
            return FlashMessages::addError('error.queryPropertyNotAllowed');
        }

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();
        // Gets the queryOnValue attribute
        $queryOnValueAttribute = $this->getFieldConfigurationAttribute('queryonvalue');
        if (empty($queryOnValueAttribute) || $queryOnValueAttribute == $value) {
            // Sets the markers
            $markers = $this->buildSpecialMarkers();
            if ($this->isSubformField()) {
                $uidSubform = $this->getFieldConfigurationAttribute('uid');
                $markers = array_merge($markers, array(
                    '###uidItem###' => $uidSubform,
                    '###uidSubform###' => $uidSubform
                ));
            }
            $markers = array_merge($markers, array(
                '###value###' => $value
            ));

            // Gets the queryForeach attribute
            $queryForeachAttribute = $this->getFieldConfigurationAttribute('queryforeach');

            if (empty($queryForeachAttribute) === FALSE) {
                $foreachCryptedFieldName = AbstractController::cryptTag($this->buildFullFieldName($queryForeachAttribute));
                $foreachValues = current($this->postVariables[$foreachCryptedFieldName]);
                foreach ($foreachValues as $foreachValue) {
                    $markers['###' . $queryForeachAttribute . '###'] = $foreachValue;
                    $temporaryQueryStrings = $contentObject->substituteMarkerArrayCached($this->getFieldConfigurationAttribute('query'), $markers, array(), array());
                    $queryStrings = explode(';', $temporaryQueryStrings);
                    foreach ($queryStrings as $queryString) {
                        $resource = $GLOBALS['TYPO3_DB']->sql_query($queryString);
                        if ($GLOBALS['TYPO3_DB']->sql_error($resource)) {
                            FlashMessages::addError('error.incorrectQueryInQueryProperty');
                            break;
                        }
                    }
                }
            } else {
                $temporaryQueryStrings = $contentObject->substituteMarkerArrayCached($this->getFieldConfigurationAttribute('query'), $markers, array(), array());
                $queryStrings = explode(';', $temporaryQueryStrings);

                foreach ($queryStrings as $queryString) {
                    $resource = $GLOBALS['TYPO3_DB']->sql_query($queryString);
                    if ($GLOBALS['TYPO3_DB']->sql_error($resource)) {
                        FlashMessages::addError('error.incorrectQueryInQueryProperty');
                        break;
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * Builds an additional part to a WHERE clause
     *
     * @return string
     */
    protected function buildAdditionalPartToWhereClause()
    {
        $tableName = $this->getFieldConfigurationAttribute('tableName');
        $uid = $this->getUidForPostProcessor();
        $whereClausePart = ' AND ' . $tableName . '.uid=' . intval($uid);

        return $whereClausePart;
    }

    /**
     * Verifier for Integer
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return boolean
     */
    protected function verifierForInteger($value)
    {
        if (! empty($value) && preg_match('/^[-]?\d+$/', $value) == 0) {
            return FlashMessages::addError('error.isNotValidInteger', array(
                $value
            ));
        } else {
            return TRUE;
        }
    }

    /**
     * Verifier for Currency
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return boolean
     */
    protected function verifierForCurrency($value)
    {
        if (! empty($value) && preg_match('/^[-]?[0-9]{1,9}(?:\.[0-9]{1,2})?$/', $value) == 0) {
            return FlashMessages::addError('error.isNotValidCurrency', array(
                $value
            ));
        } else {
            return TRUE;
        }
    }

    /**
     * Checks if the input is a valid pattern.
     *
     * @param mixed $value
     *            Value to be checked
     *
     * @return boolean
     */
    protected function isValidPattern($value)
    {
        $verifierParameter = $this->getFieldConfigurationAttribute('verifierparam');
        if (! preg_match($verifierParameter, $value)) {
            return FlashMessages::addError('error.isValidPattern', array(
                $value
            ));
        } else {
            return TRUE;
        }
    }

    /**
     * Checks if the input is a valid pattern if not empty.
     *
     * @param mixed $value
     *            Value to be checked
     *
     * @return boolean
     */
    protected function isValidPatternIfNotNull($value)
    {
        if (empty($value)) {
            return TRUE;
        } else {
            return $this->isValidPattern($value);
        }
    }

    /**
     * Checks if the input is lower or equal to a given length.
     *
     * @param mixed $value
     *            Value to be checked
     *
     * @return boolean
     */
    protected function isValidLength($value)
    {
        $verifierParameter = $this->getFieldConfigurationAttribute('verifierparam');
        if (strlen($value) > $verifierParameter) {
            return FlashMessages::addError('error.isValidLength', array(
                $value
            ));
        } else {
            return TRUE;
        }
    }

    /**
     * Checks if the input is in a given interval.
     *
     * @param mixed $value
     *            Value to be checked
     *
     * @return boolean
     */
    protected function isValidInterval($value)
    {
        $verifierParameter = $this->getFieldConfigurationAttribute('verifierparam');
        if (! preg_match('/\[([\d]+),\s*([\d]+)\]/', $verifierParameter, $matches)) {
            return FlashMessages::addError('error.verifierInvalidIntervalParameter', array(
                $value
            ));
        }

        if ((int) $value < (int) $matches[1] || (int) $value > (int) $matches[2]) {
            return FlashMessages::addError('error.isValidInterval', array(
                $value
            ));
        } else {
            return TRUE;
        }
    }

    /**
     * Checks if the input is a valid query.
     *
     * @param mixed $value
     *            Value to be checked
     *
     * @return boolean
     */
    protected function isValidQuery($value)
    {
        $verifierParameter = $this->getFieldConfigurationAttribute('verifierparam');
        // Gets the field from a query. The value marker is replaced by the selected value
        $query = str_replace('###value###', $value, $verifierParameter);
        $query = str_replace('###uid###', $this->getFieldConfigurationAttribute('uid'), $query);

        // Checks if the query is a SELECT query and for errors
        if (! $this->isSelectQuery($query)) {
            return FlashMessages::addError('error.onlySelectQueryAllowed');
        } elseif (! ($resource = $GLOBALS['TYPO3_DB']->sql_query($query))) {
            return FlashMessages::addError('error.incorrectQueryInContent');
        } else {
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resource);
            if (! current($row)) {
                return FlashMessages::addError('error.isValidQuery');
            } else {
                return TRUE;
            }
        }
    }

    /**
     * Returns TRUE if a field is required
     *
     * @return boolean
     */
    protected function isRequired()
    {
        return ($this->fieldConfiguration['required'] || preg_match('/required/', $this->fieldConfiguration['eval']) > 0);
    }

    /**
     * Returns TRUE if the field is in a subform
     *
     * @return boolean
     */
    protected function isSubformField()
    {
        return (! empty($this->fieldConfiguration['parentTableName']));
    }

    /**
     * Inserts fields in a table
     *
     * @param string $tableName
     *            Table name
     * @param array $fields
     *            Fields to insert
     *
     * @return none
     */
    protected function insertFields($tableName, $fields)
    {
        // Inserts the fields in the storage page if any or in the current page by default
        $storagePage = $this->getController()
            ->getExtensionConfigurationManager()
            ->getStoragePage();
        $fields = array_merge($fields, array(
            'pid' => ($storagePage ? $storagePage : $GLOBALS['TSFE']->id)
        ));

        // Processes the insert query and sets the uid
        $newInsertedUid = parent::insertFields($tableName, $fields);
        if ($tableName == $this->getQueryConfigurationManager()->getMainTable()) {
            UriManager::setCompressedParameters(AbstractController::changeCompressedParameters(UriManager::getCompressedParameters(), 'uid', $newInsertedUid));
        }
        $this->newInsertedUid[$tableName] = $newInsertedUid;
    }

    /**
     * Gets the current row in edit view
     *
     * @param string $date
     *            (date to convert)
     *
     * @return integer (timestamp)
     */
    public function getCurrentRowInEditView()
    {
        // Creates the querier
        $querier = GeneralUtility::makeInstance($this->editQuerierClassName);
        $querier->injectController($this->getController());
        $querier->injectQueryConfiguration();
        $querier->processQuery();
        $rows = $querier->getRows();

        return $rows[0];
    }

    /**
     * Converts a date into timestamp
     *
     * @param string $date
     *            (date to convert)
     *
     * @return integer (timestamp)
     */
    public function date2timestamp($date)
    {
        // Provides a default format
        if (! $this->getFieldConfigurationAttribute('format')) {
            $format = ($this->getFieldConfigurationAttribute('eval') == 'datetime' ? $this->getController()->getDefaultDateTimeFormat() : $this->getController()->getDefaultDateFormat());
        } else {
            $format = $this->getFieldConfigurationAttribute('format');
        }

        // Variable array
        $var = array(
            'd' => array(
                'type' => 'day',
                'pattern' => '([0-9]{2})'
            ),
            'e' => array(
                'type' => 'day',
                'pattern' => '([ 0-9][0-9])'
            ),
            'H' => array(
                'type' => 'hour',
                'pattern' => '([0-9]{2})'
            ),
            'I' => array(
                'type' => 'hour',
                'pattern' => '([0-9]{2})'
            ),
            'm' => array(
                'type' => 'month',
                'pattern' => '([0-9]{2})'
            ),
            'M' => array(
                'type' => 'minute',
                'pattern' => '([0-9]{2})'
            ),
            'S' => array(
                'type' => 'second',
                'pattern' => '([0-9]{2})'
            ),
            'Y' => array(
                'type' => 'year',
                'pattern' => '([0-9]{4})'
            ),
            'y' => array(
                'type' => 'year_without_century',
                'pattern' => '([0-9]{2})'
            )
        );

        // Intialises the variables
        foreach ($var as $key => $val) {
            $$val = 0;
        }

        // Builds the expression to match the string according to the format
        preg_match_all('/%([deHImMSYy])([^%]*)/', $format, $matchesFormat);

        $exp = '/';
        foreach ($matchesFormat[1] as $key => $match) {
            $exp .= $var[$matchesFormat[1][$key]]['pattern'] . '(?:' . str_replace('/', '\/', $matchesFormat[2][$key]) . ')';
        }
        $exp .= '/';

        $out = 0;
        if ($date) {

            if (! preg_match($exp, $date, $matchesDate)) {
                FlashMessages::addError('error.incorrectDateFormat');
                self::$doNotAddValueToUpdateOrInsert = TRUE;
                return $date;
            } else {
                unset($matchesDate[0]);
                foreach ($matchesDate as $key => $match) {
                    $res[$matchesFormat[1][$key - 1]] = $match;
                }
            }

            // Sets the variables
            foreach ($res as $key => $val) {

                if (array_key_exists($key, $var)) {
                    $type = $var[$key]['type'];
                    $$type = $val;
                } else {
                    FlashMessages::addError('error.incorrectDateOption');
                    self::$doNotAddValueToUpdateOrInsert = TRUE;
                    return '';
                }
            }

            // Deals with year without century
            if ($year_without_century && ! $year) {
                $year = 2000 + $year_without_century;
            }

            $out = mktime($hour, $minute, $second, $month, $day, $year);
        }

        return $out;
    }

    /**
     * Uploads files.
     *
     * @return array The uploaded files
     */
    protected function uploadFiles()
    {
        $uploadedFiles = array();

        // Gets the file array
        $formName = AbstractController::getFormName();
        $files = $GLOBALS['_FILES'][$formName];

        // Gets the crypted full field name
        $cryptedFullFieldName = $this->getFieldConfigurationAttribute('cryptedFullFieldName');

        // If upload folder does not exist, creates it
        $uploadFolder = $this->getFieldConfigurationAttribute('uploadfolder');
        $uploadFolder .= ($this->getFieldConfigurationAttribute('addToUploadFolder') ? '/' . $this->getFieldConfigurationAttribute('addToUploadFolder') : '');

        $error = GeneralUtility::mkdir_deep(PATH_site, $uploadFolder);
        if ($error) {
            self::$doNotAddValueToUpdateOrInsert = TRUE;
            return FlashMessages::addError('error.cannotCreateDirectoryInUpload', array(
                $uploadFolder
            ));
        }

        // Processes the file array
        foreach ($files['name'][$cryptedFullFieldName] as $uid => $field) {
            foreach ($field as $fileNameKey => $fileName) {
                // Skips the file if there is no file name
                if (empty($fileName)) {
                    continue;
                }

                // Checks the size
                if (version_compare(TYPO3_version, '7.6', '<')) {
                    // #71 110 - The TYPO3 setting $TYPO3_CONF_VARS['BE']['maxFileSize'] has been removed and the PHP-internal limit is now the upper barrier.
                    if ($files['size'][$cryptedFullFieldName][$uid][$fileNameKey] > $this->getFieldConfigurationAttribute('max_size') * 1024) {
                        self::$doNotAddValueToUpdateOrInsert = TRUE;
                        return FlashMessages::addError('error.maxFileSizeExceededInUpload');
                    }
                }

                // Checks the extension
                $path_parts = pathinfo($files['name'][$cryptedFullFieldName][$uid][$fileNameKey]);
                $fileExtension = strtolower($path_parts['extension']);
                $allowed = $this->getFieldConfigurationAttribute('allowed');
                if ($allowed && in_array($fileExtension, explode(',', $allowed)) === FALSE) {
                    self::$doNotAddValueToUpdateOrInsert = TRUE;
                    return FlashMessages::addError('error.forbiddenFileTypeInUpload', array(
                        $fileExtension
                    ));
                }

                if (empty($allowed) && in_array($fileExtension, explode(',', $this->getFieldConfigurationAttribute('disallowed'))) === TRUE) {
                    self::$doNotAddValueToUpdateOrInsert = TRUE;
                    return FlashMessages::addError('error.forbiddenFileTypeInUpload', array(
                        $fileExtension
                    ));
                }

                // Uploads the file
                if (move_uploaded_file($files['tmp_name'][$cryptedFullFieldName][$uid][$fileNameKey], $uploadFolder . '/' . $files['name'][$cryptedFullFieldName][$uid][$fileNameKey]) === FALSE) {
                    self::$doNotAddValueToUpdateOrInsert = TRUE;
                    return FlashMessages::addError('error.uploadAborted');
                }
                $uploadedFiles[$fileNameKey] = $files['name'][$cryptedFullFieldName][$uid][$fileNameKey];
            }
        }

        return $uploadedFiles;
    }

    /**
     * Sends an email.
     *
     * @return boolean True if sent successfully
     */
    public function sendEmail()
    {
        // Calls the querier
        $querier = GeneralUtility::makeInstance($this->editQuerierClassName);
        $querier->injectController($this->getController());
        $querier->injectQueryConfiguration();
        // Special processing if the field is in a subform
        if ($this->isSubformField()) {
            $additionalPartToWhereClause = $this->buildAdditionalPartToWhereClause();
            $querier->getQueryConfigurationManager()->setAdditionalPartToWhereClause($additionalPartToWhereClause);
        }
        $querier->injectAdditionalMarkers($this->additionalMarkers);
        $querier->processQuery();

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        // Processes the email sender
        $mailSender = $this->getFieldConfigurationAttribute('mailsender');
        if (empty($mailSender)) {
            $mailSender = '###user_email###';
        }
        $mailSender = $contentObject->substituteMarkerArrayCached($mailSender, array(
            '###user_email###' => $GLOBALS['TSFE']->fe_user->user['email']
        ), array(), array());


        // Processes the mail receiver
        $mailReceiverFromQuery = $this->getFieldConfigurationAttribute('mailreceiverfromquery');
        if (empty($mailReceiverFromQuery) === FALSE) {
            $mailReceiverFromQuery = $querier->parseLocalizationTags($mailReceiverFromQuery);
            $mailReceiverFromQuery = $querier->parseFieldTags($mailReceiverFromQuery);

            // Checks if the query is a SELECT query and for errors
            if ($this->isSelectQuery($mailReceiverFromQuery) === FALSE) {
                return FlashMessages::addError('error.onlySelectQueryAllowed', array(
                    $this->getFieldConfigurationAttribute('fieldName')
                ));
            } elseif (! ($resource = $GLOBALS['TYPO3_DB']->sql_query($mailReceiverFromQuery))) {
                return FlashMessages::addError('error.incorrectQueryInContent', array(
                    $this->getFieldConfigurationAttribute('fieldName')
                ));
            }
            // Processes the query
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resource);
            $mailReceiver = $row['value'];

            // Injects the row since query aliases may be used as markers
            $additionalMarkers = array();
            foreach ($row as $key => $value) {
                $additionalMarkers['###' . $key . '###'] = $value;
            }
            $querier->injectAdditionalMarkers($additionalMarkers);
        } elseif ($this->getFieldConfigurationAttribute('mailreceiverfromfield')) {
            $mailReceiver = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($this->getFieldConfigurationAttribute('mailreceiverfromfield')));
        } elseif ($this->getFieldConfigurationAttribute('mailreceiver')) {
            $mailReceiver = $this->getFieldConfigurationAttribute('mailreceiver');
        } else {
            return FlashMessages::addError('error.noEmailReceiver');
        }

        // Processes the mail carbon copy
        $mailCarbonCopyFromQuery = $this->getFieldConfigurationAttribute('mailccfromquery');
        if (empty($mailCarbonCopyFromQuery) === FALSE) {
            $mailCarbonCopyFromQuery = $querier->parseLocalizationTags($mailCarbonCopyFromQuery);
            $mailCarbonCopyFromQuery = $querier->parseFieldTags($mailCarbonCopyFromQuery);

            // Checks if the query is a SELECT query and for errors
            if ($this->isSelectQuery($mailCarbonCopyFromQuery) === FALSE) {
                return FlashMessages::addError('error.onlySelectQueryAllowed', array(
                    $this->getFieldConfigurationAttribute('fieldName')
                ));
            } elseif (! ($resource = $GLOBALS['TYPO3_DB']->sql_query($mailCarbonCopyFromQuery))) {
                return FlashMessages::addError('error.incorrectQueryInContent', array(
                    $this->getFieldConfigurationAttribute('fieldName')
                ));
            }
            // Processes the query
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resource);
            $mailCarbonCopy = $row['value'];

            // Injects the row since query aliases may be used as markers
            $additionalMarkers = array();
            foreach ($row as $key => $value) {
                $additionalMarkers['###' . $key . '###'] = $value;
            }
            $querier->injectAdditionalMarkers($additionalMarkers);
        } elseif ($this->getFieldConfigurationAttribute('mailccfromfield')) {
            $mailCarbonCopy = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($this->getFieldConfigurationAttribute('mailccfromfield')));
        } elseif ($this->getFieldConfigurationAttribute('mailcc')) {
            $mailCarbonCopy = $this->getFieldConfigurationAttribute('mailcc');
        }

        // Checks if a language configuration is set for the message
        $mailMessageLanguageFromField = $this->getFieldConfigurationAttribute('mailmessagelanguagefromfield');
        if (empty($mailMessageLanguageFromField) === FALSE) {
            $mailMessageLanguage = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($mailMessageLanguageFromField));
        } else {
            $mailMessageLanguage = $this->getFieldConfigurationAttribute('mailmessagelanguage');
        }

        // Changes the language key
        if (empty($mailMessageLanguage) === FALSE) {
            // Saves the current language key
            $languageKey = $GLOBALS['TSFE']->config['config']['language'];
            // Sets the new language key
            $GLOBALS['TSFE']->config['config']['language'] = $mailMessageLanguage;
        }

        // Gets the message and the subject for the mail
        $mailMessage = $this->getFieldConfigurationAttribute('mailmessage');
        $mailSubject = $this->getFieldConfigurationAttribute('mailsubject');

        // Replaces the field tags in the message and the subject, i.e. tags defined as ###tag###
        // This first pass is used to parse either the content or tags used in localization tags
        $mailMessage = $querier->parseFieldTags($mailMessage);
        $mailSubject = $querier->parseFieldTags($mailSubject);

        // Replaces localization tags in the message and the subject, i.e tags defined as $$$tag$$$ from the locallang.xml file.
        $mailMessage = $querier->parseLocalizationTags($mailMessage);
        $mailSubject = $querier->parseLocalizationTags($mailSubject);

        // Replaces the field tags in the message and the subject, i.e. tags defined as ###tag###
        $mailMessage = $querier->parseFieldTags($mailMessage);
        $mailSubject = $querier->parseFieldTags($mailSubject);

        // Gets the attachements if any
        $mailAttachments = $this->getFieldConfigurationAttribute('mailattachments');
        if (empty($mailAttachments) === FALSE) {
            $mailAttachments = $querier->parseLocalizationTags($mailAttachments);
            $mailAttachments = $querier->parseFieldTags($mailAttachments);
        }

        // Resets the language key
        if (empty($mailMessageLanguage) === FALSE) {
            $GLOBALS['TSFE']->config['config']['language'] = $languageKey;
        }

        // Sends the email
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $mail->setSubject($mailSubject);
        $mail->setFrom($mailSender);
        $mail->setTo(explode(',', $mailReceiver));
        $mail->setBody('<head><base href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . '" /></head><html>' . nl2br($mailMessage) . '</html>', 'text/html');
        $mail->addPart($mailMessage, 'text/plain');
        if (!empty($mailCarbonCopy)) {
            $mail->setCc(explode(',', $mailCarbonCopy));
        }
        if (!empty($mailAttachments)) {
            $files = explode(',', $mailAttachments);
            foreach ($files as $file) {
                if (is_file($file)) {
                    $mail->attach(\Swift_Attachment::fromPath($file));
                }
            }
        }
        $result = $mail->send();

        return $result;
    }
}
?>
