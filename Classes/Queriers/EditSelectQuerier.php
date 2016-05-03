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
 * Default Edit Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class EditSelectQuerier extends AbstractQuerier
{

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {
        // Checks if the user is authenticated
        if ($this->getController()
            ->getUserManager()
            ->userIsAuthenticated() === FALSE) {
            FlashMessages::addError('fatal.notAuthenticated');
            return FALSE;
        }

        // Select the items
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	$this->buildSelectClause(),
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause(),
			/* GROUP BY */	$this->buildGroupByClause());

        // Sets the rows from the query
        $this->setRows();
    }

    /**
     * Builds the WHERE clause
     *
     * @return string The WHERE clause
     */
    protected function buildWhereClause()
    {
        // Builds the where clause
        $whereClause = '1';
        $whereClause .= $this->getQueryConfigurationManager()->getAdditionalPartToWhereClause();
        $whereClause .= $this->getQueryConfigurationManager()->getUidPartToWhereClause();

        return $whereClause;
    }
}
?>
