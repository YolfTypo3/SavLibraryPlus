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

namespace YolfTypo3\SavLibraryPlus\Managers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Exception;

/**
 * General configuration manager
 *
 * @package SavLibraryPlus
 */
final class LibraryConfigurationManager extends AbstractManager
{

    /**
     * The icons path
     *
     * @var string
     */
    public static string $iconRootPath = 'Resources/Public/Icons';

    /**
     * The images path
     *
     * @var string
     */
    public static string $imageRootPath = 'Resources/Public/Images';

    /**
     * The Css path
     *
     * @var string
     */
    public static string $cssRootPath = 'Resources/Public/Css';

    /**
     * JavaScript root path
     *
     * @var string
     */
    public static string $javaScriptRootPath = 'Resources/Public/JavaScript';

    /**
     * The language path
     *
     * @var string
     */
    protected static string $languageRootPath = 'Resources/Private/Language';

    /**
     * The flexforms path
     *
     * @var string
     */
    protected static string $libraryRootPath = 'Configuration/Library';

    /**
     * Allowed icon file name extensions
     *
     * @var string
     */
    protected string $allowedIconFileNameExtensions = '.gif,.png,.jpg,.jpeg';

    /**
     * The library configuration
     *
     * @var array
     */
    protected array $libraryConfiguration;
    
    /**
     * The form configuration
     *
     * @var array
     */
    protected array $formConfiguration;

    /**
     * The images directory
     *
     * @var string
     */
    protected string $imagesDirectory;

    /**
     * Initializes the configuration
     *
     * @return bool
     */
    public function initialize(): bool
    {
        // Checks if the extension is under maintenance
        if ($this->checkIfExtensionIsUnderMaintenance() === true)
            return false;

        // Sets the library configuration
        if ($this->setLibraryAndFormConfiguration() === false)
            return false;

        // Checks the compatibility
        if ($this->checkCompatibility() === false)
            return false;

        // Adds the cascading style sheets
        $this->addCascadingStyleSheets();
        
        return true;
    }

    /**
     * Checks if the extension is under maintenance.
     *
     * @return bool
     */
    protected function checkIfExtensionIsUnderMaintenance(): bool
    {
        // Checks if a global maintenance is requested
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $libraryExtensionKey = AbstractController::LIBRARY_NAME;
        $maintenanceAllowedUsers = explode(',', $extensionConfiguration->get($libraryExtensionKey, 'maintenanceAllowedUsers'));
        if ($extensionConfiguration->get($libraryExtensionKey, 'maintenance')) {
            FlashMessages::addError('error.underMaintenance');
            $userId = $this->controller->getUserManager()->getUserId();
            if (empty($userId) || in_array($userId, $maintenanceAllowedUsers) === false) {
                return true;
            }
        }

        // Checks if a maintenance of the extension is requested
        $extensionKey = $this->controller->getExtensionKey();
        if ($extensionConfiguration->get($extensionKey, 'maintenance')) {
            FlashMessages::addError('error.underMaintenance');
            $userId = $this->controller->getUserManager()->getUserId();
            if (empty($userId) || in_array($userId, $maintenanceAllowedUsers) === false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sets the library configuration
     *
     * @return bool
     */
    protected function setLibraryAndFormConfiguration(): bool
    {
        $extensionKey = $this->controller->getExtensionKey();
        $fileName = self::$libraryRootPath . '/' . GeneralUtility::underscoredToUpperCamelCase(AbstractController::LIBRARY_NAME) . '.xml';

        if (file_exists(ExtensionManagementUtility::extPath($extensionKey) . $fileName) === false) {
            return FlashMessages::addError('error.unknownConfigurationFile', []);
        } else {
            // Sets the library configuration
            $fileName = GeneralUtility::getFileAbsFileName('EXT:' . $extensionKey . '/' . $fileName);
            $this->libraryConfiguration = GeneralUtility::xml2array(file_get_contents($fileName), 'sav_library_plus_pi');

            // Sets the library configuration
            $formIdentifier = $this->controller->getExtensionConfigurationManager()->getFormIdentifier();
            $this->formConfiguration = $this->libraryConfiguration['forms'][$formIdentifier];
            
            return true;
        }
    }

    /**
     * Gets the icon path
     *
     * @param string $fileName
     *            The file name without extension
     *
     * @return string
     */
    public function getIconPath(string $fileName): string
    {
        // The icon directory is taken from the configuration in TS if set,
        // else from the Resources/Icons folder in the extension if it exists,
        // else from the default Resources/Icons in the SAV Library Plus extension if it exists
        // File name extension is added from allowed files name extensions.
        $libraryExtensionKey = AbstractController::LIBRARY_NAME;
        $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($libraryExtensionKey, '');
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        $formTitleKey = $this->getFormTitle(). '.';
        $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey] ?? [];

        // Checks if the file name is in the iconRootPath defined by the form configuration in TS
        $fileNameWithExtension = $this->getFileNameWithExtension(($formTypoScriptConfiguration['iconRootPath'] ?? '') . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['iconRootPath']), strlen(Environment::getPublicPath() . '/')) . '/' . $fileNameWithExtension;
        }
        // If not found, checks if the file name is in the iconRootPath defined by the extension configuration in TS
        $fileNameWithExtension = $this->getFileNameWithExtension(($extensionTypoScriptConfiguration['iconRootPath'] ?? '') . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['iconRootPath']), strlen(Environment::getPublicPath() . '/')) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in the iconRootPath defined by the library configuration in TS
        $fileNameWithExtension = $this->getFileNameWithExtension(($libraryTypoScriptConfiguration['iconRootPath'] ?? '') . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return substr(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['iconRootPath']), strlen(Environment::getPublicPath() . '/')) . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in Resources/Icons folder of the extension
        $fileNameWithExtension = $this->getFileNameWithExtension(ExtensionManagementUtility::extPath($extensionKey) . self::$iconRootPath . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return 'EXT:' . $extensionKey . '/' .  self::$iconRootPath . '/' . $fileNameWithExtension;
        }

        // If not found, checks if the file name is in Resources/Icons folder of the SAV Library Plus extension
        $fileNameWithExtension = $this->getFileNameWithExtension(ExtensionManagementUtility::extPath($libraryExtensionKey) . self::$iconRootPath . '/', $fileName);
        if (! empty($fileNameWithExtension)) {
            return 'EXT:' . $libraryExtensionKey . '/' . self::$iconRootPath . '/' . $fileNameWithExtension;
        }

        return '';
    }

    /**
     * *
     * Gets the icon file name with its extension by checking if it exists in the given path.
     *
     * @param string $path
     *            The file path
     * @param string $fileName
     *            The file name without extension
     *
     * @return string The file name with extension
     */
    protected function getFileNameWithExtension(string $path, string $fileName): string
    {
        $iconFileNameExtensions = explode(',', $this->allowedIconFileNameExtensions);
        foreach ($iconFileNameExtensions as $iconFileNameExtension) {
            if (preg_match('/^[^\.]+\.\w+$/', $fileName) == 0) {
                $fileNameWithExtension = $fileName . $iconFileNameExtension;
            } else {
                $fileNameWithExtension = $fileName;
            }
            if (is_file(GeneralUtility::getFileAbsFileName($path . $fileNameWithExtension))) {
                return $fileNameWithExtension;
            }
        }
        return '';
    }

    /**
     * Gets the images directory
     *
     * @return string
     */
    public function getImageRootPath(string $fileName): string
    {
        // The images directory is taken from the configuration in TS if set,
        // else from the Resources/Images folder in the extension if it exists,
        // else from the default Resources/Images in the library.
        $libraryExtensionKey = AbstractController::LIBRARY_NAME;
        $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($libraryExtensionKey, '');
        $extensionKey = $this->controller->getExtensionKey();
        $extensionTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        $formTitleKey = $this->getFormTitle(). '.';
        $formTypoScriptConfiguration = $extensionTypoScriptConfiguration[$formTitleKey] ?? [];
        if (isset($formTypoScriptConfiguration['imageRootPath']) && is_file((GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['imageRootPath']) . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($formTypoScriptConfiguration['imageRootPath']), strlen(Environment::getPublicPath() . '/')) . '/';
        } elseif (isset($extensionTypoScriptConfiguration['imageRootPath']) && is_file((GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['imageRootPath']) . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($extensionTypoScriptConfiguration['imageRootPath']), strlen(Environment::getPublicPath() . '/')) . '/';
        } elseif (isset($libraryTypoScriptConfiguration['imageRootPath']) && is_file((GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['imageRootPath']) . '/' . $fileName))) {
            return substr(GeneralUtility::getFileAbsFileName($libraryTypoScriptConfiguration['imageRootPath']), strlen(Environment::getPublicPath() . '/')) . '/';
        } elseif (is_file(ExtensionManagementUtility::extPath($extensionKey) . self::$imageRootPath . '/' . $fileName)) {
            return 'EXT:' . $extensionKey . '/' . self::$imageRootPath . '/';
        } else {
            return 'EXT:' . $libraryExtensionKey . '/' . self::$imageRootPath . '/';
        }
    }

    /**
     * Gets the language path
     *
     * @return string The language path
     */
    public function getLanguagePath(): string
    {
        return self::$languageRootPath . '/';
    }

    /**
     * Adds the css files
     *
     * @return void
     */
    public function addCascadingStyleSheets(): void
    {
        // Adds the library cascading style sheet
        $this->addLibraryCascadingStyleSheet();

        // Adds the extension cascading style sheet
        $this->addExtensionCascadingStyleSheet();
    }

    /**
     * Adds the library css file
     * - from the stylesheet TypoScript configuration if any
     * - else from the default css file which is in the "Styles" directory of the SAV Library Plus
     *
     * @return void
     */
    protected function addLibraryCascadingStyleSheet(): void
    {
        $additionalHeaderManager = new AdditionalHeaderManager();
        $libraryExtensionKey = AbstractController::LIBRARY_NAME;
        $typoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($libraryExtensionKey, '');
        // @extensionScannerIgnoreLine
        $stylesheetFileName = $typoScriptConfiguration['stylesheet'] ?? null;
        if (empty($stylesheetFileName)) {
            $cascadingStyleSheet = 'EXT:' . $libraryExtensionKey . '/' . self::$cssRootPath . '/' . $libraryExtensionKey . '.css';
            $additionalHeaderManager->addCascadingStyleSheet($cascadingStyleSheet);
        } else {
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($stylesheetFileName);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(Environment::getPublicPath() . '/'));
                $additionalHeaderManager->addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                ]));
            }
        }
    }

    /**
     * Adds the extension css file if any
     * The css file should be extension.css in the "Css" directory
     * where "extension" is the extension key
     *
     * @return void
     */
    protected function addExtensionCascadingStyleSheet(): void
    {
        $extensionKey = $this->controller->getExtensionKey();
        $typoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey);
        // @extensionScannerIgnoreLine
        $stylesheetFileName = $typoScriptConfiguration['stylesheet'] ?? null;
        if (! empty($stylesheetFileName)) {
            $cascadingStyleSheetAbsoluteFileName = GeneralUtility::getFileAbsFileName($stylesheetFileName);
            if (is_file($cascadingStyleSheetAbsoluteFileName)) {
                $cascadingStyleSheet = substr($cascadingStyleSheetAbsoluteFileName, strlen(Environment::getPublicPath() . '/'));
                AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
            } else {
                throw new Exception(FlashMessages::translate('error.fileDoesNotExist', [
                    htmlspecialchars($cascadingStyleSheetAbsoluteFileName)
                ]));
            }
        } elseif (is_file(ExtensionManagementUtility::extPath($extensionKey) . self::$cssRootPath . '/' . $extensionKey . '.css')) {
            $cascadingStyleSheet = 'EXT:' . $extensionKey . '/' . self::$cssRootPath . '/' . $extensionKey . '.css';
            AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);
        }
    }

    /**
     * Checks the compatibility between the extension version and the library version.
     * Versions are under the format x.y.z. Compatibility is satisfied if x's are the same
     *
     * @return bool
     */
    protected function checkCompatibility(): bool
    {
        // Checks the compatibility between the extension version and the library version.
        // Versions are under the format x.y.z. Compatibility is satisfied if x's are the same
        $libraryVersion = [];
        preg_match('/^([0-9]+)\./', ExtensionManagementUtility::getExtensionVersion(AbstractController::LIBRARY_NAME), $libraryVersion);

        $extensionVersion = [];
        preg_match('/^([0-9]+)\./', $this->libraryConfiguration['general']['version'], $extensionVersion);

        if ($libraryVersion[1] != $extensionVersion[1]) {
            return FlashMessages::addError('error.incorrectVersion');
        } else {
            return true;
        }
    }

    /**
     * Gets the library configuration.
     *
     * @return array
     */
    public function getLibraryConfiguration(): array
    {
        return $this->libraryConfiguration;
    }

    /**
     * Gets a field in the general configuration.
     *
     * @param string $fieldName
     *            The field name
     *
     * @return mixed
     */
    public function getGeneralConfigurationField(string $fieldName): mixed
    {
        return $this->libraryConfiguration['general'][$fieldName];
    }

    /**
     * Gets a field in the general configuration.
     *
     * @param string $fieldName
     *            The field name
     *
     * @return bool
     */
    public function isOverridedTableForLocalization(string $tableName): bool
    {
        return isset($this->libraryConfiguration['general']['overridedTablesForLocalization']) && isset($this->libraryConfiguration['general']['overridedTablesForLocalization'][$tableName]) && $this->libraryConfiguration['general']['overridedTablesForLocalization'][$tableName];
    }

    /**
     * Gets the form configuration.
     *
     * @return array
     */
    public function getFormConfiguration(): array
    {
        $formIdentifier = $this->controller
            ->getExtensionConfigurationManager()
            ->getFormIdentifier();

        if (empty($formIdentifier)) {
            FlashMessages::addError('fatal.noFormSelectedInFlexform');
            return null;
        }
        return $this->libraryConfiguration['forms'][$formIdentifier];
    }

    /**
     * Gets the view identifier.
     *
     * @param string $viewType
     *            - the type of the view
     *
     * @return int
     */
    public function getViewIdentifier(string $viewType): int
    {
        $viewsWithCondition = $this->getViewsWithCondition($viewType);
        if ($viewsWithCondition === null) {
            $getViewIdentifierFunction = 'get' . $viewType . 'Identifier';
            $viewIdentifier = $this->$getViewIdentifierFunction();
            return intval($viewIdentifier);
        } else {
            foreach ($viewsWithCondition as $viewWithConditionKey => $viewWithCondition) {
                $viewWithConditionConfiguration = $viewWithCondition['config'];

                if (empty($viewWithConditionConfiguration['cutif']) === false || empty($viewWithConditionConfiguration['showif']) === false) {
                    // Builds a field configuration manager
                    $fieldConfigurationManager = new (FieldConfigurationManager::class)($this->controller);
                    $fieldConfigurationManager->setKickstarterFieldConfiguration($viewWithConditionConfiguration);

                    // Checks the cutif condition
                    if ($fieldConfigurationManager->cutIf() === false) {
                        return intval($viewWithConditionKey);
                    }
                }
            }
            // If no false condition was found, return the default view
            $getViewIdentifierFunction = 'get' . $viewType . 'Identifier';
            $viewIdentifier = $this->$getViewIdentifierFunction();
            return intval($viewIdentifier);
        }
    }

    /**
     * Gets the view configuration.
     *
     * @param int $viewIdentifier
     *            - the view identifier
     *
     * @return array
     */
    public function getViewConfiguration(int $viewIdentifier): array
    {
        return $this->libraryConfiguration['views'][$viewIdentifier] ?? [];
    }

    /**
     * Gets the list view template configuration.
     *
     * @return array
     */
    public function getListViewTemplateConfiguration(): array
    {
        $listViewIdentifier = $this->getListViewIdentifier();
        return $this->libraryConfiguration['templates'][$listViewIdentifier] ?? [];
    }

    /**
     * Gets the special view template configuration.
     *
     * @return array
     */
    public function getSpecialViewTemplateConfiguration(): array
    {
        $specialViewIdentifier = $this->getSpecialViewIdentifier();
        return $this->libraryConfiguration['templates'][$specialViewIdentifier] ?? [];
    }

    /**
     * Gets the form view template configuration.
     *
     * @return array
     */
    public function getFormViewTemplateConfiguration(): array
    {
        $formViewIdentifier = $this->getFormViewIdentifier();
        return $this->libraryConfiguration['templates'][$formViewIdentifier] ?? [];
    }

    /**
     * Gets the query configuration.
     *
     * @return array
     */
    public function getQueryConfiguration(): array
    {
        $queryIdentifier = $this->getQueryIdentifier();
        return $this->libraryConfiguration['queries'][$queryIdentifier] ?? [];
    }

    /**
     * Searchs for a field configuration in a view configuration
     *
     * @param array $viewConfiguration
     *            The view configuration
     * @param string $fieldKey
     *            the key to search
     *
     * @return array|false The configuration or false if the key is not found
     */
    public function searchFieldConfiguration(array &$viewConfiguration, string $fieldKey): array|false
    {
        foreach ($viewConfiguration as $itemKey => $item) {
            if ($itemKey == $fieldKey) {
                return $item['config'];
            } elseif (isset($item['config']['subform'])) {
                $fieldConfiguration = $this->searchFieldConfiguration($item['config']['subform'], $fieldKey);
                if ($fieldConfiguration != false) {
                    return $fieldConfiguration;
                }
            } elseif (isset($item['fields'])) {
                $fieldConfiguration = $this->searchFieldConfiguration($item['fields'], $fieldKey);
                if ($fieldConfiguration != false) {
                    return $fieldConfiguration;
                }
            }
        }
        return false;
    }

    /**
     * Searchs for the basic field configuration (fieldType, tableName, fieldName) in the library configuration views
     *
     * @param string $fieldKey
     *            the key to search
     * @param array $configuration
     *            The configuration in which the search is performed
     *
     * @return array|false The configuration or false if the key is not found
     */
    public function searchBasicFieldConfiguration(string $fieldKey, ?array $configuration = null): array|false
    {
        if ($configuration === null) {
            $configuration = $this->libraryConfiguration['views'];
        }
        foreach ($configuration as $itemKey => $item) {
            if ($itemKey == $fieldKey) {
                $basicFieldConfiguration = [
                    'fieldType' => $item['config']['fieldType'],
                    'tableName' => $item['config']['tableName'],
                    'fieldName' => $item['config']['fieldName']
                ];
                if ($item['config']['fieldType'] === 'ShowOnly') {
                    $basicFieldConfiguration = array_merge($basicFieldConfiguration, [
                        'renderType' => $item['config']['renderType']
                    ]);
                }
                return $basicFieldConfiguration;
            } elseif (isset($item['config']['subform'])) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item['config']['subform']);
                if ($basicFieldConfiguration != false) {
                    return $basicFieldConfiguration;
                }
            } elseif (isset($item['fields'])) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item['fields']);
                if ($basicFieldConfiguration != false) {
                    return $basicFieldConfiguration;
                }
            } elseif (is_int($itemKey)) {
                $basicFieldConfiguration = $this->searchBasicFieldConfiguration($fieldKey, $item);
                if ($basicFieldConfiguration != false) {
                    return $basicFieldConfiguration;
                }
            }
        }
        return false;
    }



    /**
     * Gets the default date format from the library TypoScript configuration if any.
     *
     * @return string|null
     */
    public function getDefaultDateFormat(): ?string
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey, '');
        $format = $libraryTypoScriptConfiguration['dateFormat.'] ?? null;
        if (is_array($format) && ! empty($format['date'])) {
            return $format['date'];
        } else {
            return null;
        }
    }

    /**
     * Gets the default dateTime format from the library TypoScript configuration if any.
     *
     * @return string|null
     */
    public function getDefaultDateTimeFormat(): ?string
    {
        $extensionKey = AbstractController::LIBRARY_NAME;
        $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey, '');
        $format = $libraryTypoScriptConfiguration['dateFormat.'] ?? null;
        if (is_array($format) && empty($format['dateTime']) === false) {
            return $format['dateTime'];
        } else {
            return null;
        }
    }

    /**
     * Sets the view configuration files from the library TypoScript configuration
     *
     * @return void
     */
    public function setViewConfigurationFilesFromTypoScriptConfiguration(): void
    {
        // Gets the viewer
        $viewer = $this->controller->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the TypoScript configuration
        $extensionKey = AbstractController::LIBRARY_NAME;
        $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey, '');        
        if ($libraryTypoScriptConfiguration === null) {
            return;
        }

        // Sets the template root path if any
        $templateRootPath = $libraryTypoScriptConfiguration['templateRootPath'];
        if (empty($templateRootPath) === false) {
            $viewer->setTemplateRootPath($templateRootPath);
        }

        // Sets the partial root path if any
        $viewType = lcfirst($viewer->getViewType()) . '.';
        if (is_array($libraryTypoScriptConfiguration[$viewType])) {
            $partialRootPath = $libraryTypoScriptConfiguration[$viewType]['partialRootPath'];
        } else {
            $partialRootPath = $libraryTypoScriptConfiguration['partialRootPath'];
        }
        if (empty($partialRootPath) === false) {
            $viewer->setPartialRootPath($partialRootPath);
        }

        // Sets the layout root path if any
        $layoutRootPath = $libraryTypoScriptConfiguration['layoutRootPath'];
        if (empty($layoutRootPath) === false) {
            $viewer->setLayoutRootPath($layoutRootPath);
        }
    }

    /**
     * Sets the link configuration for the view from the TypoScript configuration
     *
     * @return void
     */
    public function setViewLinkConfigurationFromTypoScriptConfiguration(): void
    {
        // Gets the viewer
        $viewer = $this->controller->getViewer();
        if ($viewer === null) {
            return;
        }

        // Gets the library TypoScript configuration
        $extensionKey = AbstractController::LIBRARY_NAME;
        $libraryTypoScriptConfiguration = $this->controller->getPluginTypoScriptConfiguration($extensionKey, '');       
        if ($libraryTypoScriptConfiguration === null) {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $libraryTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === false) {
            $viewer->setLinkConfiguration($linkConfiguration);
            return;
        }

        // Gets the view type
        $viewTypeKey = lcfirst($viewer->getViewType()) . '.';

        // Gets the view TypoScript configuration
        if (is_array($libraryTypoScriptConfiguration[$viewTypeKey])) {
            $viewTypoScriptConfiguration = $libraryTypoScriptConfiguration[$viewTypeKey];
        } else {
            return;
        }

        // Sets the link configuration if any
        $linkConfiguration = $viewTypoScriptConfiguration['link.'];
        if (empty($linkConfiguration) === false) {
            $viewer->setLinkConfiguration($linkConfiguration);
        }
    }
    
    /**
     * Methods for form information
     * 
     */   
    
    /**
     * Gets form configuration item
     *
     * @param string $itemKey
     *
     * @return mixed
     */
    protected function getFormConfigurationItem(string $itemKey): mixed
    {
        return $this->formConfiguration[$itemKey] ?? null;
    }
    
    /**
     * Gets the form title.
     *
     * @return string|null
     */
    public function getFormTitle(): ?string
    {
        return $this->getFormConfigurationItem('title');
    }
    
    /**
     * Gets the list view identifier.
     *
     * @return int
     */
    public function getListViewIdentifier(): int
    {
        return intval($this->getFormConfigurationItem('listView'));
    }
    
    /**
     * Gets the single view identifier.
     *
     * @return int
     */
    public function getSingleViewIdentifier(): int
    {
        return intval($this->getFormConfigurationItem('singleView'));
    }
    
    /**
     * Gets the edit view identifier.
     *
     * @return int
     */
    public function getEditViewIdentifier(): int
    {
        return intval($this->getFormConfigurationItem('editView'));
    }
    
    /**
     * Gets the query identifier.
     *
     * @return int
     */
    public function getQueryIdentifier(): int
    {
        return intval($this->getFormConfigurationItem('query'));
    }
    
    /**
     * Gets the update view identifier.
     *
     * @return int
     */
    public function getFormViewIdentifier(): int
    {
        return intval($this->getFormConfigurationItem('formView'));
    }
    
    /**
     * Gets the special view identifier.
     *
     * @return int
     */
    public function getSpecialViewIdentifier(): int
    {
        return intval($this->getFormConfigurationItem('specialView'));
    }
    
    /**
     * Gets the views with condition for a given view type.
     *
     * @param string $viewType
     *
     * @return array or null
     */
    public function getViewsWithCondition(string $viewType): ? array
    {
        $viewsWithCondition = $this->getFormConfigurationItem('viewsWithCondition');
        $key = lcfirst($viewType);
        if (is_array($viewsWithCondition) && is_array($viewsWithCondition[$key] ?? null)) {
            return $viewsWithCondition[$key];
        } else {
            return null;
        }
    }
    
    /**
     * Gets the user plugin flag.
     *
     * @return bool
     */
    public function getUserPluginFlag(): bool
    {
        return ! empty($this->getFormConfigurationItem('userPlugin') ?? '');
    }
}
