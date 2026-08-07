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

namespace YolfTypo3\SavLibraryPlus\Viewers;

use YolfTypo3\SavLibraryPlus\Queriers\ExportSelectQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;

/**
 * Default Export Viewer.
 *
 * @package SavLibraryPlus
 */
class ExportViewer extends AbstractViewer
{
    /**
     * The template file
     *
     * @var string
     */
    protected string $templateFile = 'Export.html';

    /**
     * Checks if the view can be rendered
     *
     * @return bool
     */
    public function viewCanBeRendered(): bool
    {
        $userManager = $this->controller->getUserManager();
        $result = $userManager->userIsAllowedToExportData();

        return $result;
    }

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render(): string
    {
        // Builds the item configuration
        $itemConfiguration = [
            'foreign_table' => ExportSelectQuerier::$exportTableName,
            'whereselect' => 'cid=' . intval($this->controller->getContentObjectRendererDataAttribute('uid')),
            'orderselect' => 'name',
            'overridestartingpoint' => 1
        ];

        // Builds the querier
        $querierClassName = ForeignTableSelectQuerier::class;
        $querier = new ($querierClassName)($this->controller);
        $querier->buildQueryConfigurationForForeignTable($itemConfiguration);
        $querier->setQueryConfiguration();
        $querier->processQuery();

        // Gets the rows
        $rows = $querier->getRows();

        // Builds the option for the configuration selector
        $optionsConfiguration = [];
        foreach ($rows as $row) {
            $optionsConfiguration[$row['uid']] = $row[ExportSelectQuerier::$exportTableName . '.name'];
        }

        // Adds the options for the configuration to the view configuration
        $this->addToViewConfiguration('optionsConfiguration', $optionsConfiguration);

        // Builds the groups for the user
        $optionsGroup = [];
        foreach ($this->controller->getUserManager()->getFrontendUser()->groupData['title'] as $groupKey => $group) {
            $optionsGroup[$groupKey] = $group;
        }

        // Adds the options for the group to the view configuration
        $this->addToViewConfiguration('optionsGroup', $optionsGroup);

        // Adds the export configuration to the view
        $this->addToViewConfiguration('exportConfiguration', $this->controller
            ->getQuerier()
            ->getExportConfiguration());

        // Adds information to the view configuration
        $this->addToViewConfiguration(
            'general',
            [
                'extensionKey' => $this->controller->getExtensionKey(),
                'formName' => $this->controller->getFormName(),
                'userIsAllowedToExportData' => $this->controller
                    ->getUserManager()
                    ->userIsAllowedToExportData(),
                'userIsAllowedToExportDataWithQuery' => $this->controller
                    ->getUserManager()
                    ->userIsAllowedToExportDataWithQuery(),
                'execIsAllowed' => $this->controller
                    ->getExtensionConfigurationManager()
                    ->getAllowExec()
            ]
        );

        // Renders the view
        return $this->renderView();
    }
}
