<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\RichTextEditor;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
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
 * @version $ID:$
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
            $GLOBALS['TSFE']->id,
            '',
            ['richtext' => true,
                'richtextConfiguration' => 'sav_library_plus',
            ]
            );

        // Renders the Rich Text Element
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData = array(
            'renderType' => 'text',
            'inlineStructure' => array(),
            'row' => array(
                'pid' => $GLOBALS['TSFE']->id
            ),
            'parameterArray' => array(
                'fieldConf' => array(
                    'config' => array(
                        'cols' => $this->getItemConfiguration('cols'),
                        'rows' => $this->getItemConfiguration('rows'),
                        'enableRichtext' => true,
                        'richtextConfiguration' => $richtextConfiguration,
                    ),
                    'defaultExtras' => 'richtext[]:rte_transform[mode=ts_css]'

                ),
                'itemFormElName' => $this->getItemConfiguration('itemName'),
                'itemFormElValue' => html_entity_decode($this->getItemConfiguration('value'), ENT_QUOTES, $GLOBALS['TSFE']->renderCharset)
            )
        );
        $formResult = $nodeFactory->create($formData)->render();

        // Loads the ckeditor javascript file
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFile('EXT:rte_ckeditor/Resources/Public/JavaScript/Contrib/ckeditor.js');

        // Gets the CKEDITOR.replace callback function and inserts it in the footer
        $requireJsModule = $formResult['requireJsModules'][0];
        $mainModuleName =  key($requireJsModule);
        $callBackFunction = $requireJsModule[$mainModuleName];

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

}
?>
