<?php

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

use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;

/**
 * Default Export Load Configuration Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportLoadConfigurationSelectQuerier extends ExportSelectQuerier
{
    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Gets the configuration uid
        $configurationIdentifier = intval($this->getController()
            ->getUriManager()
            ->getPostVariablesItem('configuration'));

        // Executes the select query
        $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
			/* SELECT   */	'*',
			/* FROM     */	self::$exportTableName,
 			/* WHERE    */	'uid = ' . $configurationIdentifier
        );

        // Sets the rows from the query
        $this->setRows();

        // Gets the serialized exportConfiguration
        $serializedExportConfiguration = $this->getFieldValueFromCurrentRow(self::$exportTableName . '.configuration');

        // Unserializes the export configuration, if not empty
        if (empty($serializedExportConfiguration) === false) {
            $loadedExportConfiguration = unserialize($serializedExportConfiguration);
        } else {
            $loadedExportConfiguration = $this->getController()
                ->getUriManager()
                ->getPostVariables();
        }

        // Injects the additional tables
        $this->queryConfigurationManager->setQueryConfigurationParameter('foreignTables', $loadedExportConfiguration['additionalTables']);

        // Injects the additional fields
        $aliases = $this->queryConfigurationManager->getAliases();
        $additionalFields = $loadedExportConfiguration['additionalFields'];
        if (! empty($additionalFields)) {
            $aliases .= (empty($aliases) ? $additionalFields : ', ' . $additionalFields);
            $this->queryConfigurationManager->setQueryConfigurationParameter('aliases', $aliases);
        }

        // Calls the parent Query to get the field names
        parent::executeQuery();

        // Sets the export configuration and removes the fields
        $this->exportConfiguration = $loadedExportConfiguration;
        unset($this->exportConfiguration['fields']);

        // Removes the fields which are no more in the table
        foreach ($loadedExportConfiguration['fields'] as $fieldKey => $field) {
            if (array_key_exists($fieldKey, $this->rows[0]) === false) {
                unset($loadedExportConfiguration['fields'][$fieldKey]);
            }
        }

        // Builds the export configuration
        foreach ($this->rows[0] as $rowKey => $row) {

            // Checks if the field is in the loaded configuration
            if (is_array($loadedExportConfiguration['fields']) && array_key_exists($rowKey, $loadedExportConfiguration['fields']) === false && empty($loadedExportConfiguration['includeAllFields'])) {
                continue;
            }

            // Adds the field
            if (is_array($loadedExportConfiguration['fields']) && is_array($loadedExportConfiguration['fields'][$rowKey]) && ($loadedExportConfiguration['fields'][$rowKey]['selected'] || $loadedExportConfiguration['fields'][$rowKey]['render'])) {
                $this->exportConfiguration['fields'][$rowKey] = $loadedExportConfiguration['fields'][$rowKey];
            } elseif (empty($loadedExportConfiguration['displaySelectedFields'])) {
                $this->exportConfiguration['fields'][$rowKey]['selected'] = 0;
                $this->exportConfiguration['fields'][$rowKey]['render'] = 0;
            }
        }

        return;
    }
}
