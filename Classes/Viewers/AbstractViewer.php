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

namespace YolfTypo3\SavLibraryPlus\Viewers;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Exception;
use YolfTypo3\SavLibraryPlus\Managers\FieldConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;

/**
 * Abstract class Viewer.
 *
 * @package SavLibraryPlus
 */
abstract class AbstractViewer extends AbstractDefaultRootPath
{

    /**
     * The controller
     *
     * @var AbstractController
     */
    protected AbstractController $controller;

    
    protected ViewFactoryInterface $viewFactory;
    
    /**
     * The partial root path
     *
     * @var string
     */
    protected string $partialRootPath = '';

    /**
     * The layout root path
     *
     * @var string
     */
    protected string $layoutRootPath = '';

    /**
     * The template root path
     *
     * @var string
     */
    protected string $templateRootPath;

    /**
     * The template file
     *
     * @var string
     */
    protected string $templateFile;

    /**
     * The link configuration
     *
     * @var array
     */
    protected array $linkConfiguration = [];

    /**
     * Item viewer directory
     *
     * @var string
     */
    protected string $itemViewerDirectory = self::DEFAULT_ITEM_VIEWERS_DIRECTORY;

    /**
     * The new view flag
     *
     * @var bool
     */
    protected bool $isNewView = false;

    /**
     * The library configuration manager
     *
     * @var LibraryConfigurationManager
     */
    protected LibraryConfigurationManager $libraryConfigurationManager;

    /**
     * The field configuration manager
     *
     * @var FieldConfigurationManager
     */
    protected FieldConfigurationManager $fieldConfigurationManager;

    /**
     * The view type
     *
     * @var string
     */
    protected string $viewType;

    /**
     * The view identifier
     *
     * @var int
     */
    protected int $viewIdentifier;

    /**
     * The library view configuration
     *
     * @var array
     */
    protected array $libraryViewConfiguration = [];

    /**
     * The active folder key
     *
     * @var string|null
     */
    protected ?string $activeFolderKey;

    /**
     * The folder configuration
     *
     * @var array
     */
    protected array $folderFieldsConfiguration = [];

    /**
     * The view configuration
     *
     * @var array
     */
    protected array $viewConfiguration = [];

    /**
     * Flag which is set when the rich text editor has been generated once in the view
     *
     * @var bool
     */
    protected bool $richTextEditorIsInitialized = false;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(AbstractController $controller)
    {
        $this->controller = $controller;
    }
    
    /**
     * Sets the library view configuration
     *
     * @param array $libraryViewConfiguration
     *            The library view configuration
     *
     * @return void
     */
    public function setLibraryViewConfiguration(?array $libraryViewConfiguration = null): void
    {
        if ($libraryViewConfiguration === null) {
            // Gets the library configuration manager
            $this->libraryConfigurationManager = $this->controller->getLibraryConfigurationManager();
            
            // Gets the view identifier
            $this->viewIdentifier = $this->libraryConfigurationManager->getViewIdentifier($this->viewType);
            
            // Gets the view configuration
            $this->libraryViewConfiguration = $this->libraryConfigurationManager->getViewConfiguration($this->viewIdentifier);
        } else {
            $this->libraryViewConfiguration = $libraryViewConfiguration;
        }
    }


    /**
     * Checks if the view can be rendered
     *
     *
     * @return bool
     */
    public function viewCanBeRendered(): bool
    {
        return true;
    }

    /**
     * Gets the library configuration manager
     *
     * @return LibraryConfigurationManager
     */
    public function getLibraryConfigurationManager(): LibraryConfigurationManager
    {
        return $this->libraryConfigurationManager;
    }

    /**
     * Returns true if the view is a new view
     *
     * @return bool
     */
    public function isNewView(): bool
    {
        return $this->isNewView;
    }

    /**
     * Sets the isNewView flag
     *
     * @param boolean $isNewview
     *
     * @return void
     */
    public function setIsNewView(bool $isNewview): void
    {
        $this->isNewView = $isNewview;
    }

    /**
     * Sets the partial root path
     *
     * @param string $partialRootPath
     *
     * @return void
     */
    public function setPartialRootPath(string $partialRootPath): void
    {
        $this->partialRootPath = $partialRootPath;
    }

    /**
     * Gets the partial root path
     *
     * @return string
     */
    public function getPartialRootPath(): string
    {
        if (empty($this->partialRootPath)) {
            $this->partialRootPath = $this->defaultPartialRootPath;
        }
        return $this->partialRootPath;
    }

    /**
     * Gets the default Partial root path
     *
     * @return string
     */
    public function getDefaultPartialRootPath(): string
    {
        return $this->defaultPartialRootPath;
    }

    /**
     * Sets the layout root path
     *
     * @param string $layoutRootPath
     *
     * @return void
     */
    public function setLayoutRootPath(string $layoutRootPath): void
    {
        $this->layoutRootPath = $layoutRootPath;
    }

    /**
     * Gets the layout root path
     *
     * @return string
     */
    public function getLayoutRootPath(): string
    {
        if (empty($this->layoutRootPath)) {
            $this->layoutRootPath = $this->defaultLayoutRootPath;
        }
        return $this->layoutRootPath;
    }

    /**
     * Gets the default Layout root path
     *
     * @return string
     */
    public function getDefaultLayoutRootPath(): string
    {
        return $this->defaultLayoutRootPath;
    }

    /**
     * Sets the template root path
     *
     * @param string $templateRootPath
     *
     * @return void
     */
    public function setTemplateRootPath(string $templateRootPath): void
    {
        $this->templateRootPath = $templateRootPath;
    }

    /**
     * Gets the template root path
     *
     * @return string
     */
    public function getTemplateRootPath(): string
    {
        return $this->templateRootPath;
    }

    /**
     * Gets the default template root path
     *
     * @return string
     */
    public function getDefaultTemplateRootPath(): string
    {
        return $this->defaultTemplateRootPath;
    }

    /**
     * Sets the template file
     *
     * @param string $templateFile
     *
     * @return void
     */
    public function setTemplateFile(string $templateFile): void
    {
        $this->templateFile = $templateFile;
    }

    /**
     * Gets the template file
     *
     * @return string
     */
    public function getTemplateFile(): string
    {
        $templateRootPath = $this->getTemplateRootPath();

        // Returns the template file in the template root path if it exists
        $templateFile = $templateRootPath . '/' . $this->templateFile;
        if (@is_file(GeneralUtility::getFileAbsFileName($templateFile)) === true) {
            return $templateFile;
        } else {
            // Returns the file in the default template root path
            $defaultTemplateRootPath = $this->getDefaultTemplateRootPath();
            $templateFile = $defaultTemplateRootPath . '/' . $this->templateFile;
            if (@is_file(GeneralUtility::getFileAbsFileName($templateFile)) === true) {
                return $templateFile;
            } else {
                throw new Exception('The file "' . htmlspecialchars(GeneralUtility::getFileAbsFileName($templateFile)) . '" does not exist');
            }
        }
    }

    /**
     * Sets the link configuration
     *
     * @param array $linkConfiguration
     *
     * @return void
     */
    public function setLinkConfiguration(array $linkConfiguration): void
    {
        $this->linkConfiguration = $linkConfiguration;
    }

    /**
     * Gets the link configuration
     *
     * @return array The link configuration
     */
    public function getLinkConfiguration(): array
    {
        return $this->linkConfiguration;
    }

    /**
     * Creates the field configuration manager
     *
     * @return void
     */
    protected function createFieldConfigurationManager(): void
    {
        $this->fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class, $this->controller);
    }

    /**
     * Gets the field configuration manager
     *
     * @return FieldConfigurationManager
     */
    protected function getFieldConfigurationManager(): FieldConfigurationManager
    {
        return $this->fieldConfigurationManager;
    }

    /**
     * Gets the view type
     *
     * @return string|null
     */
    public function getViewType(): string
    {
        return $this->viewType ?? '';
    }

    /**
     * Gets the item view directory
     *
     * @return string
     */
    public function getItemViewerDirectory(): string
    {
        return $this->itemViewerDirectory;
    }

    /**
     * Sets the active folder key
     *
     * @return void
     */
    public function setActiveFolderKey(): void
    {
        // Gets the active folder key
        $this->activeFolderKey = $this->controller
            ->getUriManager()
            ->getFolderKey();

        // Uses the key of the first view configuration if the active folder key is null or there is no view configuration for the key
        if ($this->activeFolderKey === null || empty($this->libraryViewConfiguration[$this->activeFolderKey])) {
            if (is_array($this->libraryViewConfiguration)) {
                reset($this->libraryViewConfiguration);
                $this->activeFolderKey = key($this->libraryViewConfiguration);
            } else {
                $info = [
                    'extensionKey' => $this->controller->getExtensionKey(),
                    'formName' => $this->controller->getFormName(),
                    'actionName' => $this->controller->getActionName()
                ];
                static::getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, 'Error in setActiveFolder()', $info);
            }
        }
    }

    /**
     * Gets the active folder key
     *
     * @return string The active folder key
     */
    public function getActiveFolderKey(): string
    {
        return $this->activeFolderKey;
    }

    /**
     * Gets the active folder
     *
     * @return array The active folder
     */
    public function getActiveFolder(): array
    {
        return $this->libraryViewConfiguration[$this->activeFolderKey];
    }

    /**
     * Gets the active folder field
     *
     * @param string $fieldName
     *            The field name
     *
     * @return mixed The active folder field
     */
    public function getActiveFolderField(string $fieldName): mixed
    {
        return $this->libraryViewConfiguration[$this->activeFolderKey][$fieldName] ?? null;
    }

    /**
     * Gets the active folder title
     *
     * @return string The active folder title
     */
    public function getActiveFolderTitle(): string
    {
        $titleField = $this->getActiveFolderField('title');
        return $titleField['config']['field'];
    }

    /**
     * Adds the folders configuration to the view configuration
     *
     * @return array The folders configuration
     */
    public function getFoldersConfiguration(): array
    {
        // Adds the folders configuration
        $foldersConfiguration = [];
        foreach ($this->libraryViewConfiguration as $folderKey => $folder) {
            if ($folderKey != AbstractController::cryptTag('0')) {
                $fieldConfigurationManager = $this->getFieldConfigurationManager();
                $fieldConfigurationManager->setKickstarterFieldConfiguration($folder['config']);
                if ($fieldConfigurationManager->cutIf() === false) {
                    $foldersConfiguration[$folderKey]['label'] = $folder['config']['label'];
                }
            }
        }

        return $foldersConfiguration;
    }

    /**
     * Adds a configuration for a given key
     *
     * @param string $key
     *            The key
     * @param array $configuration
     *            The configuration to add
     *
     * @return void
     */
    public function addToViewConfiguration(string $key, array $configuration): void
    {
        $this->viewConfiguration = array_merge_recursive($this->viewConfiguration, [
            $key => $configuration
        ]);
    }

    /**
     * Gets a field from the general configuration
     *
     * @param string $field
     *            The field
     *
     * @return mixed
     */
    public function getFieldFromGeneralViewConfiguration(string $field): mixed
    {
        return $this->viewConfiguration['general'][$field];
    }

    /**
     * Renders a view
     *
     * @return string the rendered view
     */
    public function renderView(): string
    {
        // Sets the view configuration files
        $this->setViewConfigurationFilesFromTypoScriptConfiguration();

        // Sets the link configuration
        $this->setViewLinkConfigurationFromTypoScriptConfiguration();

        // Creates the view
        // @extensionScannerIgnoreLine
        $view = $this->createView($this->getTemplateFile());

        // Gets the link configuration
        $linkConfiguration = $this->getLinkConfiguration();

        // Adds the short form name to the general configuration
        $this->addToViewConfiguration('general', [
            'extensionName' => $this->controller->getExtensionName(),
            'pageUid' => $this->controller->getPageId(),
            'shortFormName' => $this->controller->getShortFormName(),
            'contentIdentifier' => $this->controller->getContentObjectRenderer(),
            'additionalParams' => AbstractController::convertLinkAdditionalParametersToArray($linkConfiguration['additionalParams'] ?? '')
        ]);

        // Assigns the view configuration
        $view->assign('configuration', $this->viewConfiguration);

        // Renders the view
        return $view->render();
    }

    /**
     * Sets the view configuration files:
     * - from the Page TypoScript Configuration if any
     * - else from the extension TypoScript Configuration if any,
     * - else from the library TypoScript Configuration if any,
     * - else default configuration files are used.
     *
     * @return void
     */
    public function setViewConfigurationFilesFromTypoScriptConfiguration(): void
    {
        // Sets the template root path with the default
        $this->templateRootPath = $this->defaultTemplateRootPath;
        $this->controller
            ->getPageTypoScriptConfigurationManager()
            ->setViewConfigurationFilesFromPageTypoScriptConfiguration();
        $this->controller
            ->getExtensionConfigurationManager()
            ->setViewConfigurationFilesFromTypoScriptConfiguration();
        $this->controller
            ->getLibraryConfigurationManager()
            ->setViewConfigurationFilesFromTypoScriptConfiguration();
    }

    /**
     * Sets the link configuration:
     * - from the Page TypoScript Configuration if any
     * - else from the extension TypoScript Configuration if any,
     * - else from the library TypoScript Configuration if any.
     *
     * @return void
     */
    public function setViewLinkConfigurationFromTypoScriptConfiguration(): void
    {
        $this->controller
            ->getPageTypoScriptConfigurationManager()
            ->setViewLinkConfigurationFromPageTypoScriptConfiguration();
        $this->controller
            ->getExtensionConfigurationManager()
            ->setViewLinkConfigurationFromTypoScriptConfiguration();
        $this->controller
            ->getLibraryConfigurationManager()
            ->setViewLinkConfigurationFromTypoScriptConfiguration();
    }

    /**
     * Renders an item
     *
     * @param string $fieldKey
     *            The field key
     *
     * @return string the rendered item
     */
    public function renderItem($fieldKey): string
    {
        if (array_key_exists($fieldKey, $this->folderFieldsConfiguration) === true) {
            $itemConfiguration = $this->folderFieldsConfiguration[$fieldKey];

            // The item configuration should not be empty.
            if (empty($itemConfiguration)) {
                // It occurs when ###fieldName### is used and "fieldName" is not in the main table
                FlashMessages::addError('error.incorrectFieldKey');
                return '';
            }

            // Checks if the value should be in a hidden field
            if (isset($itemConfiguration['hiddenvalue']) && $itemConfiguration['edit'] === '0') {
                // Adds the hidden input element
                $htmlItem = HtmlElements::htmlInputHiddenElement([
                    HtmlElements::htmlAddAttribute('name', $itemConfiguration['itemName']),
                    HtmlElements::htmlAddAttribute('value', $itemConfiguration['value'])
                ]);
            } else {
                $htmlItem = '';
            }

            // Changes the item viewer directory to Default if the attribute edit is set to zero
            $itemViewerDirectory = (isset($itemConfiguration['edit']) && $itemConfiguration['edit'] === '0' ? self::DEFAULT_ITEM_VIEWERS_DIRECTORY : $this->getItemViewerDirectory());

            // Creates the item viewer
            $fieldType = (isset($itemConfiguration['rendertype']) ? $itemConfiguration['rendertype'] : $itemConfiguration['fieldType']);
            $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\' . $itemViewerDirectory . '\\' . $fieldType . 'ItemViewer';
            $itemViewer = new ($className)($this->controller);
            $itemViewer->setItemConfiguration($itemConfiguration);

            // Renders the item
            $renderedItem = $itemViewer->render();
            if (($itemConfiguration['hiddenrenderedvalue'] ?? false) && $itemConfiguration['edit'] === '0') {
                // Adds the hidden input element
                $htmlItem = HtmlElements::htmlInputHiddenElement([
                    HtmlElements::htmlAddAttribute('name', $itemConfiguration['itemName']),
                    HtmlElements::htmlAddAttribute('value', $renderedItem)
                ]);
            }
            return $renderedItem . $htmlItem;
        } else {
            return '';
        }
    }

    /**
     * Gets a directory name
     *
     * @param string $directoryName
     *            The directory name
     *
     * @return string the TYPO3 directory name
     */
    public function getDirectoryName(string $directoryName): string
    {
        $absoluteDirectoryName = GeneralUtility::getFileAbsFileName($directoryName);
        // Checks if the directory exists
        if (! @is_dir($absoluteDirectoryName)) {
            throw new Exception(FlashMessages::translate('error.directoryDoesNotExist', [
                $directoryName
            ]));
        } else {
            return substr($absoluteDirectoryName, strlen(Environment::getPublicPath() . '/'));
        }
    }

    /**
     * Processes the title field of a view.
     * It replaces localization and field tags by their values
     *
     * @param string|null $title
     *            The title to process
     *
     * @return string The processed title
     */
    public function processTitle(?string $title): string
    {
        // The title is not processed in a new view
        if ($this->isNewView() || is_null($title)) {
            return '';
        }
        
        // Checks if the title contains html tags
        if (preg_match('/<[^>]+>/', $title)) {
            $this->addToViewConfiguration('general', [
                'titleNeedsFormat' => 1
            ]);
        }

        // Processes localization tags
        $title = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($title);

        // Processes field tags
        $title = $this->controller
            ->getQuerier()
            ->parseFieldTags($title);

        return $title;
    }

    /**
     * Processes the field.
     *
     * @param string $cryptedFullFieldName
     *            The crypted full field name
     *
     * @return void
     */
    protected function processField(string $cryptedFullFieldName): void
    {
        if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['onlabel'] ?? false) {
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['label'] = $this->renderItem($cryptedFullFieldName);
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = '';
        } else {
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = $this->renderItem($cryptedFullFieldName);
        }
    }

    /**
     * Initializes the rich text editor
     *
     * @param bool $richTextEditorIsInitialized
     *            Flag
     *
     * @return void
     */
    public function initializeRichTextEditor(bool $richTextEditorIsInitialized = true): void
    {
        $this->richTextEditorIsInitialized = $richTextEditorIsInitialized;
    }

    /**
     * Returns true if the each tech editor is initialized
     *
     * @return bool
     */
    public function isRichTextEditorInitialized(): bool
    {
        return $this->richTextEditorIsInitialized;
    }

    /**
     * Creates the view
     *
     * @param string $template
     * @param string $isTemplateFile
     * 
     * @return mixed
     */
    public function createView(string $template, bool $isTemplateFile = true): mixed
    {   
        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $viewFactoryData = new (ViewFactoryData::class)(
            partialRootPaths: [
                $this->getDefaultPartialRootPath(),
                $this->getPartialRootPath()
            ],
            layoutRootPaths: [
                $this->getDefaultLayoutRootPath(),
                $this->getLayoutRootPath()
            ],
            templatePathAndFilename: ($isTemplateFile ? $template : null),
            request: $this->controller->getRequest(),
            );
    
        $view = $viewFactory->create($viewFactoryData);
        if (! $isTemplateFile) {
            $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        }
        return $view;
    }    
    
    /**
     * Returns a logger.
     *
     * @return Logger
     */
    protected static function getLogger(): Logger
    {
        /** @var Logger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
        return $logger;
    }

}
