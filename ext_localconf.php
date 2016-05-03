<?php
if (! defined('TYPO3_MODE'))
    die('Access denied.');

// Register FormEngine node type resolver hook to render RTE in FormEngine if enabled
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'][1450181820] = array(
    'nodeName' => 'text',
    'priority' => 50,
    'class' => \SAV\SavLibraryPlus\Form\Resolver\RichTextNodeResolver::class
);

// Makes the extension version available to the extension scripts
require (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'ext_emconf.php');

$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['version'] = $EM_CONF[$_EXTKEY]['version'];

?>
