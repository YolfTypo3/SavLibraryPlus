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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;


/**
 * General Files item Viewer.
 *
 * @package SavLibraryPlus
 */
class FilesItemViewer extends AbstractItemViewer
{

    /**
     * The file name.
     *
     * @var mixed
     */
    protected mixed $fileName;

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        $htmlArray = [];
        $fileNames = [];

        // Gets the stored file names
        if ($this->getItemConfigurationAttribute('type') == 'inline' || $this->getItemConfigurationAttribute('type') == 'file') {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileNames = $fileRepository->findByRelation($this->getItemConfigurationAttribute('tableName'), $this->getItemConfigurationAttribute('fieldName'), (int) $this->getItemConfigurationAttribute('uid'));

            if (empty($fileNames)) {
                $this->fileName = null;
                $content = $this->renderImage();
                $htmlArray[] = HtmlElements::htmlDivElement([
                    HtmlElements::htmlAddAttribute('class', 'file item0')
                ], $content);

                return $this->arrayToHTML($htmlArray);
            }
        } elseif ($this->getItemConfigurationAttribute('fieldType') == 'Files') {
            $fileNames[] = $this->getItemConfigurationAttribute('value');
        } else {
            throw new \Exception('Type of the field "' . $this->getItemConfigurationAttribute('fieldName') . '" must be <inline> (v11) or <file> (v12) but is <' . $this->getItemConfigurationAttribute('type') . '>');
        }
        
        foreach ($fileNames as $fileNameKey => $this->fileName) {
            // Renders the item
            if (empty($this->fileName)) {
                $content = '';
            } elseif ($this->isImage() === true) {
                $content = $this->renderImage();
            } elseif ($this->isIframe() === true) {
                $content = $this->renderIframe();
            } else {
                $content = $this->renderLink();
            }

            // Adds the DIV elements
            $htmlArray[] = HtmlElements::htmlDivElement([
                HtmlElements::htmlAddAttribute('class', 'file item' . $fileNameKey)
            ], $content);
        }

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Checks if it is an image
     *
     * @return bool
     */
    protected function isImage(): bool
    {
        if ($this->getItemConfigurationAttribute('renderaslink')) {
            return false;
        }

        if ($this->fileName instanceof FileReference) {
            return $this->fileName->getType() == \TYPO3\CMS\Core\Resource\FileType::IMAGE->value;
        }
        // The attribute disallowed is empty for images
        $disallowed = $this->getItemConfigurationAttribute('disallowed');
        if (empty($disallowed) === false) {
            return false;
        }

        // Gets the allowed extensions for images
        if ($this->getItemConfigurationAttribute('allowed') == 'gif,png,jpeg,jpg') {
            $allowedExtensionsForImages = explode(',', 'gif,png,jpeg,jpg');
        } else {
            $allowedExtensionsForImages = explode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']);
        }

        // Gets the extension
        $pathParts = pathinfo($this->fileName);
        $extension = strtolower($pathParts['extension']);

        return in_array($extension, $allowedExtensionsForImages);
    }

    /**
     * Checks if it is an iframe
     *
     * @return bool
     */
    protected function isIframe(): bool
    {
        if ($this->getItemConfigurationAttribute('renderaslink')) {
            return false;
        }
        return ($this->getItemConfigurationAttribute('iframe') ? true : false);
    }

    /**
     * Renders the item in an iframe
     *
     * @return string The rendered item
     */
    protected function renderIframe(): string
    {
        // Gets the upload folder
        $uploadFolder = $this->getUploadFolder();

        // It's an image to be opened in an iframe
        $width = $this->getItemConfigurationAttribute('width') ? $this->getItemConfigurationAttribute('width') : '100%';
        $height = $this->getItemConfigurationAttribute('height') ? $this->getItemConfigurationAttribute('height') : '800';
        $message = $this->getItemConfigurationAttribute('message') ? $this->getItemConfigurationAttribute('message') : '';

        // Adds the iframe element
        $content = HtmlElements::htmlIframeElement([
            HtmlElements::htmlAddAttribute('src', $uploadFolder . '/' . $this->fileName),
            HtmlElements::htmlAddAttribute('width', $width),
            HtmlElements::htmlAddAttribute('height', $height)
        ], $message);

        return $content;
    }

    /**
     * Renders the item as an image
     *
     * @return string The rendered item
     */
    protected function renderImage(): string
    {
        // Sets the file name and the upload folder
        if ($this->fileName instanceof FileReference) {
            $fileName = $this->fileName->getIdentifier();
        } else {
            $fileName = $this->fileName;
        }
        $uploadFolder = $this->getUploadFolder();

        // Sets the typoScript configurations
        if (! empty($fileName) && file_exists($uploadFolder . $fileName)) {
            // The file exists
            $fileName = $uploadFolder . $fileName;
            $typoScriptConfiguration = [
                'params' => 'class="fileImage"',
                'file' => $fileName,
                'altText' => $this->getItemConfigurationAttribute('alt'),
                'titleText' => ($this->getItemConfigurationAttribute('title') ? $this->getItemConfigurationAttribute('title') : $this->getItemConfigurationAttribute('alt'))
            ];
        } else {
            // The file does not exist, the default image (unknown) is used.
            $libraryDefaultFile = $this->controller->getLibraryConfigurationManager()->getImageRootPath('unknown.gif') . 'unknown.gif';
            $fileName = ($this->getItemConfigurationAttribute('default') ? $this->getItemConfigurationAttribute('default') : $libraryDefaultFile);
            $typoScriptConfiguration = [
                'params' => 'class="fileImage"',
                'file' => $fileName,
                'altText' => $this->getItemConfigurationAttribute('alt'),
                'titleText' => ($this->getItemConfigurationAttribute('title') ? $this->getItemConfigurationAttribute('title') : $this->getItemConfigurationAttribute('alt'))
            ];
        }

        // Cheks if only the file name should be displayed
        if ($this->getItemConfigurationAttribute('onlyfilename')) {
            return $typoScriptConfiguration['file'];
        }

        // Gets the querier
        $querier = $this->controller->getQuerier();

        // Adds the tsproperties coming from the kickstarter
        if ($this->getItemConfigurationAttribute('tsproperties')) {
            $configuration = $querier->parseLocalizationTags($this->getItemConfigurationAttribute('tsproperties'));
            $configuration = $querier->parseFieldTags($configuration);
            
            /** @var TypoScriptStringFactory $typoScriptStringFactory */
            $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
            $parsedTypoScript = $typoScriptStringFactory->parseFromString($configuration, new AstBuilder(new NoopEventDispatcher()));
            
            // Merges the typoScript configuration with the tsProperties attribute
            $typoScriptConfiguration = array_merge($typoScriptConfiguration, $parsedTypoScript->toArray());
        }

        // Calls the IMAGE content object
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();
        $content = $contentObjectRenderer->cObjGetSingle('IMAGE', $typoScriptConfiguration);

        // Changes the width (it seems params does not overload existing attributes)
        $width = $this->getItemConfigurationAttribute('width');
        if (! empty($width)) {
            $content = preg_replace('/width="(\d*)"/', 'width="' . $width . '"', $content);
        }

        // Changes the width (it seems params does not overload existing attributes)
        $height = $this->getItemConfigurationAttribute('height');
        if (! empty($height)) {
            $content = preg_replace('/height="(\d*)"/', 'height="' . $height . '"', $content);
        }

        // Checks if the image should be opened in a new window
        if ($this->getItemConfigurationAttribute('func') == 'makeNewWindowLink') {
            $this->setItemConfigurationAttribute('windowurl', $fileName);
            $content = $this->makeNewWindowLink($content);
        }

        return $content;
    }

    /**
     * Renders the item as a link
     *
     * @return string The rendered item
     */
    protected function renderLink(): string
    {
        // Sets the file name and the upload folder
        if ($this->fileName instanceof FileReference) {
            $fileName = $this->fileName->getIdentifier();
        } else {
            $fileName = $this->fileName;
        }
        $uploadFolder = $this->getUploadFolder();

        // Adds the icon file type if requested
        $content = '';
        if ($this->getItemConfigurationAttribute('addicon')) {
            // Gets the icon type file name
            $pathParts = pathinfo($fileName);
            $iconTypeFileName = $pathParts['extension'];

            // Gets the file from the library directory if it exists or from the typo3
            $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath('FileIcons/' . $iconTypeFileName);
            if (file_exists($iconPath)) {
                $iconFileName = $iconPath;
            } elseif (class_exists(IconFactory::class)) {
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $icon = $iconFactory->getIconForFileExtension($iconTypeFileName);
                $iconMarkerup = $icon->getMarkup();
            }

            // Adds the icon if it exists
            if (isset($iconFileName)) {
                $content = HtmlElements::htmlImgElement([
                    HtmlElements::htmlAddAttribute('src', $iconFileName),
                    HtmlElements::htmlAddAttribute('alt', 'Icon ' . $pathParts['extension']),
                    HtmlElements::htmlAddAttribute('class', 'fileIcon ')
                ]);
            } elseif (isset($iconMarkerup)) {
                $content = $iconMarkerup;
            } else {
                $content = '';
            }
        }

        $pathParts = pathinfo($fileName);
        $typoScriptConfiguration = [
            'parameter' => $uploadFolder . $pathParts['dirname'] . '/' . rawurlencode($pathParts['basename']),
            'fileTarget' => $this->getItemConfigurationAttribute('target')
        ];

        // Creates the link
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();
        $messageLink = $this->getItemConfigurationAttribute('message') ? $this->getItemConfigurationAttribute('message') : $pathParts['basename'];
        $link = $contentObjectRenderer->typolink($messageLink, $typoScriptConfiguration);

        // Adds the SPAN elements
        $content .= HtmlElements::htmlSpanElement([
            HtmlElements::htmlAddAttribute('class', 'fileLink')
        ], $link);

        return $content;
    }

    /**
     * Gets the upload folder
     *
     * @return string
     */
    protected function getUploadFolder(): string
    {
        if ($this->fileName instanceof FileReference) {
            $configuration = $this->fileName->getStorage()->getConfiguration();
            $uploadFolder = substr($configuration['basePath'], 0, - 1);
        } else {
            $uploadFolder = $this->getItemConfigurationAttribute('uploadfolder');
            $uploadFolder .= ($this->getItemConfigurationAttribute('addToUploadFolder') ? '/' . $this->getItemConfigurationAttribute('addToUploadFolder') : '');
            $uploadFolder .= '/';
        }
        return $uploadFolder;
    }
}
