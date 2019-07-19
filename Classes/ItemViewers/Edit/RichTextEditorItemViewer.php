<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

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

use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use YolfTypo3\SavLibraryPlus\Compatibility\RichTextEditor\RichTextEditorCompatibility;

/**
 * Edit rich text editor item Viewer.
 *
 * @package SavLibraryPlus
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
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->frontendEdit = 1;
        $GLOBALS['BE_USER']->uc['edit_RTE'] = true;
        if (!isset($GLOBALS['LANG'])) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        }

        $richTextEditorItemViewer = RichTextEditorCompatibility::getRichTextEditorItemViewer();
        $richTextEditorItemViewer->injectItemConfiguration($this->itemConfiguration);
        $richTextEditorItemViewer->injectController($this->controller);
        return $richTextEditorItemViewer->renderItem();
    }
}
?>
