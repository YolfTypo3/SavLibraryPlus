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
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Default Export Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportSelectQuerier extends AbstractQuerier
{

    /**
     * The export table name
     *
     * @var string
     */
    public static $exportTableName = 'tx_savlibraryplus_export_configuration';

    /**
     * The export configuration
     *
     * @var array
     */
    protected $exportConfiguration;

    /**
     * The fields to exclude
     *
     * @var array
     */
    protected $fieldsToExclude = [
        'uid',
        'pid',
        'crdate',
        'tstamp',
        'hidden',
        'deleted',
        'cruser_id',
        'disable',
        'starttime',
        'endtime',
        'password',
        'lockToDomain',
        'is_online',
        'lastlogin',
        't3ver_id',
        't3ver_oid',
        't3ver_label',
        't3ver_wsid',
        't3ver_stage',
        't3ver_state',
        't3ver_tstamp',
        't3_origuid',
        't3ver_count',
        'TSconfig'
    ];

    /**
     * Checks if the query can be executed
     *
     * @return boolean
     */
    public function queryCanBeExecuted()
    {
        $userManager = $this->getController()->getUserManager();
        $result = $userManager->userIsAllowedToExportData();

        return $result;
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Executes the select query to get the field names
        $saveDebugOutput = DatabaseCompatibility::getDatabaseConnection()->debugOutput;
        $saveStore_lastBuiltQuery = DatabaseCompatibility::getDatabaseConnection()->store_lastBuiltQuery;
        DatabaseCompatibility::getDatabaseConnection()->debugOutput = false;
        DatabaseCompatibility::getDatabaseConnection()->store_lastBuiltQuery = true;
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	$this->buildSelectClause(),
			/* FROM     */	$this->buildFromClause(),
 			/* WHERE    */	$this->buildWhereClause(),
			/* GROUP BY */	$this->buildGroupByClause(),
			/* ORDER BY */  $this->buildOrderByClause(),
			/* LIMIT    */  $this->buildLimitClause());
        DatabaseCompatibility::getDatabaseConnection()->debugOutput = $saveDebugOutput;
        DatabaseCompatibility::getDatabaseConnection()->store_lastBuiltQuery = $saveStore_lastBuiltQuery;

        if ($this->resource !== false) {
            $this->setRows();

            // Replaces the field values by the checkbox value
            if (! empty($this->rows)) {
                $this->exportConfiguration = [];
                foreach ($this->rows[0] as $rowKey => $row) {
                    if ($this->isFieldToExclude($rowKey) === false) {
                        $this->exportConfiguration['fields'][$rowKey]['selected'] = 0;
                        $this->exportConfiguration['fields'][$rowKey]['render'] = 0;
                    }
                }
            } else {
                FlashMessages::addError('warning.noRecord');
            }
        } else {
            FlashMessages::addError('error.query', [
                DatabaseCompatibility::getDatabaseConnection()->sql_error(),
                DatabaseCompatibility::getDatabaseConnection()->debug_lastBuiltQuery
            ]);
        }
        return;
    }

    /**
     * Returns true if the field must be excluded
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isFieldToExclude($fieldName)
    {
        $fileNameParts = explode('.', $fieldName);
        return in_array($fileNameParts[1], $this->fieldsToExclude);
    }

    /**
     * Gets the export configuration
     *
     * @return void
     */
    public function getExportConfiguration()
    {
        // Unsets fileds which should not be displayed
        if (is_array($this->exportConfiguration['fields'])) {
            foreach ($this->exportConfiguration['fields'] as $fieldKey => $field) {
                if ($this->isFieldToExclude($fieldKey) && empty($this->exportConfiguration['includeAllFields'])) {
                    unset($this->exportConfiguration['fields'][$fieldKey]);
                }
            }
        }
        return $this->exportConfiguration;
    }

    /**
     * Builds the WHERE BY Clause.
     *
     * @return string
     */
    protected function buildWhereClause()
    {
        // Gets only one row since we only need to get the field name
        $whereClause = $this->getQueryConfigurationManager()->getMainTable() . '.uid=(SELECT uid FROM ' . $this->getQueryConfigurationManager()->getMainTable() . ' LIMIT 1)';

        return $whereClause;
    }

    /**
     * Builds the LIMIT BY Clause.
     *
     * @return string
     */
    protected function buildLimitClause()
    {
        return '1';
    }

    /**
     * Builds the ORDER BY Clause.
     *
     * @return string
     */
    protected function buildOrderByClause()
    {
        return '';
    }
}
?>
