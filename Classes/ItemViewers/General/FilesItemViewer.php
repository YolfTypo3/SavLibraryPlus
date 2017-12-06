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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Imaging\IconFactory;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;

/**
 * General Files item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class FilesItemViewer extends AbstractItemViewer
{

    /**
     * The file name.
     *
     * @var string
     */
    protected $fileName;

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $htmlArray = array();

        // Gets the stored file names
        $fileNames = explode(',', $this->getItemConfiguration('value'));

        foreach ($fileNames as $fileNameKey => $this->fileName) {

            // Renders the item
            if (empty($this->fileName)) {
                $content = '';
            } elseif ($this->isImage() === TRUE) {
                $content = $this->renderImage();
            } elseif ($this->isIframe() === TRUE) {
                $content = $this->renderIframe();
            } else {
                $content = $this->renderLink();
            }

            // Adds the DIV elements
            $htmlArray[] = HtmlElements::htmlDivElement(array(
                HtmlElements::htmlAddAttribute('class', 'file item' . $fileNameKey)
            ), $content);
        }

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Checks if it is an image
     *
     * @return boolean
     */
    protected function isImage()
    {
        // The attribute disallowed is empty for images
        $disallowed = $this->getItemConfiguration('disallowed');
        if (empty($disallowed) === FALSE) {
            return FALSE;
        }

        // Gets the allowed extensions for images
        if ($this->getItemConfiguration('allowed') == 'gif,png,jpeg,jpg') {
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
     * @return boolean
     */
    protected function isIframe()
    {
        return ($this->getItemConfiguration('iframe') ? TRUE : FALSE);
    }

    /**
     * Renders the item in an iframe
     *
     * @return string The rendered item
     */
    protected function renderIframe()
    {
        // Gets the upload folder
        $uploadFolder = $this->getUploadFolder();

        // It's an image to be opened in an iframe
        $width = $this->getItemConfiguration('width') ? $this->getItemConfiguration('width') : '100%';
        $height = $this->getItemConfiguration('height') ? $this->getItemConfiguration('height') : '800';
        $message = $this->getItemConfiguration('message') ? $this->getItemConfiguration('message') : '';

        // Adds the iframe element
        $content = HtmlElements::htmlIframeElement(array(
            HtmlElements::htmlAddAttribute('src', $uploadFolder . '/' . $this->fileName),
            HtmlElements::htmlAddAttribute('width', $width),
            HtmlElements::htmlAddAttribute('height', $height)
        ), $message);

        return $content;
    }

    /**
     * Renders the item as an image
     *
     * @return string The rendered item
     */
    protected function renderImage()
    {
        // Gets the upload folder
        $uploadFolder = $this->getUploadFolder();

        // Sets the typoScript configurations
        if (empty($this->fileName) === FALSE && file_exists($uploadFolder . '/' . $this->fileName)) {
            // The file exists
            $fileName = $uploadFolder . '/' . $this->fileName;
            $typoScriptConfiguration = array(
                'params' => 'class="fileImage"',
                'file' => $fileName,
                'altText' => $this->getItemConfiguration('alt'),
                'titleText' => ($this->getItemConfiguration('title') ? $this->getItemConfiguration('title') : $this->getItemConfiguration('alt'))
            );
        } else {
            // The file does not exist, the default image (unknown) is used.
            $libraryDefaultFile = LibraryConfigurationManager::getImageRootPath('unknown.gif') . 'unknown.gif';
            $fileName = ($this->getItemConfiguration('default') ? $this->getItemConfiguration('default') : $libraryDefaultFile);
            $typoScriptConfiguration = array(
                'params' => 'class="fileImage"',
                'file' => $fileName,
                'altText' => $this->getItemConfiguration('alt'),
                'titleText' => ($this->getItemConfiguration('title') ? $this->getItemConfiguration('title') : $this->getItemConfiguration('alt'))
            );
        }

        // Cheks if only the file name should be displayed
        if ($this->getItemConfiguration('onlyfilename')) {
            return $typoScriptConfiguration['file'];
        }

        // Gets the querier
        $querier = $this->getController()->getQuerier();

        // Adds the tsproperties coming from the kickstarter
        if ($this->getItemConfiguration('tsproperties')) {
            $configuration = $querier->parseLocalizationTags($this->getItemConfiguration('tsproperties'));
            $configuration = $querier->parseFieldTags($configuration);
            $TSparser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $TSparser->parse($configuration);
            // Merges the typoScript configuration with the tsProperties attribute
            $typoScriptConfiguration = array_merge($typoScriptConfiguration, $TSparser->setup);
        }

        // Calls the IMAGE content object
        $contentObject = ExtensionConfigurationManager::getExtensionContentObject();
        $content = $contentObject->cObjGetSingle('IMAGE', $typoScriptConfiguration);

        // Changes the width (it seems params does not overload existing attributes)
        $width = $this->getItemConfiguration('width');
        if (! empty($width)) {
            $content = preg_replace('/width="(\d*)"/', 'width="' . $width . '"', $content);
        }

        // Changes the width (it seems params does not overload existing attributes)
        $height = $this->getItemConfiguration('height');
        if (! empty($height)) {
            $content = preg_replace('/height="(\d*)"/', 'height="' . $height . '"', $content);
        }

        // Checks if the image should be opened in a new window
        if ($this->getItemConfiguration('func') == 'makeNewWindowLink') {
            $this->setItemConfiguration('windowurl', $fileName);
            $content = $this->makeNewWindowLink($content);
        }

        return $content;
    }

    /**
     * Renders the item as a link
     *
     * @return string The rendered item
     */
    protected function renderLink()
    {
        // Gets the upload folder
        $uploadFolder = $this->getUploadFolder();

        // Adds the icon file type if requested
        if ($this->getItemConfiguration('addicon')) {
            // Gets the icon type file name
            $pathParts = pathinfo($this->fileName);
            $iconTypeFileName = $pathParts['extension'];

            // Gets the file from the library directory if it exists or from the typo3
            $iconPath = LibraryConfigurationManager::getIconPath('FileIcons/' . $iconTypeFileName);
            if (file_exists($iconPath)) {
                $iconFileName = $iconPath;
            } elseif (class_exists(IconFactory::class)) {
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $icon = $iconFactory->getIconForFileExtension($iconTypeFileName);
                $iconMarkerup = $icon->getMarkup();
            } elseif (file_exists('typo3/gfx/fileicons/' . $iconTypeFileName . '.gif')) {
                $iconFileName = 'typo3/gfx/fileicons/' . $iconTypeFileName . '.gif';
            }

            // Adds the icon if it exists
            if (isset($iconFileName)) {
                $content = HtmlElements::htmlImgElement(array(
                    HtmlElements::htmlAddAttribute('src', $iconFileName),
                    HtmlElements::htmlAddAttribute('alt', 'Icon ' . $pathParts['extension']),
                    HtmlElements::htmlAddAttribute('class', 'fileIcon ')
                ));
            } elseif(isset($iconMarkerup)) {
                $content = $iconMarkerup;
            } else {
                $content = '';
            }
        }

        $typoScriptConfiguration = array(
            'parameter' => $uploadFolder . '/' . rawurlencode($this->fileName),
            'target' => $this->getItemConfiguration('target')
        );

        // Creates the link
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();
        $messageLink = $this->getItemConfiguration('message') ? $this->getItemConfiguration('message') : $this->fileName;
        $link = $contentObject->typolink($messageLink, $typoScriptConfiguration);

        // Adds the SPAN elements
        $content .= HtmlElements::htmlSpanElement(array(
            HtmlElements::htmlAddAttribute('class', 'fileLink')
        ), $link);

        return $content;
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
