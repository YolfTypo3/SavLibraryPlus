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

namespace YolfTypo3\SavLibraryPlus\Utility;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Conditions methods
 *
 * @package SavLibraryPlus
 */
class Conditions
{

    /**
     * Checks if two parameters are equal
     *
     * @param mixed $x
     *            (first parameter)
     * @param mixed $y
     *            (second parameter)
     *
     * @return boolean (true if $x == $y)
     */
    public static function isEqual($x, $y)
    {
        return ($x == $y);
    }

    /**
     * Checks if the second parameter is in the first parameter considered as a string
     *
     * @param mixed $x
     *            (first parameter)
     * @param mixed $y
     *            (second parameter)
     *
     * @return boolean (true if $x is in $y)
     */
    public static function isInString($x, $y)
    {
        return (! (strpos($x, $y) === false));
    }

    /**
     * Checks if the second parameter is not in the first parameter considered as a string
     *
     * @param mixed $x
     *            (first parameter)
     * @param mixed $y
     *            (second parameter)
     *
     * @return boolean (true if $x is not in $y)
     */
    public static function isNotInString($x, $y)
    {
        return ((strpos($x, $y) === false));
    }

    /**
     * Checks if the parameter is an array
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return boolean (true if $x is an array)
     */
    public static function isArray($x)
    {
        return (is_array($x));
    }

    /**
     * Checks if the parameter is not an array
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return boolean (true if $x is not an array)
     */
    public static function isNotArray($x)
    {
        return (! is_array($x));
    }

    /**
     * Checks if a key exists in an array
     *
     * @param mixed $x
     *            (an array)
     * @param mixed $y
     *            (the key to check)
     *
     * @return boolean (true if $y is a key in $x)
     */
    public static function arrayKeyExists($x, $y)
    {
        if (is_array($x)) {
            return (array_key_exists($y, $x));
        }
        return (false);
    }

    /**
     * Checks if the parameter is null
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return boolean (true if $x is null)
     */
    public static function isNull($x)
    {
        return (is_null($x));
    }

    /**
     * Checks if the parameter is not null
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return boolean (true if $x is not null)
     */
    public static function isNotNull($x)
    {
        return (! is_null($x));
    }

    /**
     * Checks if the user is member of a group
     *
     * @param string $groupName
     *
     * @return boolean (true if the current user is a member of the group)
     */
    public static function isGroupMember($groupName)
    {
        if (empty($groupName)) {
            return false;
        }

        return is_array($GLOBALS['TSFE']->fe_user->groupData['title']) && in_array($groupName, $GLOBALS['TSFE']->fe_user->groupData['title']);
    }

    /**
     * Checks if the user is member of a group
     *
     * @param string $groupName
     *
     * @return boolean (true if the current user is not a member of the group)
     */
    public static function isNotGroupMember($groupName)
    {
        if (empty($groupName)) {
            return true;
        }

        return is_array($GLOBALS['TSFE']->fe_user->groupData['title']) && ! in_array($groupName, $GLOBALS['TSFE']->fe_user->groupData['title']);
    }

    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
