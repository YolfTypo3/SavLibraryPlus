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

use TYPO3\CMS\Core\Utility\PathUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * General Radio buttons item Viewer.
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
        // Gets the value
        $value = $this->getItemConfigurationAttribute('value');

        // Adds the option elements
        $items = $this->getItemConfigurationAttribute('items');
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
            $itemLabel = $item['label'] ?? $item[0];
            $message = HtmlElements::htmlSpanElement([
                    HtmlElements::htmlAddAttribute('class', 'radioButtonMessage')
                ],
                stripslashes(FlashMessages::translate($itemLabel))
            );

            // Adds the Div element
            $itemValue = $item['value'] ?? $item[1];
            if ($this->itemConfigurationAttributeNotSet('displayasimage') || $this->getItemConfigurationAttribute('displayasimage')) {
                if ($itemValue == $value) {
                    $htmlArray[] = HtmlElements::htmlDivElement([
                            HtmlElements::htmlAddAttribute('class', $class)
                        ],
                        $this->renderSelectedAsImage() . $message
                    );
                } else {
                    $htmlArray[] = HtmlElements::htmlDivElement([
                            HtmlElements::htmlAddAttribute('class', $class)
                        ],
                        $this->renderNotSelectedAsImage() . $message
                    );
                }
            } elseif ($itemValue == $value) {
                $htmlArray[] = HtmlElements::htmlDivElement([
                        HtmlElements::htmlAddAttribute('class', $class)
                    ],
                    $message
                );
            }
        }

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Renders a checked checkbox as an image.
     *
     * @return string
     */
    protected function renderSelectedAsImage(): string
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfigurationAttribute('radiobuttonselectedimage');
        if (empty($imageFileName)) {
            $imageFileName = 'radioButtonSelected';
        }

        $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath($imageFileName);
        $src = $this->getResourceWebPath($iconPath);
        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'radioButtonSelected'),
                HtmlElements::htmlAddAttribute('src', $src),
                HtmlElements::htmlAddAttribute('title', FlashMessages::translate('itemviewer.radioButtonSelected')),
                HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('itemviewer.radioButtonSelected'))
            ]
        );

        return $content;
    }

    /**
     * Renders a unchecked checkbox as an image.
     *
     * @return string
     */
    protected function renderNotSelectedAsImage(): string
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfigurationAttribute('radiobuttonnotselectedimage');
        if (empty($imageFileName)) {
            $imageFileName = 'radioButtonNotSelected';
        }

        $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath($imageFileName);
        $src = $this->getResourceWebPath($iconPath);
        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'radioButtonNotSelected'),
                HtmlElements::htmlAddAttribute('src', $src),
                HtmlElements::htmlAddAttribute('title', FlashMessages::translate('itemviewer.radioButtonNotSelected')),
                HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('itemviewer.radioButtonNotSelected'))
            ]
        );

        return $content;
    }
}
