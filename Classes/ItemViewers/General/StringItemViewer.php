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
 * General String item Viewer.
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

        // Gets the eval attributes
        $evalAttributes = explode(',', $this->getItemConfiguration('eval'));

        $keepzero = $this->getItemConfiguration('keepzero');
        if (empty($value) && empty($keepzero)) {
            $content = '';
        } elseif (in_array('password', $evalAttributes) === true) {
            $content = str_repeat('*', 7);
        } elseif ($this->getItemConfiguration('tsobject')) {
            if ($this->getItemConfiguration('rawhtml')) {
                $content = htmlspecialchars_decode($value);
            } else {
                $content = $value;
            }
        } else {
            if (in_array('upper', $evalAttributes) === true) {
                $value = strtoupper($value);
            }
            if (in_array('lower', $evalAttributes) === true) {
                $value = strtolower($value);
            }
            if (in_array('trim', $evalAttributes) === true) {
                $value = trim($value);
            }
            $content = nl2br(stripslashes($value));
        }

        return $content;
    }
}
?>
