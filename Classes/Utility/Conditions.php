<?php
namespace YolfTypo3\SavLibraryPlus\Utility;

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

/**
 * Conditions methods
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
     * @return boolean (TRUE if $x == $y)
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
     * @return boolean (TRUE if $x is in $y)
     */
    public static function isInString($x, $y)
    {
        return (! (strpos($x, $y) === FALSE));
    }

    /**
     * Checks if the second parameter is not in the first parameter considered as a string
     *
     * @param mixed $x
     *            (first parameter)
     * @param mixed $y
     *            (second parameter)
     *
     * @return boolean (TRUE if $x is not in $y)
     */
    public static function isNotInString($x, $y)
    {
        return ((strpos($x, $y) === FALSE));
    }

    /**
     * Checks if the parameter is an array
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return boolean (TRUE if $x is an array)
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
     * @return boolean (TRUE if $x is not an array)
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
     * @return boolean (TRUE if $y is a key in $x)
     */
    public static function arrayKeyExists($x, $y)
    {
        if (is_array($x)) {
            return (array_key_exists($y, $x));
        }
        return (FALSE);
    }

    /**
     * Checks if the parameter is null
     *
     * @param mixed $x
     *            (parameter to check)
     *
     * @return boolean (TRUE if $x is null)
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
     * @return boolean (TRUE if $x is not null)
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
     * @return boolean (TRUE if the current user is a member of the group)
     */
    public static function isGroupMember($groupName)
    {
        if (empty($groupName)) {
            return FALSE;
        }

        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
      /* SELECT   */	'uid',
      /* FROM     */	'fe_groups',
      /* WHERE    */	'title="' . $groupName . '"');

        return in_array($rows[0]['uid'], explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']));
    }

    /**
     * Checks if the user is member of a group
     *
     * @param string $groupName
     *
     * @return boolean (TRUE if the current user is not a member of the group)
     */
    public static function isNotGroupMember($groupName)
    {
        if (empty($groupName)) {
            return TRUE;
        }

        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
      /* SELECT   */	'uid',
      /* FROM     */	'fe_groups',
      /* WHERE    */	'title="' . $groupName . '"');

        return ! in_array($rows[0]['uid'], explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']));
    }
}
?>
