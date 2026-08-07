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

namespace YolfTypo3\SavLibraryPlus\Utility;

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Conditions methods
 *
 * @package SavLibraryPlus
 */
final class Conditions
{

    protected static AbstractController $controller;  

    /**
     * Sets the controller
     *
     * @param AbstractController $controller
     *
     * @return void
     */
    public static function setController(AbstractController $controller):void
    {
        self::$controller = $controller;
    }
    
    
    /**
     * Checks if two parameters are equal
     *
     * @param mixed $x
     *            (first parameter)
     * @param mixed $y
     *            (second parameter)
     *
     * @return bool (true if $x == $y)
     */
    public static function isEqual(mixed $x, mixed $y): bool
    {
        return ($x == $y);
    }

    /**
     * Checks if the second parameter is in the first parameter considered as a string
     *
     * @param string $x
     *            (first parameter)
     * @param string $y
     *            (second parameter)
     *
     * @return bool (true if $x is in $y)
     */
    public static function isInString(string $x, string $y): bool
    {
        return (! (strpos($x, $y) === false));
    }

    /**
     * Checks if the second parameter is not in the first parameter considered as a string
     *
     * @param string $x
     *            (first parameter)
     * @param string $y
     *            (second parameter)
     *
     * @return bool (true if $x is not in $y)
     */
    public static function isNotInString(string $x, string $y): bool
    {
        return ((strpos($x, $y) === false));
    }

    /**
     * Checks if the parameter is an array
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return bool (true if $x is an array)
     */
    public static function isArray(mixed $x): bool
    {
        return (is_array($x));
    }

    /**
     * Checks if the parameter is not an array
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return bool (true if $x is not an array)
     */
    public static function isNotArray(mixed $x): bool
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
     * @return bool (true if $y is a key in $x)
     */
    public static function arrayKeyExists(mixed $x, mixed $y): bool
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
     * @return bool (true if $x is null)
     */
    public static function isNull(mixed $x): bool
    {
        return (is_null($x));
    }

    /**
     * Checks if the parameter is not null
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return bool (true if $x is not null)
     */
    public static function isNotNull(mixed $x): bool
    {
        return (! is_null($x));
    }

    /**
     * Checks if the user is member of a group
     *
     * @param string $groupName
     *
     * @return bool (true if the current user is a member of the group)
     */
    public static function isGroupMember(string $groupName): bool
    {
        if (empty($groupName)) {
            return false;
        }
        $groupDataTitle = self::$controller->getUserManager()->getFrontendUser()->groupData['title'] ??  null;

        return is_array($groupDataTitle) && in_array($groupName, $groupDataTitle);
    }

    /**
     * Checks if the user is member of a group
     *
     * @param string $groupName
     *
     * @return bool (true if the current user is not a member of the group)
     */
    public static function isNotGroupMember(string $groupName): bool
    {
        if (empty($groupName)) {
            return true;
        }
        $groupDataTitle = self::$controller->getUserManager()->getFrontendUser()->groupData['title'] ??  null;
        
        return is_array($groupDataTitle) && ! in_array($groupName, $groupDataTitle);
    }

}
