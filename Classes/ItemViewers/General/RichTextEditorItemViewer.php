<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;

/**
 * General Rich text editor item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class RichTextEditorItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $content = html_entity_decode(stripslashes($this->getItemConfiguration('value')), ENT_QUOTES, $GLOBALS['TSFE']->renderCharset);
        $rteTSConfig = BackendUtility::getPagesTSconfig(0);
        $processedRteConfiguration = BackendUtility::RTEsetup($rteTSConfig['RTE.'], '', '');
        $parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
        $parseHTML->init();
        // Checks if the method setRelPath exists because it was removed in TYPO3 8
        if(method_exists($parseHTML, 'setRelPath')) {
            $parseHTML->setRelPath('');
        }
        if (version_compare(TYPO3_version, '7.0', '<')) {
            $specConfParts = BackendUtility::getSpecConfParts('richtext[]:rte_transform[mode=ts_css]', '');
        } else {
            $specConfParts = BackendUtility::getSpecConfParts('richtext[]:rte_transform[mode=ts_css]');
        }
        $content = $parseHTML->RTE_transform($content, $specConfParts, 'rte', $processedRteConfiguration);

        // Adds the content
        $htmlArray[] = HtmlElements::htmlDivElement(array(
            HtmlElements::htmlAddAttribute('class', 'richText')
        ), $content);

        return $this->arrayToHTML($htmlArray);
    }
}
?>
