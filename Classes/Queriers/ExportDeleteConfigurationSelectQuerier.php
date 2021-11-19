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
 * Default Export Delete Configuration Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportDeleteConfigurationSelectQuerier extends ExportSelectQuerier
{
    /**
     * Executes the query
     *
     * @return void
     */
    public function executeQuery()
    {
        // Gets the configuration uid
        $configurationIdentifier = intval($this->getController()
            ->getUriManager()
            ->getPostVariablesItem('configuration'));

        // Sets the deleted field for the record
        $this->setDeletedField(self::$exportTableName, $configurationIdentifier);

        // Calls the parent Query to get the field names
        parent::executeQuery();

        return;
    }
}
