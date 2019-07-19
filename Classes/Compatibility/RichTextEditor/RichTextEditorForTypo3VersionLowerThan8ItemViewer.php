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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\ItemViewers\Edit\AbstractItemViewer;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * Edit rich text editor item Viewer.
 *
 * @extensionScannerIgnoreFile
 * @todo Will be removed in TYPO3 v10
 * @package SavLibraryPlus
 */
class RichTextEditorForTypo3VersionLowerThan8ItemViewer extends AbstractItemViewer
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

        $formData = [
            'renderType' => 'text',
            'inlineStructure' => [],
            'databaseRow' => [
                'pid' => $this->getTypoScriptFrontendController()->id
            ],
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [
                        'cols' => $this->getItemConfiguration('cols'),
                        'rows' => $this->getItemConfiguration('rows')
                    ],
                    'defaultExtras' => 'richtext[]:rte_transform[mode=ts_css]'
                ],
                'itemFormElName' => $this->getItemConfiguration('itemName'),
                'itemFormElValue' => html_entity_decode($this->getItemConfiguration('value'), ENT_QUOTES)
            ]
        ];

        $formResult = $nodeFactory->create($formData)->render();

        // Adds the style sheets
        foreach ($formResult['stylesheetFiles'] as $stylesheetFile) {
            AdditionalHeaderManager::addCascadingStyleSheet('typo3/' . $stylesheetFile);
        }

        // Defines the TYPO3 variable
        AdditionalHeaderManager::addJavaScriptInlineCode('variable', 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);');

        // Adds the require javascript modules
        foreach ($formResult['requireJsModules'] as $requireJsModule) {
            self::loadRequireJsModule($requireJsModule);
        }

        // Loads the jquery javascript file
        AdditionalHeaderManager::addJavaScriptFile(AbstractController::getExtensionWebPath('core') . 'Resources/Public/JavaScript/Contrib/jquery/jquery-' . PageRenderer::JQUERY_VERSION_LATEST . '.js');

        // Loads the ext Js
        self::loadExtJS();

        // Loads other javascript files
        AdditionalHeaderManager::addJavaScriptFile(AbstractController::getExtensionWebPath('backend') . 'Resources/Public/JavaScript/notifications.js');
        AdditionalHeaderManager::addJavaScriptFile(AbstractController::getExtensionWebPath('rtehtmlarea') . 'Resources/Public/JavaScript/HTMLArea/NameSpace/NameSpace.js');

        // Adds information for the settings
        AdditionalHeaderManager::addInlineSettingArray(
            'FormEngine',
            [
                'formName' => 'data',
                'backPath' => ''
            ]
        );

        // Adds the javascript for processing the field on save action
        $editorNumber = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $this->getItemConfiguration('itemName'));
        AdditionalHeaderManager::addJavaScript('checkIfRteChanged', 'checkIfRteChanged(\'' . $editorNumber . '\');');
        AdditionalHeaderManager::addJavaScript('rteUpdate', $this->addOnSubmitJavaScriptCode());

        // Renders the view helper
        $htmlArray = [];
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
     * @return string
     */
    protected function addOnSubmitJavaScriptCode()
    {
        $editorNumber = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $this->getItemConfiguration('itemName'));

        $onSubmitCode = [];
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

    /**
     * Loads a required Js module
     *
     * @param string $mainModuleName
     * @return void
     */
    public static function loadRequireJsModule(string $mainModuleName)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        if(is_array($mainModuleName)) {
            $pageRenderer->loadRequireJsModule(key($mainModuleName), current($mainModuleName));
        } else {
            $pageRenderer->loadRequireJsModule($mainModuleName);
        }
    }

    /**
     * Loads the extJS library
     *
     * @return void
     */
    public static function loadExtJS()
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadExtJS();
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
