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
use YolfTypo3\SavLibraryPlus\Managers\TemplateConfigurationManager;

/**
 * Default PrintInList Viewer.
 *
 * @package SavLibraryPlus
 */
class PrintInListViewer extends ListViewer
{
    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'PrintInList.html';

    /**
     * The template configuration manager
     *
     * @var \YolfTypo3\SavLibraryPlus\Managers\TemplateConfigurationManager
     */
    protected $templateConfigurationManager;

    /**
     * The item count
     *
     * @var integer
     */
    protected $itemCount = 1;

    /**
     * Gets the item template
     *
     * @return array
     */
    protected function getItemTemplate()
    {
        // Creates the template configuration manager
        $this->templateConfigurationManager = GeneralUtility::makeInstance(TemplateConfigurationManager::class);
        $this->templateConfigurationManager->injectTemplateConfiguration($this->getLibraryConfigurationManager()
            ->getSpecialViewTemplateConfiguration());

        // Retuns the item template
        return $this->templateConfigurationManager->getItemTemplate();
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
        $itemsBeforeFirstPageBreak = $this->templateConfigurationManager->getItemsBeforeFirstPageBreak();
        $itemsBeforePageBreak = $this->templateConfigurationManager->getItemsBeforePageBreak();

        $pageBreak = false;

        if (! empty($itemsBeforeFirstPageBreak) && $this->itemCount == $itemsBeforeFirstPageBreak) {
            $this->itemCount = $itemsBeforePageBreak;
        }

        if (! empty($itemsBeforePageBreak) && ($this->itemCount % $itemsBeforePageBreak) == 0) {
            $pageBreak = true;
        }

        $this->itemCount ++;

        $additionalListItemConfiguration = [
            'pageBreak' => $pageBreak
        ];

        return $additionalListItemConfiguration;
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
}
?>
