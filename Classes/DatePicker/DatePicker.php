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

namespace YolfTypo3\SavLibraryPlus\DatePicker;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Exception;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Date picker.
 *
 * @package SavLibraryPlus
 */
final class DatePicker
{

    /**
     * The date picker path
     *
     * @var string
     */
    protected string $datePickerPath = 'Resources/Public/DatePicker/';

    /**
     * The date picker CSS file
     *
     * @var string
     */
    protected string $datePickerCssFile = 'calendar-win2k-2.css';

    /**
     * The javaScript file
     *
     * @var string
     */
    protected string $datePickerJsFile = 'calendar.js';

    protected string $datePickerJsSetupFile = 'calendar-setup.js';

    protected string $datePickerLanguageFile;
    
    /**
     * The controller
     *
     * @var AbstractController
     */
    protected AbstractController $controller;

    /**
     * Constructor
     * 
     * @param AbstractController $controller
     *
     * @return void
     */
    public function __construct(AbstractController $controller)
    {
        $this->controller = $controller;
        $languageCode = $this->controller->getLanguageService()->getLocale()->getLanguageCode();
        $this->datePickerLanguageFile = 'calendar-' . $languageCode . '.js';
        $extensionWebPath = ExtensionManagementUtility::extPath(AbstractController::LIBRARY_NAME);
        $datePickerLanguagePath = $extensionWebPath . $this->datePickerPath . 'lang/';
        if (file_exists($datePickerLanguagePath . $this->datePickerLanguageFile) === false) {
            $this->datePickerLanguageFile = 'calendar-en.js';
        }
        $this->addCascadingStyleSheet();
        $this->addJavaScript();
    }

    /**
     * Adds the date picker css file
     * - from the datePicker.stylesheet TypoScript configuration if any
     * - else from the default css file
     *
     * @return void
     */
    protected function addCascadingStyleSheet(): void
    { 
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        $key = 'datePicker.';
        $datePickerTypoScriptConfiguration = $extensionTypoScriptConfiguration[$key] ?? null;
        // @extensionScannerIgnoreLine
        $stylesheet = $datePickerTypoScriptConfiguration['stylesheet'] ?? null;
        if (! empty($stylesheet)) {
            // The style sheet is given by the extension TypoScript
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($stylesheet);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(Environment::getPublicPath() . '/'));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                ]));
            }
        } else {
            $libraryExtensionKey = AbstractController::LIBRARY_NAME;
            $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($libraryExtensionKey, '');
            $datePickerTypoScriptConfiguration = $libraryTypoScriptConfiguration[$key] ?? null;
            // @extensionScannerIgnoreLine
            $stylesheet = $datePickerTypoScriptConfiguration['stylesheet'] ?? null;
            if (! empty($stylesheet)) {
                // The style sheet is given by the library TypoScript
                $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($stylesheet);
                if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                    $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(Environment::getPublicPath() . '/'));
                    AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
                } else {
                    throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                        htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                    ]));
                }
            } else {
                // The style sheet is the default one
                $cascadingStyleSheet = 'EXT:' . AbstractController::LIBRARY_NAME . '/' . $this->datePickerPath . 'css/' . $this->datePickerCssFile;
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            }
        }
    }

    /**
     * Adds javascript
     *
     * @return void
     */
    public function addJavaScript(): void
    {
        AdditionalHeaderManager::addJavaScriptFile('EXT:' . AbstractController::LIBRARY_NAME . '/' . $this->datePickerPath . 'js/' . $this->datePickerJsFile);
        AdditionalHeaderManager::addJavaScriptFile('EXT:' . AbstractController::LIBRARY_NAME . '/' . $this->datePickerPath . 'lang/' . $this->datePickerLanguageFile);
        AdditionalHeaderManager::addJavaScriptFile('EXT:' . AbstractController::LIBRARY_NAME . '/' . $this->datePickerPath . 'js/' . $this->datePickerJsSetupFile);
    }

    /**
     * Gets the date picker format
     *
     * @return string|null
     */
    protected function getDatePickerFormat(): ?string
    {
        $key = 'datePicker.';
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        $datePickerTypoScriptConfiguration = $extensionTypoScriptConfiguration[$key] ?? null;
        if (is_array($datePickerTypoScriptConfiguration['dateFormat.'] ?? null)) {
            return $datePickerTypoScriptConfiguration['dateFormat.'];
        } else {
            $libraryExtensionKey = AbstractController::LIBRARY_NAME;
            $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($libraryExtensionKey, '');
            $datePickerTypoScriptConfiguration = $libraryTypoScriptConfiguration[$key] ?? null;
            if (is_array($datePickerTypoScriptConfiguration['dateFormat.'] ?? null)) {
                return $datePickerTypoScriptConfiguration['dateFormat.'];
            }
        }
        return null;
    }

    /**
     * Renders the date picker
     * 
     * @param array $datePickerConfiguration
     *
     * @return string
     */
    public function renderDatePicker(array $datePickerConfiguration): string
    {
        // Gets the source for the icon
        $iconPath = $datePickerConfiguration['iconPath'];
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $src = $resourceFactory->retrieveFileOrFolderObject($iconPath)->getProperty('identifier');

        $datePickerSetup = [];
        $datePickerSetup[] = '<a href="#">';
        $datePickerSetup[] = '<img class="datePickerCalendar" id="button_' . $datePickerConfiguration['id'] . '" src="' . $src . '" alt="" title="" />';
        $datePickerSetup[] = '</a>';
        $datePickerSetup[] = '<script type="text/javascript">';
        $datePickerSetup[] = '/*<![CDATA[*/';

        $datePickerSetup[] = '  Calendar.setup({';
        $datePickerSetup[] = '    inputField     :    "input_' . $datePickerConfiguration['id'] . '",';
        $datePickerSetup[] = '    hiddenField     :    "hidden_' . $datePickerConfiguration['id'] . '",';
        $datePickerSetup[] = '    ifFormat       :    "' . $datePickerConfiguration['dateFormat'] . '",';
        $datePickerSetup[] = '    date       :    ' . $datePickerConfiguration['date'] * 1000 . ',';
        $datePickerSetup[] = '    fieldSetDate       :    "' . $datePickerConfiguration['fieldSetDate'] . '",';

        // Gets the date picker format
        $datePickerFormat = $this->getDatePickerFormat();
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

}
