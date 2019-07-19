<?php
defined('TYPO3_MODE') or die();

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

        $languageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
        $message = '<b>' . $languageService->sL('LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang.xlf:pi_flexform.help') . '</b>';

        return \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xEXT_' . $extensionKey . '_' . $cshTag, '', '', $message . '|');
    }
}

// Context sensitive tags
$contextSensitiveHelpFiles = [
    'helpGeneral' => 'locallang_csh_flexform_helpGeneral',
    'helpInputControls' => 'locallang_csh_flexform_helpInputControls',
    'helpAdvanced' => 'locallang_csh_flexform_helpAdvanced',
    'helpHelpPages' => 'locallang_csh_flexform_helpHelpPages'
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_savlibraryplus_export_configuration');

// Sets the Context Sensitive Help
foreach ($contextSensitiveHelpFiles as $contextSensitiveHelpFileKey => $contextSensitiveHelpFile) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'xEXT_sav_library_plus_' . $contextSensitiveHelpFileKey,
        'EXT:sav_library_plus/Resources/Private/Language/ContextSensitiveHelp/' . $contextSensitiveHelpFile . '.xlf'
    );
}
?>
