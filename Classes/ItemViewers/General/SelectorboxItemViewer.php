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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * General Selectorbox item Viewer.
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
    protected function renderItem()
    {
        // Gets the value
        $value = $this->getItemConfiguration('value');

        // Finds the selected item
        $items = $this->getItemConfiguration('items');
        $itemFound = false;
        foreach ($items as $item) {
            if ($item[1] == $value) {
                $itemFound = true;
                break;
            }
        }

        // Gets the selected element
        if ($itemFound === true) {
            $content = stripslashes(FlashMessages::translate($item[0]));
        } else {
            return '';
        }

        return $content;
    }
}
