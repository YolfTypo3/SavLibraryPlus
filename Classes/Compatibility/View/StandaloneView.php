<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\View;

/**
 * Copyright notice
 *
 * (c) 2013 Laurent Foulloy (yolf.typo3@orange.fr)
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
 * Compatibility with version 7.x.
 *
 * @package SavLibraryPlus
 */
class StandaloneView extends \TYPO3\CMS\Fluid\View\StandaloneView
{

    /**
     * Sets the absolute path to the folder that contains Fluid layout files
     *
     * @param string $layoutRootPath
     *            Fluid layout root path
     * @return none
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        if (version_compare(TYPO3_version, '7.0', '<')) {
            parent::setLayoutRootPath($layoutRootPath);
        } else {
            parent::setLayoutRootPaths(array(
                $layoutRootPath
            ));
        }
    }

    /**
     * Sets the absolute path to the folder that contains Fluid partial files.
     *
     * @param string $partialRootPath
     *            Fluid partial root path
     * @return none
     */
    public function setPartialRootPath($partialRootPath)
    {
        if (version_compare(TYPO3_version, '7.0', '<')) {
            parent::setPartialRootPath($partialRootPath);
        } else {
            parent::setPartialRootPaths(array(
                $partialRootPath
            ));
        }
    }
}
?>
