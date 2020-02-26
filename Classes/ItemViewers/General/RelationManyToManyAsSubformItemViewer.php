<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\Controller;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
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
    protected function renderItem()
    {
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
        // Checks if an uidForeign value was sent by the uri (for example by makeExtLink
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
        $subformConfiguration = $this->getItemConfiguration('subform');
        if ($subformConfiguration === null) {
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
        $viewer->addToViewConfiguration('general', [
                'subformFieldKey' => AbstractController::cryptTag($fullFieldName),
                'subformUidLocal' => $this->getItemConfiguration('uid'),
                'pageInSubform' => $pageInSubform,
                'maximumItemsInSubform' => $this->getItemConfiguration('maxsubformitems'),
                'showFirstLastButtons' => ($this->getItemConfiguration('nofirstlast') ? 0 : 1),
                'title' => $controller->getViewer()
                    ->processTitle($subformTitle)
            ]
        );

        $content = $viewer->render();

        return $content;
    }
}
?>
