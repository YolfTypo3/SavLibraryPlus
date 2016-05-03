<?php
namespace SAV\SavLibraryPlus\DatePicker;

/**
 * Copyright notice
 *
 * (c) 2012 Laurent Foulloy (yolf.typo3@orange.fr)
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
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use SAV\SavLibraryPlus\Managers\AdditionalHeaderManager;
use SAV\SavLibraryPlus\Managers\LibraryConfigurationManager;
use SAV\SavLibraryPlus\Controller\FlashMessages;

/**
 * Date picker.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class DatePicker
{

    // Constants
    const KEY = 'datePicker';

    /**
     * The date picker path
     *
     * @var string
     */
    protected static $datePickerPath = 'Classes/DatePicker/';

    /**
     * The date picker CSS file
     *
     * @var string
     */
    protected static $datePickerCssFile = 'calendar-win2k-2.css';

    /**
     * The javaScript file
     *
     * @var string
     */
    protected static $datePickerJsFile = 'calendar.js';

    protected static $datePickerJsSetupFile = 'calendar-setup.js';

    protected static $datePickerLanguageFile;

    /**
     * Constructor
     *
     * @return none
     */
    public function __construct()
    {
        self::$datePickerLanguageFile = 'calendar-' . $GLOBALS['TSFE']->config['config']['language'] . '.js';
        $datePickerLanguagePath = ExtensionManagementUtility::siteRelPath(AbstractController::LIBRARY_NAME) . self::$datePickerPath . 'lang/';
        if (file_exists($datePickerLanguagePath . self::$datePickerLanguageFile) === FALSE) {
            self::$datePickerLanguageFile = 'calendar-en.js';
        }
        self::addCascadingStyleSheet();
        self::addJavaScript();
    }

    /**
     * Adds the date picker css file
     * - from the datePicker.stylesheet TypoScript configuration if any
     * - else from the default css file
     *
     * @return none
     */
    protected static function addCascadingStyleSheet()
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $key = self::KEY . '.';
        $extensionTypoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        $datePickerTypoScriptConfiguration = $extensionTypoScriptConfiguration[$key];
        if (empty($datePickerTypoScriptConfiguration['stylesheet']) === FALSE) {
            // The style sheet is given by the extension TypoScript
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($datePickerTypoScriptConfiguration['stylesheet']);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(PATH_site));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new \SAV\SavLibraryPlus\Exception(FlashMessages::translate('error.fileDoesNotExist', array(
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                )));
            }
        } else {
            $libraryTypoScriptConfiguration = LibraryConfigurationManager::getTypoScriptConfiguration();
            $datePickerTypoScriptConfiguration = $libraryTypoScriptConfiguration[$key];
            if (empty($datePickerTypoScriptConfiguration['stylesheet']) === FALSE) {
                // The style sheet is given by the library TypoScript
                $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($datePickerTypoScriptConfiguration['stylesheet']);
                if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                    $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(PATH_site));
                    AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
                } else {
                    throw new \SAV\SavLibraryPlus\Exception(FlashMessages::translate('error.fileDoesNotExist', array(
                        htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                    )));
                }
            } else {
                // The style sheet is the default one
                $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($extensionKey) . self::$datePickerPath . 'css/' . self::$datePickerCssFile;
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            }
        }
    }

    /**
     * Adds javascript
     *
     * @return none
     */
    public static function addJavaScript()
    {
        $datePickerSiteRelativePath = ExtensionManagementUtility::siteRelPath(AbstractController::LIBRARY_NAME) . self::$datePickerPath;
        AdditionalHeaderManager::addJavaScriptFile($datePickerSiteRelativePath . 'js/' . self::$datePickerJsFile);
        AdditionalHeaderManager::addJavaScriptFile($datePickerSiteRelativePath . 'lang/' . self::$datePickerLanguageFile);
        AdditionalHeaderManager::addJavaScriptFile($datePickerSiteRelativePath . 'js/' . self::$datePickerJsSetupFile);
    }

    /**
     * Gets the date picker format
     *
     * @return none
     */
    protected static function getDatePickerFormat()
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $key = self::KEY . '.';
        $extensionTypoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        $datePickerTypoScriptConfiguration = $extensionTypoScriptConfiguration[$key];
        if (is_array($datePickerTypoScriptConfiguration['format.'])) {
            return $datePickerTypoScriptConfiguration['format.'];
        } else {
            $libraryTypoScriptConfiguration = LibraryConfigurationManager::getTypoScriptConfiguration();
            $datePickerTypoScriptConfiguration = $libraryTypoScriptConfiguration[$key];
            if (is_array($datePickerTypoScriptConfiguration['format.'])) {
                return $datePickerTypoScriptConfiguration['format.'];
            }
        }
        return NULL;
    }

    /**
     * Renders the date picker
     *
     * @return none
     */
    public function render($datePickerConfiguration)
    {
        $datePickerSetup[] = '<a href="#">';
        $datePickerSetup[] = '<img class="datePickerCalendar" id="button_' . $datePickerConfiguration['id'] . '" src="' . $datePickerConfiguration['iconPath'] . '" alt="" title="" />';
        $datePickerSetup[] = '</a>';
        $datePickerSetup[] = '<script type="text/javascript">';
        $datePickerSetup[] = '/*<![CDATA[*/';
        $datePickerSetup[] = '  Calendar.setup({';
        $datePickerSetup[] = '    inputField     :    "input_' . $datePickerConfiguration['id'] . '",';
        $datePickerSetup[] = '    ifFormat       :    "' . $datePickerConfiguration['format'] . '",';

        // Gets the date picker format
        $datePickerFormat = self::getDatePickerFormat();
        if (empty($datePickerFormat['toolTipDate']) === FALSE) {
            $datePickerSetup[] = '    ttFormat       :    "' . $datePickerFormat['toolTipDate'] . '",';
        }
        if (empty($datePickerFormat['titleBarDate']) === FALSE) {
            $datePickerSetup[] = '    tbFormat       :    "' . $datePickerFormat['titleBarDate'] . '",';
        }
        $datePickerSetup[] = '    button         :    "button_' . $datePickerConfiguration['id'] . '",';
        $datePickerSetup[] = '    showsTime      :    ' . ($datePickerConfiguration['showsTime'] ? 'true' : 'false') . ',';
        $datePickerSetup[] = '    singleClick    :    true';
        $datePickerSetup[] = '  });';
        $datePickerSetup[] = '/*]]>*/';
        $datePickerSetup[] = '</script>';

        return implode(chr(10), $datePickerSetup);
    }
}

?>
