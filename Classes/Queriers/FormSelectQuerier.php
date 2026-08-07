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

use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;

/**
 * Default Form Select Querier.
 *
 * @package SavLibraryPlus
 */
class FormSelectQuerier extends AbstractQuerier
{
    /**
     * The saved row
     *
     * @var array
     */
    protected $savedRow;

    /**
     * The new row
     *
     * @var array
     */
    protected $newRow;

    /**
     * The validation array
     *
     * @var array
     */
    protected $validation;

    /**
     * The form unserialized data
     *
     * @var array
     */
    protected $formUnserializedData;

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery(): void
    {
        // Select the items
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	$this->buildSelectClause(),
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause(),
			/* GROUP BY */	$this->buildGroupByClause()
        );


        // Sets the rows from the query
        $this->setRows();

        // Saves the current row
        $this->savedRow = $this->rows[$this->currentRowId];

        // Gets the submitted data and unserializes them
        $submittedData = $this->getFieldValueFromCurrentRow($this->buildFullFieldName('_submitted_data_'));
        $unserializedData = unserialize($submittedData ?? '');

        // Gets the key for the submitted data
        $submittedDataKey = $this->getFormSubmittedDataKey();

        // Gets the temporary data associated with the form if any
        if (! empty($unserializedData[$submittedDataKey])) {
            $this->formUnserializedData = $unserializedData[$submittedDataKey];
            if (! empty($this->formUnserializedData['temporary'])) {
                if (! empty($this->formUnserializedData['temporary']['validation'])) {
                    $this->validation = $this->formUnserializedData['temporary']['validation'];
                    unset($this->formUnserializedData['temporary']['validation']);
                }

                $this->processFormUnserializedData();
            }
        }
    }

    /**
     * Processes the form unserialized data
     *
     * @return void
     */
    protected function processFormUnserializedData(): void
    {
        foreach ($this->formUnserializedData['temporary'] as $key => $row) {
            if ($key === 0 && ! $this->getFieldValueFromCurrentRow($this->buildFullFieldName('_validated_'))) {
                $this->newRow = $row;
            } else {
                $this->rows[$this->currentRowId] = array_merge($this->rows[$this->currentRowId], $row);
            }
        }
    }

    /**
     * Gets the validation for a field
     *
     * @param string $cryptedFullFieldName
     *
     * @return mixed
     */
    public function getFieldValidation(string $cryptedFullFieldName): mixed
    {
        if (isset($this->validation[$cryptedFullFieldName])) {
            return $this->validation[$cryptedFullFieldName];
        } else {
            return null;
        }
    }

    /**
     * Builds the WHERE clause
     *
     * @return string The WHERE clause
     */
    protected function buildWhereClause(): string
    {
        // Builds the where clause
        $whereClause = '1';

        // Adds the WHERE clause coming from the selected filter if any
        $selectedFilterKey = $this->controller->getSessionManager()->getSelectedFilterKey();
        if (! empty($selectedFilterKey)) {
            // Gets the addWhere
            $additionalWhereClause = $this->controller->getSessionManager()->getFilterField($selectedFilterKey, 'addWhere');
            $whereClause .= ' AND ' . (empty($additionalWhereClause) ? '0' : $additionalWhereClause);

            // Gets the uid and modifies the compressed parameters
            $uid = $this->controller->getSessionManager()->getFilterField($selectedFilterKey, 'uid');
            $compressedParameters = $this->controller->getUriManager()->getCompressedParameters();
            $compressedParameters = $this->controller->changeCompressedParameters($compressedParameters, 'uid', $uid);
            $this->controller->getUriManager()->setCompressedParameters($compressedParameters);
        }

        return $whereClause;
    }

    /**
     * Gets a saved row field
     *
     * @param string $fullFieldName
     *
     * @return mixed
     */
    public function getFieldValueFromSavedRow($fullFieldName): mixed
    {
        return $this->savedRow[$fullFieldName] ?? null;
    }

    /**
     * Gets a new row field
     *
     * @param string $fullFieldName
     *
     * @return mixed
     */
    public function getFieldValueFromNewRow(string $fullFieldName): mixed
    {
        return $this->newRow[$fullFieldName];
    }

    /**
     * Gets the temporary form unserialized data
     *
     * @return array
     */
    public function getTemporaryFormUnserializedData(): array
    {
        if (is_array($this->formUnserializedData['temporary'] ?? null)) {
            return $this->formUnserializedData['temporary'];
        } else {
            return [];
        }
    }
}
