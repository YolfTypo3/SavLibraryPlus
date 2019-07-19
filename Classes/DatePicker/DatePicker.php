<?php
namespace YolfTypo3\SavLibraryPlus\DatePicker;

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
use YolfTypo3\SavLibraryPlus\Compatibility\EnvironmentCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Exception;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Date picker.
 *
 * @package SavLibraryPlus
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
     * @return void
     */
    public function __construct()
    {
        self::$datePickerLanguageFile = 'calendar-' . $this->getTypoScriptFrontendController()->config['config']['language'] . '.js';
        $extensionWebPath = AbstractController::getExtensionWebPath(AbstractController::LIBRARY_NAME);
        $datePickerLanguagePath = $extensionWebPath . self::$datePickerPath . 'lang/';
        if (file_exists($datePickerLanguagePath . self::$datePickerLanguageFile) === false) {
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
     * @return void
     */
    protected static function addCascadingStyleSheet()
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $key = self::KEY . '.';
        $extensionTypoScriptConfiguration = ExtensionConfigurationManager::getTypoScriptConfiguration();
        $datePickerTypoScriptConfiguration = $extensionTypoScriptConfiguration[$key];
        if (empty($datePickerTypoScriptConfiguration['stylesheet']) === false) {
            // The style sheet is given by the extension TypoScript
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($datePickerTypoScriptConfiguration['stylesheet']);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(EnvironmentCompatibility::getSitePath()));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                ]));
            }
        } else {
            $libraryTypoScriptConfiguration = LibraryConfigurationManager::getTypoScriptConfiguration();
            $datePickerTypoScriptConfiguration = $libraryTypoScriptConfiguration[$key];
            if (empty($datePickerTypoScriptConfiguration['stylesheet']) === false) {
                // The style sheet is given by the library TypoScript
                $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($datePickerTypoScriptConfiguration['stylesheet']);
                if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                    $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(EnvironmentCompatibility::getSitePath()));
                    AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
                } else {
                    throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                        htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                    ]));
                }
            } else {
                // The style sheet is the default one
                $extensionWebPath = AbstractController::getExtensionWebPath($extensionKey);
                $cascadingStyleSheet = $extensionWebPath . self::$datePickerPath . 'css/' . self::$datePickerCssFile;
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            }
        }
    }

    /**
     * Adds javascript
     *
     * @return void
     */
    public static function addJavaScript()
    {
        $extensionWebPath = AbstractController::getExtensionWebPath(AbstractController::LIBRARY_NAME);
        $datePickerSiteRelativePath = $extensionWebPath . self::$datePickerPath;
        AdditionalHeaderManager::addJavaScriptFile($datePickerSiteRelativePath . 'js/' . self::$datePickerJsFile);
        AdditionalHeaderManager::addJavaScriptFile($datePickerSiteRelativePath . 'lang/' . self::$datePickerLanguageFile);
        AdditionalHeaderManager::addJavaScriptFile($datePickerSiteRelativePath . 'js/' . self::$datePickerJsSetupFile);
    }

    /**
     * Gets the date picker format
     *
     * @return void
     */
    protected static function getDatePickerFormat()
    {
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
        return null;
    }

    /**
     * Renders the date picker
     *
     * @return void
     */
    public function render($datePickerConfiguration)
    {
        $datePickerSetup = [];
        $datePickerSetup[] = '<a href="#">';
        $datePickerSetup[] = '<img class="datePickerCalendar" id="button_' . $datePickerConfiguration['id'] . '" src="' . $datePickerConfiguration['iconPath'] . '" alt="" title="" />';
        $datePickerSetup[] = '</a>';
        $datePickerSetup[] = '<script type="text/javascript">';
        $datePickerSetup[] = '/*<![CDATA[*/';

        $datePickerSetup[] = '  Calendar.setup({';
        $datePickerSetup[] = '    inputField     :    "input_' . $datePickerConfiguration['id'] . '",';
        $datePickerSetup[] = '    hiddenField     :    "hidden_' . $datePickerConfiguration['id'] . '",';
        $datePickerSetup[] = '    ifFormat       :    "' . $datePickerConfiguration['format'] . '",';
        $datePickerSetup[] = '    date       :    ' . $datePickerConfiguration['date'] * 1000 . ',';
        $datePickerSetup[] = '    fieldSetDate       :    "' . $datePickerConfiguration['fieldSetDate'] . '",';

        // Gets the date picker format
        $datePickerFormat = self::getDatePickerFormat();
        if (empty($datePickerFormat['toolTipDate']) === false) {
            $datePickerSetup[] = '    ttFormat       :    "' . $datePickerFormat['toolTipDate'] . '",';
        }
        if (empty($datePickerFormat['titleBarDate']) === false) {
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
