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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * UpInSubform Querier.
 *
 * @package SavLibraryPlus
 */
class UpInSubformQuerier extends AbstractQuerier
{

    /**
     * Checks if the query can be executed
     *
     * @return boolean
     */
    public function queryCanBeExecuted()
    {
        $userManager = $this->getController()->getUserManager();
        $result = $userManager->userIsAllowedToInputData() && $userManager->userIsAllowedToChangeData(UriManager::getSubformUidLocal());

        return $result;
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
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
        $sortingDestination = ($sortingSource == 1 ? $rowsCount : $sortingSource - 1);

        // Updates the sorting field
        $uidForeignDestination = $this->getUidForeignInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $sortingDestination);
        $this->updateSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $uidForeignDestination, $sortingSource);
        $this->updateSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $subformUidForeign, $sortingDestination);
    }
}
