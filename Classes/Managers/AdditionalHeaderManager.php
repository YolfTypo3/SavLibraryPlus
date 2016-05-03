<?php
namespace SAV\SavLibraryPlus\Managers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy <yolf.typo3@orange.fr>
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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Controller\FlashMessages;
use SAV\SavLibraryPlus\Managers\LibraryConfigurationManager;

/**
 * Additional header manager.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class AdditionalHeaderManager
{

    /**
     * Array of javaScript code used for the view
     *
     * @var array
     */
    protected static $javaScript = array();

    /**
     * Adds a cascading style Sheet
     *
     * @param string $cascadingStyleSheet
     *
     * @return none
     */
    public static function addCascadingStyleSheet($cascadingStyleSheet)
    {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addCssFile($cascadingStyleSheet);
    }

    /**
     * gets the cascading style Sheet link
     *
     * @param string $cascadingStyleSheet
     *
     * @return string
     */
    protected static function getCascadingStyleSheetLink($cascadingStyleSheet)
    {
        $cascadingStyleSheetLink = '<link rel="stylesheet" type="text/css" href="' . $cascadingStyleSheet . '" />' . chr(10);
        return $cascadingStyleSheetLink;
    }

    /**
     * Adds a javaScript file
     *
     * @param string $javaScriptFileName
     *
     * @return none
     */
    public static function addJavaScriptFile($javaScriptFileName)
    {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addJsFile($javaScriptFileName);
    }

    /**
     * Adds a javaScript inline code
     *
     * @param string $javaScriptFileName
     *
     * @return none
     */
    public static function addJavaScriptInlineCode($key, $javaScriptInlineCode)
    {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addJsInlineCode($key, $javaScriptInlineCode);
    }

    /**
     * Adds the javaScript header
     *
     * @return none
     */
    public static function addAdditionalJavaScriptHeader()
    {
        if (count(self::$javaScript) > 0) {
            if (count(self::$javaScript['selectAll']) > 0) {
                $javaScriptFileName = ExtensionManagementUtility::siteRelPath(AbstractController::LIBRARY_NAME) .
                    LibraryConfigurationManager::$javaScriptRootPath . '/' . AbstractController::LIBRARY_NAME . '.js';
                self::addJavaScriptFile($javaScriptFileName);
            }
                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $pageRenderer->addJsInlineCode(AbstractController::LIBRARY_NAME, self::getJavaScriptHeader());
        }
    }

    /**
     * Adds javaScript to a given key
     *
     * @param string $key
     *            The key
     * @param array $javaScript
     *            The javaScript
     *
     * @return none
     */
    public static function addJavaScript($key, $javaScript = NULL)
    {
        if (! is_array(self::$javaScript[$key])) {
            self::$javaScript[$key] = array();
        }
        self::$javaScript[$key][] = $javaScript;
    }

    /**
     * Gets the javaScript for a given key
     *
     * @param string $key
     *            The key
     *
     * @return string the javaScript
     */
    protected static function getJavaScript($key)
    {
        if (! empty(self::$javaScript[$key]) && is_array(self::$javaScript[$key])) {
            return implode(chr(10) . '    ', self::$javaScript[$key]);
        } else {
            return '';
        }
    }

    /**
     * Returns the javaScript Header
     *
     * @return string The javaScript Header
     */
    protected static function getJavaScriptHeader()
    {
        $javaScript = array();

        $javaScript[] = '';
        $javaScript[] = '  ' . self::getJavaScript('documentChanged');
        $javaScript[] = '  function checkIfRteChanged(x) {';
        $javaScript[] = '    if (RTEarea[x].editor.plugins.UndoRedo.instance.undoPosition>0) {';
        $javaScript[] = '      document.changed = true;';
        $javaScript[] = '    }';
        $javaScript[] = '  }';
        $javaScript[] = '  function submitIfChanged(x) {';
        $javaScript[] = '    ' . self::getJavaScript('checkIfRteChanged');
        $javaScript[] = '    if (document.changed) {';
        $javaScript[] = '      if (confirm("' . FlashMessages::translate('warning.save') . '"))	{';
        $javaScript[] = '        update(x);';
        $javaScript[] = '        document.getElementById(\'id_\' + x).submit();';
        $javaScript[] = '        return true;';
        $javaScript[] = '      }';
        $javaScript[] = '      return true;';
        $javaScript[] = '    }';
        $javaScript[] = '    return true;';
        $javaScript[] = '  }';
        $javaScript[] = '  function update(x) {';
        $javaScript[] = '    ' . self::getJavaScript('rteUpdate');
        $javaScript[] = '    ' . self::getJavaScript('selectAll');
        $javaScript[] = '    return true;';
        $javaScript[] = '  }';

        return implode(chr(10), $javaScript);
    }

    /**
     * Loads a required Js module (TYPO3 7.x)
     *
     * @param string $mainModuleName
     *
     * @return none
     */
    public static function loadRequireJsModule($mainModuleName)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule($mainModuleName);
    }

    /**
     * Loads the extJS library (TYPO3 7.x)
     *
     * @return none
     */
    public static function loadExtJS()
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadExtJS();
    }

    /**
     * Adds Javascript Inline Setting (TYPO3 7.x)
     *
     * @param string $namespace
     * @param array $array
     * @return void
     */
    public static function addInlineSettingArray($namespace, array $array)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineSettingArray($namespace, $array);
    }
}

?>