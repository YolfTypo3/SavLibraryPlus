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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Form configuration manager
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class FormConfigurationManager
{

    /**
     * The form configuration
     *
     * @var array
     */
    protected static $formConfiguration;

    /**
     * Injects the form configuration
     *
     * @param array $formConfiguration
     *
     * @return none
     */
    public static function injectFormConfiguration($formConfiguration)
    {
        self::$formConfiguration = $formConfiguration;
    }

    /**
     * Gets form configuration item
     *
     * @param string $itemKey
     *
     * @return mixed
     */
    protected static function getFormConfigurationItem($itemKey)
    {
        return self::$formConfiguration[$itemKey];
    }

    /**
     * Gets the form title.
     *
     * @return string
     */
    public static function getFormTitle()
    {
        return self::getFormConfigurationItem('title');
    }

    /**
     * Gets the list view identifier.
     *
     * @return integer
     */
    public static function getListViewIdentifier()
    {
        return self::getFormConfigurationItem('listView');
    }

    /**
     * Gets the single view identifier.
     *
     * @return integer
     */
    public static function getSingleViewIdentifier()
    {
        return self::getFormConfigurationItem('singleView');
    }

    /**
     * Gets the edit view identifier.
     *
     * @return integer
     */
    public static function getEditViewIdentifier()
    {
        return self::getFormConfigurationItem('editView');
    }

    /**
     * Gets the query identifier.
     *
     * @return integer
     */
    public static function getQueryIdentifier()
    {
        return self::getFormConfigurationItem('query');
    }

    /**
     * Gets the update view identifier.
     *
     * @return integer
     */
    public static function getFormViewIdentifier()
    {
        return self::getFormConfigurationItem('formView');
    }

    /**
     * Gets the special view identifier.
     *
     * @return integer
     */
    public static function getSpecialViewIdentifier()
    {
        return self::getFormConfigurationItem('specialView');
    }

    /**
     * Gets the views with condition for a given view type.
     *
     * @param string $viewType
     *
     * @return array or null
     */
    public static function getViewsWithCondition($viewType)
    {
        $viewsWithCondition = self::getFormConfigurationItem('viewsWithCondition');
        $key = lcfirst($viewType);
        if (is_array($viewsWithCondition) && is_array($viewsWithCondition[$key])) {
            return $viewsWithCondition[$key];
        } else {
            return NULL;
        }
    }

    /**
     * Gets the user plugin flag.
     *
     * @return boolean
     */
    public static function getUserPluginFlag()
    {
        return self::getFormConfigurationItem('userPlugin');
    }
}

?>