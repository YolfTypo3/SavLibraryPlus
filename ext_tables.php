<?php
if (! defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Adds user function for help in flexforms for extension depending on the SAV Library Plus
if (! function_exists('user_savlibraryPlusHelp')) {

    function user_savlibraryPlusHelp($PA, $fobj)
    {
        if (is_array($PA['fieldConf']['config']['userFuncParameters']) && ! empty($PA['fieldConf']['config']['userFuncParameters']['extensionKey'])) {
            $extensionKey = $PA['fieldConf']['config']['userFuncParameters']['extensionKey'];
        } else {
            $extensionKey = 'sav_library_plus';
        }
        $cshTag = $PA['fieldConf']['config']['userFuncParameters']['cshTag'];

        if (version_compare(TYPO3_version, '7.0', '<')) {
            $cshTag = \TYPO3\CMS\Core\Utility\GeneralUtility::lcfirst($cshTag);
            $languageService = $GLOBALS['LANG'];
            $message = $languageService->sL('LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang.xlf:pi_flexform.help');
            $moduleToken = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->generateToken('moduleCall', 'help_cshmanual');
            $helpUrl = 'mod.php?M=help_cshmanual&moduleToken=' . $moduleToken . '&tfID=';
            $cshTag = ($cshTag ? $cshTag . '.*' : '');
        } else {
            $cshTag = \TYPO3\CMS\Core\Utility\GeneralUtility::lcfirst($cshTag);
            $languageService = $GLOBALS['LANG'];
            $message = $languageService->sL('LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang.xlf:pi_flexform.help');
            $moduleToken = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->generateToken('moduleCall', 'help_CshmanualCshmanual');
            $helpUrl = 'index.php?M=help_CshmanualCshmanual&moduleToken=' . $moduleToken . '&tx_cshmanual_help_cshmanualcshmanual[controller]=Help&tx_cshmanual_help_cshmanualcshmanual[action]=detail&tx_cshmanual_help_cshmanualcshmanual[table]=';
            $extension = '';
        }

        $iconSrcAttribute = 'src="../typo3conf/ext/' . $extensionKey . '/Resources/Public/Icons/helpbubble.gif"';
        $icon = '<img ' . $iconSrcAttribute . ' class="typo3-csh-icon" alt="' . $cshTag . '" />';

        return '<a href="#" onclick="vHWin=window.open(\'' . $helpUrl . 'xEXT_' . $extensionKey . ($cshTag ? '_' . $cshTag : '') .  '\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return FALSE;">' . $icon . ' ' . $message . '</a>';
    }
}

// Context sensitive tags
$contextSensitiveHelpFiles = array(
    'helpGeneral' => 'locallang_csh_flexform_helpGeneral',
    'helpInputControls' => 'locallang_csh_flexform_helpInputControls',
    'helpAdvanced' => 'locallang_csh_flexform_helpAdvanced',
    'helpHelpPages' => 'locallang_csh_flexform_helpHelpPages'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_savlibraryplus_export_configuration');

// Sets the Context Sensitive Help
foreach ($contextSensitiveHelpFiles as $contextSensitiveHelpFileKey => $contextSensitiveHelpFile) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_' . $_EXTKEY . '_' . $contextSensitiveHelpFileKey, 'EXT:' . $_EXTKEY . '/Resources/Private/Language/ContextSensitiveHelp/' . $contextSensitiveHelpFile . '.xlf');
}
?>
