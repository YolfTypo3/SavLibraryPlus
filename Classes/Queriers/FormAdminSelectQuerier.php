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

use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Default Form Admin Select Querier.
 *
 * @package SavLibraryPlus
 */
class FormAdminSelectQuerier extends FormSelectQuerier
{

    /**
     * Executes the query
     *
     * @return void
     */
    public function executeQuery(): void
    {
        // Checks if the user is authenticated
        if ($this->controller
            ->getUserManager()
            ->userIsAllowedToInputData() === false) {
            FlashMessages::addError('fatal.notAllowedToEnterInFormAdministration');
            return;
        }

        // Processes the parent query
        parent::executeQuery();
    }

    /**
     * Processes the form unserialized data
     *
     * @return void
     */
    protected function processFormUnserializedData(): void
    {
        foreach ($this->formUnserializedData['temporary'] as $key => $row) {
            if ($key === 0) {
                $this->newRow = $row;
            } else {
                $this->rows[$this->currentRowId] = array_merge($this->rows[$this->currentRowId], $row);
            }
        }
    }

    /**
     * Builds the WHERE clause
     *
     * @return string The WHERE clause
     */
    protected function buildWhereClause(): string
    {
        // Gets the uid
        $uid = $this->controller->getUriManager()->getUid();

        // Builds the where clause
        $whereClause = '1 AND ';
        $whereClause .= $this->getQueryConfigurationManager()->getMainTable() . '.uid = ' . intval($uid);

        return $whereClause;
    }
}
