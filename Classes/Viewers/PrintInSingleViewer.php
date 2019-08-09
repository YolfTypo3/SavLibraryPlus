<?php
namespace YolfTypo3\SavLibraryPlus\Viewers;

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
        if (strpos($itemTemplate, '#') === false) {
            return $itemTemplate;
        }

        // Processes the field marker
        $matches = [];
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
            ->parseLocalizationTags($itemTemplate, false);

        return $itemTemplate;
    }
}
?>
