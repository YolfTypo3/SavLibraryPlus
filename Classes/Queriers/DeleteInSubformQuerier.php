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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;

/**
 * DeleteInSubform Querier.
 *
 * @package SavLibraryPlus
 */
class DeleteInSubformQuerier extends AbstractQuerier
{

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery()
    {
        // Checks if the user is authenticated
        if ($this->getController()
            ->getUserManager()
            ->userIsAuthenticated() === false) {
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

        // Gets the subform foreign uid
        $subformUidForeign = UriManager::getSubformUidForeign();

        // Updates the deleted flag in the foreign table
        $this->setDeletedField($fieldConfiguration['foreign_table'], $subformUidForeign);

        if (empty($fieldConfiguration['norelation'])) {

            // Gets the subform local uid
            $subformUidLocal = UriManager::getSubformUidLocal();

            // Deletes the record in the relation
            $this->deleteRecordsInRelationManyToMany($fieldConfiguration['MM'], $subformUidForeign, 'uid_foreign');

            // Reorders the sorting field
            $this->reorderSortingInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal);

            // Gets the rows count
            $rowsCount = $this->getRowsCountInRelationManyToMany($fieldConfiguration['MM'], $subformUidLocal);

            // Updates the count in the table
            $this->updateFields($fieldConfiguration['tableName'], [
                $fieldConfiguration['fieldName'] => $rowsCount
            ], $subformUidLocal);
        } else {
            $rowsCount = $this->getRowsCountInTable($fieldConfiguration['foreign_table']);
        }

        // Updates the page in subform value if needed
        $pageInSubform = SessionManager::getSubformFieldFromSession($subformFieldKey, 'pageInSubform');
        $pageInSubform = ($pageInSubform ? $pageInSubform : 0);

        if ($pageInSubform > 0 && $rowsCount <= $pageInSubform * $fieldConfiguration['maxsubformitems']) {
            $pageInSubform = SessionManager::getSubformFieldFromSession($subformFieldKey, 'pageInSubform');
            SessionManager::setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $pageInSubform - 1);
        }
    }
}
?>
