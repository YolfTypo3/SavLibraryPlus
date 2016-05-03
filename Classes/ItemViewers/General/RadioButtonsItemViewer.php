<?php
namespace SAV\SavLibraryPlus\ItemViewers\General;

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
use SAV\SavLibraryPlus\Managers\LibraryConfigurationManager;

/**
 * General Radio buttons item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class RadioButtonsItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $htmlArray = array();

        if ($this->getItemConfiguration('horizontalLayout')) {
            $columnsCount = count($this->getItemConfiguration('items'));
        } else {
            $columnsCount = ($this->getItemConfiguration('cols') ? $this->getItemConfiguration('cols') : 1);
        }
        $counter = 0;
        // Gets the value
        $value = $this->getItemConfiguration('value');

        // Adds the option elements
        $items = $this->getItemConfiguration('items');
        foreach ($items as $itemKey => $item) {

            // Sets the class for the item
            $class = 'radioButton item' . $itemKey;

            // Checks if the columns count is reached

            if ($counter == $columnsCount) {
                // Additional class
                $class .= ' clearLeft';
                // Resets the counter
                $counter = 0;
            }
            $counter ++;

            // Builds the message
            $message = HtmlElements::htmlSpanElement(array(
                HtmlElements::htmlAddAttribute('class', 'radioButtonMessage')
            ), stripslashes(FlashMessages::translate($item[0])));

            // Adds the Div element
            if ($this->itemConfigurationNotSet('displayasimage') || $this->getItemConfiguration('displayasimage')) {
                if ($item[1] == $value) {
                    $htmlArray[] = HtmlElements::htmlDivElement(array(
                        HtmlElements::htmlAddAttribute('class', $class)
                    ), $this->renderSelectedAsImage() . $message);
                } else {
                    $htmlArray[] = HtmlElements::htmlDivElement(array(
                        HtmlElements::htmlAddAttribute('class', $class)
                    ), $this->renderNotSelectedAsImage() . $message);
                }
            } elseif ($item[1] == $value) {
                $htmlArray[] = HtmlElements::htmlDivElement(array(
                    HtmlElements::htmlAddAttribute('class', $class)
                ), $message);
            }
        }

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Renders a checked checkbox as an image.
     *
     * @return string
     */
    protected function renderSelectedAsImage()
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfiguration('radiobuttonselectedimage');
        if (empty($imageFileName)) {
            $imageFileName = 'radioButtonSelected';
        }

        $content = HtmlElements::htmlImgElement(array(
            HtmlElements::htmlAddAttribute('class', 'radioButtonSelected'),
            HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath($imageFileName)),
            HtmlElements::htmlAddAttribute('title', FlashMessages::translate('itemviewer.radioButtonSelected')),
            HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('itemviewer.radioButtonSelected'))
        ));

        return $content;
    }

    /**
     * Renders a unchecked checkbox as an image.
     *
     * @return string
     */
    protected function renderNotSelectedAsImage()
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfiguration('radiobuttonnotselectedimage');
        if (empty($imageFileName)) {
            $imageFileName = 'radioButtonNotSelected';
        }

        $content = HtmlElements::htmlImgElement(array(
            HtmlElements::htmlAddAttribute('class', 'radioButtonNotSelected'),
            HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath($imageFileName)),
            HtmlElements::htmlAddAttribute('title', FlashMessages::translate('itemviewer.radioButtonNotSelected')),
            HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('itemviewer.radioButtonNotSelected'))
        ));

        return $content;
    }
}
?>
