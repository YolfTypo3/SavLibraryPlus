<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;

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
    protected function renderItem()
    {
        $htmlArray = [];

        if ($this->getItemConfiguration('horizontallayout')) {
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
            $message = HtmlElements::htmlSpanElement([
                    HtmlElements::htmlAddAttribute('class', 'radioButtonMessage')
                ],
                stripslashes(FlashMessages::translate($item[0]))
            );

            // Adds the Div element
            if ($this->itemConfigurationNotSet('displayasimage') || $this->getItemConfiguration('displayasimage')) {
                if ($item[1] == $value) {
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
            } elseif ($item[1] == $value) {
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
    protected function renderSelectedAsImage()
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfiguration('radiobuttonselectedimage');
        if (empty($imageFileName)) {
            $imageFileName = 'radioButtonSelected';
        }

        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'radioButtonSelected'),
                HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath($imageFileName)),
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
    protected function renderNotSelectedAsImage()
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfiguration('radiobuttonnotselectedimage');
        if (empty($imageFileName)) {
            $imageFileName = 'radioButtonNotSelected';
        }

        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'radioButtonNotSelected'),
                HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath($imageFileName)),
                HtmlElements::htmlAddAttribute('title', FlashMessages::translate('itemviewer.radioButtonNotSelected')),
                HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('itemviewer.radioButtonNotSelected'))
            ]
        );

        return $content;
    }
}
?>
