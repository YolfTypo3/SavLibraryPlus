<?php

$temporaryColumns = [
    'tx_savlibraryplus_config' => [
        'exclude' => 1,
        'label'  => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:fe_users.tx_savlibraryplus_config',
        'config' => [
            'type' => 'text',
            'cols' => 20,
            'rows' => 5,
        ],
    ],
];


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $temporaryColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users',', tx_savlibraryplus_config');
