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

namespace YolfTypo3\SavLibraryPlus\Managers;

/**
 * Form configuration manager
 *
 * @package SavLibraryPlus
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
     * @return void
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
        return self::$formConfiguration[$itemKey] ?? null;
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
        if (is_array($viewsWithCondition) && is_array($viewsWithCondition[$key] ?? null)) {
            return $viewsWithCondition[$key];
        } else {
            return null;
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
