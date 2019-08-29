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
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;

/**
 * Default List Select Querier.
 *
 * @package SavLibraryPlus
 */
class ListSelectQuerier extends AbstractQuerier
{

    /**
     * Processes the total rows count query
     *
     * @return void
     */
    public function processTotalRowsCountQuery()
    {
        // Select the item count
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
            /* SELECT   */	'count(' . ($this->buildGroupByClause() ? 'DISTINCT ' . $this->buildGroupByClause() : '*') . ') as itemCount',
            /* FROM     */	$this->buildFromClause(),
            /* WHERE    */	$this->buildWhereClause());

        // Gets the row and the item count
        $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($this->resource);

        $this->setTotalRowsCount($row['itemCount']);
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Sets the rows count
        $this->processTotalRowsCountQuery();

        // Executes the select query
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	$this->buildSelectClause(),
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause(),
			/* GROUP BY */	$this->buildGroupByClause(),
			/* ORDER BY */  $this->buildOrderByClause(),
			/* LIMIT    */  $this->buildLimitClause());

        // Sets the rows from the query
        $this->setRows();

        return;
    }

    /**
     * Builds the SELECT Clause.
     *
     * @return string
     */
    protected function buildSelectClause()
    {
        $selectClause = parent::buildSelectClause();

        // Checks if a field name alias comes from the filter
        $selectedFilterKey = SessionManager::getSelectedFilterKey();
        if (! empty($selectedFilterKey)) {
            $fieldName = SessionManager::getFilterField($selectedFilterKey, 'fieldName');
            $selectClause .= (empty($fieldName) === false ? ', ' . $fieldName . ' as fieldname' : '');
        }

        return $selectClause;
    }

    /**
     * Builds the WHERE BY Clause.
     *
     * @return string
     */
    protected function buildWhereClause()
    {
        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        // Gets the Default WHERE clause from the query configuration manager
        $whereClause = $this->queryConfigurationManager->getWhereClause();

        // Adds the WHERE clause coming from the selected filter if any
        $selectedFilterKey = SessionManager::getSelectedFilterKey();

        if (empty($selectedFilterKey) === false) {
            $additionalWhereClause = SessionManager::getFilterField($selectedFilterKey, 'addWhere');
            $searchRequestFromFilter = SessionManager::getFilterField($selectedFilterKey, 'search');
            if (empty($searchRequestFromFilter) === false) {
                // The WHERE clause coming from the filter replaces the default WHERE Clause
                $whereClause = (empty($additionalWhereClause) ? '0' : $additionalWhereClause);
            } else {
                // The WHERE clause coming from the filter is added to the default WHERE Clause
                $whereClause .= ' AND ' . (empty($additionalWhereClause) ? '0' : $additionalWhereClause);
            }
        } else {
            // Sets the WHERE clause to 0 if the rows should not be searched
            $showAllIfNoFilter = $extensionConfigurationManager->getShowAllIfNoFilter();
            if (empty($showAllIfNoFilter)) {
                return '0';
            }
        }

        // Adds the enable fields conditions for the main table
        $mainTable = $this->queryConfigurationManager->getMainTable();
        $whereClause .= $this->getPageRepository()->enableFields($mainTable);

        // Adds the allowed pages condition
        $whereClause .= $this->getAllowedPages($mainTable);

        // Adds the permanent filter if any
        $permanentFilter = $extensionConfigurationManager->getPermanentFilter();
        if (empty($permanentFilter) === false) {
            $whereClause .= ' AND ' . $permanentFilter;
        }

        // Processes WHERE clause tags
        $whereClause = $this->processWhereClauseTags($whereClause);

        return $whereClause;
    }

    /**
     * Builds the LIMIT BY Clause.
     *
     * @return string
     */
    protected function buildLimitClause()
    {
        $maxItems = $this->getController()
            ->getExtensionConfigurationManager()
            ->getMaxItems();
        return ($maxItems ? ($maxItems * UriManager::getPage()) . ',' . ($maxItems) : '');
    }
}
?>
