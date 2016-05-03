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

/**
 * Default Export Load Configuration Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExportLoadConfigurationSelectQuerier extends ExportSelectQuerier
{

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {
        // Gets the configuration uid
        $configurationIdentifier = intval($this->getController()
            ->getUriManager()
            ->getPostVariablesItem('configuration'));

        // Executes the select query
        $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			/* SELECT   */	'*',
			/* FROM     */	self::$exportTableName,
 			/* WHERE    */	'uid = ' . $configurationIdentifier);

        // Sets the rows from the query
        $this->setRows();

        // Gets the serialized exportConfiguration
        $serializedExportConfiguration = $this->getFieldValueFromCurrentRow(self::$exportTableName . '.configuration');

        // Unserializes the export configuration, if not empty
        if (empty($serializedExportConfiguration) === FALSE) {
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
            if (array_key_exists($fieldKey, $this->rows[0]) === FALSE) {
                unset($loadedExportConfiguration['fields'][$fieldKey]);
            }
        }

        // Builds the export configuration
        foreach ($this->rows[0] as $rowKey => $row) {

            // Checks if the field is in the loaded configuration
            if (array_key_exists($rowKey, $loadedExportConfiguration['fields']) === FALSE && empty($loadedExportConfiguration['includeAllFields'])) {
                continue;
            }

            // Adds the field
            if (is_array($loadedExportConfiguration['fields'][$rowKey]) && $loadedExportConfiguration['fields'][$rowKey]['selected']) {
                $this->exportConfiguration['fields'][$rowKey] = $loadedExportConfiguration['fields'][$rowKey];
            } elseif (empty($loadedExportConfiguration['displaySelectedFields'])) {
                $this->exportConfiguration['fields'][$rowKey]['selected'] = 0;
                $this->exportConfiguration['fields'][$rowKey]['render'] = 0;
            }
        }

        return;
    }
}
?>
