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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compatibility class to get extension configuration information
 *
 * @package SavLibraryKickstarter
 */
class ExtensionConfigurationCompatibility
{

    /**
     * Get a single configuration value, a sub array or the whole configuration.
     *
     * @param string $extension
     *            Extension name
     * @param string $path
     *            Configuration path - eg. "featureCategory/coolThingIsEnabled"
     * @return mixed The value. Can be a sub array or a single value.
     */
    public static function get(string $extension, string $path = '')
    {
        /**
         *
         * @todo will be remove in TYPO3 10
         */
        if (version_compare(TYPO3_version, '9.4', '<')) {
            // @extensionScannerIgnoreLine
            $unserializedConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extension]);
            return $unserializedConfiguration[$path];
        } else {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            return $extensionConfiguration->get($extension, $path);
        }
    }
}

?>
