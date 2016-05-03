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
 * Edit Radio buttons item Viewer.
 *
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

        // Adds the option elements
        $items = $this->getItemConfiguration('items');
        $value = $this->getItemConfiguration('value');
        // If the value is null it is replaced by the default one if it exists
        if ($value === NULL) {
            $defaultValue = $this->getItemConfiguration('default');
            if ($defaultValue !== NULL) {
                $value = $defaultValue;
            }
        }
        foreach ($items as $itemKey => $item) {
            $checked = ($item[1] == $value ? 'checked' : '');

            // Adds the radio input element
            $htmlItem = HtmlElements::htmlInputRadioElement(array(
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('value', $item[1]),
                HtmlElements::htmlAddAttributeIfNotNull('checked', $checked),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ));

            // Adds the span element
            $htmlItem .= HtmlElements::htmlSpanElement(array(), stripslashes(FlashMessages::translate($item[0])));

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

            // Adds the Div element
            $htmlArray[] = HtmlElements::htmlDivElement(array(
                HtmlElements::htmlAddAttribute('class', $class),
                $this->getItemConfiguration('addattributes')
            ), $htmlItem);
        }

        return $this->arrayToHTML($htmlArray);
    }
}
?>
