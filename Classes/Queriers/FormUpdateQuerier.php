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

namespace YolfTypo3\SavLibraryPlus\Queriers;

use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * Default update Querier.
 *
 * @package SavLibraryPlus
 */
class FormUpdateQuerier extends UpdateQuerier
{
    /**
     * Querier which is used to retreive data
     *
     * @var string
     */
    protected string $editQuerierClassName = \YolfTypo3\SavLibraryPlus\Queriers\FormSelectQuerier::class;

    /**
     * Checks if the query can be executed
     *
     * @return bool
     */
    public function queryCanBeExecuted(): bool
    {
        // Gets the library configuration manager
        $libraryConfigurationManager = $this->controller->getLibraryConfigurationManager();

        // Gets the view configuration
        $viewIdentifier = $libraryConfigurationManager->getViewIdentifier('formView');

        $result = (empty($viewIdentifier) ? false : true);

        return $result;
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery(): void
    {
        // Gets managers
        $libraryConfigurationManager = $this->controller->getLibraryConfigurationManager();
        $uriManager = $this->controller->getUriManager();
        
        // Gets the view configuration
        $viewIdentifier = $libraryConfigurationManager->getViewIdentifier('formView');
        $viewConfiguration = $libraryConfigurationManager->getViewConfiguration($viewIdentifier);

        // Gets the active folder key
        $activeFolderKey = $uriManager->getFolderKey();
        if ($activeFolderKey === null) {
            reset($viewConfiguration);
            $activeFolderKey = key($viewConfiguration);
        }

        // Sets the active folder
        $activeFolder = $viewConfiguration[$activeFolderKey];

        // Creates the field configuration manager
        $fieldConfigurationManager = new (FieldConfigurationManager::class)($this->controller);

        // Gets the fields configuration for the folder
        $folderFieldsConfiguration = $fieldConfigurationManager->getFolderFieldsConfiguration($activeFolder, true);

        // Gets the POST variables
        $this->postVariables = $uriManager->getPostVariables();
        unset($this->postVariables['formAction']);

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();
        $mainTableUid = $uriManager->getUid();

        // Processes the regular fields. Explodes the key to get the table and field names
        $variablesToUpdateOrInsert = [];
        if (is_array($this->postVariables)) {
            foreach ($this->postVariables as $postVariableKey => $postVariable) {
                foreach ($postVariable as $uid => $value) {

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
                        $variablesToUpdateOrInsert[$tableName][$uid][$tableName . '.' . $fieldName] = $value;
                    }
                }
            }
        }

        // Checks if error exists
        if (self::$doNotUpdateOrInsert === true) {
            FlashMessages::addError('error.dataNotSaved');
            return;
        }

        // Updates the fields if any
        if (! empty($variablesToUpdateOrInsert)) {
            // Gets the unserialized data
            $querierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\FormSelectQuerier';
            $querier = new ($querierClassName)($this->controller);
            $querier->setQueryConfiguration();
            $querier->setUpdateQuerier(null);
            $queryResult = $querier->processQuery();
            $variableToSerialize = $querier->getTemporaryFormUnserializedData();

            foreach ($variablesToUpdateOrInsert as $tableName => $variableToUpdateOrInsert) {
                if (! empty($tableName)) {
                    $key = key($variableToUpdateOrInsert);
                    if (is_array($variableToSerialize[$key] ?? null)) {
                        $variableToSerialize[$key] = $variableToUpdateOrInsert[$key] + $variableToSerialize[$key];
                    } else {
                        $variableToSerialize[$key] = $variableToUpdateOrInsert[$key];
                    }
                }
            }

            // Gets the key for the submitted data
            $submittedDataKey = $this->getFormSubmittedDataKey();

            // Updates the _submitted_data_ field
            $serializedVariable = serialize([
                $submittedDataKey => [
                    'temporary' => $variableToSerialize
                ]
            ]);
            $this->updateFields($mainTable, [
                '_submitted_data_' => $serializedVariable,
                '_validated_' => 0
            ], $mainTableUid);
            FlashMessages::addMessage('message.dataSaved');
        }

        // Post-processing
        if (! empty($this->postProcessingList)) {
            foreach ($this->postProcessingList as $postProcessingItem) {
                $this->fieldConfiguration = $postProcessingItem['fieldConfiguration'];
                $method = $postProcessingItem['method'];
                $value = $postProcessingItem['value'];
                $this->$method($value);
            }
        }
    }

}
