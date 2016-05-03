<?php
namespace SAV\SavLibraryPlus\Managers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy <yolf.typo3@orange.fr>
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

use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\UriManager;

/**
 * Session Manager.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
     * @return none
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
     * @return none
     */
    protected static function loadLibraryData()
    {
        self::$libraryData = $GLOBALS['TSFE']->fe_user->getKey('ses', AbstractController::getFormName());
    }

    /**
     * Loads the filters data
     *
     * @return none
     */
    protected static function loadFiltersData()
    {
        self::$filtersData = (array) $GLOBALS['TSFE']->fe_user->getKey('ses', 'filters');
    }

    /**
     * Loads the filter selected data
     *
     * @return none
     */
    protected static function loadSelectedFilterKey()
    {
        self::$selectedFilterKey = $GLOBALS['TSFE']->fe_user->getKey('ses', 'selectedFilterKey');
    }

    /**
     * Cleans the filter data
     *
     * @return none
     */
    protected static function cleanFiltersData()
    {
        if (UriManager::hasLibraryParameter() === FALSE) {
            // Removes filters in the same page which are not active,
            // that is not selected or with the same contentID
            foreach (self::$filtersData as $filterKey => $filter) {
                if ($filterKey != self::$selectedFilterKey && $filter['pageID'] == $GLOBALS['TSFE']->id && $filter['contentID'] != self::$filtersData[self::$selectedFilterKey]['contentID']) {
                    unset(self::$filtersData[$filterKey]);
                }
            }

            // Removes the selectedFilterKey if there no filter associated with it
            if (is_array(self::$filtersData[self::$selectedFilterKey]) === FALSE) {
                self::$selectedFilterKey = NULL;
            }
        }
    }

    /**
     * Saves the session
     *
     * @return none
     */
    public static function saveSession()
    {
        // Saves the compressed parameters
        self::setFieldFromSession('compressedParameters', UriManager::getCompressedParameters());
        $GLOBALS['TSFE']->fe_user->setKey('ses', AbstractController::getFormName(), self::$libraryData);

        // Saves the filter information
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'filters', self::$filtersData);

        // Cleans the selected filter key
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'selectedFilterKey', NULL);

        $GLOBALS['TSFE']->storeSessionData();
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
        return self::$libraryData[$fieldKey];
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
     * @return none
     */
    public static function setSubformFieldFromSession($subfromFieldKey, $field, $value)
    {
        self::$libraryData['subform'][$subfromFieldKey][$field] = $value;
    }

    /**
     * Clears the subform fields
     *
     * @return none
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