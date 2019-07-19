<?php
namespace YolfTypo3\SavLibraryPlus\Queriers;

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
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * Default update Querier.
 *
 * @package SavLibraryPlus
 */
class FormAdminUpdateQuerier extends UpdateQuerier
{
    /**
     * The validation array
     *
     * @var array
     */
    protected $validation;

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Gets the library configuration manager
        $libraryConfigurationManager = $this->getController()->getLibraryConfigurationManager();

        // Gets the view configuration
        $viewIdentifier = $libraryConfigurationManager->getViewIdentifier('formView');
        $viewConfiguration = $libraryConfigurationManager->getViewConfiguration($viewIdentifier);

        // Gets the active folder key
        $activeFolderKey = $this->getController()
            ->getUriManager()
            ->getFolderKey();
        if ($activeFolderKey === null) {
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

        // Gets the POST variables
        $postVariables = $this->getController()
            ->getUriManager()
            ->getPostVariables();
        unset($postVariables['formAction']);

        $this->validation = $postVariables['validation'];
        unset($postVariables['validation']);
        $this->postVariables = $postVariables;

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();
        $mainTableUid = UriManager::getUid();

        // Initializes special marker array
        $markerItemsManual = [];
        $markerItemsAuto = [];

        // Processes the regular fields. Explode the key to get the table and field names
        $variablesToUpdateOrInsert = [];
        if (is_array($this->validation)) {
            foreach ($this->validation as $fieldKey => $validated) {
                if ($validated) {
                    // Sets the field configuration
                    $this->fieldConfiguration = $this->searchConfiguration($folderFieldsConfiguration, $fieldKey);

                    $tableName = $this->fieldConfiguration['tableName'];
                    $fieldName = $this->fieldConfiguration['fieldName'];
                    $fieldType = $this->fieldConfiguration['fieldType'];
                    $fullFieldName = $tableName . '.' . $fieldName;

                    // Adds the cryted full field name
                    $this->fieldConfiguration['cryptedFullFieldName'] = $fieldKey;

                    // Checks if the field was posted. It may occurs that a field is not in the _POST variable.
                    // A special case is when double selector boxes are displayed with the attribute singleWindow = 1 which generates a select multiple.
                    if (! is_array($postVariables[$fieldKey])) {
                        continue;
                    }

                    // Gets the field value and uid
                    $uid = key($postVariables[$fieldKey]);
                    $value = current($postVariables[$fieldKey]);

                    // Adds the uid to the configuration
                    $this->fieldConfiguration['uid'] = $uid;

                    // Makes pre-processings.
                    self::$doNotAddValueToUpdateOrInsert = false;
                    $value = $this->preProcessor($value);

                    // Gets the rendered value
                    $fieldConfiguration = $this->fieldConfiguration;
                    $fieldConfiguration['value'] = $value;
                    $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\General\\' . $fieldConfiguration['fieldType'] . 'ItemViewer';
                    $itemViewer = GeneralUtility::makeInstance($className);
                    $itemViewer->injectController($this->getController());
                    $itemViewer->injectItemConfiguration($fieldConfiguration);
                    $renderedValue = $itemViewer->render();
                    if ($renderedValue == $value) {
                        $markerValue = $renderedValue;
                    } else {
                        $markerValue = $renderedValue . ' (' . $value . ')';
                    }

                    // Sets the items markers
                    if ($uid === 0) {
                        $markerItemsManual = array_merge($markerItemsManual, [
                                $fullFieldName => $markerValue
                            ]
                        );
                    } elseif ($uid > 0) {
                        $markerItemsAuto = array_merge($markerItemsAuto, [
                                $fullFieldName => $markerValue
                            ]
                        );
                    } else {
                        self::$doNotAddValueToUpdateOrInsert = true;
                    }

                    // Adds the variables
                    if (self::$doNotAddValueToUpdateOrInsert === false) {
                        $variablesToUpdateOrInsert[$tableName][$uid][$fullFieldName] = $value;
                    }
                }
            }
        }

        // Injects the markers
        $markerContent = '';

        foreach ($markerItemsAuto as $markerKey => $marker) {
            $markerContent .= $markerKey . ' : ' . $marker . chr(10);
        }
        $this->getController()
            ->getQuerier()
            ->injectAdditionalMarkers([
                '###ITEMS_AUTO###' => $markerContent
            ]
        );
        $markerContent = '';
        foreach ($markerItemsManual as $markerKey => $marker) {
            $markerContent .= $markerKey . ' : ' . $marker . chr(10);
        }
        $this->getController()
            ->getQuerier()
            ->injectAdditionalMarkers([
                '###ITEMS_MANUAL###' => $markerContent
            ]
        );

        // Updates the fields if any
        if (! empty($variablesToUpdateOrInsert)) {
            $variableToSerialize = [];

            foreach ($variablesToUpdateOrInsert as $tableName => $variableToUpdateOrInsert) {
                if (empty($tableName) === false) {
                    $variableToSerialize = $variableToSerialize + $variableToUpdateOrInsert;

                    // Updates the data
                    $key = key($variableToUpdateOrInsert);
                    $fields = current($variableToUpdateOrInsert);

                    if ($key > 0) {
                        $this->updateFields($tableName, $fields, $key);
                    }
                }
            }

            // Updates the _submitted_data_ field
            $shortFormName = AbstractController::getShortFormName();
            $variableToSerialize = $variableToSerialize + [
                'validation' => $this->validation
            ];
            $serializedVariable = serialize([
                    $shortFormName => [
                        'temporary' => $variableToSerialize
                    ]
                ]
            );
            $this->updateFields($mainTable, [
                    '_submitted_data_' => $serializedVariable,
                    '_validated_' => 1
                ],
                $mainTableUid
            );
            FlashMessages::addMessage('message.dataSaved');
        }

        if (! empty($this->postProcessingList)) {
            foreach ($this->postProcessingList as $postProcessingItem) {
                $this->fieldConfiguration = $postProcessingItem['fieldConfiguration'];
                $method = $postProcessingItem['method'];
                $value = $postProcessingItem['value'];
                $this->$method($value);
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
        $fieldType = $this->getFieldConfigurationAttribute('fieldType');

        // If a validation is forced and addEdit is not set, a hidden field was added such that the configuration can be processed when saving but the field is not added nor inserted.
        if ($this->getFieldConfigurationAttribute('addvalidationifadmin') && (! $this->getFieldConfigurationAttribute('addedit') || ! $this->getFieldConfigurationAttribute('addeditifadmin'))) {
            self::$doNotAddValueToUpdateOrInsert = true;
        }

        // Calls the verification method for the type if it exists
        $verifierMethod = 'verifierFor' . $fieldType;
        if (method_exists($this, $verifierMethod) && $this->$verifierMethod($value) !== true) {
            self::$doNotAddValueToUpdateOrInsert = true;
            self::$doNotUpdateOrInsert = true;
            return $value;
        }

        // Builds the method name
        $preProcessorMethod = 'preProcessorFor' . $fieldType;

        // Gets the crypted full field name
        $cryptedFullFieldName = $this->fieldConfiguration['cryptedFullFieldName'];

        if (empty($this->validation[$cryptedFullFieldName])) {
            self::$doNotAddValueToUpdateOrInsert = true;
        }

        // Calls the methods if it exists
        if (method_exists($this, $preProcessorMethod)) {
            $newValue = $this->$preProcessorMethod($value);
        } else {
            $newValue = $value;
        }

        // Checks if a required field is not empty
        if ($this->isRequired() && empty($newValue)) {
            self::$doNotUpdateOrInsert = true;
            FlashMessages::addError(
                'error.fieldRequired',
                [
                    $this->fieldConfiguration['label']
                ]
            );
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
                FlashMessages::addError('error.verifierUnknown');
            } elseif ($this->$verifierMethod($newValue) !== true) {
                self::$doNotAddValueToUpdateOrInsert = true;
                self::$doNotUpdateOrInsert = true;
            }
        }

        return $newValue;
    }
}
?>
