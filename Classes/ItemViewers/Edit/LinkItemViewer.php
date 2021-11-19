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
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit Link item Viewer.
 *
 * @package SavLibraryPlus
 */
class LinkItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        // Gets the value
        $value = $this->getItemConfiguration('value');
        $value = ($value == null ? '' : $value);

        if ($this->getItemConfiguration('generatertf')) {
            // Initializes the content
            $content = '';

            // Adds an input image element
            $generateRtfButton = false;
            $generateRtfButtonCondition = $this->getItemConfiguration('generatertfbuttonif');
            if (! empty($generateRtfButtonCondition)) {
                $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                $fieldConfigurationManager->injectController($this->getController());
                $fieldConfigurationManager->injectQuerier($this->getController()
                    ->getQuerier());
                $generateRtfButton = $fieldConfigurationManager->processFieldCondition($generateRtfButtonCondition);
            }

            if (empty($generateRtfButtonCondition) || (! empty($generateRtfButtonCondition) && $generateRtfButton)) {
                // Builds the prefix for the item name
                $extensionPrefixId = $this->getController()->getExtensionConfigurationManager()->getExtensionPrefixId();
                $prefixForItemName = $extensionPrefixId . '[' . AbstractController::getFormName() . ']';

                $content = HtmlElements::htmlInputImageElement([
                    HtmlElements::htmlAddAttribute('class', 'generateRtfButton'),
                    HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('generateRtf')),
                    HtmlElements::htmlAddAttribute('name', $prefixForItemName . '[formAction][saveAndGenerateRtf]' . $this->getItemConfiguration('itemKey')),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.generateRtf')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.generateRtf')),
                    HtmlElements::htmlAddAttribute('onclick', 'return update(\'' . AbstractController::getFormName() . '\');')
                ]
                    );
            }

            // Adds the hidden input element
            $content .= HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('value', $value)
            ]);

            if (! empty($value)) {
                $path_parts = pathinfo($this->getItemConfiguration('savefilertf'));
                $folder = $path_parts['dirname'];
                $this->setItemConfiguration('folder', $folder);
                $fileName = $folder . '/' . $value;

                // Checks if the file exists
                if (file_exists($fileName)) {
                    $content .= $this->makeLink($value);
                } else {
                    $content .= $value;
                }
            }

            // Adds a DIV element
            $content = HtmlElements::htmlDivElement([
                HtmlElements::htmlAddAttribute('class', 'generateRtf')
            ], $content);
        } else {

            // Gets the size
            $size = ($this->getItemConfiguration('size') < 20 ? 40 : $this->getItemConfiguration('size'));

            // Adds the Input text element
            $content = HtmlElements::htmlInputTextElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('value', stripslashes($value)),
                HtmlElements::htmlAddAttribute('size', $size),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ]);
        }

        return $content;
    }
}
