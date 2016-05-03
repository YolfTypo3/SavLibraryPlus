<?php
namespace SAV\SavLibraryPlus\Managers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy <yolf.typo3@orange.fr>
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
 * Template configuration manager
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class TemplateConfigurationManager extends AbstractManager
{

    /**
     * The template configuration
     *
     * @var array
     */
    protected $templateConfiguration;

    /**
     * Injects the template configuration
     *
     * @param array $templateConfiguration
     *
     * @return none
     */
    public function injectTemplateConfiguration($templateConfiguration)
    {
        $this->templateConfiguration = $templateConfiguration;
    }

    /**
     * Gets the item template.
     *
     * @return string
     */
    public function getItemTemplate()
    {
        return $this->templateConfiguration['itemTemplate'];
    }

    /**
     * Gets the item number before the page break (print views).
     *
     * @return integer
     */
    public function getItemsBeforePageBreak()
    {
        return $this->templateConfiguration['itemsBeforePageBreak'];
    }

    /**
     * Gets the item number before the page break (print views).
     *
     * @return integer
     */
    public function getItemsBeforeFirstPageBreak()
    {
        return $this->templateConfiguration['itemsBeforeFirstPageBreak'];
    }
}

?>