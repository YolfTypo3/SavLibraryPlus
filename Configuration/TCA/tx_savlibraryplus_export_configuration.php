<?php
if (! defined('TYPO3_MODE'))
    die('Access denied.');

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'fe_group' => 'fe_group'
        ),
        'iconfile' => 'EXT:sav_library_plus/Resources/Public/Icons/icon_tx_savlibraryplus_export_configuration.gif'
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_group,name,cid,configuration'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_group' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        '',
                        0
                    )
                ),
                'foreign_table' => 'fe_groups'
            )
        ),
        'name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration.name',
            'config' => array(
                'type' => 'input',
                'size' => '30'
            )
        ),
        'cid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration.cid',
            'config' => array(
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'int',
                'default' => 0
            )
        ),
        'configuration' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:sav_library_plus/Resources/Private/Language/locallang_db.xlf:tx_savlibraryplus_export_configuration.configuration',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            )
        )
    ),
    'types' => array(
        '0' => array(
            'showitem' => 'hidden, fe_group, name, cid, configuration'
        )
    ),
    'palettes' => array(
    )
);
?>
