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
 * Edit Selectorbox item Viewer.
 *
 * @package SavLibraryPlus
 */
class SelectorboxItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        $htmlArray = [];

        // Initializes the option element array
        $htmlOptionArray = [];
        $htmlOptionArray[] = '';

        // Adds the empty item option if any
        if ($this->getItemConfigurationAttribute('emptyitem')) {
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement([
                    HtmlElements::htmlAddAttribute('value', '0')
                ],
                ''
            );
        }

        // Adds the option elements
        $items = $this->getItemConfigurationAttribute('items');
        $value = $this->getItemConfigurationAttribute('value');
        foreach ($items as $itemKey => $item) {
            $itemLabel = $item['label'] ?? $item[0];
            $itemValue = $item['value'] ?? $item[1];
            $selected = ($itemValue == $value ? 'selected' : '');
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement([
                    HtmlElements::htmlAddAttribute('class', 'item' . $itemKey),
                    HtmlElements::htmlAddAttributeIfNotNull('selected', $selected),
                    HtmlElements::htmlAddAttribute('value', $itemValue)
                ],
                stripslashes(FlashMessages::translate($itemLabel) ?? '')
            );
        }

        // Adds the select element
        $htmlArray[] = HtmlElements::htmlSelectElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('size', $this->getItemConfigurationAttribute('size')),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ],
            $this->arrayToHTML($htmlOptionArray)
        );

        return $this->arrayToHTML($htmlArray);
    }
}
