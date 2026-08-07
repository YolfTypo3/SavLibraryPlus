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

use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit Checkboxes item Viewer.
 *
 * @package SavLibraryPlus
 */
class CheckboxesItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $htmlArray = [];

        $columnsCount = ($this->getItemConfigurationAttribute('cols') ? $this->getItemConfigurationAttribute('cols') : 1);
        $counter = 0;
        $itemCounter = 0;

        $value = $this->getItemConfigurationAttribute('value');
        $items = $this->getItemConfigurationAttribute('items');
        foreach ($items as $itemKey => $item) {
            $itemLabel = $item['label'] ?? $item[0];
            $itemValue = $item['value'] ?? $item[1];
            $checked = (($value & 0x01 || $itemValue == 1) ? 'checked' : '');
            $value = $value >> 1;

            // Adds the hidden input element
            $htmlItem = HtmlElements::htmlInputHiddenElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName') . '[' . $itemKey . ']'),
                    HtmlElements::htmlAddAttribute('value', '0')
                ]
            );

            // Adds the checkbox input element
            $htmlItem .= HtmlElements::htmlInputCheckBoxElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName') . '[' . $itemKey . ']'),
                    HtmlElements::htmlAddAttribute('value', '1'),
                    HtmlElements::htmlAddAttributeIfNotNull('checked', $checked),
                    HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
                ]
            );

            // Adds the span element
            $htmlItem .= HtmlElements::htmlSpanElement(
                [],
                stripslashes(FlashMessages::translate($itemLabel))
            );

            // Sets the class for the item
            $class = 'checkbox item' . $itemKey;

            // Checks if the columns count is reached
            $itemCounter ++;
            if ($itemCounter == $this->getItemConfigurationAttribute('nbitems')) {
                break;
            }
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
                    $this->getItemConfigurationAttribute('addattributes')
                ],
                $htmlItem
            );
        }
        return $this->arrayToHTML($htmlArray);
    }
}
