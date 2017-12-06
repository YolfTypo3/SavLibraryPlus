<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

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

use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit Selectorbox item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class SelectorboxItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $htmlArray = array();

        // Initializes the option element array
        $htmlOptionArray = array();
        $htmlOptionArray[] = '';

        // Adds the empty item option if any
        if ($this->getItemConfiguration('emptyitem')) {
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement(array(
                HtmlElements::htmlAddAttribute('value', '0')
            ), '');
        }

        // Adds the option elements
        $items = $this->getItemConfiguration('items');
        $value = $this->getItemConfiguration('value');
        foreach ($items as $itemKey => $item) {
            $selected = ($item[1] == $value ? 'selected' : '');
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement(array(
                HtmlElements::htmlAddAttribute('class', 'item' . $itemKey),
                HtmlElements::htmlAddAttributeIfNotNull('selected', $selected),
                HtmlElements::htmlAddAttribute('value', $item[1])
            ), stripslashes(FlashMessages::translate($item[0])));
        }

        // Adds the select element
        $htmlArray[] = HtmlElements::htmlSelectElement(array(
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
        ), $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }
}
?>
