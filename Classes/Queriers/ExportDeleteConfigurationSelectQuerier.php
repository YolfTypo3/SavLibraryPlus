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
 * Default Export Delete Configuration Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExportDeleteConfigurationSelectQuerier extends ExportSelectQuerier
{

    /**
     * Executes the query
     *
     * @return none
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
?>
