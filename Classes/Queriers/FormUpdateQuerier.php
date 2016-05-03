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

use TYPO3\CMS\Core\Utility\GeneralUtility;
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
class FormUpdateQuerier extends UpdateQuerier
{

    /**
     * Querier which is used to retreive data
     *
     * @var string
     */
    protected $editQuerierClassName = 'SAV\\SavLibraryPlus\\Queriers\\FormSelectQuerier';

    /**
     * Executes the query
     *
     * @return none
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
        if ($activeFolderKey === NULL) {
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

        // Gets the POST variables
        $postVariables = $this->getController()
            ->getUriManager()
            ->getPostVariables();
        unset($postVariables['formAction']);

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();
        $mainTableUid = UriManager::getUid();

        // Processes the regular fields. Explodes the key to get the table and field names
        $variablesToUpdate = array();
        if (is_array($postVariables)) {
            foreach ($postVariables as $postVariableKey => $postVariable) {
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
                        $variablesToUpdateOrInsert[$tableName][$uid][$tableName . '.' . $fieldName] = $value;
                    }
                }
            }
        }

        // Checks if error exists
        if (self::$doNotUpdateOrInsert === TRUE) {
            FlashMessages::addError('error.dataNotSaved');
            return;
        }

        // Updates the fields if any
        if (empty($variablesToUpdateOrInsert) === FALSE) {
            $variableToSerialize = array();
            foreach ($variablesToUpdateOrInsert as $tableName => $variableToUpdateOrInsert) {
                if (empty($tableName) === FALSE) {
                    $variableToSerialize = $variableToSerialize + $variableToUpdateOrInsert;
                }
            }

            // Updates the _submitted_data_ field
            $shortFormName = AbstractController::getShortFormName();
            $serializedVariable = serialize(array(
                $shortFormName => array(
                    'temporary' => $variableToSerialize
                )
            ));
            $this->updateFields($mainTable, array(
                '_submitted_data_' => $serializedVariable,
                '_validated_' => 0
            ), $mainTableUid);
            FlashMessages::addMessage('message.dataSaved');
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
?>
