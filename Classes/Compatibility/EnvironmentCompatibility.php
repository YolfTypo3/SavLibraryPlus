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
use TYPO3\CMS\Core\Core\Environment;

/**
 * Compatibility class to get environment paths.
 *
 * @package SavLibraryKickstarter
 */
class EnvironmentCompatibility
{

    public static function getTypo3ConfPath()
    {
        if (version_compare(TYPO3_version, '9.4', '<')) {
            // @extensionScannerIgnoreLine
            return PATH_typo3conf;
        } else {
            return Environment::getPublicPath() . '/typo3conf';
        }
    }

    public static function getThisScriptPath()
    {
        if (version_compare(TYPO3_version, '9.4', '<')) {
            // @extensionScannerIgnoreLine
            return PATH_thisScript;
        } else {
            return Environment::getCurrentScript();
        }
    }

    public static function getSitePath()
    {
        if (version_compare(TYPO3_version, '9.4', '<')) {
            // @extensionScannerIgnoreLine
            return PATH_site;
        } else {
            return Environment::getPublicPath() . '/';
        }
    }
}

?>