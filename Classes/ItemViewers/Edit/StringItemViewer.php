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

/**
 * Edit String item Viewer.
 *
 * @package SavLibraryPlus
 */
class StringItemViewer extends AbstractItemViewer
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
        $value = ($value == null ? '' : $value);

        if ($this->getItemConfiguration('default')) {
            if  ($this->getItemConfiguration('required') && empty($value)) {
                $ondblclick = '';
                $value = $this->getItemConfiguration('default');
            } else {
                $ondblclick = 'this.value=\'' . (! $this->getItemConfiguration('value') ? stripslashes($this->getItemConfiguration('default')) : stripslashes($value)) . '\';';
            }
        } else {
            $ondblclick = '';
        }

        // Checks if the string is a password
        $evalAttributes = explode(',', $this->getItemConfiguration('eval'));
        if (in_array('password', $evalAttributes) === true) {
            // Adds the input password element
            $content = HtmlElements::htmlInputPasswordElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                    HtmlElements::htmlAddAttribute('value', stripslashes($value)),
                    HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
                    HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
                ]
            );
        } else {
            // Adds the Input text element
            $content = HtmlElements::htmlInputTextElement([
                    HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                    HtmlElements::htmlAddAttribute('value', stripslashes($value)),
                    HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
                    HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;'),
                    HtmlElements::htmlAddAttributeIfNotNull('ondblclick', $ondblclick)
                ]
            );
        }

        return $content;
    }
}
?>
