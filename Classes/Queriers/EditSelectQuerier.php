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
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Default Edit Select Querier.
 *
 * @package SavLibraryPlus
 */
class EditSelectQuerier extends AbstractQuerier
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
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Select the items
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	$this->buildSelectClause(),
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause(),
			/* GROUP BY */	$this->buildGroupByClause()
        );

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
