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

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Compatibility for Marker based template service
 *
 * The method "substituteMarkerArrayCached" is not implemented in the class
 * \TYPO3\CMS\Core\Service\MarkerBasedTemplateService in TYPO3 v7.6
 *
 * @todo Will be removed in TYPO3 v10
 *
 * @package SavLibraryPlus
 */
class MarkerBasedTemplateServiceCompatibility
{
    public static function getMarkerBasedTemplateService()
    {
        if (version_compare(TYPO3_version, '8.0', '<')) {
            $markerBasedTemplateService = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }  else {
            $markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        }

        return $markerBasedTemplateService;
    }
}