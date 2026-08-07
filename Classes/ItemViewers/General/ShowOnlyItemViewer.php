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

use YolfTypo3\SavLibraryPlus\Viewers\AbstractViewer;

/**
 * General Show only item Viewer.
 *
 * @package SavLibraryPlus
 */
class ShowOnlyItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    public function render(): string
    {
        // Sets the item configuration for the rendering whose type is provided by the renderType attribute
        $itemConfiguration = $this->itemConfiguration;
        $itemConfiguration['fieldType'] = $itemConfiguration['renderType'];
        unset($itemConfiguration['renderType']);

        // Changes the item viewer directory to Default if the attribute edit is set to zero
        $itemViewerDirectory = (((isset($itemConfiguration['edit']) && $itemConfiguration['edit'] === '0') || $this->controller->getViewer() === null) ? AbstractViewer::DEFAULT_ITEM_VIEWERS_DIRECTORY : $this->controller
            ->getViewer()
            ->getItemViewerDirectory());

        // Creates the item viewer
        $fieldType = (empty($itemConfiguration['fieldType']) ? 'String' : $itemConfiguration['fieldType']);
        $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\' . $itemViewerDirectory . '\\' . $fieldType . 'ItemViewer';
        $itemViewer = new ($className)($this->controller);
        $itemViewer->setItemConfiguration($itemConfiguration);

        // Renders the item
        return $itemViewer->render();
    }
}
