<?php

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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit File item Viewer.
 *
 * @package SavLibraryPlus
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
        $htmlArray = [];

        if ($this->getItemConfiguration('size') < 10) {
            $size = 0;
        }

        // Gets the stored file names
        if ($this->getItemConfiguration('type') == 'inline')  {
            if ($this->getItemConfiguration('uid') > 0) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileNames = $fileRepository->findByRelation(
                $this->getItemConfiguration('tableName'),
                $this->getItemConfiguration('fieldName'),
                $this->getItemConfiguration('uid'));
            }
        } else {
            // For old style extension
            $fileNames = explode(',', $this->getItemConfiguration('value'));
        }

        // Adds the items
        for ($counter = 0; $counter < $this->getItemConfiguration('maxitems'); $counter ++) {

            // Sets the file name
            $fileName = (($fileNames[$counter] ?? false) ? $fileNames[$counter] : '');
            if ($fileName instanceof FileReference)  {
                $fileName = $fileName->getIdentifier();
            }

            // Adds the text element
            $content = HtmlElements::htmlInputTextElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[' . $counter . ']'),
                    HtmlElements::htmlAddAttribute('class', 'fileText'),
                    HtmlElements::htmlAddAttribute('value', $fileName),
                    HtmlElements::htmlAddAttribute('size', $size)
                ]
            );

            // Adds the file element
            $content .= HtmlElements::htmlInputFileElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[' . $counter . ']'),
                    HtmlElements::htmlAddAttribute('class', 'fileInput'),
                    HtmlElements::htmlAddAttribute('value', ''),
                    HtmlElements::htmlAddAttribute('size', $size),
                    HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
                ]
            );

            // Adds the hyperlink if required
            if ($this->getItemConfiguration('addlinkineditmode') && empty($fileName) === false) {
                // Gets the upload folder
                $uploadFolder = $this->getUploadFolder();

                // Builds the typoScript configuration
                $typoScriptConfiguration = [
                    'parameter' => $uploadFolder . '/' . rawurlencode($fileName),
                    'fileTarget' => $this->getItemConfiguration('target') ? $this->getItemConfiguration('target') : '_blank'
                ];

                // Gets the content object
                $contentObject = $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getExtensionContentObject();

                // Builds the content
                $message = FlashMessages::translate('general.clickHereToOpenInNewWindow');
                $content .= HtmlElements::htmlSpanElement([
                        HtmlElements::htmlAddAttribute('class', 'fileLink')
                    ],
                    $contentObject->typolink($message, $typoScriptConfiguration)
                );
            }

            // Adds the DIV elements
            $htmlArray[] = HtmlElements::htmlDivElement([
                    HtmlElements::htmlAddAttribute('class', 'file item' . $counter)
                ],
                $content
            );
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
