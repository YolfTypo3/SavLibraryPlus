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

namespace YolfTypo3\SavLibraryPlus\Compatibility;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compatibility class to the page repository
 *
 * @package SavLibraryPlus
 */
class PageRepositoryCompatibility
{
    /**
     * @todo Will be removed in TYPO3 12
     */

    /**
     * Gets the Page Repository
     *
     * @return string
     */
    public static function getPageRepositoryClassName(): string
    {
        if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '10.0', '<')) {
            // @extensionScannerIgnoreLine
            return \TYPO3\CMS\Frontend\Page\PageRepository::class;
        } else {
            return \TYPO3\CMS\Core\Domain\Repository\PageRepository::class;
        }
    }
}
