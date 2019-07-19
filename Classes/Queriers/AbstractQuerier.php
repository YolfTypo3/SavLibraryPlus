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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Compatibility\MarkerBasedTemplateServiceCompatibility;
use YolfTypo3\SavLibraryPlus\Compatibility\Storage\Typo3DbBackendCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\FormConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\TcaConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\QueryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Utility\Conditions;

/**
 * Abstract Querier.
 *
 * @package SavLibraryPlus
 */
abstract class AbstractQuerier
{

    /**
     * The controller
     *
     * @var \YolfTypo3\SavLibraryPlus\Controller\Controller
     */
    private $controller;

    /**
     * The query configuration manager
     *
     * @var \YolfTypo3\SavLibraryPlus\Managers\QueryConfigurationManager
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
    protected $fieldObjects = [];

    /**
     * The array of localized tables
     *
     * @var array
     */
    protected $localizedTables = [];

    /**
     * The rows
     *
     * @var array
     */
    protected $rows = [];

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
    protected $queryParameters = [];

    /**
     * The query configuration
     *
     * @var array
     */
    protected $queryConfiguration = null;

    /**
     * The parent querier
     *
     * @var \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier
     */
    protected $parentQuerier = null;

    /**
     * The update querier
     *
     * @var \YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier
     */
    protected $updateQuerier = null;

    /**
     * The pages to clear
     *
     * @var array
     */
    protected $pageIdentifiersToClearInCache = [];

    /**
     * Additional Markers
     *
     * @var array
     */
    protected $additionalMarkers = [];

    /**
     * Special Markers
     *
     * @var array
     */
    protected $specialMarkers = [];

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        // Creates the query configuration manager
        $this->queryConfigurationManager = GeneralUtility::makeInstance(QueryConfigurationManager::class);
    }

    /**
     * Injects the controller
     *
     * @param \YolfTypo3\SavLibraryPlus\Controller\AbstractController $controller
     *            The controller
     *
     * @return void
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
     * @return void
     */
    public function injectQueryConfiguration()
    {
        if ($this->queryConfiguration === null) {
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
     * @param \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier $parentQuerier
     *
     * @return void
     */
    public function injectParentQuerier($parentQuerier)
    {
        $this->parentQuerier = $parentQuerier;
    }

    /**
     * Injects the update querier
     *
     * @param \YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier $updateQuerier
     *
     * @return void
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
     * @return void
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
     * Injects special markers
     *
     * @param array $specialMarkers
     *
     * @return void
     */
    public function injectSpecialMarkers($specialMarkers)
    {
        $this->specialMarkers = array_merge($this->specialMarkers, $specialMarkers);
    }

    /**
     * Processes the query
     *
     * @return void
     */
    public function processQuery()
    {
        if ($this->executeQuery() === false) {
            return false;
        }
        // Clear pages cache if needed
        $this->clearPagesCache();
        return true;
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {}

    /**
     * Clears the pages cache if needed
     *
     * @return void
     */
    protected function clearPagesCache()
    {
        // if the plugin type is not USER, the cache has not to be cleared
        if (ExtensionConfigurationManager::isUserPlugin() === false) {
            return;
        }

        // If the page identifiers list is empty, just returns
        if (empty($this->pageIdentifiersToClearInCache)) {
            return;
        }

        // Deletes the pages in the cache
        DatabaseCompatibility::getDatabaseConnection()->exec_DELETEquery('cache_pages', 'page_id IN (' . implode(',', $this->pageIdentifiersToClearInCache) . ')');
    }

    /**
     * Sets the current row identifier
     *
     * @param integer $rowId
     *            The row identifier
     *
     * @return void
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
     * @return void
     */
    public function addEmptyRow()
    {
        $this->rows[0] = [];
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
     * @return void
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

        while (! $querier->fieldExistsInCurrentRow($fieldName) && $querier->parentQuerier !== null) {
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
            return false;
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
        while (! $querier->fieldExistsInCurrentRow($fieldName) && $querier->parentQuerier !== null) {
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
     * @return \YolfTypo3\SavLibraryPlus\Controller\AbstractController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Gets the parent querier
     *
     * @return \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier
     */
    public function getParentQuerier()
    {
        return $this->parentQuerier;
    }

    /**
     * Gets the update querier
     *
     * @return \YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier
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
        if ($updateQuerier !== null) {
            return $updateQuerier->errorDuringUpdate();
        } else {
            return false;
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
     * @return \YolfTypo3\SavLibraryPlus\Managers\QueryConfigurationManager
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
        if (empty($foreignTables) === false) {
            $foreignTables = $this->parseFieldTags($foreignTables);
            $match = [];
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
     * @return void
     */
    protected function deleteRecordsInRelationManyToMany($tableName, $uid, $whereField = 'uid_local')
    {
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_DELETEquery(
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
     * @return void
     */
    protected function insertFieldsInRelationManyToMany($tableName, $fields)
    {
        // Inserts the fields
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_INSERTquery(
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
     * @return void
     */
    protected function getRowInRelationManyToMany($tableName, $uidLocal, $uidForeign)
    {
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	'*',
			/* FROM     */	$tableName,
 			/* WHERE    */	'uid_local = ' . $uidLocal . ' AND uid_foreign = ' . $uidForeign);
        $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($this->resource);
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
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	'uid_foreign',
			/* FROM     */	$tableName,
 			/* WHERE    */	'uid_local = ' . $uidLocal . ' AND sorting = ' . $sorting);
        $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($this->resource);
        return $row['uid_foreign'];
    }

    /**
     * Gets the records count in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uidLocal
     *
     * @return void
     */
    protected function getRowsCountInRelationManyToMany($tableName, $uidLocal)
    {
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	'count(*) as recordsCount, max(sorting) as maxSorting',
			/* FROM     */	$tableName,
 			/* WHERE    */	'uid_local = ' . $uidLocal);
        $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($this->resource);

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
     * @return void
     */
    protected function updateSortingInRelationManyToMany($tableName, $uidLocal, $uidForeign, $sorting)
    {
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_UPDATEquery(
			/* TABLE   */	$tableName,
 			/* WHERE   */	'uid_local=' . $uidLocal . ' AND uid_foreign=' . $uidForeign,
			/* FIELDS  */	[
            'sorting' => $sorting
        ]);
    }

    /**
     * Reorders the sorting field in a MM table
     *
     * @param string $tableName
     *            Table name
     * @param integer $uid
     *            uid of the record to delete
     *
     * @return void
     */
    protected function reorderSortingInRelationManyToMany($tableName, $uidLocal)
    {
        if (! empty($uidLocal)) {
            $query = 'UPDATE ' . $tableName . ', (SELECT @counter:=0) AS initCount SET sorting = (@counter:=@counter+1) WHERE ' . $tableName . '.uid_local=' . intval($uidLocal);
            $this->resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($query);
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
     * @return void
     */
    protected function setDeletedField($tableName, $uid)
    {
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_UPDATEquery(
			/* TABLE   */	$tableName,
 			/* WHERE   */	$tableName . '.uid=' . intval($uid),
			/* FIELDS  */	[
            'deleted' => 1
        ]);

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
     * @return void
     */
    protected function updateFields($tableName, $fields, $uid)
    {
        $uid = SessionManager::getLocalizedFieldFromSession($tableName, $uid);

        if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] && ! array_key_exists('tstamp', $fields)) {
            $fields = array_merge($fields, [
                $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] => time()
            ]);
        }

        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_UPDATEquery(
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
            $fields = array_merge($fields, [
                $GLOBALS['TCA'][$tableName]['ctrl']['cruser_id'] => $this->getTypoScriptFrontendController()->fe_user->user['uid']
            ]);
        }
        if ($GLOBALS['TCA'][$tableName]['ctrl']['crdate']) {
            $fields = array_merge($fields, [
                $GLOBALS['TCA'][$tableName]['ctrl']['crdate'] => time()
            ]);
        }
        if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp']) {
            $fields = array_merge($fields, [
                $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] => time()
            ]);
        }

        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_INSERTquery(
            /* TABLE   */	$tableName,
  		    /* FIELDS  */	$fields);

        $uid = DatabaseCompatibility::getDatabaseConnection()->sql_insert_id($this->resource);

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
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	'count(*) as recordsCount',
			/* FROM     */	$tableName,
            /* WHERE    */	'1 ' . $this->getPageRepository()
            ->enableFields($tableName));
        $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($this->resource);

        return intval($row['recordsCount']);
    }

    /**
     * Adds the pid to the page identifiers to clear in the cache if needed.
     * If the record lies on a page, then we clear the cache of this page.
     * If the record has no PID column, we clear the cache of the current page as best-effort.
     *
     * Much of this code is taken from \TYPO3\CMS\Extbase\Persistence\Storage\Typo3DbBackend::clearPageCache .
     *
     * @param string $tableName
     *            Tablename of the record
     * @param integer $uid
     *            UID of the record
     * @return void
     */
    protected function addToPageIdentifiersToClearInCache($tableName, $uid)
    {
        // if the plugin type is not USER, the cache has not to be clerared
        if (ExtensionConfigurationManager::isUserPlugin() === false) {
            return;
        }

        $storagePage = null;

        $columns = DatabaseCompatibility::getDatabaseConnection()->admin_get_fields($tableName);
        if (array_key_exists('pid', $columns)) {
            $result = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery('pid', $tableName, 'uid=' . intval($uid));
            if ($row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($result)) {
                $storagePage = $row['pid'];
                $this->pageIdentifiersToClearInCache[] = intval($storagePage);
            }
        } elseif (isset($GLOBALS['TSFE'])) {
            // No PID column - we can do a best-effort to clear the cache of the current page if in FE
            $storagePage = $this->getTypoScriptFrontendController()->id;
            $this->pageIdentifiersToClearInCache[] = intval($storagePage);
        }

        // Gets the storage page
        $storagePage = $this->getController()
            ->getExtensionConfigurationManager()
            ->getStoragePage();
        if (empty($storagePage) === false) {
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
                $pageListArray = [];
            }
            // Adds the storage page
            $storagePage = $extensionConfigurationManager->getStoragePage();
            if (empty($storagePage) === false) {
                $pageListArray[] = $storagePage;
            }

            $pageList = implode(',', $pageListArray);

            return ($pageList ? ' AND ' . $tableName . '.pid IN (' . $pageList . ')' : '');
        }
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
        $matches = [];
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
     *            If true report the error associated when the marker is not found
     *
     * @return string
     */
    public function parseLocalizationTags($value, $reportError = true)
    {
        // Checks if the value must be parsed
        if (strpos($value, '$') === false) {
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
        $matches = [];
        if (preg_match_all('/\$\$\$label\[([^\]]+)\]\$\$\$/', $value, $matches)) {
            foreach ($matches[1] as $matchKey => $match) {
                // Checks if the label is in locallang_db.xlf, no default table is assumed
                // In that case the full name must be used, i.e. tableName.fieldName
                $label = $this->getTypoScriptFrontendController()->sL($localizationPrefix . 'locallang_db.xlf:' . $match);
                if (! empty($label)) {
                    $value = str_replace($matches[0][$matchKey], $label, $value);
                } else {
                    // Checks if the label is in locallang_db.xlf, the main table is assumed
                    $mainTable = $this->getQueryConfigurationManager()->getMainTable();
                    $label = $this->getTypoScriptFrontendController()->sL($localizationPrefix . 'locallang_db.xlf:' . $mainTable . '.' . $match);

                    if (! empty($label)) {
                        // Found in locallang_db.xlf file, replaces it
                        $value = str_replace($matches[0][$matchKey], $label, $value);
                    } elseif ($reportError === true) {
                        FlashMessages::addError('error.missingLabel', [
                            $match
                        ]);
                    } else {
                        $value = str_replace($matches[0][$matchKey], $matches[1][$matchKey], $value);
                    }
                }
            }
        }

        // Checks if the label is in the locallang.xlf file
        $matches = [];
        preg_match_all('/\$\$\$([^\$]+)\$\$\$/', $value, $matches);
        foreach ($matches[1] as $matchKey => $match) {
            $label = LocalizationUtility::translate($match, $extensionKey);
            if (! empty($label)) {
                // Found in locallang.xlf file, replaces it
                $value = str_replace($matches[0][$matchKey], $label, $value);
            } elseif ($reportError === true) {
                FlashMessages::addError('error.missingLabel', [
                    $match
                ]);
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
     *            If true report the error associated when the marker is not found
     *
     * @return string
     */
    public function parseFieldTags($value, $reportError = true)
    {
        // Checks if the value must be parsed
        if (strpos($value, '#') === false) {
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
        $markers['###linkToPage###'] = str_replace('<a href="', '<a href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), $extension->pi_linkToPage('', $this->getTypoScriptFrontendController()->id));

        // Gets the main table
        $mainTable = $this->getQueryConfigurationManager()->getMainTable();

        // Gets the tags
        $matches = [];
        preg_match_all('/###(?:(?P<render>render\[)|special\[|(?P<findOrDefault>findOrDefault\[))?(?P<fullFieldName>(?<TableNameOrAlias>[^\.\:#\]]+)\.?(?<fieldName>[^#\:\]]*))(?:\:(?<configuration>[^#\]]+))?\]?###/', $value, $matches);
        $matchKeys = array_keys($matches['fullFieldName']);
        foreach ($matchKeys as $matchKey) {

            $fullFieldName = null;
            if (array_key_exists($matches[0][$matchKey], $markers) && ($matches[0][$matchKey] != '###uid###' || ($this->getController()->getQuerier() instanceof \YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier))) {
                // Already in the markers array
                continue;
            } elseif ($matches['fieldName'][$matchKey]) {
                if ($this->fieldExists($matches['fullFieldName'][$matchKey])) {
                    // It's a full field name, i.e. tableName.fieldName
                    $fullFieldName = $matches['fullFieldName'][$matchKey];
                }
            } elseif ($matches['findOrDefault'][$matchKey]) {
                $tagName = '###' . $matches['fullFieldName'][$matchKey] . '###';
                if (! array_key_exists($tagName, $markers)) {
                    $tagValue = '';
                } else {
                    $tagValue = $markers[$tagName];
                }
                $markers[$matches[0][$matchKey]] = $tagValue;
                continue;
            } else {
                if ($this->fieldExists($matches['TableNameOrAlias'][$matchKey])) {
                    // It's an alias
                    $fullFieldName = $matches['TableNameOrAlias'][$matchKey];
                } elseif ($this->fieldExists($mainTable . '.' . $matches['TableNameOrAlias'][$matchKey])) {
                    // The main table was omitted
                    $fullFieldName = $mainTable . '.' . $matches['TableNameOrAlias'][$matchKey];
                } elseif ($matches['TableNameOrAlias'][$matchKey] == 'user') {
                    $markers[$matches[0][$matchKey]] = $this->getTypoScriptFrontendController()->fe_user->user['uid'];
                    continue;
                }
            }

            // Special Processing when the full field name is not found
            if ($fullFieldName === null) {
                if ($this->getController()->getViewer() instanceof \YolfTypo3\SavLibraryPlus\Viewers\NewViewer || ($this->getController()->getViewer() instanceof \YolfTypo3\SavLibraryPlus\Viewers\SubformEditViewer && $this->getController()
                    ->getViewer()
                    ->isNewView())) {
                    // In new view, it may occur that markers are used, in reqValue for example. The markers are replaced by 0.
                    $markers[$matches[0][$matchKey]] = '0';
                    continue;
                } elseif ($this->getController()->getQuerier() instanceof \YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier) {
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
                } elseif ($reportError === true) {
                    if ($this->updateQuerier === null || ! $this->updateQuerier::$doNotUpdateOrInsert) {
                        // Unknown marker added as a flash message
                        FlashMessages::addError('error.unknownMarker', [
                            $matches[0][$matchKey]
                        ]);
                    }
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
                $configurations = [];
                if (preg_match_all('/([^=]+)=([^;]+);?/', $matches['configuration'][$matchKey], $configurations)) {
                    $configurationKeys = array_keys($configurations[0]);
                    foreach ($configurationKeys as $configurationKey) {
                        $fieldConfiguration = array_merge($fieldConfiguration, [
                            trim(strtolower($configurations[1][$configurationKey])) => trim($configurations[2][$configurationKey])
                        ]);
                    }
                }

                // Adds the value from the current row
                $fieldConfiguration['value'] = $this->getFieldValue($fullFieldName);

                // Calls the item viewer
                $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\General\\' . $fieldConfiguration['fieldType'] . 'ItemViewer';
                $itemViewer = GeneralUtility::makeInstance($className);
                $itemViewer->injectController($this->getController());
                $itemViewer->injectItemConfiguration($fieldConfiguration);
                $markers[$matches[0][$matchKey]] = $itemViewer->render();
            } else {
                $markers[$matches[0][$matchKey]] = $this->getFieldValue($fullFieldName);
            }
        }

        // Gets the template service
        $templateService = MarkerBasedTemplateServiceCompatibility::getMarkerBasedTemplateService();

        return $templateService->substituteMarkerArrayCached($value, $markers, [], []);
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
        if (strpos($whereClause, '#') === false) {
            return $whereClause;
        }

        // Initaializes the markers
        $markers = $this->buildSpecialMarkers();

        // Gets the template service
        $templateService = MarkerBasedTemplateServiceCompatibility::getMarkerBasedTemplateService();

        // Replaces the special markers
        $whereClause = $templateService->substituteMarkerArrayCached($whereClause, $markers, [], []);

        // Processes the ###group_list### tag
        $matches = [];
        if (preg_match_all('/###group_list\s*([!]?)=([^#]*)###/', $whereClause, $matches)) {

            foreach ($matches[2] as $matchKey => $match) {
                $groups = explode(',', str_replace(' ', '', $match));
                $clause = '';

                // Gets the group list of uid
                $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
				    /* SELECT   */	'uid,title',
				    /* FROM     */	'fe_groups',
                    /* WHERE    */	'1' . $this->getPageRepository()
                    ->enableFields('fe_groups'));

                while (($rows = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_assoc($this->resource))) {
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
        $matches = [];
        if (preg_match_all('/###([^:]+):([^#]+)###/', $whereClause, $matches)) {

            foreach ($matches[1] as $matchKey => $match) {
                $replace = '1';
                $matchFunctions = [];
                preg_match('/([^\(]+)(?:\(([^\)]*)\)){0,1}/', $match, $matchFunctions);

                $conditionFunction = $matchFunctions[1];
                if ($conditionFunction && method_exists(\YolfTypo3\SavLibraryPlus\Utility\Conditions::class, $conditionFunction)) {
                    // Checks if there is one parameter
                    if ($matchFunctions[2]) {
                        if (Conditions::$conditionFunction($matchFunctions[2])) {
                            $replace .= ' AND ' . $matches[2][$matchKey];
                        }
                    } else {
                        if (Conditions::$conditionFunction()) {
                            $replace .= ' AND ' . $matches[2][$matchKey];
                        }
                    }
                } else {
                    FlashMessages::addError('error.unknownFunctionInWhere', [
                        $conditionFunction
                    ]);
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
        $markers = [];

        // ###uid### marker
        $markers['###uid###'] = (is_object($this->getController()->getViewer()) && $this->getController()
            ->getViewer()
            ->getViewType() == 'ListView' ? $this->getFieldValueFromCurrentRow('uid') : UriManager::getUid());

        // ###uidMainTable
        $markers['###uidMainTable###'] = $markers['###uid###'];

        // ###user### marker
        $markers['###user###'] = $this->getTypoScriptFrontendController()->fe_user->user['uid'];

        // ###cruser_id### marker
        if (! $this->fieldExists('cruser_id')) {
            $markers['###cruser_id###'] = $this->getTypoScriptFrontendController()->fe_user->user['uid'];
        } else {
            $markers['###cruser_id###'] = $this->getFieldValue('cruser_id');
        }

        // ###CURRENT_PID### marker
        $markers['###CURRENT_PID###'] = $this->getTypoScriptFrontendController()->page['uid'];

        // ###SITEROOT### marker
        $markers['###SITEROOT###'] = $this->getTypoScriptFrontendController()->rootLine[0]['uid'];

        // ###now### marker
        $markers['###now###'] = time();

        // Merges the markers
        $markers = array_merge($markers, $this->specialMarkers);

        // Adds special markers from the session if any
        $tagInSession = SessionManager::getFieldFromSession('tagInSession');
        if ($tagInSession !== null && is_array($tagInSession)) {
            foreach ($tagInSession as $tagKey => $tag) {
                $markers['###' . $tagKey . '###'] = $tag;
            }
        }

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
     * @return void
     */
    protected function setRows()
    {
        $counter = 0;
        $this->rows = [];
        $tablesForOverlay = [];
        while (($row = $this->getRowWithFullFieldNames($counter ++))) {
            foreach ($row as $tableName => $fields) {
                if (empty($tableName)) {
                    $this->rows[] = $fields;
                } else {
                    $tablesForOverlay[$tableName][] = $fields;
                }
            }
        }

        // Processes the tables which must be overlayed
        $localizedFields = [];
        foreach ($tablesForOverlay as $tableKey => $rows) {
            $overlayedRows = Typo3DbBackendCompatibility::doLanguageAndWorkspaceOverlay($tableKey, $rows);
            foreach ($overlayedRows as $rowKey => $row) {
                foreach ($row as $fieldKey => $field) {
                    $this->rows[$rowKey][$tableKey . '.' . $fieldKey] = $field;
                    if ($fieldKey == '_LOCALIZED_UID') {
                        $uid = $row['uid'];
                        $localizedFields[$tableKey][$uid] = $field;
                    }
                }
            }
        }

        // Puts the localized fields in the session for further processing in the update querier
        if ($this instanceof EditSelectQuerier) {
            SessionManager::setFieldFromSession('localizedFields', $localizedFields);
        }
    }

    /**
     * Reads rows and return an array with the tablenames
     *
     * @param integer $rowCounter
     *            (row counter)
     *
     * @return array or boolean
     */
    protected function getRowWithFullFieldNames($rowCounter = 0, $overlay = true)
    {
        // Gets the row
        $row = DatabaseCompatibility::getDatabaseConnection()->sql_fetch_row($this->resource);

        if ($row) {
            $result = [];

            // Gets the fields objects once
            if ($rowCounter == 0) {
                foreach ($row as $fieldKey => $field) {
                    $this->fieldObjects[$fieldKey] = $this->resource->fetch_field_direct($fieldKey);
                    $tableName = $this->fieldObjects[$fieldKey]->table;
                    if (! empty($tableName) && $this->localizedTables[$tableName] !== true && TcaConfigurationManager::isLocalized($tableName)) {
                        $this->localizedTables[$tableName] = true;
                    }
                }
            }

            // Processes the row
            foreach ($row as $fieldKey => $field) {
                $fieldObject = $this->fieldObjects[$fieldKey];
                if ($fieldObject->table) {
                    if ($this->localizedTables[$fieldObject->table] === true && $overlay === true) {
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
            if ($this->localizedTables[$mainTable] === true) {
                $result['']['uid'] = $result[$mainTable]['uid'];
                $result['']['cruser_id'] = $result[$mainTable]['cruser_id'];
            } else {
                $result['']['uid'] = $result[''][$mainTable . '.uid'];
                $result['']['cruser_id'] = $result[''][$mainTable . '.cruser_id'];
            }

            return ($overlay === true ? $result : $result['']);
        } else {
            return false;
        }
    }

    /**
     * Gets the key for the submitted data in form views.
     * By defaultn the key is the short form name
     *
     * @return string
     */
    protected function getFormSubmittedDataKey()
    {
        $submittedDataKey = AbstractController::getShortFormName();
        $formTitle = FormConfigurationManager::getFormTitle();
        $typoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        if (is_array($typoScriptConfiguration[$formTitle . '.']) && is_array($typoScriptConfiguration[$formTitle . '.']['formView.']) && $typoScriptConfiguration[$formTitle . '.']['formView.']['key']) {
            $submittedDataKey = $typoScriptConfiguration[$formTitle . '.']['formView.']['key'];
        }
        return $submittedDataKey;
    }

    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Gets the Page Repository
     *
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepository(): PageRepository
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        return $pageRepository;
    }
}
?>
