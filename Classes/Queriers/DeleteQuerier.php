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

namespace YolfTypo3\SavLibraryPlus\Queriers;

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
     * @return bool
     */
    public function queryCanBeExecuted(): bool
    {
        $userManager = $this->controller->getUserManager();
        $uriManager = $this->controller->getUriManager();
        $result = $userManager->userIsAllowedToInputData() && $userManager->userIsAllowedToChangeData($uriManager->getUid());

        return $result;
    }

    /**
     * Executes the query.
     *
     * @return void
     */
    protected function executeQuery(): void
    {
        // Gets the uid
        $uid = $this->controller->getUriManager()->getUid();

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();

        $this->setDeletedField($mainTable, $uid);
    }
}
