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

namespace YolfTypo3\SavLibraryPlus\Managers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Additional header manager.
 *
 * @package SavLibraryPlus
 */
class AdditionalHeaderManager
{

    /**
     * Array of javaScript code used for the view
     *
     * @var array
     */
    protected static $javaScript = [];

    /**
     * Adds a cascading style Sheet
     *
     * @param string $cascadingStyleSheet
     *
     * @return void
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
     * @return void
     */
    public static function addJavaScriptFile($javaScriptFileName)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFile($javaScriptFileName);
    }

    /**
     * Adds a javaScript footer file
     *
     * @param string $javaScriptFileName
     *
     * @return void
     */
    public static function addJavaScriptFooterFile(string $javaScriptFileName)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFooterFile($javaScriptFileName);
    }

    /**
     * Adds a javaScript footer inline code
     *
     * @param string $key
     * @param string $javaScriptFileName
     *
     * @return void
     */
    public static function addJavaScriptFooterInlineCode(string $key, string $javaScriptInlineCode)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFooterInlineCode($key, $javaScriptInlineCode);
    }

    /**
     * Adds a javaScript inline code
     *
     * @param string $key
     * @param string $javaScriptFileName
     *
     * @return void
     */
    public static function addJavaScriptInlineCode(string $key, string $javaScriptInlineCode)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsInlineCode($key, $javaScriptInlineCode);
    }

    /**
     * Adds the javaScript header
     *
     * @return void
     */
    public static function addAdditionalJavaScriptHeader()
    {
        if (count(self::$javaScript) > 0) {
            if (is_array(self::$javaScript['selectAll'] ?? null) && count(self::$javaScript['selectAll']) > 0) {
                $extensionWebPath = AbstractController::getExtensionWebPath(AbstractController::LIBRARY_NAME);
                $javaScriptFileName = $extensionWebPath . LibraryConfigurationManager::$javaScriptRootPath . '/' . AbstractController::LIBRARY_NAME . '.js';
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
     * @return void
     */
    public static function addJavaScript($key, $javaScript = null)
    {
        if (! is_array(self::$javaScript[$key] ?? null)) {
            self::$javaScript[$key] = [];
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
        $javaScript = [];

        $javaScript[] = '';
        $javaScript[] = '  ' . self::getJavaScript('documentChanged');
        $javaScript[] = '  function submitIfChanged(x) {';
        $javaScript[] = '    if (document.changed) {';
        $javaScript[] = '      if (confirm("' . FlashMessages::translate('warning.save') . '"))	{';
        $javaScript[] = '        update(x);';
        $javaScript[] = '        document.getElementById(\'id_\' + x).submit();';
        $javaScript[] = '        return false;';
        $javaScript[] = '      }';
        $javaScript[] = '      return true;';
        $javaScript[] = '    }';
        $javaScript[] = '    return true;';
        $javaScript[] = '  }';
        $javaScript[] = '  function update(x) {';
        $javaScript[] = '    ' . self::getJavaScript('selectAll');
        $javaScript[] = '    return true;';
        $javaScript[] = '  }';

        return implode(chr(10), $javaScript);
    }

    /**
     * Adds the javaScript to confirm delete action
     *
     * @param string $className
     *
     * @return void
     */
    public static function addConfirmDeleteJavaScript($className)
    {
        $javaScript = [];

        $javaScript[] = '  function confirmDelete() {';
        $javaScript[] = '    document.activeElement.closest(".' . $className . '").classList.add("deleteWarning");';
        $javaScript[] = '    if (confirm("' . FlashMessages::translate('warning.delete') . '"))	{';
        $javaScript[] = '      return true;';
        $javaScript[] = '    }';
        $javaScript[] = '    document.activeElement.closest(".' . $className . '").classList.remove("deleteWarning");';
        $javaScript[] = '    return false;';
        $javaScript[] = '  }';

        self::addJavaScriptFooterInlineCode('confirmDelete',implode(chr(10), $javaScript));
    }
}
