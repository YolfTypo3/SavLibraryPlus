<?php
defined('TYPO3_MODE') or die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('rte_ckeditor')) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['sav_library_plus'] = 'EXT:sav_library_plus/Configuration/RTE/SavLibraryPlus.yaml';
}

// Registers the help node
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1565023070] = [
    'nodeName' => 'help',
    'priority' => 40,
    'class' => \YolfTypo3\SavLibraryPlus\Form\Element\Help::class
];

// Adds the SavLibraryPlusPattern mapper
$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['SavLibraryPlusPatternMapper'] =
\YolfTypo3\SavLibraryPlus\Routing\Aspect\SavLibraryPlusPatternMapper::class;
