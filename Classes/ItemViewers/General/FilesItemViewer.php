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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;

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
        $htmlArray = [];
        $fileNames = [];

        // Gets the stored file names
        if ($this->getItemConfiguration('type') == 'inline') {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileNames = $fileRepository->findByRelation($this->getItemConfiguration('tableName'), $this->getItemConfiguration('fieldName'), $this->getItemConfiguration('uid'));
        } else {
            // For old style extension
            $fileNames = explode(',', $this->getItemConfiguration('value'));
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
     * @return boolean
     */
    protected function isImage()
    {
        if ($this->getItemConfiguration('renderaslink')) {
            return false;
        }

        if ($this->fileName instanceof FileReference) {
            return $this->fileName->getType() == AbstractFile::FILETYPE_IMAGE;
        }
        // The attribute disallowed is empty for images
        $disallowed = $this->getItemConfiguration('disallowed');
        if (empty($disallowed) === false) {
            return false;
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
        if ($this->getItemConfiguration('renderaslink')) {
            return false;
        }
        return ($this->getItemConfiguration('iframe') ? true : false);
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
    protected function renderImage()
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
                'altText' => $this->getItemConfiguration('alt'),
                'titleText' => ($this->getItemConfiguration('title') ? $this->getItemConfiguration('title') : $this->getItemConfiguration('alt'))
            ];
        } else {
            // The file does not exist, the default image (unknown) is used.
            $libraryDefaultFile = LibraryConfigurationManager::getImageRootPath('unknown.gif') . 'unknown.gif';
            $fileName = ($this->getItemConfiguration('default') ? $this->getItemConfiguration('default') : $libraryDefaultFile);
            $typoScriptConfiguration = [
                'params' => 'class="fileImage"',
                'file' => $fileName,
                'altText' => $this->getItemConfiguration('alt'),
                'titleText' => ($this->getItemConfiguration('title') ? $this->getItemConfiguration('title') : $this->getItemConfiguration('alt'))
            ];
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
        // Sets the file name and the upload folder
        if ($this->fileName instanceof FileReference) {
            $fileName = $this->fileName->getIdentifier();
        } else {
            $fileName = $this->fileName;
        }
        $uploadFolder = $this->getUploadFolder();

        // Adds the icon file type if requested
        if ($this->getItemConfiguration('addicon')) {
            // Gets the icon type file name
            $pathParts = pathinfo($fileName);
            $iconTypeFileName = $pathParts['extension'];

            // Gets the file from the library directory if it exists or from the typo3
            $iconPath = LibraryConfigurationManager::getIconPath('FileIcons/' . $iconTypeFileName);
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
            'fileTarget' => $this->getItemConfiguration('target')
        ];

        // Creates the link
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();
        $messageLink = $this->getItemConfiguration('message') ? $this->getItemConfiguration('message') : $pathParts['basename'];
        $link = $contentObject->typolink($messageLink, $typoScriptConfiguration);

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
    protected function getUploadFolder()
    {
        if ($this->fileName instanceof FileReference) {
            $configuration = $this->fileName->getStorage()->getConfiguration();
            $uploadFolder = substr($configuration['basePath'], 0, - 1);
        } else {
            $uploadFolder = $this->getItemConfiguration('uploadfolder');
            $uploadFolder .= ($this->getItemConfiguration('addToUploadFolder') ? '/' . $this->getItemConfiguration('addToUploadFolder') : '');
            $uploadFolder .= '/';
        }
        return $uploadFolder;
    }
}
?>
