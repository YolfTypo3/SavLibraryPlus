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

/**
 * Session Manager.
 *
 * @package SavLibraryPlus
 */
final class SessionManager extends AbstractManager
{

    /**
     * The library Data
     *
     * @var array
     */
    protected ?array $libraryData;

    /**
     * The filters session
     *
     * @var array
     */
    protected ?array $filtersData;

    /**
     * The selected filter Key
     *
     * @var string
     */
    protected ?string $selectedFilterKey;

    /**
     * Loads the session
     *
     * @return void
     */
    public function loadSession(): void
    {
        // Loads the library, filters data and the selected filter key
        $this->loadLibraryData();
        $this->loadFiltersData();
        $this->loadSelectedFilterKey();

        // Cleans the filters data
        $this->cleanFiltersData();
    }

    /**
     * Loads the library data
     *
     * @return void
     */
    protected function loadLibraryData(): void
    {
        $formName = $this->controller->getFormName();
        $this->libraryData = $this->getDataFromSession($formName);
    }

    /**
     * Loads the filters data
     *
     * @return void
     */
    protected function loadFiltersData(): void
    {
        $this->filtersData = (array) $this->getDataFromSession('filters');
    }

    /**
     * Loads the filter selected data
     *
     * @return void
     */
    protected function loadSelectedFilterKey(): void
    {
        $this->selectedFilterKey = $this->getDataFromSession('selectedFilterKey');
    }

    /**
     * Cleans the filter data
     *
     * @return void
     */
    protected function cleanFiltersData(): void
    {
        if ($this->controller->getUriManager()->hasLibraryParameter() === false) {
            // Removes filters in the same page which are not active,
            // that is not selected or with the same contentID
            foreach ($this->filtersData as $filterKey => $filter) {
                if (isset($this->selectedFilterKey) && $filterKey != $this->selectedFilterKey 
                    && $filter['pageId'] == $this->controller->getPageId() 
                    && $filter['contentUid'] != $this->filtersData[$this->selectedFilterKey]['contentUid']) {
                    unset($this->filtersData[$filterKey]);
                }
            }
       
            // Removes the selectedFilterKey if there no filter associated with it
            if (! is_array($this->filtersData[$this->selectedFilterKey] ?? null)) {
                $this->selectedFilterKey = null;
            } elseif ($this->filtersData[$this->selectedFilterKey]['pageId'] != $this->controller->getPageId()){
                $this->selectedFilterKey = null;
            }
        }
    }

    /**
     * Saves the session
     *
     * @return void
     */
    public function saveSession(): void
    {
        // Saves the compressed parameters
        $formName = $this->controller->getFormName();
        $this->setFieldFromSession('compressedParameters', $this->controller->getUriManager()->getCompressedParameters());
        $this->setDataToSession($formName, $this->libraryData);

        // Saves the filter information
        $this->setDataToSession('filters', $this->filtersData);

        // Cleans the selected filter key
        //self::setDataToSession('selectedFilterKey', null);

        $this->storeDataInSession();
    }

    /**
     * Gets a field in the session
     *
     * @param string $fieldKey
     *            The field key
     *
     * @return mixed
     */
    public function getFieldFromSession(string $fieldKey): mixed
    {
        if (isset($this->libraryData[$fieldKey])) {
            return $this->libraryData[$fieldKey];
        } else {
            return null;
        }
    }

    /**
     * Sets a field in the session
     *
     * @param string $fieldKey
     *            The field key
     * @param mixed $value
     *            The value
     *
     * @return void
     */
    public function setFieldFromSession(string $fieldKey, mixed $value): void
    {
        $this->libraryData[$fieldKey] = $value;
    }

    /**
     * Clears field from session
     *
     * @param string $fieldKey
     *            The field key
     *
     * @return void
     */
    public function clearFieldFromSession(string $fieldKey): void
    {
        unset($this->libraryData[$fieldKey]);
    }

    /**
     * Gets a field in a subform
     *
     * @param string $subfromFieldKey
     *            The subform field key
     * @param string $field
     *            The field
     *
     * @return mixed
     */
    public function getSubformFieldFromSession(string $subfromFieldKey, string $field): mixed
    {
        return $this->libraryData['subform'][$subfromFieldKey][$field] ?? null;
    }

    /**
     * Sets the value of a field in a subform
     *
     * @param string $subfromFieldKey
     *            The subform field key
     * @param string $field
     *            The field
     * @param mixed $value
     *            The value
     *
     * @return void
     */
    public function setSubformFieldFromSession(string $subfromFieldKey, string $field, mixed $value): void
    {
        $this->libraryData['subform'][$subfromFieldKey][$field] = $value;
    }

    /**
     * Gets a localized field
     *
     * @param string $tableName
     *            The table name
     * @param int $uid
     *            The record uid
     *
     * @return int|null
     */
    public function getLocalizedFieldFromSession(string $tableName, int $uid): ?int
    {
        $localizedField = $this->libraryData['localizedFields'][$tableName][$uid] ?? null;
        return $localizedField > 0 ? $localizedField : $uid;
    }

    /**
     * Clears the subform fields
     *
     * @return void
     */
    public function clearSubformFromSession(): void
    {
        unset($this->libraryData['subform']);
    }

    /**
     * Gets the selected filter key
     *
     * @return string|null
     */
    public function getSelectedFilterKey(): ?string
    {
        return $this->selectedFilterKey;
    }

    /**
     * Gets a field in a filter
     *
     * @param string|null $filterKey
     *            The filter key
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public function getFilterField(?string $filterKey, string $fieldName): mixed
    {
        return $this->filtersData[$filterKey][$fieldName] ?? null;
    }


    /**
     * Gets data from session
     *
     * @param string $key
     * 
     * @return mixed
     */
    protected function getDataFromSession(string $key): mixed
    {
        $frontEndUser = $this->controller->getUserManager()->getFrontendUser();
        return $frontEndUser->getKey('ses', $key);
    }

    /**
     * Sets data to session
     *
     * @param string $key
     * @param mixed $value
     * 
     * @return void
     */
    protected function setDataToSession(string $key, mixed $value): void
    {
        $frontEndUser = $this->controller->getUserManager()->getFrontendUser();
        $frontEndUser->setKey('ses', $key, $value);
    }

    /**
     * Stores the data in session
     *
     * @return void
     */
    protected function storeDataInSession(): void
    {
        $frontEndUser = $this->controller->getUserManager()->getFrontendUser();
        // @extensionScannerIgnoreLine
        $frontEndUser->storeSessionData();
    }
}
