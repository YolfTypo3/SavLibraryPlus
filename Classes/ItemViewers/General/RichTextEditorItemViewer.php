<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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

use YolfTypo3\SavLibraryPlus\Compatibility\RichTextEditor\RichTextEditorCompatibility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;

/**
 * General Rich text editor item Viewer.
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
        $htmlArray = [];
        
        $content = html_entity_decode(stripslashes($this->getItemConfiguration('value')), ENT_QUOTES);

        // @todo Will be removed in TYPO3 v10
        $content = RichTextEditorCompatibility::preProcessorForRichTextEditor($content);

        // Adds the content
        $htmlArray[] = HtmlElements::htmlDivElement([
                HtmlElements::htmlAddAttribute('class', 'richText')
            ],
            $content
        );

        return $this->arrayToHTML($htmlArray);
    }
}
?>
