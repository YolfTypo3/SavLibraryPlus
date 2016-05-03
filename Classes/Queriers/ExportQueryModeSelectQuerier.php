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
 * Default Export Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExportQueryModeSelectQuerier extends ExportSelectQuerier
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

        $this->exportConfiguration = array();

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
?>
