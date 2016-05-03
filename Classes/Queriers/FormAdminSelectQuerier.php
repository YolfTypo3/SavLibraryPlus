<?php
namespace SAV\SavLibraryPlus\Queriers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
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

use SAV\SavLibraryPlus\Controller\FlashMessages;

/**
 * Default Form Admin Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class FormAdminSelectQuerier extends FormSelectQuerier
{

    /**
     * Executes the query
     *
     * @return none
     */
    public function executeQuery()
    {
        // Checks if the user is authenticated
        if ($this->getController()
            ->getUserManager()
            ->userIsAllowedToInputData() === FALSE) {
            FlashMessages::addError('fatal.notAllowedToEnterInFormAdministration');
            return FALSE;
        }

        // Processes the parent query
        parent::executeQuery();
    }

    /**
     * Processes the form unserialized data
     *
     * @return none
     */
    protected function processFormUnserializedData()
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
    protected function buildWhereClause()
    {
        // Gets the uid
        $uid = \SAV\SavLibraryPlus\Managers\UriManager::getUid();

        // Builds the where clause
        $whereClause = '1 AND ';
        $whereClause .= $this->getQueryConfigurationManager()->getMainTable() . '.uid = ' . intval($uid);

        return $whereClause;
    }
}
?>
