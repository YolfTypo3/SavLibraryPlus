<?php
defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use YolfTypo3\SavLibraryPlus\Form\Element\Help;
use YolfTypo3\SavLibraryPlus\Routing\Aspect\SavLibraryPlusPatternMapper;
use YolfTypo3\SavLibraryPlus\Routing\Enhancer\SavLibraryPlusPluginEnhancer;

(function () {
    if (ExtensionManagementUtility::isLoaded('rte_ckeditor')) {
        $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['sav_library_plus'] = 
            'EXT:sav_library_plus/Configuration/RTE/SavLibraryPlus.yaml';
    }

    // Registers the help node
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1565023070] = [
        'nodeName' => 'help',
        'priority' => 40,
        'class' => Help::class
    ];

    // Adds the SavLibraryPlusPlugin enhancer
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers']['SavLibraryPlusPlugin'] = 
        SavLibraryPlusPluginEnhancer::class;

})();