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

/**
 * Edit Textarea item Viewer.
 *
 * @package SavLibraryPlus
 */
class TextItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        // Adds the textarea element
        $content = HtmlElements::htmlTextareaElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('cols', $this->getItemConfigurationAttribute('cols')),
                HtmlElements::htmlAddAttribute('rows', $this->getItemConfigurationAttribute('rows')),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ],
            $this->getItemConfigurationAttribute('value')
        );

        return $content;
    }
}
