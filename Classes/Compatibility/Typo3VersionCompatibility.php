<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Compatibility class to get information.
 *
 * @package SavLibraryPlus
 */
class Typo3VersionCompatibility
{
    /**
     * @todo Will be removed in TYPO3 11
     */
    public static function getVersion()
    {
        if (class_exists(Typo3Version::class)) {
            $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getVersion();
        } else {
            // @extensionScannerIgnoreLine
            $typo3Version = TYPO3_version;
        }
        return $typo3Version;
    }
}

?>