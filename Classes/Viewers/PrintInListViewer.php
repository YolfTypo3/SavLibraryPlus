<?php
namespace YolfTypo3\SavLibraryPlus\Viewers;

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
use YolfTypo3\SavLibraryPlus\Managers\TemplateConfigurationManager;

/**
 * Default PrintInList Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
     * @return array
     */
    protected function additionalListItemConfiguration()
    {
        $itemsBeforeFirstPageBreak = $this->templateConfigurationManager->getItemsBeforeFirstPageBreak();
        $itemsBeforePageBreak = $this->templateConfigurationManager->getItemsBeforePageBreak();

        $pageBreak = FALSE;

        if (! empty($itemsBeforeFirstPageBreak) && $this->itemCount == $itemsBeforeFirstPageBreak) {
            $this->itemCount = $itemsBeforePageBreak;
        }

        if (! empty($itemsBeforePageBreak) && ($this->itemCount % $itemsBeforePageBreak) == 0) {
            $pageBreak = TRUE;
        }

        $this->itemCount ++;

        $additionalListItemConfiguration = array(
            'pageBreak' => $pageBreak
        );

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
