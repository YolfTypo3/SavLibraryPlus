<?php
namespace SAV\SavLibraryPlus\ViewHelpers;

/*
 * This script belongs to the FLOW3 package "Kickstart". *
 * *
 * It is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version. *
 * *
 * This script is distributed in the hope that it will be useful, but *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN- *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser *
 * General Public License for more details. *
 * *
 * You should have received a copy of the GNU Lesser General Public *
 * License along with the script. *
 * If not, see http://www.gnu.org/licenses/lgpl.html *
 * *
 * The TYPO3 project - inspiring people to share! *
 */
class RemoveEmptyLinesViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Remove empty lines
     *
     * @return string The altered string.
     * @author Laurent Foulloy <yolf.typo3@orange.fr>
     */
    public function render()
    {
        $content = $this->renderChildren();
        $content = preg_replace('/([\t ]+[\r\n])+/', '', $content);
        $content = preg_replace('/\n\n+/', chr(10), $content);

        return $content;
    }
}
?>
