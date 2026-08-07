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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Queriers\DefaultSelectQuerier;

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
     * Gets the Frontend user
     *
     * @return FrontendUserAuthentication
     */
    public function getFrontendUser(): FrontendUserAuthentication
    {
        return $this->controller->getRequest()->getAttribute('frontend.user');
    }
    
    /**
     * Gets the Frontend user configuration
     *
     * @return array
     */
    public function getUserConfiguration(): array
    {
        $configurationArray = explode(chr(10), $this->getFrontendUser()->user['tx_savlibraryplus_config'] ?? '');
        $result = [];
      
        foreach ($configurationArray as $configurationString) {
            $position = strpos($configurationString, '=');
            if ($position !== false) {
                $parts = explode('=', $configurationString);
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $result;
    }
    
    /**
     * Gets the Frontend user id
     *
     * @return int
     */
    public function getUserId(): ?int
    {
        return $this->getFrontendUser()->getUserId();
    }
    
    /**
     * Checks if the a user is authenticated in FE.
     *
     * @return bool
     */
    public function userIsAuthenticated(): bool
    {
        return (is_null($this->getUserId()) ? false : true);
    }

    /**
     * Checks if the user is allowed to display the data
     *
     * @return bool
     */
    public function userIsAllowedToDisplayData(): bool
    {
        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->controller->getExtensionConfigurationManager();

        $allowDisplayDataQuery = $extensionConfigurationManager->getExtensionConfigurationItem('allowDisplayDataQuery');

        if (empty($allowDisplayDataQuery)) {
            return true;
        } else {
            // Processes the SELECT Query
            if (UriManager::getUid() === 0) {
                return true;
            }
            $querier = GeneralUtility::makeInstance(DefaultSelectQuerier::class, $this->controller);
            $querier->setSpecialMarkers([
                '###uid###' => UriManager::getUid()
            ]);
            $allowDisplayDataQuery = $querier->processWhereClauseTags($allowDisplayDataQuery);

            // Checks if the query is a select query
            if (! $querier->isSelectQuery($allowDisplayDataQuery)) {
                FlashMessages::addError('error.onlySelectQueryAllowed', [
                    'Flexform->allowDisplayDataQuery'
                ]);
                return false;
            }

            // Executes the query
            $resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($allowDisplayDataQuery);
            if ($resource === false) {
                FlashMessages::addError('error.incorrectQuery', [
                    'Flexform->allowDisplayDataQuery'
                ]);
                return false;
            }
            $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($resource);

            return (empty($row) ? false : true);
        }
    }

    /**
     * Checks if the user is allowed to input data in the form
     *
     * @return bool
     */
    public function userIsAllowedToInputData(): bool
    {
        // Checks if the user is authenticated
        if ($this->userIsAuthenticated() === false) {
            return false;
        }

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->controller->getExtensionConfigurationManager();

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
        $result = (count(array_intersect(explode(',', $extensionConfigurationManager->getAllowedGroups()), array_keys($this->getFrontendUser()->groupData['uid']))) > 0 ? true : false);
        $conditionOnAllowedGroups = ($extensionConfigurationManager->getAllowedGroups() ? $result : true);

        return $extensionConfigurationManager->getInputIsAllowed() && $conditionOnAllowedGroups && $conditionOnInputDate;
    }

    /**
     * Checks if the user is allowed to change data in the form
     *
     * param int $uid
     * @param string $additionalString
     *            (default '') String which will be added to the field value
     *
     * @return bool
     */
    public function userIsAllowedToChangeData(int $uid, string $additionalString = ''): bool
    {
        if ($this->userIsSuperAdmin()) {
            return true;
        }

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->controller->getExtensionConfigurationManager();

        $userConfiguration = $this->getUserConfiguration();

        // Condition on the Input Admin Field
        $conditionOnInputAdminField = true;
        $inputAdminField = $extensionConfigurationManager->getInputAdminField();
        if (! empty($inputAdminField)) {
            // Splits the inputAdminField
            $mainTable = $this->controller
            ->getQuerier()
            ->getQueryConfigurationManager()->getMainTable();
            $explodedInputAdminField = explode('.', $inputAdminField);
            if(count($explodedInputAdminField) == 1) {
                $tableName = $mainTable;
                $fieldName = $explodedInputAdminField[0];
            } elseif(count($explodedInputAdminField) == 2) {
                $tableName = $explodedInputAdminField[0];
                // The table must be the main table
                if ($tableName != $mainTable) {
                    return false;
                }
                $fieldName = $explodedInputAdminField[1];
            } else {
                return false;
            }
            $uid = intval($uid);
            if ($uid > 0) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
                $queryBuilder
                    ->select($fieldName)
                    ->from($tableName)
                    ->where($queryBuilder->expr()
                        ->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
                $row = $queryBuilder->executeQuery()->fetchAssociative();

                if (empty($row)) {
                    return false;
                }
                $fieldValue = $row[$fieldName];
            } else {
                return false;
            }
            $fieldValue = html_entity_decode($fieldValue . $additionalString, ENT_QUOTES);
            switch ($inputAdminField) {
                case 'fe_users.uid':
                case 'cruser_id':
                case $tableName . 'cruser_id':
                    // Checks if the user created the record
                    if ($fieldValue != $this->getUserId()) {
                        $conditionOnInputAdminField = false;
                    }
                    break;
                default:
                    $conditionOnInputAdminField = (strpos($userConfiguration[$this->controller->getExtensionKey() . '_Admin'], $fieldValue) === false ? false : true);
                    break;
            }
        }

        return $conditionOnInputAdminField;
    }

    /**
     * Checks if the user is a super admin for the extension
     *
     * @return bool
     */
    public function userIsSuperAdmin(): bool
    {
        // Gets the extension key
        $extensionKey = $this->controller->getExtensionKey();

        // Gets the user configuration
        $userConfiguration = $this->getUserConfiguration();

        // Sets the condition
        $condition = (($userConfiguration[$extensionKey . '_Admin'] ?? '') == '*');

        return $condition;
    }

    /**
     * Checks if the user is allowed to export data
     *
     * @return bool
     */
    public function userIsAllowedToExportData(): bool
    {
        // Gets the extension key
        $extensionKey = $this->controller->getExtensionKey();

        // Gets the user configuration
        $userConfiguration = $this->getUserConfiguration();

        // Sets the condition
        $condition = (($userConfiguration[$extensionKey . '_Export'] ?? '') == '*' || ($userConfiguration[$extensionKey . '_ExportWithQuery'] ?? '') == '*');

        return $condition;
    }

    /**
     * Checks if the user is allowed to use query when exporting data
     *
     * @return boolean
     */
    public function userIsAllowedToExportDataWithQuery(): bool
    {
        // Checks if the user is allowad to export data
        if ($this->userIsAllowedToExportData() === false) {
            return false;
        }

        // Gets the extension key
        $extensionKey = $this->controller->getExtensionKey();

        // Gets the user configuration
        $userConfiguration = $this->getUserConfiguration();

        // Sets the condition
        $condition = (($userConfiguration[$extensionKey . '_ExportWithQuery'] ?? '') == '*');

        return $condition;
    }
}
