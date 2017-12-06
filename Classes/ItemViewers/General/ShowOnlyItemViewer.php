<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Viewers\AbstractViewer;

/**
 * General Show only item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
        $itemViewerDirectory = (($itemConfiguration['edit'] === '0' || $this->getController()->getViewer() === NULL) ? AbstractViewer::DEFAULT_ITEM_VIEWERS_DIRECTORY : $this->getController()
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
