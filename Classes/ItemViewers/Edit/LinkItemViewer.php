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

use TYPO3\CMS\Core\Utility\PathUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;
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
    protected function renderItem(): string
    {
        // Gets the value
        $value = $this->getItemConfigurationAttribute('value');
        $value = ($value == null ? '' : $value);

        if ($this->getItemConfigurationAttribute('generatertf')) {
            // Initializes the content
            $content = '';

            // Adds an input image element
            $generateRtfButton = false;
            $generateRtfButtonCondition = $this->getItemConfigurationAttribute('generatertfbuttonif');
            if (! empty($generateRtfButtonCondition)) {
                $fieldConfigurationManager = new (FieldConfigurationManager::class)($this->controller);
                $fieldConfigurationManager->setQuerier($this->controller
                    ->getQuerier());
                $generateRtfButton = $fieldConfigurationManager->processFieldCondition($generateRtfButtonCondition);
            }

            if (empty($generateRtfButtonCondition) || (! empty($generateRtfButtonCondition) && $generateRtfButton)) {
                // Builds the prefix for the item name
                $extensionPrefixId = $this->controller->getExtensionPrefixId();
                $prefixForItemName = $extensionPrefixId . '[' . $this->controller->getFormName() . ']';

                $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath('generateRtf');
                $src = $this->getResourceWebPath($iconPath);
                $content = HtmlElements::htmlInputImageElement([
                    HtmlElements::htmlAddAttribute('class', 'generateRtfButton'),
                    HtmlElements::htmlAddAttribute('src', $src),
                    HtmlElements::htmlAddAttribute('name', $prefixForItemName . '[formAction][saveAndGenerateRtf]' . $this->getItemConfigurationAttribute('itemKey')),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.generateRtf')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.generateRtf')),
                    HtmlElements::htmlAddAttribute('onclick', 'return update(\'' . $this->controller->getFormName() . '\');')
                ]
                    );
            }

            // Adds the hidden input element
            $content .= HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('value', $value)
            ]);

            if (! empty($value)) {
                $path_parts = pathinfo($this->getItemConfigurationAttribute('savefilertf'));        
                $folder = $path_parts['dirname'];
                $this->setItemConfigurationAttribute('folder', $folder);
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
            $size = ($this->getItemConfigurationAttribute('size') < 20 ? 40 : $this->getItemConfigurationAttribute('size'));

            // Adds the Input text element
            $content = HtmlElements::htmlInputTextElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('value', stripslashes($value)),
                HtmlElements::htmlAddAttribute('size', $size),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ]);
        }

        return $content;
    }
}
