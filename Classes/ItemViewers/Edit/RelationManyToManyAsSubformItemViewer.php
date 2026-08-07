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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Viewers\SubformEditViewer;

/**
 * Edit RelationManyToManyAsSubform item Viewer.
 *
 * @package SavLibraryPlus
 */
class RelationManyToManyAsSubformItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        $htmlArray = [];

        // Builds the crypted field Name
        $fullFieldName = $this->getItemConfigurationAttribute('tableName') . '.' . $this->getItemConfigurationAttribute('fieldName');
        $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

        // Creates the controller
        $controller = new (get_class($this->controller));
        $controller->setRequest($this->controller->getRequest());
        $controller->setContentObjectRenderer($this->controller->getContentObjectRenderer());
        $controller->initialize($this->controller->getPluginTypoScriptConfiguration($this->controller->getExtensionKey()));
        
        $uriManager = $controller->getUriManager();
        if ($uriManager->hasLibraryParameter()) {
            $uriManager->setGetVariables();
        }
        
        // Gets the maximum item number in the subform (must be called before the querier to process deprecated maxsubitems attribute)
        $maxSubformItems = $this->getMaximumItemsInSubform();

        // Builds the querier
        $querier = new (ForeignTableSelectQuerier::class)($controller);
        $controller->setQuerier($querier);
        $querier->setUpdateQuerier($this->controller
            ->getQuerier()
            ->getUpdateQuerier());
        $querier->setParentQuerier($this->controller
            ->getQuerier());
        $this->itemConfiguration['uidLocal'] = $this->itemConfiguration['uid'];

        // Checks if an uidForeign value was sent by the uri (for example by makeExtLink)
        $subformUidForeignInLink = $this->controller->getUriManager()->getSubformUidForeignInLink();
        if ($subformUidForeignInLink) {
            $this->itemConfiguration['uidForeign'] = $subformUidForeignInLink;
        }

        // Sets the page in the subform
        $pageInSubform = $this->controller->getSessionManager()->getSubformFieldFromSession($cryptedFullFieldName, 'pageInSubform');
        $pageInSubform = ($pageInSubform ? $pageInSubform : 0);
        $this->itemConfiguration['pageInSubform'] = $pageInSubform;

        // Builds the query
        if ($this->getItemConfigurationAttribute('norelation')) {
            $querier->buildQueryConfigurationForSubformWithNoRelation($this->itemConfiguration);
        } else {
            $querier->buildQueryConfigurationForTrueManyToManyRelation($this->itemConfiguration);
        }
        $querier->setQueryConfiguration();
        $querier->processTotalRowsCountQuery();

        // Gets the rows count
        $totalRowsCount = $querier->getTotalRowsCount();

        // Checks if the maximum number of relations is reached
        if ($totalRowsCount < $this->getItemConfigurationAttribute('maxitems')) {
            $newButtonIsAllowed = true;
        } else {
            $newButtonIsAllowed = false;
        }

        // Processes the query
        //$uriManager = $this->controller->getUriManager();
        if ($uriManager->getFormAction() == 'newInSubform' && $uriManager->getSubformFieldKey() == $cryptedFullFieldName) {
            if ($uriManager->getSubformUidLocal() == $this->itemConfiguration['uidLocal']) {
                $isNewInSubform = true;
                $querier->addEmptyRow();
            } else {
                return '';
            }
        } else {
            $isNewInSubform = false;
            $querier->processQuery();
        }

        // Calls the viewer
        $viewer = new (SubformEditViewer::class)($controller);
        $viewer->setIsNewView($isNewInSubform);
        $controller->setViewer($viewer);
        $subformConfiguration = $this->getItemConfigurationAttribute('subform');

        if ($subformConfiguration === null) {
            FlashMessages::addError('error.noFieldSelectedInSubForm');
        }
        $viewer->setLibraryViewConfiguration($subformConfiguration);

        // Adds the hidden element
        $htmlArray[] = HtmlElements::htmlInputHiddenElement([
            HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
            HtmlElements::htmlAddAttribute('value', $this->getItemConfigurationAttribute('value'))
        ]);

        // Gets the subform title
        $subformTitle = $this->getItemConfigurationAttribute('subformtitle');
        if (empty($subformTitle)) {
            // Gets the label cutter
            $cutLabel = $this->getItemConfigurationAttribute('cutlabel');
            if (empty($cutLabel)) {
                $subformTitle = $this->getItemConfigurationAttribute('label');
            }
        }

        // Sets the view configuration
        $deleteButtonIsAllowed = $this->getItemConfigurationAttribute('adddelete') || $this->getItemConfigurationAttribute('adddeletebutton');
        $upDownButtonIsAllowed = ($this->getItemConfigurationAttribute('addupdown') || $this->getItemConfigurationAttribute('addupdownbutton')) && ($this->getItemConfigurationAttribute('maxitems') > 1);
        $saveButtonIsAllowed = $this->getItemConfigurationAttribute('addsave') || $this->getItemConfigurationAttribute('addsavebutton');
        $viewer->addToViewConfiguration('general', [
            'newButtonIsAllowed' => $newButtonIsAllowed,
            'deleteButtonIsAllowed' => ($isNewInSubform === false) && $deleteButtonIsAllowed && ! $viewer->errorsInNewRecord(),
            'upDownButtonIsAllowed' => ($isNewInSubform === false) && $upDownButtonIsAllowed,
            'saveButtonIsAllowed' => ($isNewInSubform === false) && $saveButtonIsAllowed,
            'subformFieldKey' => $cryptedFullFieldName,
            'subformUidLocal' => $this->getItemConfigurationAttribute('uid'),
            'pageInSubform' => $pageInSubform,
            'maximumItemsInSubform' => $maxSubformItems,
            'showFirstLastButtons' => ($this->getItemConfigurationAttribute('nofirstlast') ? 0 : 1),
            'title' => $controller->getViewer()
                ->processTitle($subformTitle),
            'saveAndNew' => array_key_exists($this->getItemConfigurationAttribute('foreign_table'), $this->controller
                ->getLibraryConfigurationManager()
                ->getGeneralConfigurationField('saveAndNew')) && ($this->getItemConfigurationAttribute('maxitems') > 1)
        ]);

        // Adds the javascript to confirm the delete action
        if ($deleteButtonIsAllowed) {
            AdditionalHeaderManager::addConfirmDeleteJavaScript('subformItem');
        }

        $htmlArray[] = $viewer->render();

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Gets the maximum number of items in a subform.
     *
     * @return int
     */
    protected function getMaximumItemsInSubform(): int
    {
        // Checks if the deprecated "maxsubitems" attribute is used
        $maxSubItems = $this->getItemConfigurationAttribute('maxsubitems');
        if (! empty($maxSubItems)) {
            // Replaces it by the "maxsubformitems" attribute
            $this->itemConfiguration['maxsubformitems'] = $maxSubItems;
            unset($this->itemConfiguration['maxsubitems']);
        }
        $maxSubformItems = $this->getItemConfigurationAttribute('maxsubformitems');
        if (! empty($maxSubformItems)) {
            return intval($maxSubformItems);
        } else {
            return 0;
        }
    }
}
