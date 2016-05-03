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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use SAV\SavLibraryPlus\Controller\FlashMessages;
use SAV\SavLibraryPlus\Managers\TcaConfigurationManager;
use SAV\SavLibraryPlus\Managers\UriManager;
use SAV\SavLibraryPlus\Managers\QueryConfigurationManager;

/**
 * Abstract Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
abstract class AbstractQuerier
{

    /**
     * The controller
     *
     * @var \SAV\SavLibraryPlus\Controller\Controller
     */
    private $controller;

    /**
     * The query configuration manager
     *
     * @var \SAV\SavLibraryPlus\Managers\QueryConfigurationManager
     */
    protected $queryConfigurationManager;

    /**
     * The query resource
     *
     * @var resource
     */
    protected $resource;

    /**
     * The array of field objects
     *
     * @var array
     */
    protected $fieldObjects = array();

    /**
     * The array of localized tables
     *
     * @var array
     */
    protected $localizedTables = array();

    /**
     * The rows
     *
     * @var array
     */
    protected $rows;

    /**
     * The total rows count, i.e.
     * without the limit clause
     *
     * @var array
     */
    private $totalRowsCount = 1;

    /**
     * The curent row id
     *
     * @var integer
     */
    protected $currentRowId = 0;

    /**
     * The query parameters
     *
     * @var array
     */
    protected $queryParameters = array();

    /**
     * The query configuration
     *
     * @var array
     */
    protected $queryConfiguration = NULL;

    /**
     * The parent querier
     *
     * @var \SAV\SavLibraryPlus\Queriers\AbstractQuerier
     */
    protected $parentQuerier = NULL;

    /**
     * The update querier
     *
     * @var \SAV\SavLibraryPlus\Queriers\UpdateQuerier
     */
    protected $updateQuerier = NULL;

    /**
     * The pages to clear
     *
     * @var array
     */
    protected $pageIdentifiersToClearInCache = array();

    /**
     * Additional Markers
     *
     * @var array
     */
    protected $additionalMarkers = array();

    /**
     * Constructor
     *
     * @return none
     */
    public function __construct()
    {
        // Creates the query configuration manager
        $this->queryConfigurationManager = GeneralUtility::makeInstance(QueryConfigurationManager::class);
    }

    /**
     * Injects the controller
     *
     * @param \SAV\SavLibraryPlus\Controller\AbstractController $controller
     *            The controller
     *
     * @return none
     */
    public function injectController($controller)
    {
        $this->controller = $controller;
        $this->queryConfigurationManager->injectController($controller);
        if ($controller->getQuerier() !== $this) {
            $this->parentQuerier = $controller->getQuerier();
        }
    }

    /**
     * Injects the query configuration
     *
     * @return none
     */
    public function injectQueryConfiguration()
    {
        if ($this->queryConfiguration === NULL) {
            // Sets the query configuration manager
            $libraryConfigurationManager = $this->getController()->getLibraryConfigurationManager();
            $this->queryConfiguration = $libraryConfigurationManager->getQueryConfiguration();
        }

        // Injects the query configuration
        $this->queryConfigurationManager->injectQueryConfiguration($this->queryConfiguration);
    }

    /**
     * Injects the parent querier
     *
     * @param \SAV\SavLibraryPlus\Queriers\AbstractQuerier $parentQuerier
     *
     * @return none
     */
    public function injectParentQuerier($parentQuerier)
    {
        $this->parentQuerier = $parentQuerier;
    }

    /**
     * Injects the update querier
     *
     * @param \SAV\SavLibraryPlus\Queriers\UpdateQuerier $updateQuerier
     *
     * @return none
     */
    public function injectUpdateQuerier($updateQuerier)
    {
        $this->updateQuerier = $updateQuerier;
    }

    /**
     * Injects additional markers
     *
     * @param array $additionalMarkers
     *
     * @return none
     */
    public function injectAdditionalMarkers($additionalMarkers)
    {
        $this->additionalMarkers = array_merge($this->additionalMarkers, $additionalMarkers);
    }

    /**
     * Gets additional markers
     *
     * @return array
     */
    public function getAdditionalMarkers()
    {
        return $this->additionalMarkers;
    }

    /**
     * Processes the query
     *
     * @return none
     */
    public function processQuery()
    {
        if ($this->executeQuery() === FALSE) {
            return FALSE;
        }
        // Clear pages cache if needed
        $this->clearPagesCache();
        return TRUE;
    }

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {}

    /**
     * Clears the pages cache if needed
     *
     * @return none
     */
    protected function clearPagesCache()
    {
        // if the plugin type is not USER, the cache has not to be cleared
        if (ExtensionConfigurationManager::isUserPlugin() === FALSE) {
            return;
        }

        // If the page identifiers list is empty, just returns
        if (empty($this->pageIdentifiersToClearInCache)) {
            return;
        }

        // Deletes the pages in the cache
        $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN (' . implode(',', $this->pageIdentifiersToClearInCache) . ')');
    }

    /**
     * Sets the current row identifier
     *
     * @param integer $rowId
     *            The row identifier
     *
     * @return none
     */
    public function setCurrentRowId($rowId)
    {
        $this->currentRowId = $rowId;
    }

    /**
     * Gets the current row identifier
     *
     * @return integer
     */
    public function getCurrentRowId()
    {
        return $this->currentRowId;
    }

    /**
     * Gets the rows
     *
     * @return array The rows
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Adds an empty row
     *
     * @return none
     */
    public function addEmptyRow()
    {
        $this->rows[0] = array();
    }

    /**
     * Gets the rows count
     *
     * @return integer The rows count
     */
    public function getRowsCount()
    {
        return count($this->rows);
    }

    /**
     * Checks if the rows are not empty
     *
     * @return boolean
     */
    public function rowsNotEmpty()
    {
        return ! empty($this->rows) && ! empty($this->rows[0]);
    }

    /**
     * Gets the total rows count, i.e.
     * without the limit clause
     *
     * @return integer The rows count
     */
    public function getTotalRowsCount()
    {
        return $this->totalRowsCount;
    }

    /**
     * Sets the total rows count
     *
     * @param integer $totalRowsCount
     *            The total rows count
     *
     * @return none
     */
    public function setTotalRowsCount($totalRowsCount)
    {
        $this->totalRowsCount = $totalRowsCount;
    }

    /**
     * Gets the value of a field in the current row
     *
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public function getFieldValueFromCurrentRow($fieldName)
    {
        return $this->rows[$this->currentRowId][$fieldName];
    }

    /**
     * Sets the value of a field in the current row
     *
     * @param string $fieldName
     *            The field name
     * @param mixed $value
     *            The value
     *
     * @return mixed
     */
    protected function setFieldValueFromCurrentRow($fieldName, $value)
    {
        $this->rows[$this->currentRowId][$fieldName] = $value;
    }

    /**
     * Gets the value of a field in the row if the table is the main table else it is search in the parent rows
     *
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public function getFieldValue($fieldName)
    {
        // Gets the querier where the field exists, if it exists
        $querier = $this;

        while (! $querier->fieldExistsInCurrentRow($fieldName) && $querier->parentQuerier !== NULL) {
            $querier = $querier->getParentQuerier();
        }
        return $querier->getFieldValueFromCurrentRow($fieldName);
    }

    /**
     * Checks if a field exists in the current row
     *
     * @param string $fieldName
     *            The field name
     *
     * @return boolean
     */
    public function fieldExistsInCurrentRow($fieldName)
    {
        if (is_array($this->rows[$this->currentRowId])) {
            return array_key_exists($fieldName, $this->rows[$this->currentRowId]);
        } else {
            return FALSE;
        }
    }

    /**
     * Checks if a field exists in the row if the table is the main table else it is search in the parent rows
     *
     * @param string $fieldName
     *            The field name
     *
     * @return boolean
     */
    public function fieldExists($fieldName)
    {
        // Gets the querier where the field exists, if it exists
        $querier = $this;
        while (! $querier->fieldExistsInCurrentRow($fieldName) && $querier->parentQuerier !== NULL) {
            $querier = $querier->getParentQuerier();
        }
        return $querier->fieldExistsInCurrentRow($fieldName);
    }

    /**
     * Builds the full field name
     *
     * @param string $fieldName
     *            The field name
     *
     * @return string
     */
    public function buildFullFieldName($fieldName)
    {
        $fieldNameParts = explode('.', $fieldName);
        if (count($fieldNameParts) == 1) {
            // The main table is assumed by default
            $fieldName = $this->getQueryConfigurationManager()->getMainTable() . '.' . $fieldName;
        }
        return $fieldName;
    }

    /**
     * Gets the controller
     *
     * @return \SAV\SavLibraryPlus\Controller\AbstractController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Gets the parent querier
     *
     * @return \SAV\SavLibraryPlus\Queriers\AbstractQuerier
     */
    public function getParentQuerier()
    {
        return $this->parentQuerier;
    }

    /**
     * Gets the update querier
     *
     * @return \SAV\SavLibraryPlus\Queriers\UpdateQuerier
     */
    public function getUpdateQuerier()
    {
        return $this->updateQuerier;
    }

    /**
     * Checks if the was at leat one error during the update.
     *
     * @return boolean
     */
    public function errorDuringUpdate()
    {
        $updateQuerier = $this->getUpdateQuerier();
        if ($updateQuerier !== NULL) {
            return $updateQuerier->errorDuringUpdate();
        } else {
            return FALSE;
        }
    }

    /**
     * Gets the value content from the POST variable after processing by the update querier.
     * It is called when an error occurs in order to retrieve the user's inputs.
     *
     * @param string $fullFieldName
     *
     * @return mixed
     */
    public function getFieldValueFromProcessedPostVariables($fullFieldName)
    {
        $uid = $this->getFieldValueFromCurrentRow(preg_replace('/\.\w+$/', '.uid', $fullFieldName));
        if ($this->getUpdateQuerier()->isNewRecord()) {
            $uid = 0;
        }
        $processedPostVariable = $this->getUpdateQuerier()->getProcessedPostVariable($fullFieldName, $uid);
        $value = $processedPostVariable['value'];
        return $value;
    }

    /**
     * Gets the error code from the POST variable after processing by the update querier.
     * It is called when an error occurs in order to retrieve the user's inputs.
     *
     * @param string $fullFieldName
     *
     * @return integer
     */
    public function getFieldErrorCodeFromProcessedPostVariables($fullFieldName)
    {
        $uid = $this->getFieldValueFromCurrentRow(preg_replace('/\.\w+$/', '.uid', $fullFieldName));
        if ($this->getUpdateQuerier()->isNewRecord()) {
            $uid = 0;
        }
        $processedPostVariable = $this->getUpdateQuerier()->getProcessedPostVariable($fullFieldName, $uid);
        $errorCode = $processedPostVariable['errorCode'];
        return $errorCode;
    }

    /**
     * Gets the query configuration manager
     *
     * @return \SAV\SavLibraryPlus\Managers\QueryConfigurationManager
     */
    public function getQueryConfigurationManager()
    {
        return $this->queryConfigurationManager;
    }

    /**
     * Gets a query parameter.
     *
     * @param string $parameterName
     *            The parameter name
     *
     * @return string
     */
    protected function getQueryParameter($parameterName)
    {
        return $this->queryParameters[$parameterName];
    }

    /**
     * Builds the SELECT clause
     *
     * @return string
     */
    protected function buildSelectClause()
    {
        $selectClause = $this->queryConfigurationManager->getSelectClause();
        $aliases = $this->queryConfigurationManager->getAliases();
        $selectClause .= ($aliases ? ', ' . $aliases : '');
        $selectClause = $this->processWhereClauseTags($selectClause);
        $selectClause = $this->parseLocalizationTags($selectClause);
        $selectClause = $this->parseFieldTags($selectClause);

        return $selectClause;
    }

    /**
     * Builds the FROM clause
     *
     * @return string
     */
    protected function buildFromClause()
    {
        // Gets the main table
        $fromClause = $this->queryConfigurationManager->getMainTable();

        // Adds the foreign table
        // Checks that the 'tableForeign' start either by LEFT JOIN, INNER JOIN or RIGHT JOIN or a comma
        $foreignTables = $this->getQueryConfigurationManager()->getForeignTables();
        if (empty($foreignTables) === FALSE) {
            $foreignTables = $this->parseFieldTags($foreignTables);
            if (! preg_match('/^[\s]*(?i)(,|inner join|left join|right join)\s?([^\s]*)/', $foreignTables, $match)) {
                FlashMessages::addError('error.incorrectQueryForeignTable');
            } else {
                $fromClause = '(' . $fromClause . ') ' . $foreignTables;
            }
        }

        return $fromClause;
    }

    /**
     * Builds the WHERE clause
     *
     * @return string
     */
    protected function buildWhereClause()
    {
        $whereClause = $this->queryConfigurationManager->getWhereClause();
        $whereClause = $this->parseLocalizationTags($whereClause);
        $whereClause = $this->parseFieldTags($whereClause);
        return $whereClause;
    }

    /**
     * Builds the GROUP BY clause
     *
     * @return string
     */
    protected function buildGroupByClause()
    {
        return $this->queryConfigurationManager->getGroupByClause();
    }

    /**
     * Builds the ORDER BY clause
     *
     * @return string
     */
    protected function buildOrderByClause()
    {
        return $this->queryConfigurationManager->getOrderByClause();
    }

    /**
     * Builds the LIMIT clause
     *
     * @return string
     */
    protected function buildLimitClause()
    {
        return $this->queryConfigurationManager->getLimitClause();
    }

    /**
     * Deletes a record in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uid
     *            uid of the record to delete
     * @param string $whereField
     *            The where field - default uid_local
     *
     * @return none
     */
    protected function deleteRecordsInRelationManyToMany($tableName, $uid, $whereField = 'uid_local')
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
      /* TABLE   */	$tableName,
      /* WHERE   */	$tableName . '.' . $whereField . '=' . intval($uid));
    }

    /**
     * Inserts fields in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param array $fields
     *            Fields to insert
     *
     * @return none
     */
    protected function insertFieldsInRelationManyToMany($tableName, $fields)
    {
        // Inserts the fields
        $this->resource = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
      /* TABLE   */	$tableName,
  		/* FIELDS  */	$fields);
    }

    /**
     * Gets the row in a MM table
     *
     * @param $tableName string
     *            Table name
     * @param $uidLocal integer
     *            uid of the record in the source table
     * @param $uidInteger integer
     *            uid of the record in the foreign table
     *
     * @return none
     */
    protected function getRowInRelationManyToMany($tableName, $uidLocal, $uidForeign)
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	'*',
			/* FROM     */	$tableName,
 			/* WHERE    */	'uid_local = ' . $uidLocal . ' AND uid_foreign = ' . $uidForeign);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource);
        return $row;
    }

    /**
     * Gets the uid_foreign in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uidLocal
     * @param integer $sorting
     *
     * @return integer
     */
    protected function getUidForeignInRelationManyToMany($tableName, $uidLocal, $sorting)
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	'uid_foreign',
			/* FROM     */	$tableName,
 			/* WHERE    */	'uid_local = ' . $uidLocal . ' AND sorting = ' . $sorting);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource);
        return $row['uid_foreign'];
    }

    /**
     * Gets the records count in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uidLocal
     *
     * @return none
     */
    protected function getRowsCountInRelationManyToMany($tableName, $uidLocal)
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	'count(*) as recordsCount, max(sorting) as maxSorting',
			/* FROM     */	$tableName,
 			/* WHERE    */	'uid_local = ' . $uidLocal);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource);

        // Reorders the sorting field if needed
        if ($row['recordsCount'] != $row['maxSorting']) {
            $this->reorderSortingInRelationManyToMany($tableName, $uidLocal);
        }
        return intval($row['recordsCount']);
    }

    /**
     * Gets the sorting field in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uidLocal
     *
     * @return none
     */
    protected function updateSortingInRelationManyToMany($tableName, $uidLocal, $uidForeign, $sorting)
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			/* TABLE   */	$tableName,
 			/* WHERE   */	'uid_local=' . $uidLocal . ' AND uid_foreign=' . $uidForeign,
			/* FIELDS  */	array(
            'sorting' => $sorting
        ));
    }

    /**
     * Reorders the sorting field in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uid
     *            uid of the record to delete
     *
     * @return none
     */
    protected function reorderSortingInRelationManyToMany($tableName, $uidLocal)
    {
        if (! empty($uidLocal)) {
            $query = 'UPDATE ' . $tableName . ', (SELECT @counter:=0) AS initCount SET sorting = (@counter:=@counter+1) WHERE ' . $tableName . '.uid_local=' . intval($uidLocal);
            $this->resource = $GLOBALS['TYPO3_DB']->sql_query($query);
        }
    }

    /**
     * Sets the deleted field in a table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uid
     *            uid of the record to delete
     *
     * @return none
     */
    protected function setDeletedField($tableName, $uid)
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			/* TABLE   */	$tableName,
 			/* WHERE   */	$tableName . '.uid=' . intval($uid),
			/* FIELDS  */	array(
            'deleted' => 1
        ));

        $this->addToPageIdentifiersToClearInCache($tableName, $uid);
    }

    /**
     * Updates a record in a table
     *
     * @param string $tableName
     *            Table name
     * @param array $fields
     *            Fields to update
     * @param integer $uid
     *            uid of the record to update
     *
     * @return none
     */
    protected function updateFields($tableName, $fields, $uid)
    {
        if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] && ! array_key_exists('tstamp', $fields)) {
            $fields = array_merge($fields, array(
                $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] => time()
            ));
        }

        $this->resource = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
      /* TABLE   */	$tableName,
      /* WHERE   */	$tableName . '.uid=' . intval($uid),
      /* FIELDS  */	$fields);

        $this->addToPageIdentifiersToClearInCache($tableName, $uid);
    }

    /**
     * Inserts a record in a table
     *
     * @param string $tableName
     *            Table name
     * @param array $fields
     *            Fields to update
     *
     * @return integer The uid of the inserted record
     */
    protected function insertFields($tableName, $fields)
    {
        // Adds the controls
        if ($GLOBALS['TCA'][$tableName]['ctrl']['cruser_id']) {
            $fields = array_merge($fields, array(
                $GLOBALS['TCA'][$tableName]['ctrl']['cruser_id'] => $GLOBALS['TSFE']->fe_user->user['uid']
            ));
        }
        if ($GLOBALS['TCA'][$tableName]['ctrl']['crdate']) {
            $fields = array_merge($fields, array(
                $GLOBALS['TCA'][$tableName]['ctrl']['crdate'] => time()
            ));
        }
        if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp']) {
            $fields = array_merge($fields, array(
                $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] => time()
            ));
        }

        $this->resource = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
      /* TABLE   */	$tableName,
  		/* FIELDS  */	$fields);

        $uid = $GLOBALS['TYPO3_DB']->sql_insert_id($this->resource);

        $this->addToPageIdentifiersToClearInCache($tableName, $uid);

        return $uid;
    }

    /**
     * Gets the records count in a table
     *
     * @param string $tableName
     *            Table name
     *
     * @return integer
     */
    protected function getRowsCountInTable($tableName)
    {
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	'count(*) as recordsCount',
			/* FROM     */	$tableName,
 			/* WHERE    */	'1 ' . $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject()
            ->enableFields($tableName));
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource);

        return intval($row['recordsCount']);
    }

    /**
     * Adds the pid to the page identifiers to clear in the cache if needed.
     * If the record lies on a page, then we clear the cache of this page.
     * If the record has no PID column, we clear the cache of the current page as best-effort.
     *
     * Much of this code is taken from Tx_Extbase_Persistence_Storage_Typo3DbBackend::clearPageCache .
     *
     * @param string $tableName
     *            Tablename of the record
     * @param integer $uid
     *            UID of the record
     * @return none
     */
    protected function addToPageIdentifiersToClearInCache($tableName, $uid)
    {
        // if the plugin type is not USER, the cache has not to be clerared
        if (ExtensionConfigurationManager::isUserPlugin() === FALSE) {
            return;
        }

        $pageIdsToClear = array();
        $storagePage = NULL;

        $columns = $GLOBALS['TYPO3_DB']->admin_get_fields($tableName);
        if (array_key_exists('pid', $columns)) {
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', $tableName, 'uid=' . intval($uid));
            if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                $storagePage = $row['pid'];
                $this->pageIdentifiersToClearInCache[] = intval($storagePage);
            }
        } elseif (isset($GLOBALS['TSFE'])) {
            // No PID column - we can do a best-effort to clear the cache of the current page if in FE
            $storagePage = $GLOBALS['TSFE']->id;
            $this->pageIdentifiersToClearInCache[] = intval($storagePage);
        }

        // Gets the storage page
        $storagePage = $this->getController()
            ->getExtensionConfigurationManager()
            ->getStoragePage();
        if (empty($storagePage) === FALSE) {
            $this->pageIdentifiersToClearInCache[] = intval($storagePage);
        }
    }

    /**
     * Gets allowed Pages from the starting point and the storage page
     *
     * @param string $tableName
     *            The table name
     *
     * @return string
     */
    public function getAllowedPages($tableName)
    {
        if (empty($tableName)) {
            return '';
        } else {
            // Adds the starting point pages
            $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
            $contentObject = $extensionConfigurationManager->getExtensionContentObject();
            if ($contentObject->data['pages']) {
                $pageListArray = explode(',', $contentObject->data['pages']);
            } else {
                $pageListArray = array();
            }
            // Adds the storage page
            $storagePage = $extensionConfigurationManager->getStoragePage();
            if (empty($storagePage) === FALSE) {
                $pageListArray[] = $storagePage;
            }

            $pageList = implode(',', $pageListArray);

            return ($pageList ? ' AND ' . $tableName . '.pid IN (' . $pageList . ')' : '');
        }
    }

    /**
     * Builds the record localization WHERE condition
     *
     * @param string $tableName
     *            The table name
     *
     * @return string
     */
    public function buildRecordLocalizationCondition()
    {
        $languageUid = $GLOBALS['TSFE']->sys_language_uid;
    }

    /**
     * Parses contant tags
     *
     * @param string $value
     *            (string to process)
     *
     * @return string ()
     */
    public function parseConstantTags($value)
    {
        // Processes constants
        if (preg_match_all('/\$\$\$constant\[([^\]]+)\]\$\$\$/', $value, $matches)) {
            foreach ($matches[1] as $matchKey => $match) {
                if (defined($match)) {
                    $value = str_replace($matches[0][$matchKey], constant($match), $value);
                }
            }
        }
        return $value;
    }

    /**
     * Parses localization tags
     *
     * @param string $value
     *            The string to process
     * @param boolean $reportError
     *            If TRUE report the error associated when the marker is not found
     *
     * @return string
     */
    public function parseLocalizationTags($value, $reportError = TRUE)
    {
        // Checks if the value must be parsed
        if (strpos($value, '$') === FALSE) {
            return $value;
        }

        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Builds the localization prefix
        $localizationPrefix = 'LLL:EXT:' . $extensionKey . '/' . $this->getController()
            ->getLibraryConfigurationManager()
            ->getLanguagePath();

        // Processes labels associated with fields
        if (preg_match_all('/\$\$\$label\[([^\]]+)\]\$\$\$/', $value, $matches)) {
            foreach ($matches[1] as $matchKey => $match) {
                // Checks if the label is in locallang_db.xml, no default table is assumed
                // In that case the full name must be used, i.e. tableName.fieldName
                $label = $GLOBALS['TSFE']->sL($localizationPrefix . 'locallang_db.xml:' . $match);

                if (empty($label) === FALSE) {
                    $value = str_replace($matches[0][$matchKey], $label, $value);
                } else {
                    // Checks if the label is in locallang_db.xml, the main table is assumed
                    $mainTable = $this->getQueryConfigurationManager()->getMainTable();
                    $label = $GLOBALS['TSFE']->sL($localizationPrefix . 'locallang_db.xml:' . $mainTable . '.' . $match);

                    if (empty($label) === FALSE) {
                        // Found in locallang_db.xml file, replaces it
                        $value = str_replace($matches[0][$matchKey], $label, $value);
                    } elseif ($reportError === TRUE) {
                        FlashMessages::addError('error.missingLabel', array(
                            $match
                        ));
                    } else {
                        $value = str_replace($matches[0][$matchKey], $matches[1][$matchKey], $value);
                    }
                }
            }
        }

        // Checks if the label is in the locallang.xml file
        preg_match_all('/\$\$\$([^\$]+)\$\$\$/', $value, $matches);
        foreach ($matches[1] as $matchKey => $match) {
            $label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($match, $extensionKey);
            if (! empty($label)) {
                // Found in locallang.xml file, replaces it
                $value = str_replace($matches[0][$matchKey], $label, $value);
            } elseif ($reportError === TRUE) {
                FlashMessages::addError('error.missingLabel', array(
                    $match
                ));
            } else {
                $value = str_replace($matches[0][$matchKey], $matches[1][$matchKey], $value);
            }
        }

        return $value;
    }

    /**
     * Parses ###field### tags.
     *
     * @param string $value
     *            The string to process
     * @param boolean $reportError
     *            If TRUE report the error associated when the marker is not found
     *
     * @return string
     */
    public function parseFieldTags($value, $reportError = TRUE)
    {
        // Checks if the value must be parsed
        if (strpos($value, '#') === FALSE) {
            return $value;
        }

        // Gets the extension object
        $extension = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtension();

        // Initaializes the markers
        $markers = $this->buildSpecialMarkers();
        $markers = array_merge($markers, $this->additionalMarkers);

        // Processes special tags
        $markers['###linkToPage###'] = str_replace('<a href="', '<a href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), $extension->pi_linkToPage('', $GLOBALS['TSFE']->id));
        // Compatiblity with SAV Library
        $value = preg_replace('/###row\[([^\]]+)\]###/', '###$1###', $value);

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();

        // Gets the tags
        preg_match_all('/###(?:(?P<render>render\[)|special\[)?(?P<fullFieldName>(?<TableNameOrAlias>[^\.\:#\]]+)\.?(?<fieldName>[^#\:\]]*))(?:\:(?<configuration>[^#\]]+))?\]?###/', $value, $matches);

        foreach ($matches['fullFieldName'] as $matchKey => $match) {
            $fullFieldName = NULL;
            if (array_key_exists($matches[0][$matchKey], $markers) && ($matches[0][$matchKey] != '###uid###' || ($this->getController()->getQuerier() instanceof \SAV\SavLibraryPlus\Queriers\UpdateQuerier))) {
                // Already in the markers array
                continue;
            } elseif ($matches['fieldName'][$matchKey]) {
                if ($this->fieldExists($matches['fullFieldName'][$matchKey])) {
                    // It's a full field name, i.e. tableName.fieldName
                    $fullFieldName = $matches['fullFieldName'][$matchKey];
                }
            } else {
                if ($this->fieldExists($matches['TableNameOrAlias'][$matchKey])) {
                    // It's an alias
                    $fullFieldName = $matches['TableNameOrAlias'][$matchKey];
                } elseif ($this->fieldExists($mainTable . '.' . $matches['TableNameOrAlias'][$matchKey])) {
                    // The main table was omitted
                    $fullFieldName = $mainTable . '.' . $matches['TableNameOrAlias'][$matchKey];
                } elseif ($matches['TableNameOrAlias'][$matchKey] == 'user') {
                    $markers[$matches[0][$matchKey]] = $GLOBALS['TSFE']->fe_user->user['uid'];
                    continue;
                }
            }

            // Special Processing when the full field name is not found
            if ($fullFieldName === NULL) {
                if ($this->getController()->getViewer() instanceof \SAV\SavLibraryPlus\Viewers\NewViewer || ($this->getController()->getViewer() instanceof \SAV\SavLibraryPlus\Viewers\SubformEditViewer && $this->getController()
                    ->getViewer()
                    ->isNewView())) {
                    // In new view, it may occur that markers are used, in reqValue for example. The markers are replaced by 0.
                    $markers[$matches[0][$matchKey]] = '0';
                    continue;
                } elseif ($this->getController()->getQuerier() instanceof \SAV\SavLibraryPlus\Queriers\UpdateQuerier) {
                    // In an update, it may occur that markers are used, in reqValue for example.
                    $fullFieldName = $this->getController()
                        ->getQuerier()
                        ->buildFullFieldname($matches['fullFieldName'][$matchKey]);
                    $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);
                    if ($this->getController()
                        ->getQuerier()
                        ->fieldExistsInPostVariable($cryptedFullFieldName)) {
                        // Replaces the marker by the current value in the post variable
                        $markers[$matches[0][$matchKey]] = $this->getController()
                            ->getQuerier()
                            ->getPostVariable($cryptedFullFieldName);
                    } else {
                        // Replaces the marker by 0
                        $markers[$matches[0][$matchKey]] = '0';
                    }
                    continue;
                } elseif ($reportError === TRUE) {
                    // Unknown marker
                    FlashMessages::addError('error.unknownMarker', array(
                        $matches[0][$matchKey]
                    ));
                    continue;
                } else {
                    // Error is not reported and the value is unchanged
                    $markers[$matches[0][$matchKey]] = $matches[0][$matchKey];
                    continue;
                }
            }

            // Sets the marker either by rendering the field from the single view configuration or directly from the database
            if ($matches['render'][$matchKey]) {
                // Renders the field based on the TCA configuration as it would be rendered in a single view
                $fieldKey = AbstractController::cryptTag($fullFieldName);
                $basicFieldConfiguration = $this->getController()
                    ->getLibraryConfigurationManager()
                    ->searchBasicFieldConfiguration($fieldKey);
                $fieldConfiguration = TcaConfigurationManager::getTcaConfigFieldFromFullFieldName($fullFieldName);

                // Adds the basic configuration if found
                if (is_array($basicFieldConfiguration)) {
                    $fieldConfiguration = array_merge($fieldConfiguration, $basicFieldConfiguration);
                }

                // Adds the configuration from the pattern if any
                if (preg_match_all('/([^=]+)=([^;]+);?/', $matches['configuration'][$matchKey], $configurations)) {
                    foreach ($configurations[0] as $configurationKey => $configuration) {
                        $fieldConfiguration = array_merge($fieldConfiguration, array(
                            trim(strtolower($configurations[1][$configurationKey])) => trim($configurations[2][$configurationKey])
                        ));
                    }
                }

                // Adds the value from the current row
                $fieldConfiguration['value'] = $this->getFieldValue($fullFieldName);

                // Calls the item viewer
                $className = 'SAV\\SavLibraryPlus\\ItemViewers\\General\\' . $fieldConfiguration['fieldType'] . 'ItemViewer';
                $itemViewer = GeneralUtility::makeInstance($className);
                $itemViewer->injectController($this->getController());
                $itemViewer->injectItemConfiguration($fieldConfiguration);
                $markers[$matches[0][$matchKey]] = $itemViewer->render();
            } else {
                $markers[$matches[0][$matchKey]] = $this->getFieldValue($fullFieldName);
            }
        }

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        return $contentObject->substituteMarkerArrayCached($value, $markers, array(), array());
    }

    /**
     * Processes tags in where clause.
     *
     * @param string $whereClause
     *            The string to process
     *
     * @return string
     */
    public function processWhereClauseTags($whereClause)
    {
        // Checks if the value must be parsed
        if (strpos($whereClause, '#') === FALSE) {
            return $whereClause;
        }

        // Initaializes the markers
        $markers = $this->buildSpecialMarkers();

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        // Replaces the special markers
        $whereClause = $contentObject->substituteMarkerArrayCached($whereClause, $markers, array(), array());

        // Processes the ###group_list### tag
        if (preg_match_all('/###group_list\s*([!]?)=([^#]*)###/', $whereClause, $matches)) {

            foreach ($matches[2] as $matchKey => $match) {
                $groups = explode(',', str_replace(' ', '', $match));
                $clause = '';

                // Gets the content object
                $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
                $contentObject = $extensionConfigurationManager->getExtensionContentObject();

                // Gets the group list of uid
                $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				    /* SELECT   */	'uid,title',
				    /* FROM     */	'fe_groups',
	 			    /* WHERE    */	'1' . $contentObject->enableFields('fe_groups'));

                while ($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource)) {
                    if (in_array($rows['title'], $groups)) {
                        if ($matches[1][$matchKey] == '!') {
                            $clause .= ' AND find_in_set(' . $rows['uid'] . ', fe_users.usergroup)=0';
                        } else {
                            $clause .= ' OR find_in_set(' . $rows['uid'] . ', fe_users.usergroup)>0';
                        }
                    }
                }

                // Replaces the tag
                if ($matches[1][$matchKey] == '!') {
                    $whereClause = preg_replace('/###group_list\s*!=([^#]*)###/', '(1' . $clause . ')', $whereClause);
                } else {
                    $whereClause = preg_replace('/###group_list\s*=([^#]*)###/', '(0' . $clause . ')', $whereClause);
                }
            }
        }

        // Processes conditionnal part
        if (preg_match_all('/###([^:]+):([^#]+)###/', $whereClause, $matches)) {

            foreach ($matches[1] as $matchKey => $match) {
                $replace = '1';
                preg_match('/([^\(]+)(?:\(([^\)]*)\)){0,1}/', $match, $matchFunctions);

                $conditionFunction = $matchFunctions[1];
                if ($conditionFunction && method_exists('\SAV\SavLibraryPlus\Utility\Conditions', $conditionFunction)) {
                    // Checks if there is one parameter
                    if ($matchFunctions[2]) {
                        if (\SAV\SavLibraryPlus\Utility\Conditions::$conditionFunction($matchFunctions[2])) {
                            $replace .= ' AND ' . $matches[2][$matchKey];
                        }
                    } else {
                        if (\SAV\SavLibraryPlus\Utility\Conditions::$conditionFunction()) {
                            $replace .= ' AND ' . $matches[2][$matchKey];
                        }
                    }
                } else {
                    FlashMessages::addError('error.unknownFunctionInWhere', array(
                        $matchFunc[1]
                    ));
                }

                $whereClause = preg_replace('/###[^:]+:[^#]+###/', $replace, $whereClause);
            }
        }

        return $whereClause;
    }

    /**
     * Builds special markers
     *
     * @return array
     */
    protected function buildSpecialMarkers()
    {
        // ###uid### marker
        $markers['###uid###'] = (is_object($this->getController()->getViewer()) && $this->getController()
            ->getViewer()
            ->getViewType() == 'ListView' ? $this->getFieldValueFromCurrentRow('uid') : UriManager::getUid());

        // ###uidMainTable
        $markers['###uidMainTable###'] = $markers['###uid###'];

        // ###user### marker
        $markers['###user###'] = $GLOBALS['TSFE']->fe_user->user['uid'];

        // ###STORAGE_PID### marker
        if (version_compare(TYPO3_version, '7.0', '<')) {
            // Deprecated since TYPO3 CMS 7, removed in TYPO3 CMS 8
            $storageSiterootPids = $GLOBALS['TSFE']->getStorageSiterootPids();
            $markers['###STORAGE_PID###'] = $storageSiterootPids['_STORAGE_PID'];
        }

        // ###CURRENT_PID### marker
        $markers['###CURRENT_PID###'] = $GLOBALS['TSFE']->page['uid'];

        // ###SITEROOT### marker
        $markers['###SITEROOT###'] = $GLOBALS['TSFE']->rootLine[0]['uid'];

        return $markers;
    }

    /**
     * Check if a quey is a SELECT query
     */
    public function isSelectQuery($query)
    {
        return preg_match('/^[ \r\t\n]*(?i)select\s*/', $query);
    }

    /**
     * Sets the rows
     *
     * @return none
     */
    protected function setRows()
    {
        $counter = 0;
        $this->rows = array();
        $tablesForOverlay = array();
        while ($row = $this->getRowWithFullFieldNames($counter ++)) {
            foreach ($row as $tableName => $fields) {
                if (empty($tableName)) {
                    $this->rows[] = $fields;
                } else {
                    $tablesForOverlay[$tableName][] = $fields;
                }
            }
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($this->resource);

        // Processes the tables which must be overlayed
        foreach ($tablesForOverlay as $tableKey => $rows) {
            $overlayedRows = $this->doLanguageAndWorkspaceOverlay($tableKey, $rows);
            foreach ($overlayedRows as $rowKey => $row) {
                foreach ($row as $fieldKey => $field) {
                    $this->rows[$rowKey][$tableKey . '.' . $fieldKey] = $field;
                }
            }
        }
    }

    /**
     * Function adapted from Tx_Extbase_Persistence_Storage_Typo3DbBackend
     *
     * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
     * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
     *
     * @param string $tableName
     *            The tableName)
     * @param array $row
     *            The row array (as reference)
     * @param string $languageUid
     *            The language id
     * @param string $workspaceUidUid
     *            The workspace id
     * @return void
     */
    protected function doLanguageAndWorkspaceOverlay($tableName, array &$rows, $languageUid = NULL, $workspaceUid = NULL)
    {
        $overlayedRows = array();
        foreach ($rows as $row) {
            if (! ($pageSelectObject instanceof \TYPO3\CMS\Frontend\Page\PageRepository)) {
                if (TYPO3_MODE == 'FE') {
                    if (is_object($GLOBALS['TSFE'])) {
                        $pageSelectObject = $GLOBALS['TSFE']->sys_page;
                    } else {
                        $pageSelectObject = GeneralUtility::makeInstance(PageRepository::class);
                    }
                } else {
                    $pageSelectObject = GeneralUtility::makeInstance(\PageRepository::class);
                }
            }
            if (is_object($GLOBALS['TSFE'])) {
                if ($languageUid === NULL) {
                    $languageUid = $GLOBALS['TSFE']->sys_language_uid;
                    $languageMode = $GLOBALS['TSFE']->sys_language_mode;
                }
                if ($workspaceUid !== NULL) {
                    $pageSelectObject->versioningWorkspaceId = $workspaceUid;
                }
            } else {
                if ($languageUid === NULL) {
                    $languageUid = intval(GeneralUtility::_GP('L'));
                }
                if ($workspaceUid === NULL) {
                    $workspaceUid = $GLOBALS['BE_USER']->workspace;
                }
                $pageSelectObject->versioningWorkspaceId = $workspaceUid;
            }
            $pageSelectObject->versionOL($tableName, $row, TRUE);
            if ($tableName == 'pages') {
                $row = $pageSelectObject->getPageOverlay($row, $languageUid);
            } elseif (TcaConfigurationManager::isLocalized($tableName)) {
                if (in_array($row[TcaConfigurationManager::getTcaCtrlField($tableName, 'languageField')], array(
                    - 1,
                    0
                ))) {
                    $overlayMode = ($languageMode === 'strict') ? 'hideNonTranslated' : '';
                    $row = $pageSelectObject->getRecordOverlay($tableName, $row, $languageUid, $overlayMode);
                }
            }
            if ($row !== NULL && is_array($row)) {
                $overlayedRows[] = $row;
            }
        }
        return $overlayedRows;
    }

    /**
     * Reads rows and return an array with the tablenames
     *
     * @param integer $rowCounter
     *            (row counter)
     *
     * @return array or boolean
     */
    protected function getRowWithFullFieldNames($rowCounter = 0, $overlay = TRUE)
    {
        // Gets the row
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_row($this->resource);
        if ($row) {
            $result = array();

            // Gets the fields objects once
            if ($rowCounter == 0) {
                foreach ($row as $fieldKey => $field) {
                    $this->fieldObjects[$fieldKey] = $this->resource->fetch_field_direct($fieldKey);
                    $tableName = $this->fieldObjects[$fieldKey]->table;
                    if (! empty($tableName) && $this->localizedTables[$tableName] !== TRUE && TcaConfigurationManager::isLocalized($tableName)) {
                        $this->localizedTables[$tableName] = TRUE;
                    }
                }
            }

            // Processes the row
            foreach ($row as $fieldKey => $field) {
                $fieldObject = $this->fieldObjects[$fieldKey];
                if ($fieldObject->table) {
                    if ($this->localizedTables[$fieldObject->table] === TRUE && $overlay === TRUE) {
                        $result[$fieldObject->table][$fieldObject->name] = $field;
                    } else {
                        $result[''][$fieldObject->table . '.' . $fieldObject->name] = $field;
                    }
                } else {
                    $result[''][$fieldObject->name] = $field;
                }
            }

            // Adds the uid and cruser_id aliases
            $mainTable = $this->queryConfigurationManager->getMainTable();
            if ($this->localizedTables[$mainTable] === TRUE) {
                $result['']['uid'] = $result[$mainTable]['uid'];
                $result['']['cruser_id'] = $result[$mainTable]['cruser_id'];
            } else {
                $result['']['uid'] = $result[''][$mainTable . '.uid'];
                $result['']['cruser_id'] = $result[''][$mainTable . '.cruser_id'];
            }

            return ($overlay === TRUE ? $result : $result['']);
        } else {
            return FALSE;
        }
    }
}
?>
