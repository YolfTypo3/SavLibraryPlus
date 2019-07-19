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

use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    public function render()
    {
        // Sets the item configuration for the rendering whose type is provided by the renderType attribute
        $itemConfiguration = $this->itemConfiguration;
        $itemConfiguration['fieldType'] = $itemConfiguration['renderType'];
        unset($itemConfiguration['renderType']);

        // Changes the item viewer directory to Default if the attribute edit is set to zero
        $itemViewerDirectory = (($itemConfiguration['edit'] === '0' || $this->getController()->getViewer() === null) ? AbstractViewer::DEFAULT_ITEM_VIEWERS_DIRECTORY : $this->getController()
            ->getViewer()
            ->getItemViewerDirectory());

        // Creates the item viewer
        $fieldType = (empty($itemConfiguration['fieldType']) ? 'String' : $itemConfiguration['fieldType']);
        $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\' . $itemViewerDirectory . '\\' . $fieldType . 'ItemViewer';
        $itemViewer = GeneralUtility::makeInstance($className);
        $itemViewer->injectController($this->getController());
        $itemViewer->injectItemConfiguration($itemConfiguration);

        // Renders the item
        return $itemViewer->render();
    }
}
?>
