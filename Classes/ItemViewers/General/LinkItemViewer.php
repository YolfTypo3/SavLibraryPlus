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

/**
 * General Link item Viewer.
 *
 * @package SavLibraryPlus
 */
class LinkItemViewer extends AbstractItemViewer
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

        // Checks if the link is related to a RTF file
        if ($this->getItemConfiguration('generatertf')) {
            if (empty($value) === false) {
                $path_parts = pathinfo($this->getItemConfiguration('savefilertf'));
                $folder = $path_parts['dirname'];
                $this->setItemConfiguration('folder', $folder);
                $fileName = $folder . '/' . $value;

                // Checks if the file exists
                if (file_exists($fileName)) {
                    $content .= $this->makeLink($value);
                } else {
                    $content .= $value;
                }
            }
        } else {
            // Adds the typolink
            $content = $this->makeUrlLink($value);
        }

        return $content;
    }
}
?>
