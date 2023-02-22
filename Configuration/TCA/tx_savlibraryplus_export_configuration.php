<?php
defined('TYPO3') or die();
if (version_compare(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class)->getVersion(), '10.0', '<')) {
    $interface = [
        'showRecordFieldList' => 'hidden,fe_group,name,cid,configuration'
    ];
} else {
    $interface = [];
}
return [
    'ctrl' => [
        'title' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'fe_group' => 'fe_group'
        ],
        'iconfile' => 'EXT:sav_library_plus/Resources/Public/Icons/icon_tx_savlibraryplus_export_configuration.gif'
    ],
    'interface' => $interface,
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'fe_groups'
            ]
        ],
        'name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration.name',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'cid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration.cid',
            'config' => [
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'int',
                'default' => 0
            ]
        ],
        'configuration' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ]
    ],
    'types' => [
        '0' => [
            'showitem' => 'hidden, fe_group, name, cid, configuration'
        ]
    ],
    'palettes' => []
];
