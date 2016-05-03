<?php
namespace SAV\SavLibraryPlus\ItemViewers\General;

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

/**
 * General String item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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

        // Gets the eval attributes
        $evalAttributes = explode(',', $this->getItemConfiguration('eval'));

        $keepzero = $this->getItemConfiguration('keepzero');
        if (empty($value) && empty($keepzero)) {
            $content = '';
        } elseif (in_array('password', $evalAttributes) === TRUE) {
            $content = str_repeat('*', 7);
        } elseif ($this->getItemConfiguration('tsobject')) {
            if ($this->getItemConfiguration('rawhtml')) {
                $content = htmlspecialchars_decode($value);
            } else {
                $content = $value;
            }
        } else {
            $content = nl2br(stripslashes($value));
        }

        return $content;
    }
}
?>
