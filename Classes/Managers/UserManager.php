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

use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;

/**
 * User manager.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class UserManager extends AbstractManager
{

    // Constants used in admin methods
    const NOBODY = 0;

    const ALL = 1;

    const ADMIN_PLUS_USER = 2;

    const ALL_EXCLUDING_SUPER_ADMIN = 3;

    /**
     * Checks if the a user is authenticated in FE.
     *
     * @return boolean
     */
    public function userIsAuthenticated()
    {
        return (is_null($GLOBALS['TSFE']->fe_user->user['uid']) ? FALSE : TRUE);
    }

    /**
     * Checks if the user is allowed to input data in the form
     *
     * @return boolean
     */
    public function userIsAllowedToInputData()
    {

        // Checks if the user is authenticated
        if ($this->userIsAuthenticated() === FALSE) {
            return FALSE;
        }

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        // Condition on date
        $time = time();
        $conditionOnInputDate = ($extensionConfigurationManager->getInputStartDate() && ($time >= $extensionConfigurationManager->getInputStartDate()) && $extensionConfigurationManager->getInputEndDate() && ($time <= $extensionConfigurationManager->getInputEndDate()));
        switch ($extensionConfigurationManager->getDateUserRestriction()) {
            case self::NOBODY:
                $conditionOnInputDate = TRUE;
            case self::ALL:
                // The condition is applied to all users including super Admin
                break;
            case self::ADMIN_PLUS_USER:
                // The condition will be checked in userIsAdmin and applied to admin Plus users
                $conditionOnInputDate = TRUE;
                break;
            case self::ALL_EXCLUDING_SUPER_ADMIN:
                // Checks if the user is super Admin.
                $conditionOnInputDate = ($this->userIsSuperAdmin() ? TRUE : $conditionOnInputDate);
                break;
        }

        // Condition on allowedGroups
        $result = (count(array_intersect(explode(',', $extensionConfigurationManager->getAllowedGroups()), array_keys($GLOBALS['TSFE']->fe_user->groupData['uid']))) > 0 ? TRUE : FALSE);
        $conditionOnAllowedGroups = ($extensionConfigurationManager->getAllowedGroups() ? $result : TRUE);

        return $extensionConfigurationManager->getInputIsAllowed() && $conditionOnAllowedGroups && $conditionOnInputDate;
    }

    /**
     * Checks if the user is allowed to change data in the form
     *
     * @param string $additionalString
     *            (default '') String which will be added to the field value
     *
     * @return boolean
     */
    public function userIsAllowedToChangeData($additionalString = '')
    {
        if ($this->userIsSuperAdmin()) {
            return TRUE;
        }

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        $inputAdminConfiguration = $GLOBALS['TSFE']->fe_user->getUserTSconf();

        // Condition on the Input Admin Field
        $conditionOnInputAdminField = TRUE;
        $inputAdminField = $extensionConfigurationManager->getInputAdminField();
        if (! empty($inputAdminField)) {
            $fieldValue = $this->getQuerier()->getFieldValueFromCurrentRow($this->getQuerier()
                ->buildFullFieldName($inputAdminField));
            $fieldValue = html_entity_decode($fieldValue . $additionalString, ENT_QUOTES);
            switch ($inputAdminField) {
                case 'cruser_id':
                    // Checks if the user created the record
                    if ($fieldValue != $GLOBALS['TSFE']->fe_user->user['uid']) {
                        $conditionOnInputAdminField = FALSE;
                    }
                    break;
                default:
                    $conditionOnInputAdminField = (strpos($inputAdminConfiguration[ExtensionConfigurationManager::getExtensionKey() . '_Admin'], $fieldValue) === FALSE ? FALSE : TRUE);
                    break;
            }
        }

        return $conditionOnInputAdminField;
    }

    /**
     * Checks if the user is a super admin for the extension
     *
     * @return boolean
     */
    public function userIsSuperAdmin()
    {

        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Gets the user TypoScript configuration
        $userTypoScriptConfiguration = $GLOBALS['TSFE']->fe_user->getUserTSconf();

        // Sets the condition
        $condition = ($userTypoScriptConfiguration[$extensionKey . '_Admin'] == '*');

        return $condition;
    }

    /**
     * Checks if the user is allowed to export data
     *
     * @return boolean
     */
    public function userIsAllowedToExportData()
    {

        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Gets the user TypoScript configuration
        $userTypoScriptConfiguration = $GLOBALS['TSFE']->fe_user->getUserTSconf();

        // Sets the condition
        $condition = ($userTypoScriptConfiguration[$extensionKey . '_Export'] == '*' || $userTypoScriptConfiguration[$extensionKey . '_ExportWithQuery'] == '*');

        return $condition;
    }

    /**
     * Checks if the user is allowed to use query when exporting data
     *
     * @return boolean
     */
    public function userIsAllowedToExportDataWithQuery()
    {

        // Checks if the user is allowad to export data
        if ($this->userIsAllowedToExportData() === FALSE) {
            return FALSE;
        }

        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Gets the user TypoScript configuration
        $userTypoScriptConfiguration = $GLOBALS['TSFE']->fe_user->getUserTSconf();

        // Sets the condition
        $condition = ($userTypoScriptConfiguration[$extensionKey . '_ExportWithQuery'] == '*');

        return $condition;
    }
}

?>