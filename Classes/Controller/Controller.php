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

namespace YolfTypo3\SavLibraryPlus\Controller;

use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;
use YolfTypo3\SavLibraryPlus\Viewers\EditViewer;
use YolfTypo3\SavLibraryPlus\Viewers\ErrorViewer;
use YolfTypo3\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\ListSelectQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\DeleteQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\DeleteInSubformQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\DownInSubformQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\FormUpdateQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\FormAdminUpdateQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\UpInSubformQuerier;

/**
 * Controller
 *
 * @package SavLibraryPlus
 */
class Controller extends AbstractController
{

    /**
     * Common code for change page in subform actions
     *
     * @return void
     */
    protected function changePageInSubform(): void
    {
        $subformFieldKey = $this->uriManager->getSubformFieldKey();
        $this->sessionManager->setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $this->uriManager->getPageInSubform());
    }

    /**
     * Renders change page in subform action
     *
     * @return string
     */
    protected function changePageInSubformAction(): string
    {
        $this->changePageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders change page in subform action
     *
     * @return string
     */
    protected function changePageInSubformInEditModeAction(): string
    {
        $this->changePageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the Close action
     *
     * @return string
     */
    protected function closeAction(): string
    {
        $this->sessionManager->clearSubformFromSession();
        return $this->renderForm('list');
    }

    /**
     * Renders the Close in edit mode action
     *
     * @return string
     */
    protected function closeInEditModeAction(): string
    {
        $this->sessionManager->clearSubformFromSession();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Renders the Delete action
     *
     * @return string
     */
    protected function deleteAction(): string
    {
        $this->querier = new (DeleteQuerier::class)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->processQuery();

        return $this->renderForm('listInEditMode');
    }
    
    /**
     * Renders the Delete action
     *
     * @return string
     */
    protected function deleteInSubformAction(): string
    {
        $this->querier = new (DeleteInSubformQuerier::class)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->processQuery();

        // Renders the form in edit mode
        return $this->renderForm('edit');
    }

    /**
     * Renders the down action
     *
     * @return string
     */
    protected function downInSubformAction(): string
    {
        $this->querier = new (DownInSubformQuerier::class)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->processQuery();

        // Renders the form in edit mode
        return $this->renderForm('edit');
    }

    /**
     * Renders the Edit action
     *
     * @return string
     */
    protected function editAction(): string
    {
        $this->sessionManager->clearSubformFromSession();
        return $this->renderForm('edit');
    }

    /**
     * Renders the Error action
     *
     * @return string
     */
    protected function errorAction(): string
    {
        FlashMessages::addError('fatal.notAllowedToExecuteRequestedAction');
        $viewer = new (ErrorViewer::class)($this);
        return $viewer->render();
    }

    /**
     * Renders the Export action
     *
     * @return string
     */
    protected function exportAction(): string
    {
        return $this->renderForm('export');
    }

    /**
     * Renders the Export Submit action
     *
     * @return string
     */
    protected function exportSubmitAction(): string
    {
        // Sets the post variables
        $this->uriManager->setPostVariables();

        // Gets the form action
        $formAction = $this->uriManager->getFormActionFromPostVariables();
        if (isset($formAction['exportLoadConfiguration'])) {
            return $this->renderForm('exportLoadConfiguration');
        } elseif (isset($formAction['exportSaveConfiguration'])) {
            return $this->renderForm('exportSaveConfiguration');
        } elseif (isset($formAction['exportDeleteConfiguration'])) {
            return $this->renderForm('exportDeleteConfiguration');
        } elseif (isset($formAction['exportToggleDisplay'])) {
            return $this->renderForm('exportToggleDisplay');
        } elseif (isset($formAction['exportExecute'])) {
            return $this->renderForm('exportExecute');
        } elseif (isset($formAction['exportQueryMode'])) {
            return $this->renderForm('exportQueryMode');
        } else {
            return $this->renderForm('export');
        }
    }

    /**
     * Common code for the first page actions
     *
     * @return void
     */
    protected function firstPage(): void
    {
        $compressedParameters = $this->uriManager->getCompressedParameters();
        $compressedParameters = $this->changeCompressedParameters($compressedParameters, 'page', 0);
        $this->uriManager->setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the first page action
     *
     * @return string
     */
    protected function firstPageAction(): string
    {
        $this->firstPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the first page in edit mode action
     *
     * @return string
     */
    protected function firstPageInEditModeAction(): string
    {
        $this->firstPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the first page in subform actions
     *
     * @return void
     */
    protected function firstPageInSubform(): void
    {
        $subformFieldKey = $this->uriManager->getSubformFieldKey();
        $this->sessionManager->setSubformFieldFromSession($subformFieldKey, 'pageInSubform', 0);
    }

    /**
     * Renders the first page in subform action
     *
     * @return string
     */
    protected function firstPageInSubformAction(): string
    {
        $this->firstPageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders the first page in subform action
     *
     * @return string
     */
    protected function firstPageInSubformInEditModeAction(): string
    {
        $this->firstPageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the form action
     *
     * @return string
     */
    protected function formAction(): string
    {
        return $this->renderForm('form');
    }

    /**
     * Renders the form admin action
     *
     * @return string
     */
    protected function formAdminAction(): string
    {
        return $this->renderForm('formAdmin');
    }

    /**
     * Common code for the last page actions
     *
     * @return void
     */
    protected function lastPage(): void
    {
        // Creates a querier to get the total rows count
        $querier = new (ListSelectQuerier::class)($this);
        $querier->setQueryConfiguration();
        $querier->processTotalRowsCountQuery();

        $lastPage = floor(($querier->getTotalRowsCount() - 1) / $this->getExtensionConfigurationManager()->getMaxItems());
        $compressedParameters = $this->uriManager->getCompressedParameters();
        $compressedParameters = $this->changeCompressedParameters($compressedParameters, 'page', $lastPage);
        $this->uriManager->setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the last page action
     *
     * @return string
     */
    protected function lastPageAction(): string
    {
        $this->lastPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the last page in edit mode action
     *
     * @return string
     */
    protected function lastPageInEditModeAction(): string
    {
        $this->lastPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the last page in subform actions
     *
     * @param string $viewType
     * 
     * @return void
     */
    protected function lastPageInSubform($viewType): void
    {
        // Sets the querier
        $querierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\' . ucfirst(str_replace('View', '', $viewType)) . 'SelectQuerier';
        $querier = new ($querierClassName)($this);
        $querier->setQueryConfiguration();
        $this->setQuerier($querier);
        
        // Gets the subform field key
        $subformFieldKey = $this->uriManager->getSubformFieldKey();
        
        // Gets the view identifier
        $viewIdentifier = $this->getLibraryConfigurationManager()->getViewIdentifier($viewType);

        // Gets the view configuration
        $libraryViewConfiguration = $this->getLibraryConfigurationManager()->getViewConfiguration($viewIdentifier);

        // Gets the kickstarter configuration for the subform field key
        $kickstarterFieldConfiguration = $this->getLibraryConfigurationManager()->searchFieldConfiguration($libraryViewConfiguration, $subformFieldKey);

        // Gets the field configuration
        $fieldConfigurationManager = new (FieldConfigurationManager::class)($this);
        $fieldConfigurationManager->setKickstarterFieldConfiguration($kickstarterFieldConfiguration);
        $fieldConfiguration = $fieldConfigurationManager->getFieldConfiguration();
        
        // Adds the uidLocal and the page in the subform
        $fieldConfiguration['uidLocal'] = $this->uriManager->getSubformUidLocal();
        
        // Builds the querier for the total rows count
        $querier = new (ForeignTableSelectQuerier::class)($this);
        $querier->buildQueryConfigurationForTrueManyToManyRelation($fieldConfiguration);
        $querier->setQueryConfiguration();
        $querier->processTotalRowsCountQuery();

        // Changes the page in subform
        $lastPage = floor(($querier->getTotalRowsCount() - 1) / $fieldConfiguration['maxsubformitems']);       
        $this->sessionManager->setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $lastPage);
    }

    /**
     * Renders the last page in subform action
     *
     * @return string
     */
    protected function lastPageInSubformAction(): string
    {
        $this->lastPageInSubform('singleView');
        return $this->renderForm('single');
    }

    /**
     * Renders the last page in subform in edit mode action
     *
     * @return string
     */
    protected function lastPageInSubformInEditModeAction(): string
    {
        $this->lastPageInSubform('editView');
        return $this->renderForm('edit');
    }

    /**
     * Renders the List action
     *
     * @return string
     */
    protected function listAction(): string
    {
        return $this->renderForm('list');
    }

    /**
     * Renders the List action in edit mode
     *
     * @return string
     */
    protected function listInEditModeAction(): string
    {
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the next page actions
     *
     * @return void
     */
    protected function nextPage(): void
    {
        $compressedParameters = $this->uriManager->getCompressedParameters();
        $compressedParameters = $this->changeCompressedParameters($compressedParameters, 'page', $this->uriManager->getPage() + 1);
        $this->uriManager->setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the next page action
     *
     * @return string
     */
    protected function nextPageAction(): string
    {
        $this->nextPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the next page action in edit mode
     *
     * @return string
     */
    protected function nextPageInEditModeAction(): string
    {
        $this->nextPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the next page in subform actions
     *
     * @return void
     */
    protected function nextPageInSubform(): void
    {
        $subformFieldKey = $this->uriManager->getSubformFieldKey();
        $pageInSubform = $this->sessionManager->getSubformFieldFromSession($subformFieldKey, 'pageInSubform');
        $this->sessionManager->setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $pageInSubform + 1);
    }

    /**
     * Renders the next page in subform action
     *
     * @return string
     */
    protected function nextPageinSubformAction(): string
    {
        $this->nextPageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders the next page in subform in edit mode action
     *
     * @return string
     */
    protected function nextPageinSubformInEditModeAction(): string
    {
        $this->nextPageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the new action
     *
     * @return string
     */
    protected function newAction(): string
    {
        return $this->renderForm('new');
    }

    /**
     * Renders the new action
     *
     * @return string
     */
    protected function newInSubformAction(): string
    {
        return $this->renderForm('newInSubform');
    }

    /**
     * Renders the noDisplay action
     *
     * @return string
     */
    protected function noDisplayAction(): string
    {
        return '';
    }

    /**
     * Common code for the previous page actions
     *
     * @return void
     */
    protected function previousPage(): void
    {
        $compressedParameters = $this->uriManager->getCompressedParameters();
        $compressedParameters = $this->changeCompressedParameters($compressedParameters, 'page', $this->uriManager->getPage() - 1);
        $this->uriManager->setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the previous page action
     *
     * @return string
     */
    protected function previousPageAction(): string
    {
        $this->previousPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the previous page action in edit mode
     *
     * @return string
     */
    protected function previousPageInEditModeAction(): string
    {
        $this->previousPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the previous page in subform actions
     *
     * @return void
     */
    protected function previousPageInSubform(): void
    {
        $subformFieldKey = $this->uriManager->getSubformFieldKey();
        $pageInSubform = $this->sessionManager->getSubformFieldFromSession($subformFieldKey, 'pageInSubform');
        $this->sessionManager->setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $pageInSubform - 1);
    }

    /**
     * Renders the previous page in subform action
     *
     * @return string
     */
    protected function previousPageInSubformAction(): string
    {
        $this->previousPageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders the previous page in subform in edit mode action
     *
     * @return string
     */
    protected function previousPageInSubformInEditModeAction(): string
    {
        $this->previousPageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the printInList action
     *
     * @return string
     */
    protected function printInListAction(): string
    {
        return $this->renderForm('printInList');
    }

    /**
     * Renders the printInSingle action
     *
     * @return string
     */
    protected function printInSingleAction(): string
    {
        return $this->renderForm('printInSingle');
    }

    /**
     * Renders the save action
     *
     * @return string
     */
    protected function saveAction(): string
    {
        // Sets the POST and GET variables
        $this->uriManager->setPostVariables();
        $this->querier = new (UpdateQuerier::class)($this);
        $this->querier->setQueryConfiguration();

        // Processes the query and renders the edit form in case of errors
        $this->viewer = new (EditViewer::class)($this);
        if ($this->querier->processQuery() === false) {
            return $this->renderForm('edit');
        }

        // Gets the form action
        $formAction = $this->uriManager->getFormActionFromPostVariables();
        if (isset($formAction['saveAndShow'])) {
            return $this->renderForm('single');
        } elseif (isset($formAction['saveAndClose'])) {
            return $this->renderForm('listInEditMode');
        } elseif (isset($formAction['saveAndNew'])) {
            return $this->renderForm('new');
        } elseif (isset($formAction['saveAndNewInSubform'])) {
            // Changes the form action
            $compressedParameters = $this->uriManager->getCompressedParameters();
            $compressedParameters = $this->changeCompressedParameters($compressedParameters, 'formAction', 'newInSubform');

            // Gets the compressed string
            $compressedString = key($formAction['saveAndNewInSubform']);
            $uncompressedParameters = $this->uncompressParameters($compressedString);

            // Changes the parameters
            foreach ($uncompressedParameters as $parameterKey => $parameter) {
                $compressedParameters = $this->changeCompressedParameters($compressedParameters, $parameterKey, $parameter);
            }
            $this->uriManager->setCompressedParameters($compressedParameters);

            return $this->renderForm('newInSubform');
        } else {
            return $this->renderForm('edit');
        }
    }

    /**
     * Renders the save form action
     *
     * @return string
     */
    protected function saveFormAction(): string
    {
        // Sets the post variables
        $this->uriManager->setPostVariables();

        $this->querier = new (FormUpdateQuerier::class)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->processQuery();

        return $this->renderForm('form');
    }

    /**
     * Renders the save form action
     *
     * @return string
     */
    protected function saveFormAdminAction(): string
    {
        // Sets the post variables
        $this->uriManager->setPostVariables();

        $this->querier = new (FormAdminUpdateQuerier::class)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->processQuery();

        return $this->renderForm('formAdmin');
    }

    /**
     * Renders the single action
     *
     * @return string
     */
    protected function singleAction(): string
    {
        $this->sessionManager->clearSubformFromSession();
        return $this->renderForm('single');
    }

    /**
     * Renders the up action
     *
     * @return string
     */
    protected function upInSubformAction(): string
    {
        $this->querier = new (UpInSubformQuerier::class)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->processQuery();

        // Renders the form in edit mode
        return $this->renderForm('edit');
    }
}
