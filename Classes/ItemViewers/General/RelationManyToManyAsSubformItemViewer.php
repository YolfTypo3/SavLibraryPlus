<?php
namespace SAV\SavLibraryPlus\ItemViewers\General;

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
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Controller\Controller;
use SAV\SavLibraryPlus\Managers\UriManager;
use SAV\SavLibraryPlus\Controller\FlashMessages;
use SAV\SavLibraryPlus\Managers\SessionManager;
use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use SAV\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;
use SAV\SavLibraryPlus\Viewers\SubformSingleViewer;

/**
 * General RelationManyToManyAsSubform item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class RelationManyToManyAsSubformItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item
     *
     * @return string The rendered item
     */
    protected function renderItem()
    {
        $htmlArray = array();

        // Builds the crypted field Name
        $fullFieldName = $this->getItemConfiguration('tableName') . '.' . $this->getItemConfiguration('fieldName');
        $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

        // Creates the controller
        $controller = GeneralUtility::makeInstance(Controller::class);
        $extensionConfigurationManager = $controller->getExtensionConfigurationManager();
        $extensionConfigurationManager->injectExtension($this->getController()
            ->getExtensionConfigurationManager()
            ->getExtension());
        $extensionConfigurationManager->injectTypoScriptConfiguration(ExtensionConfigurationManager::getTypoScriptConfiguration());
        $controller->initialize();

        // Builds the querier
        $querier = GeneralUtility::makeInstance(ForeignTableSelectQuerier::class);
        $controller->injectQuerier($querier);
        $querier->injectController($controller);
        $this->itemConfiguration['uidLocal'] = $this->itemConfiguration['uid'];
        // Checks if and uidForeign value was sent by the uri (for example by makeExtLink
        $this->itemConfiguration['uidForeign'] = UriManager::getSubformUidForeign();
        // Sets the page in the subform
        $pageInSubform = SessionManager::getSubformFieldFromSession($cryptedFullFieldName, 'pageInSubform');
        $pageInSubform = ($pageInSubform ? $pageInSubform : 0);
        $this->itemConfiguration['pageInSubform'] = $pageInSubform;
        // Builds the query
        if ($this->getItemConfiguration('norelation')) {
            $querier->buildQueryConfigurationForSubformWithNoRelation($this->itemConfiguration);
        } else {
            $querier->buildQueryConfigurationForTrueManyToManyRelation($this->itemConfiguration);
        }
        $querier->injectParentQuerier($this->getController()
            ->getQuerier());
        $querier->injectQueryConfiguration();
        $querier->processTotalRowsCountQuery();
        $querier->processQuery();

        // Calls the viewer
        $viewer = GeneralUtility::makeInstance(SubformSingleViewer::class);
        $controller->injectViewer($viewer);
        $viewer->injectController($controller);
        $viewer->setJpGraphCounter($this->getController()
            ->getViewer()
            ->getJpGraphCounter());
        $subformConfiguration = $this->getItemConfiguration('subform');
        if ($subformConfiguration === NULL) {
            FlashMessages::addError('error.noFieldSelectedInSubForm');
        }
        $viewer->injectLibraryViewConfiguration($subformConfiguration);

        // Gets the subform title
        $subformTitle = $this->getItemConfiguration('subformtitle');
        if (empty($subformTitle)) {
            // Gets the label cutter
            $cutLabel = $this->getItemConfiguration('cutlabel');
            if (empty($cutLabel)) {
                $subformTitle = $this->getItemConfiguration('label');
            }
        }

        // Sets the view configuration
        $fullFieldName = $this->getItemConfiguration('tableName') . '.' . $this->getItemConfiguration('fieldName');
        $viewer->addToViewConfiguration('general', array(
            'subformFieldKey' => AbstractController::cryptTag($fullFieldName),
            'subformUidLocal' => $this->getItemConfiguration('uid'),
            'pageInSubform' => $pageInSubform,
            'maximumItemsInSubform' => $this->getItemConfiguration('maxsubformitems'),
            'showFirstLastButtons' => ($this->getItemConfiguration('nofirstlast') ? 0 : 1),
            'title' => $controller->getViewer()
                ->processTitle($subformTitle)
        ));

        $content = $viewer->render();

        return $content;
    }
}
?>
