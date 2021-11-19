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

namespace YolfTypo3\SavLibraryPlus\Queriers;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Default update Querier.
 *
 * @package SavLibraryPlus
 */
class UpdateQuerier extends AbstractQuerier
{

    // Error constants
    const ERROR_NONE = 0;

    const ERROR_FIELD_REQUIRED = 1;

    const ERROR_EMAIL_RECEIVER_MISSING = 2;

    const ERROR_VERIFIER_FAILLED = 3;

    // Line feed
    const LF = "\n";

    /**
     * The POST variables
     *
     * @var array
     */
    protected $postVariables;

    /**
     * The form action
     *
     * @var array
     */
    protected $formAction;

    /**
     * The processed POST variables
     *
     * @var array
     */
    protected $processedPostVariables;

    /**
     * If true, the value is not updated nor inserted
     *
     * @var boolean
     */
    public static $doNotAddValueToUpdateOrInsert = false;

    /**
     * If true, then no data are updated nor inserted
     *
     * @var boolean
     */
    public static $doNotUpdateOrInsert = false;

    /**
     * If true, the no data are updated or inserted
     *
     * @var boolean
     */
    protected $newRecord = false;

    /**
     * The error code
     *
     * @var integer
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
     * True if all field have been processed
     *
     * @var boolean
     */
    protected $fieldsProcessed;

    /**
     * Querier which is used to retreive data
     *
     * @var string
     */
    protected $editQuerierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\EditSelectQuerier';

    /**
     * Searches recursively a configuration if an aray, given a key
     *
     * @param array $arrayToSearchIn
     * @param string $key
     * @return array or false
     */
    public function searchConfiguration($arrayToSearchIn, $key)
    {
        foreach ($arrayToSearchIn as $itemKey => $item) {
            if ($itemKey == $key) {
                return $item;
            } elseif (isset($item['subform'])) {
                $configuration = $this->searchConfiguration($item['subform'], $key);
                if ($configuration != false) {
                    return $configuration;
                }
            }
        }
        return false;
    }

    /**
     * Gets an attribute in the field configuration
     *
     * @param string $attributeKey
     *
     * @return mixed
     */
    public function getFieldConfigurationAttribute($attributeKey)
    {
        return $this->fieldConfiguration[$attributeKey];
    }

    /**
     * Checks if an attribute is in the field configuration
     *
     * @param string $attributeKey
     *
     * @return boolean
     */
    public function isFieldConfigurationAttribute($attributeKey)
    {
        return array_key_exists($attributeKey, $this->fieldConfiguration);
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
     * Gets the form action
     *
     *
     * @return array
     */
    public function getFormAction()
    {
        return $this->formAction;
    }

    /**
     * Gets the current value of a post variable field
     *
     * @param string $cryptedFullFieldName
     *
     * @return mixed
     */
    public function getPostVariable($cryptedFullFieldName)
    {
        if (isset($this->postVariables[$cryptedFullFieldName])) {
            return current($this->postVariables[$cryptedFullFieldName]);
        } else {
            return null;
        }
    }

    /**
     * Gets the key of a post variable field
     *
     * @param string $cryptedFullFieldName
     *
     * @return mixed
     */
    public function getPostVariableKey($cryptedFullFieldName)
    {
        if (isset($this->postVariables[$cryptedFullFieldName]) && is_array($this->postVariables[$cryptedFullFieldName])) {
            return key($this->postVariables[$cryptedFullFieldName]);
        } else {
            return null;
        }
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
     * Returns true if there is at least one error during update
     *
     * @return boolean
     */
    public function errorDuringUpdate()
    {
        return self::$doNotUpdateOrInsert;
    }

    /**
     * Returns true if the fields were processed
     *
     * @return boolean
     */
    public function FieldsProcessed()
    {
        return $this->fieldsProcessed;
    }

    /**
     * Returns true if the record is a new one
     *
     * @return boolean
     */
    public function isNewRecord()
    {
        return $this->newRecord;
    }

    /**
     * Checks if the query can be executed
     *
     * @return boolean
     */
    public function queryCanBeExecuted()
    {
        return true;
    }

    /**
     * Checks if the user can change data
     *
     * @param string $tableName
     * @param integer $uid
     *
     * @return boolean
     */
    protected function userCanModifyData($tableName, $uid)
    {
        $result = true;
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();
        if ($tableName == $mainTable) {
            // Restriction, if any, are only on the main table field
            $userManager = $this->getController()->getUserManager();
            $result = ($this->isNewRecord() || $userManager->userIsAllowedToChangeData($uid));
        }
        return $result;
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Gets the POST variables
        $this->postVariables = $this->getController()
            ->getUriManager()
            ->getPostVariables();

        if ($this->postVariables === null) {
            return;
        }

        $this->formAction = $this->postVariables['formAction'];
        unset($this->postVariables['formAction']);

        // Gets the library configuration manager
        $libraryConfigurationManager = $this->getController()->getLibraryConfigurationManager();

        // Gets the view configuration
        $viewConfiguration = $libraryConfigurationManager->getViewConfiguration(UriManager::getViewId());

        // Gets the active folder key
        $activeFolderKey = UriManager::getFolderKey();
        if ($activeFolderKey === null || empty($viewConfiguration[$activeFolderKey])) {
            reset($viewConfiguration);
            $activeFolderKey = key($viewConfiguration);
        }

        // Sets the active folder
        $activeFolder = $viewConfiguration[$activeFolderKey];

        // Creates the field configuration manager
        $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
        $fieldConfigurationManager->injectController($this->getController());

        // Gets the fields configuration for the folder
        $folderFieldsConfiguration = $fieldConfigurationManager->getFolderFieldsConfiguration($activeFolder, true);

        // Processes the fields
        $variablesToUpdateOrInsert = [];
        $this->fieldsProcessed = false;

        foreach ($this->postVariables as $postVariableKey => $postVariable) {
            foreach ($postVariable as $uid => $value) {

                // Sets the new record flag
                $this->newRecord = ($uid === 0);

                // Sets the field configuration
                $this->fieldConfiguration = $this->searchConfiguration($folderFieldsConfiguration, $postVariableKey);
                $tableName = $this->fieldConfiguration['tableName'];
                $fieldName = $this->fieldConfiguration['fieldName'];

                // Adds the cryted full field name
                $this->fieldConfiguration['cryptedFullFieldName'] = $postVariableKey;

                // Adds the uid to the configuration
                $this->fieldConfiguration['uid'] = $uid;

                // Checks if the user can modify the data
                if ($this->userCanModifyData($tableName, $uid) === false) {
                    FlashMessages::addError('fatal.notAllowedToExecuteRequestedAction');
                    return false;
                }

                // Resets the error code
                self::$errorCode = self::ERROR_NONE;

                // Makes pre-processings.
                self::$doNotAddValueToUpdateOrInsert = false;

                $value = $this->preProcessor($value);

                // Sets the processed Post variables to retrieve for error processing if any
                $fullFieldName = $tableName . '.' . $fieldName;
                $this->processedPostVariables[$fullFieldName][$uid] = [
                    'value' => $value,
                    'errorCode' => self::$errorCode
                ];

                // Adds the variables
                if (self::$doNotAddValueToUpdateOrInsert === false) {
                    $variablesToUpdateOrInsert[$tableName][$uid][$fieldName] = $value;
                }
            }
        }
        $this->fieldsProcessed = true;

        // Checks if error exists
        if (self::$doNotUpdateOrInsert === true) {
            FlashMessages::addError('error.dataNotSaved');
            return false;
        } else {
            // No error, inserts or updates the data
            if (empty($variablesToUpdateOrInsert) === false) {
                foreach ($variablesToUpdateOrInsert as $tableName => $variableToUpdateOrInsert) {
                    if (empty($tableName) === false) {
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
            if (empty($this->postProcessingList) === false) {
                foreach ($this->postProcessingList as $postProcessingItem) {
                    $this->fieldConfiguration = $postProcessingItem['fieldConfiguration'];
                    $method = $postProcessingItem['method'];
                    $value = $postProcessingItem['value'];
                    $this->$method($value);
                }
            }

            // Unsets the localized fields in the session
            SessionManager::clearFieldFromSession('localizedFields');
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
            if (empty($this->getFieldConfigurationAttribute('updateshowonlyfield'))) {
                self::$doNotAddValueToUpdateOrInsert = true;
            }
        }

        // Calls the verification method for the type if it exists
        $verifierMethod = 'verifierFor' . $fieldType;
        if (method_exists($this, $verifierMethod) && $this->$verifierMethod($value) !== true) {
            self::$doNotAddValueToUpdateOrInsert = true;
            self::$doNotUpdateOrInsert = true;
            self::$errorCode = self::ERROR_VERIFIER_FAILLED;
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
            self::$doNotUpdateOrInsert = true;
            self::$errorCode = self::ERROR_FIELD_REQUIRED;
            FlashMessages::addError('error.fieldRequired', [
                $this->fieldConfiguration['label']
            ]);
        }

        // Sets a post-processor for query attribute if any
        if ($this->getFieldConfigurationAttribute('query')) {
            // Sets a post processor
            $this->postProcessingList[] = [
                'method' => 'postProcessorToExecuteQuery',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            ];
        }

        // Sets a post-processor for the rtf if any
        if ($this->getFieldConfigurationAttribute('generatertf')) {
            // Sets a post processor
            $this->postProcessingList[] = [
                'method' => 'postProcessorToGenerateRTF',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            ];
        }

        // Sets a post-processor for the email if any
        if ($this->getFieldConfigurationAttribute('mail')) {
            // Sets a post processor
            $this->postProcessingList[] = [
                'method' => 'postProcessorToSendEmail',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            ];

            // Gets the row before processing
            $this->rows['before'] = $this->getCurrentRowInEditView();
        }

        // Calls the verifier if it exists
        $verifierMethod = $this->getFieldConfigurationAttribute('verifier');
        if (! empty($verifierMethod)) {
            if (! method_exists($this, $verifierMethod)) {
                self::$doNotAddValueToUpdateOrInsert = true;
                self::$doNotUpdateOrInsert = true;
                self::$errorCode = self::ERROR_VERIFIER_FAILLED;
                FlashMessages::addError('error.verifierUnknown');
            } elseif ($this->$verifierMethod($newValue) !== true) {
                self::$doNotAddValueToUpdateOrInsert = true;
                self::$doNotUpdateOrInsert = true;
                self::$errorCode = self::ERROR_VERIFIER_FAILLED;
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
        $newValue = [];
        foreach ($value as $itemKey => $item) {
            if (isset($uploadedFiles[$itemKey])) {
                $newValue[$itemKey] = $uploadedFiles[$itemKey];
            } else {
                $newValue[$itemKey] = $item;
            }
        }

        // Sets a post-processor for files in FAL
        if ($this->getFieldConfigurationAttribute('type') == 'inline') {
            self::$doNotAddValueToUpdateOrInsert = true;
            $this->postProcessingList[] = [
                'method' => 'postProcessorForFilesInFal',
                'value' => $newValue,
                'fieldConfiguration' => $this->fieldConfiguration
            ];
        } else {
            // @todo Will be probably removed in TYPO3 V10
            return implode(',', $newValue);
        }
    }

    /**
     * Pre-processor for Numeric
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForNumeric($value)
    {
        return str_replace(',', '.', $value);
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
            $this->processedPostVariables[$fullFieldName][$uid] = [
                'value' => $value,
                'errorCode' => self::$errorCode
            ];

            $this->postProcessingList[] = [
                'method' => 'postProcessorForRelationManyToManyAsDoubleSelectorbox',
                'value' => $value,
                'fieldConfiguration' => $this->fieldConfiguration
            ];

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
        $this->postProcessingList[] = [
            'method' => 'postProcessorForRelationManyToManyAsSubform',
            'value' => $value,
            'fieldConfiguration' => $this->fieldConfiguration
        ];

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
        if ($this->getFieldConfigurationAttribute('toupper')) {
            $value = strtoupper($value);
        }
        if ($this->getFieldConfigurationAttribute('tolower')) {
            $value = strtolower($value);
        }
        if ($this->getFieldConfigurationAttribute('trim')) {
            $value = trim($value);
        }
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
     * Pre-processor for Rich text Editor
     *
     * @param mixed $value
     *            Value to be pre-processed
     *
     * @return mixed
     */
    protected function preProcessorForRichTextEditor($value)
    {
        $content = html_entity_decode($value, ENT_QUOTES);

        return $content;
    }

    /**
     * Gets the uid for post processors
     *
     * @return integer
     */
    public function getUidForPostProcessor()
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
                $this->insertFieldsInRelationManyToMany($this->getFieldConfigurationAttribute('MM'), [
                    'uid_local' => $uid,
                    'uid_foreign' => $item,
                    'sorting' => $itemKey + 1
                ] // The order of the selector is assumed
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
                $this->insertFieldsInRelationManyToMany($this->getFieldConfigurationAttribute('MM'), [
                    'uid_local' => $uidLocal,
                    'uid_foreign' => $uidForeign,
                    'sorting' => $rowsCount + 1
                ]);
            }

            // Sets the count
            $itemCount = $rowsCount + 1;
            $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_UPDATEquery(
                /* TABLE   */ $this->getFieldConfigurationAttribute('tableName'),
                /* WHERE   */ 'uid=' . intval($uidLocal),
                /* FIELDS  */ [
                $this->getFieldConfigurationAttribute('fieldName') => $itemCount
            ]);
        }

        return true;
    }

    /**
     * Post-processor for files in FAL.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    protected function postProcessorForFilesInFal($value)
    {
        $files = $value;

        if (is_array($files)) {
            // Gets the pid for the record
            $tableName = $this->getFieldConfigurationAttribute('tableName');
            $fieldName = $this->getFieldConfigurationAttribute('fieldName');
            $uid = $this->getUidForPostProcessor();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder->select('pid')
                ->from($tableName)
                ->where($queryBuilder->expr()
                ->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)));
            $rows = $queryBuilder->execute()->fetchAll();
            $pid = $rows[0]['pid'];

            // Deletes references in FAL
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->delete('sys_file_reference')
                ->where($queryBuilder->expr()
                ->eq('uid_foreign', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)), $queryBuilder->expr()
                ->eq('tablenames', $queryBuilder->createNamedParameter($tableName)), $queryBuilder->expr()
                ->eq('fieldname', $queryBuilder->createNamedParameter($fieldName)), $queryBuilder->expr()
                ->eq('table_local', $queryBuilder->createNamedParameter('sys_file')))
                ->execute();

            // Inserts the files in sys_file
            $fileCount = 0;
            foreach ($files as $fileKey => $file) {
                if (! empty($file)) {
                    // Inserts or updates the files in sys_file
                    $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                    $identifier = '1:/' . $file;
                    $fileObject = $resourceFactory->getFileObjectFromCombinedIdentifier($identifier);

                    // Inserts the reference
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                    $queryBuilder->insert('sys_file_reference')
                        ->values([
                        'pid' => $pid,
                        'tstamp' => time(),
                        'crdate' => time(),
                        'uid_local' => $fileObject->getUid(),
                        'uid_foreign' => $uid,
                        'cruser_id' => 0,
                        'tablenames' => $tableName,
                        'fieldname' => $fieldName,
                        'sorting_foreign' => $fileKey + 1,
                        'table_local' => 'sys_file'
                    ])
                        ->execute();
                    $fileCount = $fileCount + 1;
                }
            }

            // Update the field in the table with the file count
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder->update($tableName)
                ->where($queryBuilder->expr()
                ->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->set($fieldName, $fileCount)
                ->execute();

            return true;
        }
        return false;
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

        $cryptedFullFieldName = $this->getFieldConfigurationAttribute('cryptedFullFieldName');
        $sendMailFieldKey = null;
        if (isset($formAction['saveAndSendMail'])) {
            $sendMailFieldKey = key($formAction['saveAndSendMail'][$cryptedFullFieldName]);
        }

        // Checks if the mail can be sent
        $mailCanBeSent = false;
        if ($this->getFieldConfigurationAttribute('mailauto')) {
            // Mail is sent if a field has changed
            // Gets the current row in the edit view after insert or update
            $this->rows['after'] = $this->getCurrentRowInEditView();
            foreach ($this->rows['after'] as $fieldKey => $field) {
                if (is_array($this->postVariables) && array_key_exists(AbstractController::cryptTag($fieldKey), $this->postVariables) && $field != $this->rows['before'][$fieldKey]) {
                    $mailCanBeSent = true;
                }
            }
        } elseif ($this->getFieldConfigurationAttribute('mailalways')) {
            $mailCanBeSent = true;
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
                $mailCanBeSent = true;
            } else {
                $mailCanBeSent = false;
            }
        } elseif (empty($value) && $sendMailFieldKey !== null && $sendMailFieldKey == $this->getFieldConfigurationAttribute('uid')) {
            // A checkbox with an email button was hit
            $mailCanBeSent = true;
        } else {
            $fieldForCheckMail = $this->getFieldConfigurationAttribute('fieldforcheckmail');
            if (! empty($fieldForCheckMail)) {
                $fullFieldName = $this->buildFullFieldName($fieldForCheckMail);
                $mailIf = $this->getFieldConfigurationAttribute('mailif');
                if (! empty($mailIf)) {
                    // Creates the field configuration manager
                    $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                    $fieldConfigurationManager->injectController($this->getController());
                    $fieldConfigurationManager->injectQuerier($this);
                    $mailCanBeSent = $fieldConfigurationManager->processFieldCondition($mailIf);
                } else {
                    if (empty($this->rows['after'][$fullFieldName])) {
                        $mailCanBeSent = false;
                    }
                }
            }
        }

        // Send the email
        if ($mailCanBeSent === true) {
            $mailSuccesFlag = ($this->sendEmail() > 0 ? 1 : 0);

            // Updates the fields if needed
            $update = false;
            if ($mailSuccesFlag) {
                // Checkbox with an email button
                if ($sendMailFieldKey !== null && $sendMailFieldKey == $this->getFieldConfigurationAttribute('uid')) {
                    $fields = [
                        $this->getFieldConfigurationAttribute('fieldName') => $mailSuccesFlag
                    ];
                    $update = true;
                }

                // Attribute fieldToSetAfterMailSent is used
                if ($this->getFieldConfigurationAttribute('fieldtosetaftermailsent')) {
                    $fields = [
                        $this->getFieldConfigurationAttribute('fieldtosetaftermailsent') => $mailSuccesFlag
                    ];
                    $update = true;
                }
            } elseif (self::$errorCode > 0) {
                $tableName = $this->getFieldConfigurationAttribute('tableName');
                $fieldName = $this->getFieldConfigurationAttribute('fieldName');
                $uid = $this->getUidForPostProcessor();
                $fullFieldName = $tableName . '.' . $fieldName;
                $this->processedPostVariables[$fullFieldName][$uid] = [
                    'value' => 0,
                    'errorCode' => self::$errorCode
                ];

                // Attribute fieldToSetAfterMailSent is used
                $update = true;
                if ($this->getFieldConfigurationAttribute('fieldtosetaftermailsent')) {
                    $fieldName = $this->getFieldConfigurationAttribute('fieldtosetaftermailsent');
                } else {
                    $fieldName = $this->getFieldConfigurationAttribute('fieldName');
                }
                $fields = [
                    $fieldName => 0
                ];
                $fullFieldName = $tableName . '.' . $fieldName;
                $this->processedPostVariables[$fullFieldName][$uid] = [
                    'value' => 0,
                    'errorCode' => self::$errorCode
                ];
            }
            if ($update === true) {
                $tableName = $this->getFieldConfigurationAttribute('tableName');
                $uid = $this->getUidForPostProcessor();
                $this->updateFields($tableName, $fields, $uid);
            }
        }

        return false;
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
        $cryptedFullFieldName = $this->getFieldConfigurationAttribute('cryptedFullFieldName');
        $generateRtfFieldKey = null;
        if (isset($formAction['saveAndGenerateRtf']) && isset($formAction['saveAndGenerateRtf'][$cryptedFullFieldName])) {
            $generateRtfFieldKey = key($formAction['saveAndGenerateRtf'][$cryptedFullFieldName]);
        }

        if (($generateRtfFieldKey !== null && $generateRtfFieldKey == $this->getFieldConfigurationAttribute('uid')) || $this->getFieldConfigurationAttribute('generatertfonsave')) {
            // Creates the querier
            $querier = GeneralUtility::makeInstance($this->editQuerierClassName);
            $querier->injectController($this->getController());
            $querier->injectUpdateQuerier($this);
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
                    return true;
                }
            }

            // Checks if there exists replacement strings for fields
            foreach ($this->fieldConfiguration as $fieldKey => $field) {
                $matchFieldKey = [];
                $matchField = [];
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
            $file = @file_get_contents(Environment::getPublicPath() . '/' . $templateRtf);
            if (empty($file)) {
                return FlashMessages::addError('error.incorrectRTFTemplateFileName');
            }

            // Cleans the file content
            $file = preg_replace('/((?:#[\\r\\n]*){3})((?:[^#][\\r\\n]*)+)((?:#[\\r\\n]*){3})/m', '###' . str_replace('\\n\\r', '', '$2') . '###', $file);
            $matches = [];
            preg_match_all('/###([^#]+)###/', $file, $matches);
            foreach ($matches[0] as $matchKey => $match) {
                $match = preg_replace('/\\\\[^\s]+ /', '', $match);
                $file = str_replace($matches[0][$matchKey], $match, $file);
            }

            // Parses the file content
            $file = html_entity_decode($querier->parseFieldTags($file));

            // Gets the file name for saving the file
            $saveFileRtf = $querier->parseFieldTags($this->getFieldConfigurationAttribute('savefilertf'));

            // Creates the directories if necessary
            $pathParts = pathinfo($saveFileRtf);
            $directories = explode('/', $pathParts['dirname']);
            $path = Environment::getPublicPath() . '/';
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
            $fields = [
                $this->getFieldConfigurationAttribute('fieldName') => $pathParts['basename']
            ];
            $tableName = $this->getFieldConfigurationAttribute('tableName');
            $uid = $this->getUidForPostProcessor();
            $this->updateFields($tableName, $fields, $uid);
        }
        return true;
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

        // Gets the template service
        $markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        // Evaluates the query condition if any
        if ($this->getFieldConfigurationAttribute('queryif')) {
            $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
            $fieldConfigurationManager->injectController($this->getController());
            $fieldConfigurationManager->injectQuerier($this);
            $queryIfCondition = $fieldConfigurationManager->processFieldCondition($this->getFieldConfigurationAttribute('queryif'));
        } else {
            $queryIfCondition = true;
        }
        // Gets the queryOnValue attribute
        $queryOnValueAttribute = $this->getFieldConfigurationAttribute('queryonvalue');
        if ($queryIfCondition && (empty($queryOnValueAttribute) || $queryOnValueAttribute == $value)) {
            // Sets the markers
            $markers = $this->buildSpecialMarkers();
            if ($this->isSubformField()) {
                $uidSubform = $this->getFieldConfigurationAttribute('uid');
                $markers = array_merge($markers, [
                    '###uidItem###' => $uidSubform,
                    '###uidSubform###' => $uidSubform
                ]);
            }
            $markers = array_merge($markers, [
                '###value###' => $value
            ]);

            // Gets the queryForeach attribute
            $queryForeachAttribute = $this->getFieldConfigurationAttribute('queryforeach');

            if (! empty($queryForeachAttribute)) {
                $foreachCryptedFieldName = AbstractController::cryptTag($this->buildFullFieldName($queryForeachAttribute));
                $foreachValues = current($this->postVariables[$foreachCryptedFieldName]);
                foreach ($foreachValues as $foreachValue) {
                    $markers['###' . $queryForeachAttribute . '###'] = $foreachValue;
                    // @extensionScannerIgnoreLine
                    $temporaryQueryStrings = $markerBasedTemplateService->substituteMarkerArrayCached($this->getFieldConfigurationAttribute('query'), $markers, [], []);
                    $queryStrings = explode(';', $temporaryQueryStrings);
                    foreach ($queryStrings as $queryString) {
                        $resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($queryString);
                        if (DatabaseCompatibility::getDatabaseConnection()->sql_error($resource)) {
                            FlashMessages::addError('error.incorrectQueryInQueryProperty');
                            break;
                        }
                    }
                }
            } else {
                // Calls the querier
                $querier = GeneralUtility::makeInstance($this->editQuerierClassName);
                $querier->injectController($this->getController());
                $querier->injectUpdateQuerier($this);
                $querier->injectQueryConfiguration();
                $querier->injectAdditionalMarkers($this->additionalMarkers);
                $querier->processQuery();
                // @extensionScannerIgnoreLine
                $temporaryQueryStrings = $markerBasedTemplateService->substituteMarkerArrayCached($this->getFieldConfigurationAttribute('query'), $markers, [], []);
                $queryStrings = explode(';', $temporaryQueryStrings);

                foreach ($queryStrings as $queryString) {
                    $queryString = $querier->parseFieldTags($queryString);
                    $queryString = $querier->parseLocalizationTags($queryString);
                    $resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($queryString);
                    if (DatabaseCompatibility::getDatabaseConnection()->sql_error($resource)) {
                        FlashMessages::addError('error.incorrectQueryInQueryProperty');
                        break;
                    }
                }
            }
        }
        return true;
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
            return FlashMessages::addError('error.isNotValidInteger', [
                $value
            ]);
        } else {
            return true;
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
            return FlashMessages::addError('error.isNotValidCurrency', [
                $value
            ]);
        } else {
            return true;
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
            return FlashMessages::addError('error.isValidPattern', [
                $value
            ]);
        } else {
            return true;
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
            return true;
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
            return FlashMessages::addError('error.isValidLength', [
                $value
            ]);
        } else {
            return true;
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
        $matches = [];
        if (! preg_match('/\[([\d]+),\s*([\d]+)\]/', $verifierParameter, $matches)) {
            return FlashMessages::addError('error.verifierInvalidIntervalParameter', [
                $value
            ]);
        }

        if ((int) $value < (int) $matches[1] || (int) $value > (int) $matches[2]) {
            return FlashMessages::addError('error.isValidInterval', [
                $value
            ]);
        } else {
            return true;
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
        } elseif (! ($resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($query))) {
            return FlashMessages::addError('error.incorrectQueryInContent');
        } else {
            $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($resource);
            if (! current($row)) {
                return FlashMessages::addError('error.isValidQuery');
            } else {
                return true;
            }
        }
    }

    /**
     * Returns true if a field is required
     *
     * @return boolean
     */
    protected function isRequired()
    {
        return ($this->fieldConfiguration['required'] || preg_match('/required/', $this->fieldConfiguration['eval']) > 0);
    }

    /**
     * Returns true if the field is in a subform
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
     * @return void
     */
    protected function insertFields($tableName, $fields)
    {
        // Inserts the fields in the storage page if any or in the current page by default
        $storagePage = $this->getController()
            ->getExtensionConfigurationManager()
            ->getStoragePage();
        $fields = array_merge($fields, [
            'pid' => ($storagePage ? $storagePage : $this->getTypoScriptFrontendController()->id)
        ]);

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
        $querier->injectUpdateQuerier($this);
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
        $var = [
            'd' => [
                'type' => 'day',
                'pattern' => '([0-9]{2})'
            ],
            'e' => [
                'type' => 'day',
                'pattern' => '([ 0-9][0-9])'
            ],
            'H' => [
                'type' => 'hour',
                'pattern' => '([0-9]{2})'
            ],
            'I' => [
                'type' => 'hour',
                'pattern' => '([0-9]{2})'
            ],
            'm' => [
                'type' => 'month',
                'pattern' => '([0-9]{2})'
            ],
            'M' => [
                'type' => 'minute',
                'pattern' => '([0-9]{2})'
            ],
            'S' => [
                'type' => 'second',
                'pattern' => '([0-9]{2})'
            ],
            'Y' => [
                'type' => 'year',
                'pattern' => '([0-9]{4})'
            ],
            'y' => [
                'type' => 'year_without_century',
                'pattern' => '([0-9]{2})'
            ]
        ];

        // Intialises the variables
        $year = 0;
        $year_without_century = 0;
        $month = 0;
        $hour = 0;
        $day = 0;
        $minute = 0;
        $second = 0;

        // Builds the expression to match the string according to the format
        $matchesFormat = [];
        preg_match_all('/%([deHImMSYy])([^%]*)/', $format, $matchesFormat);

        $exp = '/';
        foreach ($matchesFormat[1] as $key => $match) {
            $exp .= $var[$matchesFormat[1][$key]]['pattern'] . '(?:' . str_replace('/', '\/', $matchesFormat[2][$key]) . ')';
        }
        $exp .= '/';

        $out = 0;
        if ($date) {
            $matchesDate = [];
            if (! preg_match($exp, $date, $matchesDate)) {
                FlashMessages::addError('error.incorrectDateFormat');
                self::$doNotAddValueToUpdateOrInsert = true;
                self::$doNotUpdateOrInsert = true;
                self::$errorCode = self::ERROR_VERIFIER_FAILLED;
                return $date;
            } else {
                unset($matchesDate[0]);
                $res = [];
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
                    self::$doNotAddValueToUpdateOrInsert = true;
                    self::$doNotUpdateOrInsert = true;
                    self::$errorCode = self::ERROR_VERIFIER_FAILLED;
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
        $uploadedFiles = [];

        // Gets the file array
        $prefixId = $this->getController()->getExtensionConfigurationManager()->getExtensionPrefixId();
        $files = $GLOBALS['_FILES'][$prefixId];

        // Gets the crypted full field name
        $cryptedFullFieldName = $this->getFieldConfigurationAttribute('cryptedFullFieldName');

        // If upload folder does not exist, creates it
        $uploadFolder = $this->getFieldConfigurationAttribute('uploadfolder');
        $uploadFolder .= ($this->getFieldConfigurationAttribute('addToUploadFolder') ? '/' . $this->getFieldConfigurationAttribute('addToUploadFolder') : '');

        if ($this->getFieldConfigurationAttribute('type') == 'inline') {
            $folderPath = $uploadFolder;
            $uploadFolder = 'fileadmin/' . $uploadFolder;
        }
        // @todo use try catch
        $error = GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/' . $uploadFolder);

        if ($error) {
            self::$doNotAddValueToUpdateOrInsert = true;
            return FlashMessages::addError('error.cannotCreateDirectoryInUpload', [
                $uploadFolder
            ]);
        }

        // Processes the file array
        $formName = AbstractController::getFormName();
        foreach ($files['name'][$formName][$cryptedFullFieldName] as $uid => $field) {
            foreach ($field as $fileNameKey => $fileName) {
                // Skips the file if there is no file name
                if (empty($fileName)) {
                    continue;
                }

                // Checks the extension
                $path_parts = pathinfo($files['name'][$formName][$cryptedFullFieldName][$uid][$fileNameKey]);
                $fileExtension = strtolower($path_parts['extension']);
                $allowed = $this->getFieldConfigurationAttribute('allowed');
                if ($allowed && in_array($fileExtension, explode(',', $allowed)) === false) {
                    self::$doNotAddValueToUpdateOrInsert = true;
                    return FlashMessages::addError('error.forbiddenFileTypeInUpload', [
                        $fileExtension
                    ]);
                }

                if (empty($allowed) && in_array($fileExtension, explode(',', $this->getFieldConfigurationAttribute('disallowed'))) === true) {
                    self::$doNotAddValueToUpdateOrInsert = true;
                    return FlashMessages::addError('error.forbiddenFileTypeInUpload', [
                        $fileExtension
                    ]);
                }

                // Uploads the file
                if (move_uploaded_file($files['tmp_name'][$formName][$cryptedFullFieldName][$uid][$fileNameKey], $uploadFolder . '/' . $files['name'][$formName][$cryptedFullFieldName][$uid][$fileNameKey]) === false) {
                    self::$doNotAddValueToUpdateOrInsert = true;
                    return FlashMessages::addError('error.uploadAborted');
                }

                if ($this->getFieldConfigurationAttribute('type') == 'inline') {
                    // FAL
                    $uploadedFiles[$fileNameKey] = $folderPath . '/' . $files['name'][$formName][$cryptedFullFieldName][$uid][$fileNameKey];
                } else {
                    $uploadedFiles[$fileNameKey] = $files['name'][$formName][$cryptedFullFieldName][$uid][$fileNameKey];
                }
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
        $this->additionalMarkers = array_merge($this->additionalMarkers, [
            '###user_email###' => $this->getTypoScriptFrontendController()->fe_user->user['email']
        ]);
        $querier->injectAdditionalMarkers($this->additionalMarkers);
        $querier->processQuery();

        $result = true;
        $indexesMail = [
            '',
            '.1',
            '.2',
            '.3',
            '.4',
            '.5',
            '.6',
            '.7',
            '.8',
            '.9'
        ];

        foreach ($indexesMail as $indexMail) {

            if ($this->isFieldConfigurationAttribute('mailsender' . $indexMail)) {
                // Processes the email sender
                $mailSender = $this->getFieldConfigurationAttribute('mailsender' . $indexMail);

                // Replaces the field tags in the mailSender, i.e. tags defined as ###tag###
                // This first pass is used to parse either the content or tags used in localization tags
                $mailSender = $querier->parseFieldTags($mailSender);

                // Replaces localization tags in the message and the subject, i.e tags defined as $$$tag$$$ from the locallang.xlf file.
                $mailSender = $querier->parseLocalizationTags($mailSender);

                // Replaces the field tags in the message and the subject, i.e. tags defined as ###tag###
                $mailSender = $querier->parseFieldTags($mailSender);

                // Processes the mail receiver
                $mailReceiverFromQuery = $this->getFieldConfigurationAttribute('mailreceiverfromquery' . $indexMail);
                if (! empty($mailReceiverFromQuery)) {
                    $mailReceiverFromQuery = $querier->parseLocalizationTags($mailReceiverFromQuery);
                    $mailReceiverFromQuery = $querier->parseFieldTags($mailReceiverFromQuery);

                    // Checks if the query is a SELECT query and for errors
                    if ($this->isSelectQuery($mailReceiverFromQuery) === false) {
                        return FlashMessages::addError('error.onlySelectQueryAllowed', [
                            $this->getFieldConfigurationAttribute('fieldName')
                        ]);
                    } elseif (! ($resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($mailReceiverFromQuery))) {
                        return FlashMessages::addError('error.incorrectQueryInContent', [
                            $this->getFieldConfigurationAttribute('fieldName')
                        ]);
                    }
                    // Processes the query
                    $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($resource);
                    $mailReceiver = $row['value'];

                    if (! empty($row)) {
                        // Injects the row since query aliases may be used as markers
                        $additionalMarkers = [];
                        foreach ($row as $key => $value) {
                            $additionalMarkers['###' . $key . '###'] = $value;
                        }
                    }
                    $querier->injectAdditionalMarkers($additionalMarkers);
                } elseif ($this->getFieldConfigurationAttribute('mailreceiverfromfield' . $indexMail)) {
                    $mailReceiver = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($this->getFieldConfigurationAttribute('mailreceiverfromfield' . $indexMail)));
                } elseif ($this->getFieldConfigurationAttribute('mailreceiver' . $indexMail)) {
                    $mailReceiver = $this->getFieldConfigurationAttribute('mailreceiver' . $indexMail);
                    $mailReceiver = $querier->parseLocalizationTags($mailReceiver);
                    $mailReceiver = $querier->parseFieldTags($mailReceiver);
                } else {
                    return FlashMessages::addError('error.noEmailReceiver');
                }

                if (empty($mailReceiver)) {
                    self::$doNotUpdateOrInsert = true;
                    self::$errorCode = self::ERROR_EMAIL_RECEIVER_MISSING;
                    return FlashMessages::addError('error.noEmailReceiver');
                }

                // Processes the mail carbon copy
                $mailCarbonCopyFromQuery = $this->getFieldConfigurationAttribute('mailccfromquery' . $indexMail);
                if (empty($mailCarbonCopyFromQuery) === false) {
                    $mailCarbonCopyFromQuery = $querier->parseLocalizationTags($mailCarbonCopyFromQuery);
                    $mailCarbonCopyFromQuery = $querier->parseFieldTags($mailCarbonCopyFromQuery);

                    // Checks if the query is a SELECT query and for errors
                    if ($this->isSelectQuery($mailCarbonCopyFromQuery) === false) {
                        return FlashMessages::addError('error.onlySelectQueryAllowed', [
                            $this->getFieldConfigurationAttribute('fieldName')
                        ]);
                    } elseif (! ($resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($mailCarbonCopyFromQuery))) {
                        return FlashMessages::addError('error.incorrectQueryInContent', [
                            $this->getFieldConfigurationAttribute('fieldName')
                        ]);
                    }
                    // Processes the query
                    $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($resource);
                    $mailCarbonCopy = $row['value'];

                    // Injects the row since query aliases may be used as markers
                    $additionalMarkers = [];
                    foreach ($row as $key => $value) {
                        $additionalMarkers['###' . $key . '###'] = $value;
                    }
                    $querier->injectAdditionalMarkers($additionalMarkers);
                } elseif ($this->getFieldConfigurationAttribute('mailccfromfield' . $indexMail)) {
                    $mailCarbonCopy = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($this->getFieldConfigurationAttribute('mailccfromfield' . $indexMail)));
                } elseif ($this->getFieldConfigurationAttribute('mailcc' . $indexMail)) {
                    $mailCarbonCopy = $this->getFieldConfigurationAttribute('mailcc' . $indexMail);
                }

                // Checks if a language configuration is set for the message
                $mailMessageLanguageFromField = $this->getFieldConfigurationAttribute('mailmessagelanguagefromfield' . $indexMail);
                if (empty($mailMessageLanguageFromField) === false) {
                    $mailMessageLanguage = $querier->getFieldValueFromCurrentRow($querier->buildFullFieldName($mailMessageLanguageFromField));
                } else {
                    $mailMessageLanguage = $this->getFieldConfigurationAttribute('mailmessagelanguage' . $indexMail);
                }

                // Changes the language key
                if (empty($mailMessageLanguage) === false) {
                    // Saves the current language key
                    $languageKey = $this->getTypoScriptFrontendController()->config['config']['language'];
                    // Sets the new language key
                    $this->getTypoScriptFrontendController()->config['config']['language'] = $mailMessageLanguage;
                }

                // Gets the message and the subject for the mail
                $mailMessage = $this->getFieldConfigurationAttribute('mailmessage' . $indexMail);
                $mailSubject = $this->getFieldConfigurationAttribute('mailsubject' . $indexMail);

                // Replaces the field tags in the message and the subject, i.e. tags defined as ###tag###
                // This first pass is used to parse either the content or tags used in localization tags
                $mailMessage = $querier->parseFieldTags($mailMessage);
                $mailSubject = $querier->parseFieldTags($mailSubject);

                // Replaces localization tags in the message and the subject, i.e tags defined as $$$tag$$$ from the locallang.xlf file.
                $mailMessage = $querier->parseLocalizationTags($mailMessage);
                $mailSubject = $querier->parseLocalizationTags($mailSubject);

                // Replaces the field tags in the message and the subject, i.e. tags defined as ###tag###
                $mailMessage = $querier->parseFieldTags($mailMessage);
                $mailSubject = $querier->parseFieldTags($mailSubject);

                // Gets the attachements if any
                $mailAttachments = $this->getFieldConfigurationAttribute('mailattachments' . $indexMail);
                if (empty($mailAttachments) === false) {
                    $mailAttachments = $querier->parseLocalizationTags($mailAttachments);
                    $mailAttachments = $querier->parseFieldTags($mailAttachments);
                }

                // Resets the language key
                if (empty($mailMessageLanguage) === false) {
                    $this->getTypoScriptFrontendController()->config['config']['language'] = $languageKey;
                }

                // Sends the email
                /** @var MailMessage $mail */
                $mail = GeneralUtility::makeInstance(MailMessage::class);
                /**
                 * @todo Will be removed in TYPO3 12
                 */
                if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '10.0', '<')) {
                    $mail->setSubject($mailSubject);
                    $mail->setFrom($mailSender);
                    $mail->setTo(explode(',', $mailReceiver));
                    $mail->setBody('<head><base href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . '" /></head><html>' . nl2br($mailMessage) . '</html>', 'text/html');
                    $mail->addPart($mailMessage, 'text/plain');
                    if (! empty($mailCarbonCopy)) {
                        $mail->setCc(explode(',', $mailCarbonCopy));
                    }
                } else {
                    $mail->subject($mailSubject);
                    $mail->from($mailSender);
                    $mail->to(...explode(',', $mailReceiver));
                    $mail->html(nl2br($mailMessage));
                    $mail->text($mailMessage);
                    if (! empty($mailCarbonCopy)) {
                        $mail->setCc(explode(',', $mailCarbonCopy));
                    }
                }
                if (! empty($mailAttachments)) {
                    $files = explode(',', $mailAttachments);
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            /**
                             *
                             * @todo Will be removed in TYPO3 12
                             */
                            if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '10.0', '<')) {
                                // @extensionScannerIgnoreLine
                                $mail->attach(\Swift_Attachment::fromPath($file));
                            } else {
                                $mail->attachFromPath($file);
                            }
                        }
                    }
                }

                $result = $result && $mail->send();

//                 debug([
//                 '$mailSender' => $mailSender,
//                 '$mailReceiver' => $mailReceiver,
//                 '$mailCarbonCopy' => $mailCarbonCopy,
//                 '$mailSubject' => $mailSubject,
//                 '$mailMessage' => $mailMessage,
//                 '$result' => $result,
//                 ]);
            }
        }
        return $result;
    }
}
