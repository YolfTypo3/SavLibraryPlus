<?php
defined('TYPO3_MODE') or die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('rte_ckeditor')) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['sav_library_plus'] = 'EXT:sav_library_plus/Configuration/RTE/SavLibraryPlus.yaml';
}

?>
