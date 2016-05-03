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
 * Default Export Toggle Display Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExportToggleDisplaySelectQuerier extends ExportSelectQuerier
{

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {
        // Gets the uri manager
        $uriManager = $this->getController()->getUriManager();

        // Gets the post variables
        $postVariables = $uriManager->getPostVariables();

        // Toggles the display
        $postVariables['displaySelectedFields'] = (empty($postVariables['displaySelectedFields']) ? 1 : 0);

        // Injects the additional tables
        $this->queryConfigurationManager->setQueryConfigurationParameter('foreignTables', $postVariables['additionalTables']);

        // Injects the additional fields
        $aliases = $this->queryConfigurationManager->getAliases();
        $additionalFields = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('additionalFields');
        if (! empty($additionalFields)) {
            $aliases .= (empty($aliases) ? $additionalFields : ', ' . $additionalFields);
            $this->queryConfigurationManager->setQueryConfigurationParameter('aliases', $aliases);
        }

        // Calls the parent Query to get the field names
        parent::executeQuery();

        // Sets the export configuration and cleans the fields
        $this->exportConfiguration = $postVariables;
        $this->exportConfiguration['fields'] = array();

        // Adds the fields according to displaySelectedFields
        foreach ($this->rows[0] as $rowKey => $row) {
            if (empty($postVariables['displaySelectedFields']) === FALSE) {
                if (empty($postVariables['fields'][$rowKey]['selected']) === FALSE) {
                    $this->exportConfiguration['fields'][$rowKey] = $postVariables['fields'][$rowKey];
                }
            } else {
                $this->exportConfiguration['fields'][$rowKey] = (empty($postVariables['fields'][$rowKey]['selected']) ? array(
                    'selected' => 0,
                    'render' => 0
                ) : $postVariables['fields'][$rowKey]);
            }
        }

        return;
    }
}
?>
