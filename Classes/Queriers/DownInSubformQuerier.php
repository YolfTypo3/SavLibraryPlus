<?php

declare(strict_types=1);

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

use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * DownInSubform Querier.
 *
 * @package SavLibraryPlus
 */
class DownInSubformQuerier extends AbstractQuerier
{

    /**
     * Checks if the query can be executed
     *
     * @return bool
     */
    public function queryCanBeExecuted(): bool
    {
        $userManager = $this->controller->getUserManager();
        $uriManager = $this->controller->getUriManager();
        $result = $userManager->userIsAllowedToInputData() && $userManager->userIsAllowedToChangeData($uriManager->getSubformUidLocal());

        return $result;
    }

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery(): void
    {
        // Gets the subform field key
        $uriManager = $this->controller->getUriManager();
        $subformFieldKey = $uriManager->getSubformFieldKey();

        // Gets the kickstarter configuration for the subform field key
        $viewIdentifier = $this->controller
            ->getLibraryConfigurationManager()
            ->getViewIdentifier('EditView');
        $viewConfiguration = $this->controller
            ->getLibraryConfigurationManager()
            ->getViewConfiguration($viewIdentifier);
        $kickstarterFieldConfiguration = $this->controller
            ->getLibraryConfigurationManager()
            ->searchFieldConfiguration($viewConfiguration, $subformFieldKey);

        // Creates the field configuration manager
        $fieldConfigurationManager = new (FieldConfigurationManager::class)($this->controller);
        $fieldConfigurationManager->setKickstarterFieldConfiguration($kickstarterFieldConfiguration);
        $fieldConfiguration = $fieldConfigurationManager->getFieldConfiguration();

        // Gets the subform item foreign uid
        $subformUidForeign = $uriManager->getSubformUidForeign();

        // Gets the subform item local uid
        $subformUidLocal = $uriManager->getSubformUidLocal();

        // Gets the rows count
        $rowsCount = $this->getRowsCountInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal);

        // Gets the sorting field for the subform item
        $row = $this->getRowInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $subformUidForeign);
        $sortingSource = intval($row['sorting']);
        $sortingDestination = ($sortingSource % $rowsCount) + 1;

        // Updates the sorting field
        $uidForeignDestination = $this->getUidForeignInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $sortingDestination);
        $this->updateSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $uidForeignDestination, $sortingSource);
        $this->updateSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal, $subformUidForeign, $sortingDestination);
    }
}
