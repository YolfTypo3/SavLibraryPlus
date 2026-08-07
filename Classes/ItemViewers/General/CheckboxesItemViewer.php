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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * General Checkboxes item Viewer.
 *
 * @package SavLibraryPlus
 */
class CheckboxesItemViewer extends CheckboxItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        $htmlArray = [];

        $columnsCount = ($this->getItemConfigurationAttribute('cols') ? $this->getItemConfigurationAttribute('cols') : 1);

        $counter = 0;
        $itemCounter = 0;

        // Gets the value
        $value = $this->getItemConfigurationAttribute('value');

        // Processes the items
        $items = $this->getItemConfigurationAttribute('items');
        foreach ($items as $itemKey => $item) {
            $checked = ($value & 0x01 ? 'checked' : '');
            $value = $value >> 1;
            $itemLabel = $item['label'] ?? $item[0];
            $message = HtmlElements::htmlSpanElement([
                    HtmlElements::htmlAddAttribute('class', 'checkboxMessage')
                ],
                stripslashes(FlashMessages::translate($itemLabel))
            );

            // Checks if donotdisplayifnotchecked is set
            if ($this->getItemConfigurationAttribute('donotdisplayifnotchecked') && ! $checked) {
                $message = '';
            }

            // Sets the class for the item
            $class = 'checkbox item' . $itemKey;

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
            if ($this->itemConfigurationAttributeNotSet('displayasimage') || $this->getItemConfigurationAttribute('displayasimage')) {
                $renderIfChecked = HtmlElements::htmlDivElement([
                        HtmlElements::htmlAddAttribute('class', $class)
                    ],
                    $this->renderCheckedAsImage() . $message
                );
                $renderIfNotChecked = HtmlElements::htmlDivElement([
                        HtmlElements::htmlAddAttribute('class', $class)
                    ],
                    $this->renderNotCheckedAsImage() . $message
                );
            } else {
                $renderIfChecked = HtmlElements::htmlDivElement([
                        HtmlElements::htmlAddAttribute('class', $class)
                    ],
                    HtmlElements::htmlSpanElement([
                            HtmlElements::htmlAddAttribute('class', 'checkboxSelected')
                        ],
                        FlashMessages::translate('itemviewer.yesMult')
                    ) . $message
                );
                $renderIfNotChecked = HtmlElements::htmlDivElement([
                        HtmlElements::htmlAddAttribute('class', $class)
                    ],
                    HtmlElements::htmlSpanElement([
                            HtmlElements::htmlAddAttribute('class', 'checkboxNotSelected')
                        ],
                        FlashMessages::translate('itemviewer.noMult')
                    ) . $message
                );

                // Checks if donotdisplayifnotchecked is set
                if ($this->getItemConfigurationAttribute('donotdisplayifnotchecked')) {
                    $renderIfNotChecked = '';
                }
            }
            $htmlArray[] = ($checked ? $renderIfChecked : $renderIfNotChecked);
        }
        return $this->arrayToHTML($htmlArray);
    }
}
