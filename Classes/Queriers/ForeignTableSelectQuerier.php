<?php
namespace YolfTypo3\SavLibraryPlus\Queriers;

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

/**
 * Default ForeignTableSelect Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ForeignTableSelectQuerier extends AbstractQuerier
{

    /**
     * If TRUE the query is not processed
     *
     * @var boolean
     *
     */
    protected $doNotProcessQuery = FALSE;

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {
        // Checks if the query must be processed
        if ($this->doNotProcessQuery) {
            return;
        }

        // Selects the items
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	$this->buildSelectClause(),
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause(),
			/* GROUP BY */	$this->buildGroupByClause(),
			/* ORDER BY */	$this->buildOrderByClause(),
			/* LIMIT    */	$this->buildLimitClause());

        // Sets the rows from the query
        $this->setRows();
    }

    /**
     * Processes the total rows count query
     *
     * @return none
     */
    public function processTotalRowsCountQuery()
    {
        // Checks if the query msut be processed
        if ($this->doNotProcessQuery) {
            return;
        }

        // Selects the item count
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	'count(' . ($this->buildGroupByClause() ? 'DISTINCT ' . $this->buildGroupByClause() : '*') . ') as itemCount',
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause());

        // Gets the row and the item count
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource);

        $this->setTotalRowsCount($row['itemCount']);
    }

    /**
     * Builds Where Clause.
     *
     * @return string
     */
    public function buildFromClause()
    {
        $foreignTables = $this->getQueryConfigurationManager()->getForeignTables();
        if (empty($foreignTables) === FALSE) {
            $foreignTables = $this->parseFieldTags($foreignTables);
        }
        $fromClause = $this->getQueryConfigurationManager()->getMainTable() . $foreignTables;

        return $fromClause;
    }

    /**
     * Builds the default WHERE clause
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return string The WHERE Clause
     */
    protected function buildDefautWhereClause(&$fieldConfiguration)
    {
        // Builds the where clause
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        $whereClause = (! $fieldConfiguration['overrideenablefields'] ? '1' . $contentObject->enableFields($fieldConfiguration['foreign_table']) : '1');

        // Sets the override starting point condition
        $overrideStartingPoint = $fieldConfiguration['fieldType'] == 'RelationManyToManyAsDoubleSelectorbox' || $fieldConfiguration['fieldType'] == 'RelationOneToManyAsSelectorbox' || $fieldConfiguration['overridestartingpoint'];

        $whereClause .= ((! $overrideStartingPoint && $contentObject->data['pages']) ? ' AND ' . $fieldConfiguration['foreign_table'] . '.pid IN (' . $contentObject->data['pages'] . ')' : '');

        return $whereClause;
    }

    /**
     * Builds a query configuration for a one-to-many relation
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return none
     */
    public function buildQueryConfigurationForOneToManyRelation(&$fieldConfiguration)
    {
        $this->doNotProcessQuery = FALSE;

        // Builds the where clause
        $whereClause = $this->buildDefautWhereClause($fieldConfiguration);

        // Adds the additional configuration WHERE clause
        $whereClause .= ($fieldConfiguration['whereselect'] ? ' AND ' . $fieldConfiguration['whereselect'] : '');

        // Processes the tags
        $whereClause = $this->processWhereClauseTags($whereClause);
        $whereClause = $this->parseLocalizationTags($whereClause);
        $whereClause = $this->parseFieldTags($whereClause);

        // Prepares the query configuration
        $this->queryConfiguration = array(
            'mainTable' => $fieldConfiguration['foreign_table'],
            'aliases' => $fieldConfiguration['aliasselect'],
            'foreignTables' => ($fieldConfiguration['additionaljointableselect'] ? ' ' . $fieldConfiguration['additionaljointableselect'] : '') . ($fieldConfiguration['additionaltableselect'] ? ',' . $fieldConfiguration['additionaltableselect'] : ''),
            'whereClause' => $whereClause . ' AND ' . $fieldConfiguration['foreign_table'] . '.uid = ' . intval($fieldConfiguration['value']),
            'groupByClause' => $fieldConfiguration['groupbyselect'],
            'orderByClause' => $fieldConfiguration['orderselect']
        );
    }

    /**
     * Builds a query configuration for a many-to-many relation
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return none
     */
    public function buildQueryConfigurationForTrueManyToManyRelation(&$fieldConfiguration)
    {

        // Builds the where clause
        $whereClause = $this->buildDefautWhereClause($fieldConfiguration);

        // Adds the additional configuration WHERE clause
        $whereClause .= ($fieldConfiguration['whereselect'] ? ' AND ' . $fieldConfiguration['whereselect'] : '');

        // Processes the tags
        $whereClause = $this->processWhereClauseTags($whereClause);
        $whereClause = $this->parseLocalizationTags($whereClause);
        $whereClause = $this->parseFieldTags($whereClause);

        if (empty($fieldConfiguration['uidLocal'])) {
            $this->doNotProcessQuery = TRUE;
        }

        // Prepares the query configuration
        $this->queryConfiguration = array(
            'mainTable' => $fieldConfiguration['foreign_table'],
            'aliases' => $fieldConfiguration['aliasselect'],
            'foreignTables' => ',' . $fieldConfiguration['MM'] . ($fieldConfiguration['additionaljointableselect'] ? ' ' . $fieldConfiguration['additionaljointableselect'] : '') . ($fieldConfiguration['additionaltableselect'] ? ',' . $fieldConfiguration['additionaltableselect'] : ''),
            'whereClause' => $whereClause . ' AND ' . $fieldConfiguration['MM'] . '.uid_foreign = ' . $fieldConfiguration['foreign_table'] . '.uid' . ' AND ' . $fieldConfiguration['MM'] . '.uid_local = ' . $fieldConfiguration['uidLocal'] . (empty($fieldConfiguration['uidForeign']) ? '' : ' AND ' . $fieldConfiguration['MM'] . '.uid_foreign = ' . $fieldConfiguration['uidForeign']),
            'groupByClause' => $fieldConfiguration['groupbyselect'],
            'orderByClause' => $fieldConfiguration['orderselect'] ? $fieldConfiguration['orderselect'] : $fieldConfiguration['MM'] . '.sorting',
            'limitClause' => ($fieldConfiguration['maxsubformitems'] ? ($fieldConfiguration['maxsubformitems'] * $fieldConfiguration['pageInSubform']) . ',' . ($fieldConfiguration['maxsubformitems']) : '')
        );
    }

    /**
     * Builds a query configuration for a subform with no relation (subforms are based on a many-to-many relation by default)
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return none
     */
    public function buildQueryConfigurationForSubformWithNoRelation(&$fieldConfiguration)
    {
        $this->doNotProcessQuery = FALSE;

        // Builds the where clause
        $whereClause = $this->buildDefautWhereClause($fieldConfiguration);

        // Adds the additional configuration WHERE clause
        $whereClause .= ($fieldConfiguration['whereselect'] ? ' AND ' . $fieldConfiguration['whereselect'] : '');

        // Processes the tags
        $whereClause = $this->processWhereClauseTags($whereClause);
        $whereClause = $this->parseLocalizationTags($whereClause);
        $whereClause = $this->parseFieldTags($whereClause);

        // Prepares the query configuration
        $this->queryConfiguration = array(
            'mainTable' => $fieldConfiguration['foreign_table'],
            'aliases' => $fieldConfiguration['aliasselect'],
            'whereClause' => $whereClause,
            'groupByClause' => $fieldConfiguration['groupbyselect'],
            'orderByClause' => $fieldConfiguration['orderselect'] ? $fieldConfiguration['orderselect'] : '',
            'limitClause' => ($fieldConfiguration['maxsubformitems'] ? ($fieldConfiguration['maxsubformitems'] * $fieldConfiguration['pageInSubform']) . ',' . ($fieldConfiguration['maxsubformitems']) : '')
        );
    }

    /**
     * Builds a query configuration for a comma-list many-to-many relation
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return none
     */
    public function buildQueryConfigurationForCommaListManyToManyRelation(&$fieldConfiguration)
    {
        $this->doNotProcessQuery = FALSE;

        // Builds the where clause
        $whereClause = $this->buildDefautWhereClause($fieldConfiguration);

        // Adds the additional configuration WHERE clause
        $whereClause .= ($fieldConfiguration['whereselect'] ? ' AND ' . $fieldConfiguration['whereselect'] : '');

        // Processes the tags
        $whereClause = $this->processWhereClauseTags($whereClause);
        $whereClause = $this->parseLocalizationTags($whereClause);
        $whereClause = $this->parseFieldTags($whereClause);

        // Prepares the query configuration
        $this->queryConfiguration = array(
            'mainTable' => $fieldConfiguration['foreign_table'],
            'aliases' => $fieldConfiguration['aliasselect'],
            'foreignTables' => ($fieldConfiguration['additionaljointableselect'] ? ' ' . $fieldConfiguration['additionaljointableselect'] : '') . ($fieldConfiguration['additionaltableselect'] ? ',' . $fieldConfiguration['additionaltableselect'] : ''),
            'whereClause' => $whereClause . ' AND (FIND_IN_SET(' . $fieldConfiguration['foreign_table'] . '.uid, \'' . $fieldConfiguration['value'] . '\')>0)',
            'groupByClause' => $fieldConfiguration['groupbyselect'],
            'orderByClause' => $fieldConfiguration['orderselect']
        );
    }

    /**
     * Builds a query configuration for a foreign table
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return none
     */
    public function buildQueryConfigurationForForeignTable(&$fieldConfiguration)
    {
        $this->doNotProcessQuery = FALSE;

        // Builds the where clause
        $whereClause = $this->buildDefautWhereClause($fieldConfiguration);

        // Processes the "foreign_table_where" field configuration
        preg_match('/^(?P<whereClause>.*?) ORDER BY (?P<orderByClause>.*)$/', $fieldConfiguration['foreign_table_where'], $match);

        // Adds the additional configuration WHERE clause
        $whereClause .= ($fieldConfiguration['whereselect'] ? ' AND ' . $fieldConfiguration['whereselect'] : ' ' . $match['whereClause']);

        // Processes the tags
        $whereClause = $this->processWhereClauseTags($whereClause);
        $whereClause = $this->parseLocalizationTags($whereClause);
        $whereClause = $this->parseFieldTags($whereClause);

        // Builds the ORDER BY clause
        $orderByClause = ($fieldConfiguration['orderselect'] ? $fieldConfiguration['orderselect'] : $match['orderByClause']);

        // Prepares the query configuration
        $this->queryConfiguration = array(
            'mainTable' => $fieldConfiguration['foreign_table'],
            'selectClause' => $fieldConfiguration['selectclause'],
            'aliases' => $fieldConfiguration['aliasselect'],
            'foreignTables' => ($fieldConfiguration['additionaljointableselect'] ? ' ' . $fieldConfiguration['additionaljointableselect'] : '') . ($fieldConfiguration['additionaltableselect'] ? ',' . $fieldConfiguration['additionaltableselect'] : ''),
            'whereClause' => $whereClause,
            'groupByClause' => $fieldConfiguration['groupbyselect'],
            'orderByClause' => $orderByClause
        );
    }
}
?>
