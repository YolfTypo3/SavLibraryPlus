<?php
namespace YolfTypo3\SavLibraryPlus\Queriers;

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
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Default Delete Querier.
 *
 * @package SavLibraryPlus
 */
class DeleteQuerier extends AbstractQuerier
{

    /**
     * Checks if the query can be executed
     *
     * @return boolean
     */
    public function queryCanBeExecuted()
    {
        $userManager = $this->getController()->getUserManager();
        $result = $userManager->userIsAllowedToInputData() && $userManager->userIsAllowedToChangeData(UriManager::getUid());

        return $result;
    }

    /**
     * Executes the query.
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Gets the uid
        $uid = UriManager::getUid();

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();

        $this->setDeletedField($mainTable, $uid);
    }
}
?>
