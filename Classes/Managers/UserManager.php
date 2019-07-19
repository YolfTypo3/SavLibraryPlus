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

/**
 * User manager.
 *
 * @package SavLibraryPlus
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
        return (is_null(self::getTypoScriptFrontendController()->fe_user->user['uid']) ? false : true);
    }

    /**
     * Checks if the user is allowed to input data in the form
     *
     * @return boolean
     */
    public function userIsAllowedToInputData()
    {
        // Checks if the user is authenticated
        if ($this->userIsAuthenticated() === false) {
            return false;
        }

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        // Condition on date
        $time = time();
        $conditionOnInputDate = ($extensionConfigurationManager->getInputStartDate() && ($time >= $extensionConfigurationManager->getInputStartDate()) && $extensionConfigurationManager->getInputEndDate() && ($time <= $extensionConfigurationManager->getInputEndDate()));
        switch ($extensionConfigurationManager->getDateUserRestriction()) {
            case self::NOBODY:
                $conditionOnInputDate = true;
            case self::ALL:
                // The condition is applied to all users including super Admin
                break;
            case self::ADMIN_PLUS_USER:
                // The condition will be checked in userIsAdmin and applied to admin Plus users
                $conditionOnInputDate = true;
                break;
            case self::ALL_EXCLUDING_SUPER_ADMIN:
                // Checks if the user is super Admin.
                $conditionOnInputDate = ($this->userIsSuperAdmin() ? true : $conditionOnInputDate);
                break;
        }

        // Condition on allowedGroups
        $result = (count(array_intersect(explode(',', $extensionConfigurationManager->getAllowedGroups()), array_keys(self::getTypoScriptFrontendController()->fe_user->groupData['uid']))) > 0 ? true : false);
        $conditionOnAllowedGroups = ($extensionConfigurationManager->getAllowedGroups() ? $result : true);

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
            return true;
        }

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        $inputAdminConfiguration = self::getTypoScriptFrontendController()->fe_user->getUserTSconf();

        // Condition on the Input Admin Field
        $conditionOnInputAdminField = true;
        $inputAdminField = $extensionConfigurationManager->getInputAdminField();
        if (! empty($inputAdminField)) {
            $fieldValue = $this->getQuerier()->getFieldValueFromCurrentRow($this->getQuerier()
                ->buildFullFieldName($inputAdminField));
            $fieldValue = html_entity_decode($fieldValue . $additionalString, ENT_QUOTES);
            switch ($inputAdminField) {
                case 'cruser_id':
                    // Checks if the user created the record
                    if ($fieldValue != self::getTypoScriptFrontendController()->fe_user->user['uid']) {
                        $conditionOnInputAdminField = false;
                    }
                    break;
                default:
                    $conditionOnInputAdminField = (strpos($inputAdminConfiguration[ExtensionConfigurationManager::getExtensionKey() . '_Admin'], $fieldValue) === false ? false : true);
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
            $userTypoScriptConfiguration = self::getTypoScriptFrontendController()->fe_user->getUserTSconf();

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
            $userTypoScriptConfiguration = self::getTypoScriptFrontendController()->fe_user->getUserTSconf();

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
        if ($this->userIsAllowedToExportData() === false) {
            return false;
        }

        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Gets the user TypoScript configuration
            $userTypoScriptConfiguration = self::getTypoScriptFrontendController()->fe_user->getUserTSconf();

        // Sets the condition
        $condition = ($userTypoScriptConfiguration[$extensionKey . '_ExportWithQuery'] == '*');

        return $condition;
    }
}
?>