<?php

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

namespace YolfTypo3\SavLibraryPlus\Managers;

/**
 * Template configuration manager
 *
 * @package SavLibraryPlus
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
     * @return void
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
