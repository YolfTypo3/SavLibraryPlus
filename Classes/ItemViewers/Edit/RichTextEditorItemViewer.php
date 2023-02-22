<?php

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;

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
    protected function renderItem()
    {
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->uc['edit_RTE'] = true;

        $richtextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richtextConfiguration = $richtextConfigurationProvider->getConfiguration('', '', $this->getPageId(), '', [
            'richtext' => true,
            'richtextConfiguration' => 'sav_library_plus'
        ]);

        // Renders the Rich Text Element
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData = [
            'renderType' => 'text',
            'inlineStructure' => [],
            'row' => [
                'pid' => $this->getPageId()
            ],
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [
                        'cols' => $this->getItemConfiguration('cols'),
                        'rows' => $this->getItemConfiguration('rows'),
                        'enableRichtext' => true,
                        'richtextConfiguration' => $richtextConfiguration
                    ],
                    'defaultExtras' => 'richtext[]:rte_transform[mode=ts_css]'
                ],
                'itemFormElName' => $this->getItemConfiguration('itemName'),
                'itemFormElValue' => html_entity_decode($this->getItemConfiguration('value'), ENT_QUOTES)
            ]
        ];
        $formResult = $nodeFactory->create($formData)->render();

        // Loads the ckeditor javascript file
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFile('EXT:rte_ckeditor/Resources/Public/JavaScript/Contrib/ckeditor.js');

        // Gets the CKEDITOR.replace callback function and inserts it in the footer
        $sanitizedFieldId = $this->sanitizeFieldId($this->getItemConfiguration('itemName'));
        $requireJsModule = $formResult['requireJsModules'][0];
        if ($requireJsModule instanceof \TYPO3\CMS\Core\Page\JavaScriptModuleInstruction) {
            $configuration = $requireJsModule->getItems()[0]['args'][0]['configuration'];
            $javaScript = [];
            $javaScript[] = 'var editor_' . $sanitizedFieldId .
            ' = CKEDITOR.replace("' . $sanitizedFieldId . '",' .
            json_encode($configuration) . ');';
            $javaScript[] = 'editor_' . $sanitizedFieldId . '.on(\'change\', function(evt) {';
            $javaScript[] = '    document.changed = true;';
            $javaScript[] = '});';
            $pageRenderer->addJsFooterInlineCode($sanitizedFieldId, implode(chr(10), $javaScript));
        } else {
            $mainModuleName = key($requireJsModule);
            $callBackFunction = $requireJsModule[$mainModuleName];

            $match = [];
            if (preg_match('/CKEDITOR\.replace\(.+\);/', $callBackFunction, $match)) {
                $javaScript = [];
                $javaScript[] = 'var editor_' . $sanitizedFieldId . ' = ' . $match[0];
                $javaScript[] = 'editor_' . $sanitizedFieldId . '.on(\'change\', function(evt) {';
                $javaScript[] = '    document.changed = true;';
                $javaScript[] = '});';
                $pageRenderer->addJsFooterInlineCode($sanitizedFieldId, implode(chr(10), $javaScript));
            }
        }

        // Renders the view helper
        $htmlArray = [];
        $htmlArray[] = $formResult['html'];

        return implode(chr(10), $htmlArray);
    }

    /**
     * @param string $itemFormElementName
     * @return string
     */
    protected function sanitizeFieldId(string $itemFormElementName): string
    {
        $fieldId = (string)preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $itemFormElementName);
        return htmlspecialchars((string)preg_replace('/^[^a-zA-Z]/', 'x', $fieldId));
    }
}
