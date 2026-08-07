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
    protected function renderItem(): string
    {
        $htmlArray = [];

        if ($this->getItemConfigurationAttribute('horizontallayout')) {
            $columnsCount = count($this->getItemConfigurationAttribute('items'));
        } else {
            $columnsCount = ($this->getItemConfigurationAttribute('cols') ? $this->getItemConfigurationAttribute('cols') : 1);
        }
        $counter = 0;

        // Adds the option elements
        $items = $this->getItemConfigurationAttribute('items');
        $value = $this->getItemConfigurationAttribute('value');

        // If the value is null it is replaced by the default one if it exists
        if ($value === null) {
            $defaultValue = $this->getItemConfigurationAttribute('default');
            if ($defaultValue !== null) {
                $value = $defaultValue;
            }
        }

        // If the field is required and there is a default value, adds an hidden item with default value
        if ($this->getItemConfigurationAttribute('default') !== null && $this->getItemConfigurationAttribute('required')) {
            $htmlArray[] = HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttributeIfNotNull('checked', true),
                HtmlElements::htmlAddAttribute('value', $this->getItemConfigurationAttribute('default'))
            ]);
        }

        foreach ($items as $itemKey => $item) {
            $itemLabel = $item['label'] ?? $item[0];
            $itemValue = $item['value'] ?? $item[1];
            $checked = ($itemValue == $value ? 'checked' : '');

            // Adds the radio input element
            $htmlItem = HtmlElements::htmlInputRadioElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('value', $itemValue),
                HtmlElements::htmlAddAttributeIfNotNull('checked', $checked),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ]);

            // Adds the span element
            $htmlItem .= HtmlElements::htmlSpanElement([], stripslashes(FlashMessages::translate($itemLabel)));

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
                $this->getItemConfigurationAttribute('addattributes')
            ], $htmlItem);
        }

        return $this->arrayToHTML($htmlArray);
    }
}
