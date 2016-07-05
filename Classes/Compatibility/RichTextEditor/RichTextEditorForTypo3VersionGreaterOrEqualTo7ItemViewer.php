<?php
namespace SAV\SavLibraryPlus\Compatibility\RichTextEditor;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use SAV\SavLibraryPlus\ItemViewers\Edit\AbstractItemViewer;
use SAV\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * Edit rich text editor item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class RichTextEditorForTypo3VersionGreaterOrEqualTo7ItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    public function renderItem()
    {

        // Adds the rich text editor cascading style sheet, if any
        if (!empty($this->getItemConfiguration('rtestylesheet'))) {
            $content = 'RTE.default.contentCSS=' . $this->getItemConfiguration('rtestylesheet');
            ExtensionManagementUtility::addPageTSConfig($content);
        }

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
                        'rows' => $this->getItemConfiguration('rows')
                    ),
                    'defaultExtras' => 'richtext[]'
                ),
                'itemFormElName' => $this->getItemConfiguration('itemName'),
                'itemFormElValue' => html_entity_decode($this->getItemConfiguration('value'), ENT_QUOTES, $GLOBALS['TSFE']->renderCharset)
            )
        );
        $formResult = $nodeFactory->create($formData)->render();

        // Adds the style sheets
        foreach ($formResult['stylesheetFiles'] as $stylesheetFile) {
            AdditionalHeaderManager::addCascadingStyleSheet($stylesheetFile);
        }

        // Defines the TYPO3 variable
        AdditionalHeaderManager::addJavaScriptInlineCode('variable', 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);');

        // Adds the require javascript modules
        foreach ($formResult['requireJsModules'] as $requireJsModule) {
            AdditionalHeaderManager::loadRequireJsModule($requireJsModule);
        }

        // Loads the jquery javascript file
        AdditionalHeaderManager::addJavaScriptFile(ExtensionManagementUtility::siteRelPath('core') . 'Resources/Public/JavaScript/Contrib/jquery/jquery-' . PageRenderer::JQUERY_VERSION_LATEST . '.js');

        // Loads the ext Js
        AdditionalHeaderManager::loadExtJS();

        // Loads other javascript files
        AdditionalHeaderManager::addJavaScriptFile(ExtensionManagementUtility::siteRelPath('backend') . 'Resources/Public/JavaScript/notifications.js');
        AdditionalHeaderManager::addJavaScriptFile(ExtensionManagementUtility::siteRelPath('rtehtmlarea') . 'Resources/Public/JavaScript/HTMLArea/NameSpace/NameSpace.js');

        // Adds information for the settings
        AdditionalHeaderManager::addInlineSettingArray('FormEngine', array(
            'formName' => 'data',
            'backPath' => ''
        ));

        // Adds the javascript for processing the field on save action
        $editorNumber = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $this->getItemConfiguration('itemName'));
        AdditionalHeaderManager::addJavaScript('checkIfRteChanged', 'checkIfRteChanged(\'' . $editorNumber . '\');');
        AdditionalHeaderManager::addJavaScript('rteUpdate', $this->addOnSubmitJavaScriptCode());

        // Renders the view helper
        $htmlArray = array();
        $htmlArray[] = preg_replace('/<input [^>]+>/', '', $formResult['html']);

        // Adds the javaScript after the textarea tag
        $htmlArray[] = '<script type="text/javascript">';
        $htmlArray[] = '/*<![CDATA[*/';
        foreach ($formResult['additionalJavaScriptPost'] as $additionalJavaScriptPost) {
            $htmlArray[] = $additionalJavaScriptPost;
        }
        $htmlArray[] = '/*]]>*/';
        $htmlArray[] = '</script>';

        return implode(chr(10), $htmlArray);
    }

    /**
     * Adds the RTE init code.
     *
     * @return string
     */
    protected function addRteInitJsCode()
    {
        // Adds the initial javascript
        $javaScriptCode = $this->additionalJS_initial;
        // Adds the additional javaScript
        $javaScriptCode .= '<script type="text/javascript">';
        $javaScriptCode .= $this->additionalJS_pre['rtehtmlarea-loadJScode'];
        $javaScriptCode .= '</script>';

        // Adds the javaScript
        AdditionalHeaderManager::addJavaScriptInlineCode('RichTextEditor', $javaScriptCode);
    }

    /**
     * Return the Javascript code for copying the HTML code from the editor into the hidden input field.
     *
     * @return void
     */
    protected function addOnSubmitJavaScriptCode()
    {
        $editorNumber = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $this->getItemConfiguration('itemName'));

        $onSubmitCode = array();
        $onSubmitCode[] = 'if (RTEarea[' . GeneralUtility::quoteJSvalue($editorNumber) . ']) {';
        $onSubmitCode[] = '    var field = document.getElementById(' . GeneralUtility::quoteJSvalue('RTEarea' . $editorNumber) . ');';
        $onSubmitCode[] = '    if (field && field.nodeName.toLowerCase() == \'textarea\') {';
        $onSubmitCode[] = '        field.value = RTEarea[' . GeneralUtility::quoteJSvalue($editorNumber) . '].editor.getHTML();';
        $onSubmitCode[] = '    }';
        $onSubmitCode[] = '} else {';
        $onSubmitCode[] =     'OK = 0;';
        $onSubmitCode[] = '};';
        return implode(LF, $onSubmitCode);
    }
}
?>
