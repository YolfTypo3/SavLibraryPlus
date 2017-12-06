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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * DownInSubform Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class DownInSubformQuerier extends AbstractQuerier
{

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {

        // Checks if the user is authenticated
        if ($this->getController()
            ->getUserManager()
            ->userIsAuthenticated() === FALSE) {
            FlashMessages::addError('fatal.notAuthenticated');
            return;
        }

        // Gets the subform field key
        $subformFieldKey = UriManager::getSubformFieldKey();

        // Gets the kickstarter configuration for the subform field key
        $viewIdentifier = $this->getController()
            ->getLibraryConfigurationManager()
            ->getViewIdentifier('EditView');
        $viewConfiguration = $this->getController()
            ->getLibraryConfigurationManager()
            ->getViewConfiguration($viewIdentifier);
        $kickstarterFieldConfiguration = $this->getController()
            ->getLibraryConfigurationManager()
            ->searchFieldConfiguration($viewConfiguration, $subformFieldKey);

        // Creates the field configuration manager
        $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
        $fieldConfigurationManager->injectController($this->getController());
        $fieldConfigurationManager->injectKickstarterFieldConfiguration($kickstarterFieldConfiguration);
        $fieldConfiguration = $fieldConfigurationManager->getFieldConfiguration();

        // Gets the subform item foreign uid
        $subformUidForeign = UriManager::getSubformUidForeign();

        // Gets the subform item local uid
        $subformUidLocal = UriManager::getSubformUidLocal();

        // Gets the rows count
        $rowsCount = $this->getRowsCountInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal);

        // Gets the sorting field for the subform item
        $row = $this->getRowInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $subformUidForeign);
        $sortingSource = $row['sorting'];
        $sortingDestination = ($sortingSource % $rowsCount) + 1;

        // Updates the sorting field
        $uidForeignDestination = $this->getUidForeignInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $sortingDestination);
        $this->updateSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $uidForeignDestination, $sortingSource);
        $this->updateSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $subformUidForeign, $sortingDestination);
    }
}
?>
