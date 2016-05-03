<?php
namespace SAV\SavLibraryPlus\Viewers;

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
use SAV\SavLibraryPlus\Managers\TemplateConfigurationManager;

/**
 * Default PrintInSingle Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class PrintInSingleViewer extends ListViewer
{

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'PrintInSingle.html';

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType = 'SpecialView';

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
            ->getSpecialViewTemplateConfiguration());
        $itemTemplate = $templateConfigurationManager->getItemTemplate();

        return $itemTemplate;
    }

    /**
     * Gets the last page
     *
     * @return integer
     */
    protected function getLastPage()
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
    protected function itemTemplatePreprocessor($itemTemplate)
    {
        // Checks if the value must be parsed
        if (strpos($itemTemplate, '#') === FALSE) {
            return $template;
        }

        // Processes the field marker
        preg_match_all('/###field\[(?<fieldName>[^\],]+)(?<separator>,?)(?<label>[^\]]*)\]###/', $itemTemplate, $matches);

        foreach ($matches[0] as $matchKey => $match) {

            // Gets the crypted full field name
            $fullFieldName = $this->getController()
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

        $itemTemplate = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($itemTemplate, FALSE);

        return $itemTemplate;
    }
}
?>
