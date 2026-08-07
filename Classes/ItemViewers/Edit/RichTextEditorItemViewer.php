<?php

declare(strict_types=1);

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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * Edit rich text editor item Viewer.
 *
 * @package SavLibraryPlus
 */
class RichTextEditorItemViewer extends AbstractItemViewer
{
     
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->uc['edit_RTE'] = true;
        $GLOBALS['BE_USER']->user['lang'] = null;
        $GLOBALS['LANG'] = $this->controller->getLanguageService();

        $container = GeneralUtility::getContainer();
        $richtext = $container->get(Richtext::class);
        $richtextConfiguration = $richtext->getConfiguration('', '', $this->controller->getPageId(), '', [
            'richtext' => true,
            'richtextConfiguration' => 'sav_library_plus'
        ]);

        // Renders the Rich Text Element
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData = [
            'renderType' => 'text',
            'fieldName' => $this->getItemConfigurationAttribute('itemName'),
            'processedTca' => [
                'columns' => [
                    $this->getItemConfigurationAttribute('itemName') => [
                        'config' => [
                            'type' => 'text',
                        ]
                    ]
                ]
            ],
            'databaseRow' => [
                'uid' => ''
            ],
            'tableName' => '',
            'defaultLanguageDiffRow' => [
            ],
            'recordTypeValue' => null,
            'effectivePid' => null,
            'inlineStructure' => [],
            'row' => [
                'pid' => $this->controller->getPageId(),
            ],
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [
                        'cols' => $this->getItemConfigurationAttribute('cols'),
                        'rows' => $this->getItemConfigurationAttribute('rows'),
                        'enableRichtext' => true,
                        'richtextConfiguration' => $richtextConfiguration,
                        'richtextConfigurationName' => null,
                    ],
                    'defaultExtras' => 'richtext[]:rte_transform[mode=ts_css]'
                ],
                'itemFormElID' => null,
                'itemFormElName' => $this->getItemConfigurationAttribute('itemName'),
                'itemFormElValue' => html_entity_decode($this->getItemConfigurationAttribute('value') ?? '', ENT_QUOTES)
            ]
        ];

        $formResult = $nodeFactory->create($formData)->render();

        // Adds javaScript and cascading style sheet
        AdditionalHeaderManager::loadJavaScriptModules($formResult['javaScriptModules']);
        AdditionalHeaderManager::addCascadingStyleSheet($formResult['stylesheetFiles'][0]);

        // Renders the view helper
        $htmlArray = [];
        // Adds the DIV elements
        $htmlArray[] = HtmlElements::htmlDivElement([
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;'),
            HtmlElements::htmlAddAttribute('class', 'richtexteditor')
            ],
            $formResult['html']
        );

        return implode(chr(10), $htmlArray);
    }

}

