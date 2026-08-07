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

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\TemplateConfigurationManager;

/**
 * Default PrintInSingle Viewer.
 *
 * @package SavLibraryPlus
 */
class PrintInSingleViewer extends ListViewer
{

    /**
     * The template file
     *
     * @var string
     */
    protected string $templateFile = 'PrintInSingle.html';

    /**
     * The view type
     *
     * @var string
     */
    protected string $viewType = 'SpecialView';

    /**
     * Gets the item template
     *
     * @return string
     */
    protected function getItemTemplate(): string
    {
        // Creates the template configuration manager
        $templateConfigurationManager = new (TemplateConfigurationManager::class)($this->controller);
        $templateConfigurationManager->setTemplateConfiguration($this->getLibraryConfigurationManager()
            ->getSpecialViewTemplateConfiguration());
        $itemTemplate = $templateConfigurationManager->getItemTemplate();

        return $itemTemplate;
    }

    /**
     * Gets the last page
     *
     * @return int
     */
    protected function getLastPage(): int
    {
        $lastPage = 0;
        return $lastPage;
    }

    /**
     * Parses the ###field[]### markers
     *
     * @param string $itemTemplate
     *
     * @return string
     */
    protected function itemTemplatePreprocessor(string $itemTemplate): string
    {
        // Checks if the value must be parsed
        if (strpos($itemTemplate, '#') === false) {
            return $itemTemplate;
        }

        // Processes the field marker
        $matches = [];
        preg_match_all('/###field\[(?<fieldName>[^\],]+)(?<separator>,?)(?<label>[^\]]*)\]###/', $itemTemplate, $matches);

        foreach ($matches[0] as $matchKey => $match) {

            // Gets the crypted full field name
            $fullFieldName = $this->controller
                ->getQuerier()
                ->buildFullFieldName($matches['fieldName'][$matchKey]);
            $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

            // Processes the field
            if ($matches['separator'][$matchKey]) {
                if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['cutDivItemInner']) {
                    $replacementString = '';
                } else {
                    $replacementString = '<div class="printCol1">$$$label[' . $matches['label'][$matchKey] . ']$$$</div>' . '<div class="printCol2">###render[' . $matches['fieldName'][$matchKey] . ']###</div>';
                }
            } else {
                $replacementString = '###render[' . $matches['fieldName'][$matchKey] . ']###';
            }
            $itemTemplate = str_replace($matches[0][$matchKey], $replacementString, $itemTemplate);
        }

        $itemTemplate = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($itemTemplate, false);

        return $itemTemplate;
    }
}
