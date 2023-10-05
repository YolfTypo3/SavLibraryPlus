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

namespace YolfTypo3\SavLibraryPlus\Viewers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\TemplateConfigurationManager;

/**
 * Default List Viewer.
 *
 * @package SavLibraryPlus
 */
class ListViewer extends AbstractViewer
{

    /**
     * Item viewer directory
     *
     * @var string
     */
    protected $itemViewerDirectory = self::DEFAULT_ITEM_VIEWERS_DIRECTORY;

    /**
     * Edit mode flag
     *
     * @var boolean
     */
    protected $inEditMode = false;

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'List.html';

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType = 'ListView';

    /**
     * The previous folder fields configuration
     *
     * @var array
     */
    protected $previousFolderFieldsConfiguration = [];

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render()
    {
        // Sets the library view configuration
        $this->setLibraryViewConfiguration();

        // Sets the active folder Key
        $this->setActiveFolderKey();

        // Gets the item template
        $itemTemplate = $this->getItemTemplate();
        if (empty($itemTemplate)) {
            FlashMessages::addError('error.itemTemplateMissingInListView');
            return $this->renderView();
        }

        // Creates the field configuration manager
        $this->createFieldConfigurationManager();

        // Processes the rows
        $rows = $this->getController()
            ->getQuerier()
            ->getRows();

        $fields = [];
        foreach ($rows as $rowKey => $row) {

            $this->getController()
                ->getQuerier()
                ->setCurrentRowId($rowKey);

            // Gets the fields configuration for the folder
            $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder(), true, true);

            $listItemConfiguration = array_merge($this->parseItemTemplate($itemTemplate), [
                'uid' => $row['uid']
            ]);
            // Additional list item configuration
            $listItemConfiguration = array_merge($listItemConfiguration, $this->additionalListItemConfiguration($row['uid']));
            $fields[] = $listItemConfiguration;

            $this->previousFolderFieldsConfiguration = $this->folderFieldsConfiguration;
        }

        // Adds the fields configuration
        $this->addToViewConfiguration('fields', $fields);

        // Adds information to the view configuration
        $this->addToViewConfiguration('general', [
            'extensionKey' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getExtensionKey(),
            'userIsAllowedToInputData' => $this->getController()
                ->getUserManager()
                ->userIsAllowedToInputData(),
            'userIsAllowedToExportData' => $this->getController()
                ->getUserManager()
                ->userIsAllowedToExportData(),
            'helpPage' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getHelpPageForListView(),
            'addPrintIcon' => $this->getActiveFolderField('addPrintIcon'),
            'page' => $this->getCurrentPage(),
            'lastPage' => $this->getLastPage(),
            'pages' => $this->getPages(),
            'title' => $this->processTitle($this->parseTitle($this->getActiveFolderTitle()))
        ]);

        // Additional view configuration if no rows are returned by the querier
        $this->additionalViewConfigurationIfNoRows();

        // Additional view configuration
        $this->additionalViewConfiguration();

        return $this->renderView();
    }

    /**
     * Gets the item template
     *
     * @return array
     */
    protected function getItemTemplate()
    {
        // Creates the template configuration manager
        $templateConfigurationManager = GeneralUtility::makeInstance(TemplateConfigurationManager::class);
        $templateConfigurationManager->injectTemplateConfiguration($this->getLibraryConfigurationManager()
            ->getListViewTemplateConfiguration());
        $itemTemplate = $templateConfigurationManager->getItemTemplate();

        return $itemTemplate;
    }

    /**
     * Gets the current page
     *
     * @return integer
     */
    protected function getCurrentPage()
    {
        $currentPage = UriManager::getPage();
        return $currentPage;
    }

    /**
     * Gets the last page
     *
     * @return integer
     */
    protected function getLastPage()
    {
        $maxItems = $this->getController()
            ->getExtensionConfigurationManager()
            ->getMaxItems();
        if (empty($maxItems)) {
            $lastPage = 0;
        } else {
            $lastPage = floor(($this->getController()
                ->getQuerier()
                ->getTotalRowsCount() - 1) / $maxItems);
        }
        return $lastPage;
    }

    /**
     * Gets the pages
     *
     * @return array
     */
    protected function getPages()
    {
        $currentPage = $this->getCurrentPage();
        $lastPage = $this->getLastPage();
        $maxPages = $this->getController()
            ->getExtensionConfigurationManager()
            ->getMaxPages();
        $pages = [];
        for ($i = min($currentPage, max(0, $lastPage - $maxPages)); $i <= min($lastPage, $currentPage + $maxPages - 1); $i ++) {
            $pages[$i] = $i + 1;
        }
        return $pages;
    }

    /**
     * Adds elements to the item list configuration
     *
     * @param integer $uid
     *
     * @return array
     */
    protected function additionalListItemConfiguration($uid)
    {
        return [];
    }

    /**
     * Adds elements to the view configuration
     *
     * @return void
     */
    protected function additionalViewConfiguration()
    {}

    /**
     * Parses the item template
     *
     * @param string $itemTemplate
     *            The item template
     *
     * @return string The item configuration
     */
    protected function parseItemTemplate($itemTemplate)
    {
        // Pre-processes the item template
        if (method_exists($this, 'itemTemplatePreprocessor')) {
            $itemTemplate = $this->itemTemplatePreprocessor($itemTemplate);
        }

        $itemConfiguration = [];
        // Initalizes the fields from the folder configuration to possibly use them as fluid variables
        $fields = [];
        foreach ($this->folderFieldsConfiguration as $fieldConfiguration) {
            $fields[$fieldConfiguration['fieldName']] = $fieldConfiguration;
        }

        // Gets the querier
        $querier = $this->getController()->getQuerier();

        // Gets the tags
        $matches = [];
        preg_match_all('/###(?<render>render\[)?(?<fullFieldName>(?<TableNameOrAlias>[^\.#\]]+)\.?(?<fieldName>[^#\]]*))\]?###/', $itemTemplate, $matches);

        // Sets the default class item
        $classItem = 'item';
        foreach ($matches[0] as $matchKey => $match) {

            // Gets the crypted full field name
            $fullFieldName = $this->getController()
                ->getQuerier()
                ->buildFullFieldName($matches['fullFieldName'][$matchKey]);
            $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

            // Checks if the configuration exists for the field name
            if (is_null($this->folderFieldsConfiguration[$cryptedFullFieldName])) {
                FlashMessages::addError('error.unknownFieldName', [
                    $fullFieldName
                ]);
            }

            // Checks if the value must be cut
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['cutDivItemInner']) {
                $value = '';
            } else {
                // It's a full field name, i.e. tableName.fieldName, without render
                if ($matches['fieldName'][$matchKey] && empty($matches['render'][$matchKey])) {
                    $value = $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'];
                } else {
                    $value = $this->renderItem($cryptedFullFieldName);
                }
            }

            // Sets the class item
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['classItem'] != 'item') {
                $classItem = $this->folderFieldsConfiguration[$cryptedFullFieldName]['classItem'];
            }

            // Processes the cutIfSameAsPrevious attribute if any
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['cutifsameasprevious'] ?? false) {
                if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] == $this->previousFolderFieldsConfiguration[$cryptedFullFieldName]['value']) {
                    $value = '';
                    $classItem = 'item';
                }
            }

            // Renders the item
            $itemTemplate = str_replace($matches[0][$matchKey], $value, $itemTemplate);

            // Sets the field configuration for the fluid processings
            if ($matches['fieldName'][$matchKey]) {
                $fields[$matches['TableNameOrAlias'][$matchKey]] = [
                    $matches['fieldName'][$matchKey] => $this->folderFieldsConfiguration[$cryptedFullFieldName]
                ];
            } else {
                $fields[$matches['fullFieldName'][$matchKey]] = $this->folderFieldsConfiguration[$cryptedFullFieldName];
            }
        }

        // Creates a view for FLUID processings of the template
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateSource($itemTemplate);

        // Assigns the field configuration and renders the view
        $view->assign('field', $fields);
        $itemTemplate = $view->render();

        // Sets the class item
        $itemConfiguration = [
            'classItem' => $classItem,
            'template' => $itemTemplate
        ];

        return $itemConfiguration;
    }

    /**
     * Parses the item template
     *
     * @param string $itemTemplate
     *            The item template
     *
     * @return string The parsed item template
     */
    protected function parseTitle($title)
    {
        // Replaces the tags in the title by $$$label[tag]$$$
        $matches = [];
        preg_match_all('/###(?<link>linkDefault|link)?(?:\[)?(?<fullFieldName>(?<TableNameOrAlias>[^\.#\]]+)[\.]?(?<fieldName>[^#\]]*))(?:\])?###/', $title, $matches);

        // Processes the matched information
        foreach ($matches[0] as $matchKey => $match) {

            // Gets the field configuration
            if ($matches['link'][$matchKey]) {
                // It is tag for a simple link with no ordering

                // Gets the extension key
                $extensionKey = $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getExtensionKey();

                // Gets the label
                $label = LocalizationUtility::translate($matches['fullFieldName'][$matchKey], $extensionKey);
                if (empty($label)) {
                    $label = $matches['fullFieldName'][$matchKey];
                }

                // Sets the field configuration
                $fieldConfiguration = [
                    'orderlinkintitle' => 1,
                    'linkwithnoordering' => 1,
                    'orderlinkintitlesetup' => ':' . $matches['link'][$matchKey] . ':',
                    'labelAsc' => $label,
                    'labelDesc' => $label,
                    'fieldName' => $matches['fullFieldName'][$matchKey]
                ];
            } else {
                // Gets the crypted full field name
                $fullFieldName = $this->getController()
                    ->getQuerier()
                    ->buildFullFieldName($matches['fullFieldName'][$matchKey]);
                $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

                // Gets the field configuration
                $fieldConfiguration = $this->folderFieldsConfiguration[$cryptedFullFieldName] ?? null;
            }

            // Checks if an order link in title is set
            if ($fieldConfiguration['orderlinkintitle'] ?? false) {
                $replacementString = $this->processLink($fieldConfiguration);
            } else {
                $replacementString = '$$$label[' . $matches['fullFieldName'][$matchKey] . ']$$$';
            }
            $title = str_replace($matches[0][$matchKey], $replacementString, $title);
        }

        return $title;
    }

    /**
     * Processes the link
     *
     * @param array $fieldConfiguration
     *            The field configuration
     *
     * @return string
     */
    protected function processLink($fieldConfiguration)
    {
        $replacementString = '';

        // Gets the query configuration manager
        $queryConfigurationManager = $this->getController()
            ->getQuerier()
            ->getQueryConfigurationManager();

        // Builds the field name and full field name
        $fieldName = $fieldConfiguration['fieldName'];
        $fieldNameParts = explode(',', $fieldName);
        $fullFieldName = (($fieldConfiguration['tableName'] ?? false) ? $fieldConfiguration['tableName'] . '.' . $fieldName : $fieldName);

        // Gets the ascending whereTag Key
        $order = (($fieldConfiguration['linkwithnoordering'] ?? false) ? '' : '+');
        $whereTagAscendingOrderKey = AbstractController::cryptTag($fullFieldName . $order);
        if ($queryConfigurationManager->getWhereTag($whereTagAscendingOrderKey) == null) {
            $fieldName = trim($fieldNameParts[0]);
            if(empty($fieldConfiguration['label'])) {
                $fieldConfiguration['labelAsc'] = $fieldName;
            } else {
                $fieldConfiguration['labelAsc'] = $fieldConfiguration['label'];
            }
            $whereTagAscendingOrderKey = AbstractController::cryptTag($fieldName . $order);
        }
        if ($queryConfigurationManager->getWhereTag($whereTagAscendingOrderKey) == null) {
            FlashMessages::addError('error.noWhereTag', [
                $fullFieldName . $order,
                $fieldName . $order
            ]);
        }

        // Gets the descending whereTag Key
        $order = (($fieldConfiguration['linkwithnoordering'] ?? false) ? '' : '-');
        $whereTagDescendingOrderKey = AbstractController::cryptTag($fullFieldName . $order);
        if ($queryConfigurationManager->getWhereTag($whereTagDescendingOrderKey) == null) {
            $fieldName = (empty($fieldNameParts[1]) ? trim($fieldNameParts[0]) : trim($fieldNameParts[1]));
            if(empty($fieldConfiguration['label'])) {
                $fieldConfiguration['labelDesc'] = $fieldName;
            } else {
                $fieldConfiguration['labelDesc'] = $fieldConfiguration['label'];
            }
            $whereTagDescendingOrderKey = AbstractController::cryptTag($fieldName . $order);
        }
        if ($queryConfigurationManager->getWhereTag($whereTagDescendingOrderKey) == null) {
            FlashMessages::addError('error.noWhereTag', [
                $fullFieldName . $order,
                $fieldName . $order
            ]);
        }

        // Sets the default pattern for the display
        if (! isset($fieldConfiguration['orderlinkintitlesetup'])) {
            $fieldConfiguration['orderlinkintitlesetup'] = ':link:';
        }
        $orderLinksInTitle = explode(':', $fieldConfiguration['orderlinkintitlesetup']);

        foreach ($orderLinksInTitle as $orderLinkInTitle) {
            if ($orderLinkInTitle) {
                // Creates the view
                $view = GeneralUtility::makeInstance(StandaloneView::class);
                $view->setTemplatePathAndFilename($this->getPartialRootPath() . '/TitleBars/OrderLinks/' . ucfirst($orderLinkInTitle) . '.html');

                // Assigns the view configuration
                $view->assign('field', [
                    'label'=> $fieldConfiguration['label'] ?? '',
                    'value' => $fieldConfiguration['label'] ?? '',
                    'valueAsc' => $fieldConfiguration['labelAsc'],
                    'valueDesc' => $fieldConfiguration['labelDesc'],
                    'whereTagAscendingOrderKey' => $whereTagAscendingOrderKey,
                    'whereTagDescendingOrderKey' => $whereTagDescendingOrderKey,
                    'whereTagKey' => UriManager::getWhereTagKey(),
                    'inEditMode' => ($this->inEditMode ? 'InEditMode' : '')
                ]);

                // Gets the link configuration
                $linkConfiguration = $this->getLinkConfiguration();
                $view->assign('configuration', [
                    'general' => [
                        'additionalParams' => AbstractController::convertLinkAdditionalParametersToArray($linkConfiguration['additionalParams'] ?? '')
                    ]
                ]);

                $replacementString .= $view->render();
            }
        }
        return $replacementString;
    }

    /**
     * Additional view configuration if no rows are returned by the querier
     *
     * @return void
     */
    protected function additionalViewConfigurationIfNoRows()
    {
        // Gets the rows count
        $rowsCount = $this->getController()
            ->getQuerier()
            ->getRowsCount();

        // Builds the message when the rows count is equal to zero
        if ($rowsCount == 0) {
            switch ($this->getController()
                ->getExtensionConfigurationManager()
                ->getShowNoAvailableInformation()) {
                case ExtensionConfigurationManager::SHOW_MESSAGE:
                    $this->addToViewConfiguration('general', [
                        'message' => FlashMessages::translate('general.noAvailableInformation')
                    ]);
                    break;
                case ExtensionConfigurationManager::DO_NOT_SHOW_EXTENSION:
                    $this->addToViewConfiguration('general', [
                        'hideExtension' => true
                    ]);
                    break;
            }
        }
    }
}
