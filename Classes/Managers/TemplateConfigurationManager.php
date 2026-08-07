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

namespace YolfTypo3\SavLibraryPlus\Managers;

/**
 * Template configuration manager
 *
 * @package SavLibraryPlus
 */
final class TemplateConfigurationManager extends AbstractManager
{
    /**
     * The template configuration
     *
     * @var array
     */
    protected array $templateConfiguration;

    /**
     * Sets the template configuration
     *
     * @param string $templateConfiguration
     *
     * @return void
     */
    public function setTemplateConfiguration(array $templateConfiguration): void
    {
        $this->templateConfiguration = $templateConfiguration;
    }

    /**
     * Gets the item template.
     *
     * @return string
     */
    public function getItemTemplate(): string
    {
        return $this->templateConfiguration['itemTemplate'] ?? '';
    }

    /**
     * Gets the item number before the page break (print views).
     *
     * @return int
     */
    public function getItemsBeforePageBreak(): int
    {
        return intval($this->templateConfiguration['itemsBeforePageBreak']);
    }

    /**
     * Gets the item number before the page break (print views).
     *
     * @return int
     */
    public function getItemsBeforeFirstPageBreak(): int
    {
        return intval($this->templateConfiguration['itemsBeforeFirstPageBreak']);
    }
}
