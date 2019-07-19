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

/**
 * Default Export Toggle Display Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportToggleDisplaySelectQuerier extends ExportSelectQuerier
{
    /**
     * Executes the query
     *
     * @return void
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
        $this->exportConfiguration['fields'] = [];

        // Adds the fields according to displaySelectedFields
        foreach ($this->rows[0] as $rowKey => $row) {
            if (empty($postVariables['displaySelectedFields']) === false) {
                if (empty($postVariables['fields'][$rowKey]['selected']) === false) {
                    $this->exportConfiguration['fields'][$rowKey] = $postVariables['fields'][$rowKey];
                }
            } else {
                if (empty($postVariables['fields'][$rowKey]['selected'])) {
                    $this->exportConfiguration['fields'][$rowKey] = [
                        'selected' => 0,
                        'render' => 0
                    ];
                } else {
                    $this->exportConfiguration['fields'][$rowKey] = $postVariables['fields'][$rowKey];
                }
            }
        }

        return;
    }
}
?>
