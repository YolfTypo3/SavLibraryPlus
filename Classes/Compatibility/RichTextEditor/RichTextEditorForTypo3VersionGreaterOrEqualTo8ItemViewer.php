<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\RichTextEditor;

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

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\ItemViewers\Edit\AbstractItemViewer;

/**
 * Edit rich text editor item Viewer.
 *
 * @package SavLibraryPlus
 */
class RichTextEditorForTypo3VersionGreaterOrEqualTo8ItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    public function renderItem()
    {
        $richtextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richtextConfiguration = $richtextConfigurationProvider->getConfiguration(
            '',
            '',
            $this->getTypoScriptFrontendController()->id,
            '',
            ['richtext' => true,
                'richtextConfiguration' => 'sav_library_plus',
            ]
            );

        // Renders the Rich Text Element
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData = [
            'renderType' => 'text',
            'inlineStructure' => [],
            'row' => [
                'pid' => $GLOBALS['TSFE']->id
            ],
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [
                        'cols' => $this->getItemConfiguration('cols'),
                        'rows' => $this->getItemConfiguration('rows'),
                        'enableRichtext' => true,
                        'richtextConfiguration' => $richtextConfiguration,
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
        $requireJsModule = $formResult['requireJsModules'][0];
        $mainModuleName =  key($requireJsModule);
        $callBackFunction = $requireJsModule[$mainModuleName];

        $match = [];
        if (preg_match('/CKEDITOR\.replace\("(.+__(\d+)_)".+\);/', $callBackFunction, $match)) {
            $javaScript = [];
            $javaScript[] = 'var editor' . $match[2] . ' = ' . $match[0];
            $javaScript[] = 'editor' . $match[2] . '.on(\'change\', function(evt) {';
            $javaScript[] = '    document.changed = true;';
            $javaScript[] = '});';
            $pageRenderer->addJsFooterInlineCode($match[1], implode(chr(10), $javaScript));
        }

        // Renders the view helper
        $htmlArray = [];
        $htmlArray[] = $formResult['html'];

        return implode(chr(10), $htmlArray);
    }
    
    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

}
?>
