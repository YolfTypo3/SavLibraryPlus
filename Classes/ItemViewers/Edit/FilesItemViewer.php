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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
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
    protected function renderItem(): string
    {
        $htmlArray = [];

        if ($this->getItemConfigurationAttribute('size') < 10) {
            $size = 0;
        }

        // Gets the stored file names
        if ($this->getItemConfigurationAttribute('type') == 'inline' || $this->getItemConfigurationAttribute('type') == 'file')  {
            if ($this->getItemConfigurationAttribute('uid') > 0) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $files = $fileRepository->findByRelation(
                $this->getItemConfigurationAttribute('tableName'),
                $this->getItemConfigurationAttribute('fieldName'),
                intval($this->getItemConfigurationAttribute('uid')));
            }
        } else {
            throw new \Exception('Type of the field must be inline (v11) or file (v12)');
        }
    
        // Adds the items
        for ($counter = 0; $counter < $this->getItemConfigurationAttribute('maxitems'); $counter ++) {
    
            // Sets the file name
            $fileName = (($files[$counter] ?? false) ? $files[$counter] : '');
            if ($fileName instanceof FileReference)  {
                $fileName = $files[$counter]->getName();
            }
            
            // Deletes the file reference if requested
            if (($files[$counter] ?? false) instanceof FileReference && isset($this->controller->getUriManager()->getFormActionFromPostVariables()['deleteFile'][$counter])) {
                // Deletes the reference in FAL
                $files[$counter]->delete();
                $fileName = '';
            }
            
            $content = '';
            if (!empty($fileName)) {
                // Adds the link to the file
                $uploadFolder = $this->getUploadFolder();
                $fullFileName = $uploadFolder . '/' . $fileName;
                $contentObjectRenderer = $this->controller->getContentObjectRenderer();
                $content .= HtmlElements::htmlSpanElement([
                    HtmlElements::htmlAddAttribute('class', 'fileLink')
                    ],
                    strval($contentObjectRenderer->createLink($fileName, [
                        'parameter' => 't3://file?identifier=' . $fullFileName
                    ]))
                );

                // Adds the hidden element
                $content .= HtmlElements::htmlInputHiddenElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName') . '[' . $counter . ']'),
                    HtmlElements::htmlAddAttribute('value', $fileName)
                    ]
                );
                $extensionPrefixId = $this->controller->getExtensionPrefixId();
                $prefixForItemName = $extensionPrefixId . '[' . $this->controller->getFormName() . ']';

                $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath('delete');
                $src = $this->getResourceWebPath($iconPath);
                $content .= HtmlElements::htmlInputImageElement([
                    HtmlElements::htmlAddAttribute('class', 'deleteButton'),
                    HtmlElements::htmlAddAttribute('src', $src),
                    HtmlElements::htmlAddAttribute('name', $prefixForItemName . '[formAction][deleteFile][' . $counter . ']'),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.deleteFile')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.deleteFile')),
                    HtmlElements::htmlAddAttribute('onclick', 'if(confirmDelete())return update(\'' . $this->controller->getFormName() . '\');else return false;')
                    ]
                );
            
            } else {
                // Adds the hidden element
                $content .= HtmlElements::htmlInputHiddenElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName') . '[' . $counter . ']'),
                    HtmlElements::htmlAddAttribute('value', $fileName)
                    ]
                );
                // Adds the file element
                $content .= HtmlElements::htmlInputFileElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName') . '[' . $counter . ']'),
                    HtmlElements::htmlAddAttribute('class', 'fileInput'),
                    HtmlElements::htmlAddAttribute('value', ''),
                    HtmlElements::htmlAddAttribute('size', $size),
                    HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
                    ]
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
    protected function getUploadFolder(): string
    {
        $uploadFolder = $this->getItemConfigurationAttribute('uploadfolder') ?? '';
        $uploadFolder = empty($uploadFolder) ? 'fileadmin' : $uploadFolder;
        $uploadFolder .= ($this->getItemConfigurationAttribute('addToUploadFolder') ? '/' . $this->getItemConfigurationAttribute('addToUploadFolder') : '');

        return $uploadFolder;
    }
}
