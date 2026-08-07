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
    protected string $templateFile = 'PrintInList.html';

    /**
     * The template configuration manager
     *
     * @var TemplateConfigurationManager
     */
    protected TemplateConfigurationManager $templateConfigurationManager;

    /**
     * The item count
     *
     * @var int
     */
    protected int $itemCount = 1;

    /**
     * Gets the item template
     *
     * @return string
     */
    protected function getItemTemplate(): string
    {
        // Creates the template configuration manager
        $this->templateConfigurationManager = new (TemplateConfigurationManager::class)($this->controller);
        $this->templateConfigurationManager->setTemplateConfiguration($this->getLibraryConfigurationManager()
            ->getSpecialViewTemplateConfiguration());

        // Retuns the item template
        return $this->templateConfigurationManager->getItemTemplate();
    }

    /**
     * Adds elements to the item list configuration
     *
     * @param int $uid
     *
     * @return array
     */
    protected function additionalListItemConfiguration(int $uid): array
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
     * @return int
     */
    protected function getLastPage(): int
    {
        $lastPage = 0;
        return $lastPage;
    }
}
