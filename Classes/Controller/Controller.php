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

namespace YolfTypo3\SavLibraryPlus\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
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
    protected function changePageInSubform()
    {
        $subformFieldKey = UriManager::getSubformFieldKey();
        SessionManager::setSubformFieldFromSession($subformFieldKey, 'pageInSubform', UriManager::getPageInSubform());
    }

    /**
     * Renders change page in subform action
     *
     * @return string
     */
    protected function changePageInSubformAction()
    {
        $this->changePageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders change page in subform action
     *
     * @return string
     */
    protected function changePageInSubformInEditModeAction()
    {
        $this->changePageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the Close action
     *
     * @return string
     */
    protected function closeAction()
    {
        SessionManager::clearSubformFromSession();
        return $this->renderForm('list');
    }

    /**
     * Renders the Close in edit mode action
     *
     * @return string
     */
    protected function closeInEditModeAction()
    {
        SessionManager::clearSubformFromSession();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Renders the Delete action
     *
     * @return string
     */
    protected function deleteAction()
    {
        $this->querier = GeneralUtility::makeInstance(DeleteQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->processQuery();

        return $this->renderForm('listInEditMode');
    }

    /**
     * Renders the Delete action
     *
     * @return string
     */
    protected function deleteInSubformAction()
    {
        $this->querier = GeneralUtility::makeInstance(DeleteInSubformQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->processQuery();

        // Renders the form in edit mode
        return $this->renderForm('edit');
    }

    /**
     * Renders the down action
     *
     * @return string
     */
    protected function downInSubformAction()
    {
        $this->querier = GeneralUtility::makeInstance(DownInSubformQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->processQuery();

        // Renders the form in edit mode
        return $this->renderForm('edit');
    }

    /**
     * Renders the Edit action
     *
     * @return string
     */
    protected function editAction()
    {
        SessionManager::clearSubformFromSession();
        return $this->renderForm('edit');
    }

    /**
     * Renders the Error action
     *
     * @return string
     */
    protected function errorAction()
    {
        FlashMessages::addError('fatal.notAllowedToExecuteRequestedAction');
        $viewer = GeneralUtility::makeInstance(ErrorViewer::class);
        $viewer->injectController($this);
        return $viewer->render();
    }

    /**
     * Renders the Export action
     *
     * @return string
     */
    protected function exportAction()
    {
        return $this->renderForm('export');
    }

    /**
     * Renders the Export Submit action
     *
     * @return string
     */
    protected function exportSubmitAction()
    {
        // Sets the post variables
        $uriManager = $this->getUriManager();
        $uriManager->setPostVariables();

        // Gets the form action
        $formAction = $uriManager->getFormActionFromPostVariables();
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
    protected function firstPage()
    {
        $compressedParameters = UriManager::getCompressedParameters();
        $compressedParameters = self::changeCompressedParameters($compressedParameters, 'page', 0);
        UriManager::setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the first page action
     *
     * @return string
     */
    protected function firstPageAction()
    {
        $this->firstPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the first page in edit mode action
     *
     * @return string
     */
    protected function firstPageInEditModeAction()
    {
        $this->firstPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the first page in subform actions
     *
     * @return void
     */
    protected function firstPageInSubform()
    {
        $subformFieldKey = UriManager::getSubformFieldKey();
        SessionManager::setSubformFieldFromSession($subformFieldKey, 'pageInSubform', 0);
    }

    /**
     * Renders the first page in subform action
     *
     * @return string
     */
    protected function firstPageInSubformAction()
    {
        $this->firstPageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders the first page in subform action
     *
     * @return string
     */
    protected function firstPageInSubformInEditModeAction()
    {
        $this->firstPageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the form action
     *
     * @return string
     */
    protected function formAction()
    {
        return $this->renderForm('form');
    }

    /**
     * Renders the form admin action
     *
     * @return string
     */
    protected function formAdminAction()
    {
        return $this->renderForm('formAdmin');
    }

    /**
     * Common code for the last page actions
     *
     * @return void
     */
    protected function lastPage()
    {
        // Creates a querier to get the total rows count
        $querier = GeneralUtility::makeInstance(ListSelectQuerier::class);
        $querier->injectController($this);
        $querier->injectQueryConfiguration();
        $querier->processTotalRowsCountQuery();

        $lastPage = floor(($querier->getTotalRowsCount() - 1) / $this->getExtensionConfigurationManager()->getMaxItems());
        $compressedParameters = UriManager::getCompressedParameters();
        $compressedParameters = self::changeCompressedParameters($compressedParameters, 'page', $lastPage);
        UriManager::setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the last page action
     *
     * @return string
     */
    protected function lastPageAction()
    {
        $this->lastPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the last page in edit mode action
     *
     * @return string
     */
    protected function lastPageInEditModeAction()
    {
        $this->lastPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the last page in subform actions
     *
     * @return void
     */
    protected function lastPageInSubform($view)
    {
        // Gets the subform field key
        $subformFieldKey = UriManager::getSubformFieldKey();

        // Gets the view identifier
        $viewIdentifier = $this->getLibraryConfigurationManager()->getViewIdentifier($view);

        // Gets the view configuration
        $libraryViewConfiguration = $this->getLibraryConfigurationManager()->getViewConfiguration($viewIdentifier);

        // Gets the kickstarter configuration for the subform field key
        $kickstarterFieldConfiguration = $this->getLibraryConfigurationManager()->searchFieldConfiguration($libraryViewConfiguration, $subformFieldKey);

        // Gets the field configuration
        $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
        $fieldConfigurationManager->injectController($this);
        $fieldConfigurationManager->injectKickstarterFieldConfiguration($kickstarterFieldConfiguration);
        $fieldConfiguration = $fieldConfigurationManager->getFieldConfiguration();

        // Adds the uidLocal and the page in the subform
        $fieldConfiguration['uidLocal'] = UriManager::getSubformUidLocal();

        // Builds the querier for the total rows count
        $querier = GeneralUtility::makeInstance(ForeignTableSelectQuerier::class);
        $querier->injectController($this);
        $querier->buildQueryConfigurationForTrueManyToManyRelation($fieldConfiguration);
        $querier->injectQueryConfiguration();
        $querier->processTotalRowsCountQuery();

        // Changes the page in subform
        $lastPage = floor(($querier->getTotalRowsCount() - 1) / $fieldConfiguration['maxsubformitems']);
        SessionManager::setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $lastPage);
    }

    /**
     * Renders the last page in subform action
     *
     * @return string
     */
    protected function lastPageInSubformAction()
    {
        $this->lastPageInSubform('singleView');
        return $this->renderForm('single');
    }

    /**
     * Renders the last page in subform in edit mode action
     *
     * @return string
     */
    protected function lastPageInSubformInEditModeAction()
    {
        $this->lastPageInSubform('editView');
        return $this->renderForm('edit');
    }

    /**
     * Renders the List action
     *
     * @return string
     */
    protected function listAction()
    {
        return $this->renderForm('list');
    }

    /**
     * Renders the List action in edit mode
     *
     * @return string
     */
    protected function listInEditModeAction()
    {
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the next page actions
     *
     * @return void
     */
    protected function nextPage()
    {
        $compressedParameters = UriManager::getCompressedParameters();
        $compressedParameters = self::changeCompressedParameters($compressedParameters, 'page', UriManager::getPage() + 1);
        UriManager::setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the next page action
     *
     * @return string
     */
    protected function nextPageAction()
    {
        $this->nextPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the next page action in edit mode
     *
     * @return string
     */
    protected function nextPageInEditModeAction()
    {
        $this->nextPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the next page in subform actions
     *
     * @return void
     */
    protected function nextPageInSubform()
    {
        $subformFieldKey = UriManager::getSubformFieldKey();
        $pageInSubform = SessionManager::getSubformFieldFromSession($subformFieldKey, 'pageInSubform');
        SessionManager::setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $pageInSubform + 1);
    }

    /**
     * Renders the next page in subform action
     *
     * @return string
     */
    protected function nextPageinSubformAction()
    {
        $this->nextPageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders the next page in subform in edit mode action
     *
     * @return string
     */
    protected function nextPageinSubformInEditModeAction()
    {
        $this->nextPageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the new action
     *
     * @return string
     */
    protected function newAction()
    {
        return $this->renderForm('new');
    }

    /**
     * Renders the new action
     *
     * @return string
     */
    protected function newInSubformAction()
    {
        return $this->renderForm('newInSubform');
    }

    /**
     * Renders the noDisplay action
     *
     * @return string
     */
    protected function noDisplayAction()
    {
        return '';
    }

    /**
     * Common code for the previous page actions
     *
     * @return void
     */
    protected function previousPage()
    {
        $compressedParameters = UriManager::getCompressedParameters();
        $compressedParameters = self::changeCompressedParameters($compressedParameters, 'page', UriManager::getPage() - 1);
        UriManager::setCompressedParameters($compressedParameters);
    }

    /**
     * Renders the previous page action
     *
     * @return string
     */
    protected function previousPageAction()
    {
        $this->previousPage();
        return $this->renderForm('list');
    }

    /**
     * Renders the previous page action in edit mode
     *
     * @return string
     */
    protected function previousPageInEditModeAction()
    {
        $this->previousPage();
        return $this->renderForm('listInEditMode');
    }

    /**
     * Common code for the previous page in subform actions
     *
     * @return string
     */
    protected function previousPageInSubform()
    {
        $subformFieldKey = UriManager::getSubformFieldKey();
        $pageInSubform = SessionManager::getSubformFieldFromSession($subformFieldKey, 'pageInSubform');
        SessionManager::setSubformFieldFromSession($subformFieldKey, 'pageInSubform', $pageInSubform - 1);
    }

    /**
     * Renders the previous page in subform action
     *
     * @return string
     */
    protected function previousPageInSubformAction()
    {
        $this->previousPageInSubform();
        return $this->renderForm('single');
    }

    /**
     * Renders the previous page in subform in edit mode action
     *
     * @return string
     */
    protected function previousPageInSubformInEditModeAction()
    {
        $this->previousPageInSubform();
        return $this->renderForm('edit');
    }

    /**
     * Renders the printInList action
     *
     * @return string
     */
    protected function printInListAction()
    {
        return $this->renderForm('printInList');
    }

    /**
     * Renders the printInSingle action
     *
     * @return string
     */
    protected function printInSingleAction()
    {
        return $this->renderForm('printInSingle');
    }

    /**
     * Renders the save action
     *
     * @return string
     */
    protected function saveAction()
    {
        // Sets the post variables
        $uriManager = $this->getUriManager();
        $uriManager->setPostVariables();
        $this->querier = GeneralUtility::makeInstance(UpdateQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();

        // Processes the query and renders the edit form in case of errors
        $this->viewer = GeneralUtility::makeInstance(EditViewer::class);
        $this->viewer->injectController($this);
        if ($this->querier->processQuery() === false) {
            return $this->renderForm('edit');
        }

        // Gets the form action
        $formAction = $uriManager->getFormActionFromPostVariables();

        if (isset($formAction['saveAndShow'])) {
            return $this->renderForm('single');
        } elseif (isset($formAction['saveAndClose'])) {
            return $this->renderForm('listInEditMode');
        } elseif (isset($formAction['saveAndNew'])) {
            return $this->renderForm('new');
        } elseif (isset($formAction['saveAndNewInSubform'])) {
            // Changes the form action
            $compressedParameters = UriManager::getCompressedParameters();
            $compressedParameters = self::changeCompressedParameters($compressedParameters, 'formAction', 'newInSubform');

            // Gets the compressed string
            $compressedString = key($formAction['saveAndNewInSubform']);
            $uncompressedParameters = self::uncompressParameters($compressedString);

            // Changes the parameters
            foreach ($uncompressedParameters as $parameterKey => $parameter) {
                $compressedParameters = self::changeCompressedParameters($compressedParameters, $parameterKey, $parameter);
            }
            UriManager::setCompressedParameters($compressedParameters);

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
    protected function saveFormAction()
    {
        // Sets the post variables
        $uriManager = $this->getUriManager();
        $uriManager->setPostVariables();

        $this->querier = GeneralUtility::makeInstance(FormUpdateQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->processQuery();

        return $this->renderForm('form');
    }

    /**
     * Renders the save form action
     *
     * @return string
     */
    protected function saveFormAdminAction()
    {
        // Sets the post variables
        $uriManager = $this->getUriManager();
        $uriManager->setPostVariables();

        $this->querier = GeneralUtility::makeInstance(FormAdminUpdateQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->processQuery();

        return $this->renderForm('formAdmin');
    }

    /**
     * Renders the single action
     *
     * @return string
     */
    protected function singleAction()
    {
        SessionManager::clearSubformFromSession();
        return $this->renderForm('single');
    }

    /**
     * Renders the up action
     *
     * @return string
     */
    protected function upInSubformAction()
    {
        $this->querier = GeneralUtility::makeInstance(UpInSubformQuerier::class);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->processQuery();

        // Renders the form in edit mode
        return $this->renderForm('edit');
    }
}
