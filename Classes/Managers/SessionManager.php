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

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Session Manager.
 *
 * @package SavLibraryPlus
 */
class SessionManager extends AbstractManager
{
    /**
     * The library Data
     *
     * @var array
     */
    protected static $libraryData;

    /**
     * The filters session
     *
     * @var array
     */
    protected static $filtersData;

    /**
     * The selected filter Key
     *
     * @var string
     */
    protected static $selectedFilterKey;

    /**
     * Loads the session
     *
     * @return void
     */
    public static function loadSession()
    {
        // Loads the library, filters data and the selected filter key
        self::loadLibraryData();
        self::loadFiltersData();
        self::loadSelectedFilterKey();

        // Cleans the filters data
        self::cleanFiltersData();
    }

    /**
     * Loads the library data
     *
     * @return void
     */
    protected static function loadLibraryData()
    {
        self::$libraryData = self::getTypoScriptFrontendController()->fe_user->getKey('ses', AbstractController::getFormName());
    }

    /**
     * Loads the filters data
     *
     * @return void
     */
    protected static function loadFiltersData()
    {
        self::$filtersData = (array) self::getTypoScriptFrontendController()->fe_user->getKey('ses', 'filters');
    }

    /**
     * Loads the filter selected data
     *
     * @return void
     */
    protected static function loadSelectedFilterKey()
    {
        self::$selectedFilterKey = self::getTypoScriptFrontendController()->fe_user->getKey('ses', 'selectedFilterKey');
    }

    /**
     * Cleans the filter data
     *
     * @return void
     */
    protected static function cleanFiltersData()
    {
        if (UriManager::hasLibraryParameter() === false) {
            // Removes filters in the same page which are not active,
            // that is not selected or with the same contentID
            foreach (self::$filtersData as $filterKey => $filter) {
                if ($filterKey != self::$selectedFilterKey && $filter['pageID'] == self::getTypoScriptFrontendController()->id && $filter['contentID'] != self::$filtersData[self::$selectedFilterKey]['contentID']) {
                    unset(self::$filtersData[$filterKey]);
                }
            }

            // Removes the selectedFilterKey if there no filter associated with it
            if (is_array(self::$filtersData[self::$selectedFilterKey]) === false) {
                self::$selectedFilterKey = null;
            }
        }
    }

    /**
     * Saves the session
     *
     * @return void
     */
    public static function saveSession()
    {
        // Saves the compressed parameters
        self::setFieldFromSession('compressedParameters', UriManager::getCompressedParameters());
        self::getTypoScriptFrontendController()->fe_user->setKey('ses', AbstractController::getFormName(), self::$libraryData);

        // Saves the filter information
        self::getTypoScriptFrontendController()->fe_user->setKey('ses', 'filters', self::$filtersData);

        // Cleans the selected filter key
        self::getTypoScriptFrontendController()->fe_user->setKey('ses', 'selectedFilterKey', null);

        self::getTypoScriptFrontendController()->fe_user->storeSessionData();
    }

    /**
     * Gets a field in the session
     *
     * @param string $fieldKey
     *            The field key
     *
     * @return mixed
     */
    public static function getFieldFromSession($fieldKey)
    {
        if (isset(self::$libraryData[$fieldKey])) {
            return self::$libraryData[$fieldKey];
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
     * @return mixed
     */
    public static function setFieldFromSession($fieldKey, $value)
    {
        self::$libraryData[$fieldKey] = $value;
    }

    /**
     * Clears field from session
     *
     * @param string $fieldKey  The field key
     *
     * @return void
     */
    public static function clearFieldFromSession($fieldKey)
    {
        unset(self::$libraryData[$fieldKey]);
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
    public static function getSubformFieldFromSession($subfromFieldKey, $field)
    {
        return self::$libraryData['subform'][$subfromFieldKey][$field];
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
    public static function setSubformFieldFromSession($subfromFieldKey, $field, $value)
    {
        self::$libraryData['subform'][$subfromFieldKey][$field] = $value;
    }

    /**
     * Gets a localized field
     *
     * @param string $tableName
     *            The table name
     * @param integer $uid
     *            The record uid
     *
     * @return mixed
     */
    public static function getLocalizedFieldFromSession($tableName, $uid)
    {
        $localizedField = self::$libraryData['localizedFields'][$tableName][$uid] ?? null;
        return $localizedField > 0 ? $localizedField : $uid;
    }


    /**
     * Clears the subform fields
     *
     * @return void
     */
    public static function clearSubformFromSession()
    {
        unset(self::$libraryData['subform']);
    }

    /**
     * Gets the selected filter key
     *
     * @return string
     */
    public static function getSelectedFilterKey()
    {
        return self::$selectedFilterKey;
    }

    /**
     * Gets a field in a filter
     *
     * @param string $filterKey
     *            The filter key
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public static function getFilterField($filterKey, $fieldName)
    {
        return self::$filtersData[$filterKey][$fieldName];
    }

}

?>