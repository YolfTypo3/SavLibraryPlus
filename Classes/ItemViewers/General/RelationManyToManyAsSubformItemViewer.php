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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;
use YolfTypo3\SavLibraryPlus\Viewers\SubformSingleViewer;

/**
 * General RelationManyToManyAsSubform item Viewer.
 *
 * @package SavLibraryPlus
 */
class RelationManyToManyAsSubformItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item
     *
     * @return string The rendered item
     */
    protected function renderItem(): string
    {
        // Builds the crypted field Name
        $fullFieldName = $this->getItemConfigurationAttribute('tableName') . '.' . $this->getItemConfigurationAttribute('fieldName');
        $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

        // Creates the controller
        $controller = new (get_class($this->controller));
        $controller->setRequest($this->controller->getRequest());
        $controller->setContentObjectRenderer($this->controller->getContentObjectRenderer());
        $controller->initialize($this->controller->getPluginTypoScriptConfiguration($this->controller->getExtensionKey()));
        
        // Builds the querier
        $querier = new (ForeignTableSelectQuerier::class)($controller);
        $controller->setQuerier($querier);
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
        $querier->setParentQuerier($this->controller
            ->getQuerier());
        $querier->setQueryConfiguration();
        $querier->processTotalRowsCountQuery();
        $querier->processQuery();
        
        // Calls the viewer
        $viewer = new (SubformSingleViewer::class)($controller);
        $controller->setViewer($viewer);

        $subformConfiguration = $this->getItemConfigurationAttribute('subform');
        if ($subformConfiguration === null) {
            FlashMessages::addError('error.noFieldSelectedInSubForm');
        }
        $viewer->setLibraryViewConfiguration($subformConfiguration);

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
        $fullFieldName = $this->getItemConfigurationAttribute('tableName') . '.' . $this->getItemConfigurationAttribute('fieldName');
        $viewer->addToViewConfiguration('general', [
            'subformFieldKey' => AbstractController::cryptTag($fullFieldName),
            'subformUidLocal' => $this->getItemConfigurationAttribute('uid'),
            'pageInSubform' => $pageInSubform,
            'maximumItemsInSubform' => $this->getItemConfigurationAttribute('maxsubformitems'),
            'showFirstLastButtons' => ($this->getItemConfigurationAttribute('nofirstlast') ? 0 : 1),
            'title' => $controller->getViewer()
                ->processTitle($subformTitle)
        ]);

        $content = $viewer->render();

        return $content;
    }
}
