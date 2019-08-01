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

/**
 * Compatibility class to sanitize a path.
 *
 * @package SavLibraryKickstarter
 */
class FilePathSanitizer
{
    /**
     * Returns the reference used for the frontend inclusion, checks against allowed paths for inclusion.
     *
     * @param string $originalFileName
     * @return string Resulting filename, is either a full absolute URL or a relative path.
     */
    public static function sanitize(string $originalFileName): string
    {
        if (version_compare(TYPO3_version, '9.4', '<')) {
            // @extensionScannerIgnoreLine
            return self::getTypoScriptFrontendController()->tmpl->getFileName($originalFileName);
        } else {       
            return GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class)->sanitize($originalFileName);
        }
    }
    
    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

?>