<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\Storage;

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
 * Compatibility for Typo3DbBackend
 *
 * @extensionScannerIgnoreFile
 * @package SavLibraryPlus
 */
class Typo3DbBackendCompatibility
{

    /**
     * Function adapted from \TYPO3\CMS\Extbase\Persistence\Storage\Typo3DbBackend
     *
     * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
     * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
     *
     * @param
     *            @param string $tableName The tablename
     * @param array $rows
     * @return array
     */
    public static function doLanguageAndWorkspaceOverlay(string $tableName, array $rows)
    {
        if (version_compare(TYPO3_version, '9.0', '<')) {
            return Typo3DbBackendCompatibilityForTypo3VersionLowerThan9::doLanguageAndWorkspaceOverlay($tableName, $rows);
        } elseif (version_compare(TYPO3_version, '10.0', '<')) {
            return Typo3DbBackendCompatibilityForTypo3VersionLowerThan10::doLanguageAndWorkspaceOverlay($tableName, $rows);
        } else {
            return Typo3DbBackendCompatibilityForTypo3VersionGreaterOrEqualTo10::doLanguageAndWorkspaceOverlay($tableName, $rows);
        }

    }
}