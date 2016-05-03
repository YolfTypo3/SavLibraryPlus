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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use SAV\SavLibraryPlus\Utility\HtmlElements;
use SAV\SavLibraryPlus\Managers\FieldConfigurationManager;
use SAV\SavLibraryPlus\Managers\LibraryConfigurationManager;
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit Link item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
        $value = ($value == NULL ? '' : $value);

        if ($this->getItemConfiguration('generatertf')) {
            // Initializes the content
            $content = '';

            // Adds an input image element
            $generateRtfButton = FALSE;
            $generateRtfButtonCondition = $this->getItemConfiguration('generatertfbuttonif');
            if (! empty($generateRtfButtonCondition)) {
                $fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
                $fieldConfigurationManager->injectController($this->getController());
                $fieldConfigurationManager->injectQuerier($this->getController()
                    ->getQuerier());
                $generateRtfButton = $fieldConfigurationManager->processFieldCondition($generateRtfButtonCondition);
            }

            if (empty($generateRtfButtonCondition) || (! empty($generateRtfButtonCondition) && $generateRtfButton)) {
                $content = HtmlElements::htmlInputImageElement(array(
                    HtmlElements::htmlAddAttribute('class', 'generateRtfButton'),
                    HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('generateRtf')),
                    HtmlElements::htmlAddAttribute('name', AbstractController::getFormName() . '[formAction][saveAndGenerateRtf][' . $this->getCryptedFullFieldName() . ']'),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.generateRtf')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.generateRtf')),
                    HtmlElements::htmlAddAttribute('onclick', 'return update(\'' . AbstractController::getFormName() . '\');')
                ));
            }

            // Adds the hidden input element
            $content .= HtmlElements::htmlInputHiddenElement(array(
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('value', $value)
            ));

            if (empty($value) === FALSE) {
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
            $content = HtmlElements::htmlDivElement(array(
                HtmlElements::htmlAddAttribute('class', 'generateRtf')
            ), $content);
        } else {

            // Gets the size
            $size = ($this->getItemConfiguration('size') < 20 ? 40 : $this->getItemConfiguration('size'));

            // Adds the Input text element
            $content = HtmlElements::htmlInputTextElement(array(
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('value', stripslashes($value)),
                HtmlElements::htmlAddAttribute('size', $size),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ));
        }

        return $content;
    }
}
?>
