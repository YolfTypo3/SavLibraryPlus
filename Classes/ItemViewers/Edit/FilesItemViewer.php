<?php
namespace SAV\SavLibraryPlus\ItemViewers\Edit;

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

use SAV\SavLibraryPlus\Utility\HtmlElements;
use SAV\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit File item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class FilesItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $htmlArray = array();

        if ($this->getItemConfiguration('size') < 10) {
            $size = '';
        }

        // Gets the stored file names
        $fileNames = explode(',', $this->getItemConfiguration('value'));

        // Adds the items
        for ($counter = 0; $counter < $this->getItemConfiguration('maxitems'); $counter ++) {

            // Sets the file name
            $fileName = ($fileNames[$counter] ? $fileNames[$counter] : '');

            // Adds the text element
            $content = HtmlElements::htmlInputTextElement(array(
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[' . $counter . ']'),
                HtmlElements::htmlAddAttribute('class', 'fileText'),
                HtmlElements::htmlAddAttribute('value', $fileName),
                HtmlElements::htmlAddAttribute('size', $size)
            ));

            // Adds the file element
            $content .= HtmlElements::htmlInputFileElement(array(
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[' . $counter . ']'),
                HtmlElements::htmlAddAttribute('class', 'fileInput'),
                HtmlElements::htmlAddAttribute('value', ''),
                HtmlElements::htmlAddAttribute('size', $size),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ));

            // Adds the hyperlink if required
            if ($this->getItemConfiguration('addlinkineditmode') && empty($fileName) === FALSE) {
                // Gets the upload folder
                $uploadFolder = $this->getUploadFolder();

                // Builds the typoScript configuration
                $typoScriptConfiguration = array(
                    'parameter' => $uploadFolder . '/' . rawurlencode($fileName),
                    'fileTarget' => $this->getItemConfiguration('target') ? $this->getItemConfiguration('target') : '_blank'
                );

                // Gets the content object
                $contentObject = $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getExtensionContentObject();

                // Builds the content
                $message = FlashMessages::translate('general.clickHereToOpenInNewWindow');
                $content .= HtmlElements::htmlSpanElement(array(
                    HtmlElements::htmlAddAttribute('class', 'fileLink')
                ), $contentObject->typolink($message, $typoScriptConfiguration));
            }

            // Adds the DIV elements
            $htmlArray[] = HtmlElements::htmlDivElement(array(
                HtmlElements::htmlAddAttribute('class', 'file item' . $counter)
            ), $content);
        }

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Gets the upload folder
     *
     * @return string
     */
    protected function getUploadFolder()
    {
        $uploadFolder = $this->getItemConfiguration('uploadfolder');
        $uploadFolder .= ($this->getItemConfiguration('addToUploadFolder') ? '/' . $this->getItemConfiguration('addToUploadFolder') : '');

        return $uploadFolder;
    }
}
?>
