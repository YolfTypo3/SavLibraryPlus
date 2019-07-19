<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

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

use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit Radio buttons item Viewer.
 *
 *
 * @package SavLibraryPlus
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
        $htmlArray = [];

        if ($this->getItemConfiguration('horizontallayout')) {
            $columnsCount = count($this->getItemConfiguration('items'));
        } else {
            $columnsCount = ($this->getItemConfiguration('cols') ? $this->getItemConfiguration('cols') : 1);
        }
        $counter = 0;

        // Adds the option elements
        $items = $this->getItemConfiguration('items');
        $value = $this->getItemConfiguration('value');

        // If the value is null it is replaced by the default one if it exists
        if ($value === null) {
            $defaultValue = $this->getItemConfiguration('default');
            if ($defaultValue !== null) {
                $value = $defaultValue;
            }
        }
        foreach ($items as $itemKey => $item) {
            $checked = ($item[1] == $value ? 'checked' : '');

            // Adds the radio input element
            $htmlItem = HtmlElements::htmlInputRadioElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                    HtmlElements::htmlAddAttribute('value', $item[1]),
                    HtmlElements::htmlAddAttributeIfNotNull('checked', $checked),
                    HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
                ]
            );

            // Adds the span element
            $htmlItem .= HtmlElements::htmlSpanElement([], stripslashes(FlashMessages::translate($item[0])));

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
            $htmlArray[] = HtmlElements::htmlDivElement([
                    HtmlElements::htmlAddAttribute('class', $class),
                    $this->getItemConfiguration('addattributes')
                ],
                $htmlItem
            );
        }

        return $this->arrayToHTML($htmlArray);
    }
}
?>
