<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\RichTextEditor;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compatibility for the rich text editor
 *
 * @extensionScannerIgnoreFile
 * @package SavLibraryPlus
 */
class RichTextEditorCompatibility
{
    /**
     * Gets the rich text editor item viewer
     *
     * This method is used in:
     *  \YolfTypo3\SavLibraryPlus\ItemViewers\Edit\RichTextEditorItemViewer
     *
     * @todo Will be removed in TYPO3 v10
     */
    public static function getRichTextEditorItemViewer()
    {
        if (version_compare(TYPO3_version, '8.0', '<')) {
            $richTextEditorItemViewer = GeneralUtility::makeInstance(RichTextEditorForTypo3VersionLowerThan8ItemViewer::class);
        }  else {
            $richTextEditorItemViewer = GeneralUtility::makeInstance(RichTextEditorForTypo3VersionGreaterOrEqualTo8ItemViewer::class);
        }

        return $richTextEditorItemViewer;
    }

    /**
     * Pre-processor for Rich text Editor
     *
     * This method is used in:
     *  \YolfTypo3\SavLibraryPlus\QueriersUpdateQuerier
     *  \YolfTypo3\SavLibraryPlus\ItemViewers\General\RichTextEditorItemViewer
     *
     * @todo Will be removed in TYPO3 v10
     */
    public static function preProcessorForRichTextEditor($content)
    {
        if (version_compare(TYPO3_version, '8.0', '<')) {
            $rteTSConfig = BackendUtility::getPagesTSconfig(0);
            $processedRteConfiguration = BackendUtility::RTEsetup($rteTSConfig['RTE.'], '', '');
            $parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
            $parseHTML->init();
            // Checks if the method setRelPath exists because it was removed in TYPO3 8
            if(method_exists($parseHTML, 'setRelPath')) {
                $parseHTML->setRelPath('');
            }
            $specConfParts = BackendUtility::getSpecConfParts('richtext[]:rte_transform[mode=ts_css]');
            $content = $parseHTML->RTE_transform($content, $specConfParts, 'db', $processedRteConfiguration);
        }
        return $content;
    }

}
