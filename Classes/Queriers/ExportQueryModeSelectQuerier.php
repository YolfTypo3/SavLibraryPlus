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

/**
 * Default Export Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportQueryModeSelectQuerier extends ExportSelectQuerier
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

        $this->exportConfiguration = [];

        // Sets the query mode
        $postVariables['queryMode'] = (empty($postVariables['queryMode']) ? 1 : 0);

        if (! $postVariables['queryMode']) {
            // Calls the parent Query to get the field names
            parent::executeQuery();
        } elseif (empty($postVariables['query'])) {
            unset($postVariables['fields']);
        }

        $this->exportConfiguration = array_merge($this->exportConfiguration, $postVariables);
    }
}
