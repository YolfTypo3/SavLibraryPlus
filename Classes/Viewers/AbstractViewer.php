<?php
namespace YolfTypo3\SavLibraryPlus\Viewers;

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
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Fluid\View\StandaloneView;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\Controller;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Compatibility\EnvironmentCompatibility;
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
     * @var Controller
     */
    private $controller;

    /**
     * The partial root path
     *
     * @var string
     */
    protected $partialRootPath = '';

    /**
     * The layout root path
     *
     * @var string
     */
    protected $layoutRootPath = '';

    /**
     * The template root path
     *
     * @var string
     */
    protected $templateRootPath;

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile;

    /**
     * The link configuration
     *
     * @var array
     */
    protected $linkConfiguration = [];

    /**
     * Item viewer directory
     *
     * @var string
     */
    protected $itemViewerDirectory = self::DEFAULT_ITEM_VIEWERS_DIRECTORY;

    /**
     * The new view flag
     *
     * @var boolean
     */
    protected $isNewView = false;

    /**
     * The library configuration manager
     *
     * @var LibraryConfigurationManager
     */
    protected $libraryConfigurationManager;

    /**
     * The field configuration manager
     *
     * @var FieldConfigurationManager
     */
    protected $fieldConfigurationManager;

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType;

    /**
     * The view identifier
     *
     * @var integer
     */
    protected $viewIdentifier;

    /**
     * The library view configuration
     *
     * @var array
     */
    protected $libraryViewConfiguration = [];

    /**
     * The active folder key
     *
     * @var string
     */
    protected $activeFolderKey;

    /**
     * The folder configuration
     *
     * @var array
     */
    protected $folderFieldsConfiguration = [];

    /**
     * The view configuration
     *
     * @var array
     */
    protected $viewConfiguration = [];

    /**
     * Flag which is set when the rich text editor has been generated once in the view
     *
     * @var boolean
     */
    protected $richTextEditorIsInitialized = false;

    /**
     * Injects the controller
     *
     * @param AbstractController $controller
     *            The controller
     *
     * @return array
     */
    public function injectController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Injects the library view configuration
     *
     * @param array $libraryViewConfiguration
     *            The library view configuration
     *
     * @return array
     */
    public function injectLibraryViewConfiguration(&$libraryViewConfiguration)
    {
        $this->libraryViewConfiguration = $libraryViewConfiguration;
    }

    /**
     * Gets the controller
     *
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Checks if the view can be rendered
     *
     *
     * @return boolean
     */
    public function viewCanBeRendered()
    {
        return true;
    }

    /**
     * Gets the library configuration manager
     *
     * @return LibraryConfigurationManager
     */
    public function getLibraryConfigurationManager()
    {
        return $this->libraryConfigurationManager;
    }

    /**
     * Returns true if the view is a new view
     *
     * @return boolean
     */
    public function isNewView()
    {
        return $this->isNewView;
    }

    /**
     * Sets the isNewView flag
     *
     * @param boolean $isNewview
     *
     * @return boolean
     */
    public function setIsNewView($isNewview)
    {
        $this->isNewView = $isNewview;
    }

    /**
     * Sets the library view configuration
     *
     * @return void
     */
    public function setLibraryViewConfiguration()
    {
        // Gets the library configuration manager
        $this->libraryConfigurationManager = $this->getController()->getLibraryConfigurationManager();

        // Gets the view identifier
        $this->viewIdentifier = $this->libraryConfigurationManager->getViewIdentifier($this->viewType);

        // Gets the view configuration
        $this->libraryViewConfiguration = $this->libraryConfigurationManager->getViewConfiguration($this->viewIdentifier);
    }

    /**
     * Sets the partial root path
     *
     * @param string $partialRootPath
     *
     * @return void
     */
    public function setPartialRootPath($partialRootPath)
    {
        $this->partialRootPath = $partialRootPath;
    }

    /**
     * Gets the partial root path
     *
     * @return string
     */
    public function getPartialRootPath()
    {
        if (empty($this->partialRootPath)) {
            $this->partialRootPath = $this->defaultPartialRootPath;
        }
        return $this->getDirectoryName($this->partialRootPath);
    }

    /**
     * Gets the default Partial root path
     *
     * @return string
     */
    public function getDefaultPartialRootPath()
    {
        return $this->getDirectoryName($this->defaultPartialRootPath);
    }

    /**
     * Sets the layout root path
     *
     * @param string $layoutRootPath
     *
     * @return void
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        $this->layoutRootPath = $layoutRootPath;
    }

    /**
     * Gets the layout root path
     *
     * @return string
     */
    public function getLayoutRootPath()
    {
        if (empty($this->layoutRootPath)) {
            $this->layoutRootPath = $this->defaultLayoutRootPath;
        }
        return $this->getDirectoryName($this->layoutRootPath);
    }

    /**
     * Gets the default Layout root path
     *
     * @return string
     */
    public function getDefaultLayoutRootPath()
    {
        return $this->getDirectoryName($this->defaultLayoutRootPath);
    }

    /**
     * Sets the template root path
     *
     * @param string $templateRootPath
     *
     * @return void
     */
    public function setTemplateRootPath($templateRootPath)
    {
        $this->templateRootPath = $templateRootPath;
    }

    /**
     * Gets the template root path
     *
     * @return string
     */
    public function getTemplateRootPath()
    {
        return $this->getDirectoryName($this->templateRootPath);
    }

    /**
     * Gets the default template root path
     *
     * @return string
     */
    public function getDefaultTemplateRootPath()
    {
        return $this->getDirectoryName($this->defaultTemplateRootPath);
    }

    /**
     * Sets the template file
     *
     * @param string $templateFile
     *
     * @return void
     */
    public function setTemplateFile($templateFile)
    {
        $this->templateFile = $templateFile;
    }

    /**
     * Gets the template file
     *
     * @return string
     */
    public function getTemplateFile()
    {
        $templateRootPath = $this->getTemplateRootPath();

        // Returns the template file in the template root path if it exists
        $templateFile = $templateRootPath . '/' . $this->templateFile;
        if (@is_file(EnvironmentCompatibility::getSitePath() . $templateFile) === true) {
            return $templateFile;
        } else {
            // Returns the file in the default template root path
            $defaultTemplateRootPath = $this->getDefaultTemplateRootPath();
            $templateFile = $defaultTemplateRootPath . '/' . $this->templateFile;
            if (@is_file(EnvironmentCompatibility::getSitePath() . $templateFile) === true) {
                return $templateFile;
            } else {
                throw new Exception('The file "' . htmlspecialchars(EnvironmentCompatibility::getSitePath() . $templateFile) . '" does not exist');
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
    public function setLinkConfiguration($linkConfiguration)
    {
        $this->linkConfiguration = $linkConfiguration;
    }

    /**
     * Gets the link configuration
     *
     * @return array The link configuration
     */
    public function getLinkConfiguration()
    {
        return $this->linkConfiguration;
    }

    /**
     * Creates the field configuration manager
     *
     * @return void
     */
    protected function createFieldConfigurationManager()
    {
        $this->fieldConfigurationManager = GeneralUtility::makeInstance(FieldConfigurationManager::class);
        $this->fieldConfigurationManager->injectController($this->getController());
    }

    /**
     * Gets the field configuration manager
     *
     * @return FieldConfigurationManager
     */
    protected function getFieldConfigurationManager()
    {
        return $this->fieldConfigurationManager;
    }

    /**
     * Gets the view type
     *
     * @return string
     */
    public function getViewType()
    {
        return $this->viewType;
    }

    /**
     * Gets the item view directory
     *
     * @return string
     */
    public function getItemViewerDirectory()
    {
        return $this->itemViewerDirectory;
    }

    /**
     * Sets the active folder key
     *
     * @return void
     */
    public function setActiveFolderKey()
    {
        // Gets the active folder key
        $this->activeFolderKey = $this->getController()
            ->getUriManager()
            ->getFolderKey();

        // Uses the key of the first view configuration if the active folder key is null or there is no view configuration for the key
        if ($this->activeFolderKey === null || empty($this->libraryViewConfiguration[$this->activeFolderKey])) {
            if (is_array($this->libraryViewConfiguration)) {
                reset($this->libraryViewConfiguration);
                $this->activeFolderKey = key($this->libraryViewConfiguration);
            } else {
                $info = [
                    'extensionKey' => $this->getController()->getExtensionConfigurationManager()::getExtensionKey(),
                    'formName' => $this->getController()::getFormName(),
                    'actionName' => $this->getController()->getActionName()
                ];
                static::getLogger()->error('Error in setActiveFolder()', $info);
            }
        }
    }

    /**
     * Gets the active folder key
     *
     * @return string The active folder key
     */
    public function getActiveFolderKey()
    {
        return $this->activeFolderKey;
    }

    /**
     * Gets the active folder
     *
     * @return array The active folder
     */
    public function getActiveFolder()
    {
        return $this->libraryViewConfiguration[$this->activeFolderKey];
    }

    /**
     * Gets the active folder field
     *
     * @param string $fieldName
     *            The field name
     *
     * @return array The active folder field
     */
    public function getActiveFolderField($fieldName)
    {
        return $this->libraryViewConfiguration[$this->activeFolderKey][$fieldName];
    }

    /**
     * Gets the active folder title
     *
     * @return string The active folder title
     */
    public function getActiveFolderTitle()
    {
        $titleField = $this->getActiveFolderField('title');
        return $titleField['config']['field'];
    }

    /**
     * Adds the folders configuration to the view configuration
     *
     * @return array The folders configuration
     */
    public function getFoldersConfiguration()
    {
        // Adds the folders configuration
        $foldersConfiguration = [];
        foreach ($this->libraryViewConfiguration as $folderKey => $folder) {
            if ($folderKey != AbstractController::cryptTag('0')) {
                $fieldConfigurationManager = $this->getFieldConfigurationManager();
                $fieldConfigurationManager->injectKickstarterFieldConfiguration($folder['config']);
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
    public function addToViewConfiguration($key, $configuration)
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
    public function getFieldFromGeneralViewConfiguration($field)
    {
        return $this->viewConfiguration['general'][$field];
    }

    /**
     * Renders a view
     *
     * @return string|null the rendered view
     */
    public function renderView()
    {
        // Sets the view configuration files
        $this->setViewConfigurationFilesFromTypoScriptConfiguration();

        // Sets the link configuration
        $this->setViewLinkConfigurationFromTypoScriptConfiguration();

        // Creates the view
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        // Sets the file template
        $view->setTemplatePathAndFilename($this->getTemplateFile());

        // Sets the layout and the partial root paths
        $view->setLayoutRootPaths([
            $this->getDefaultLayoutRootPath(),
            $this->getLayoutRootPath()
        ]);
        $view->setPartialRootPaths([
            $this->getDefaultPartialRootPath(),
            $this->getPartialRootPath()
        ]);

        // Gets the link configuration
        $linkConfiguration = $this->getLinkConfiguration();

        // Adds the short form name to the general configuration
        $this->addToViewConfiguration('general', [
            'shortFormName' => AbstractController::getShortFormName(),
            'contentIdentifier' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getContentIdentifier(),
            'additionalParams' => AbstractController::convertLinkAdditionalParametersToArray($linkConfiguration['additionalParams'])
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
    public function setViewConfigurationFilesFromTypoScriptConfiguration()
    {
        // Sets the template root path with the default
        $this->templateRootPath = $this->defaultTemplateRootPath;
        $this->getController()
            ->getPageTypoScriptConfigurationManager()
            ->setViewConfigurationFilesFromPageTypoScriptConfiguration();
        $this->getController()
            ->getExtensionConfigurationManager()
            ->setViewConfigurationFilesFromTypoScriptConfiguration();
        $this->getController()
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
    public function setViewLinkConfigurationFromTypoScriptConfiguration()
    {
        $this->getController()
            ->getPageTypoScriptConfigurationManager()
            ->setViewLinkConfigurationFromPageTypoScriptConfiguration();
        $this->getController()
            ->getExtensionConfigurationManager()
            ->setViewLinkConfigurationFromTypoScriptConfiguration();
        $this->getController()
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
    public function renderItem($fieldKey)
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
            if ($itemConfiguration['hiddenvalue'] && $itemConfiguration['edit'] === '0') {
                // Adds the hidden input element
                $htmlItem = HtmlElements::htmlInputHiddenElement([
                    HtmlElements::htmlAddAttribute('name', $itemConfiguration['itemName']),
                    HtmlElements::htmlAddAttribute('value', $itemConfiguration['value'])
                ]);
            } else {
                $htmlItem = '';
            }

            // Changes the item viewer directory to Default if the attribute edit is set to zero
            $itemViewerDirectory = ($itemConfiguration['edit'] === '0' ? self::DEFAULT_ITEM_VIEWERS_DIRECTORY : $this->getItemViewerDirectory());

            // Creates the item viewer
            $fieldType = ($itemConfiguration['rendertype'] ? $itemConfiguration['rendertype'] : $itemConfiguration['fieldType']);
            $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\' . $itemViewerDirectory . '\\' . $fieldType . 'ItemViewer';
            $itemViewer = GeneralUtility::makeInstance($className);
            $itemViewer->injectController($this->getController());
            $itemViewer->injectItemConfiguration($itemConfiguration);

            // Renders the item
            $renderedItem = $itemViewer->render();
            if ($itemConfiguration['hiddenrenderedvalue'] && $itemConfiguration['edit'] === '0') {
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
    public function getDirectoryName($directoryName)
    {
        $absoluteDirectoryName = GeneralUtility::getFileAbsFileName($directoryName);
        // Checks if the directory exists
        if (! @is_dir($absoluteDirectoryName)) {
            throw new Exception(FlashMessages::translate('error.directoryDoesNotExist', [
                $directoryName
            ]));
        } else {
            return substr($absoluteDirectoryName, strlen(EnvironmentCompatibility::getSitePath()));
        }
    }

    /**
     * Processes the title field of a view.
     * It replaces localization and field tags by their values
     *
     * @param string $title
     *            The title to process
     *
     * @return string The processed title
     */
    public function processTitle($title)
    {
        // The title is not processed in a new view
        if ($this->isNewView()) {
            return '';
        }

        // Checks if the title contains html tags
        if (preg_match('/<[^>]+>/', $title)) {
            $this->addToViewConfiguration('general', [
                'titleNeedsFormat' => 1
            ]);
        }

        // Processes localization tags
        $title = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($title);

        // Processes field tags
        $title = $this->getController()
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
    protected function processField($cryptedFullFieldName)
    {
        if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['onlabel']) {
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['label'] = $this->renderItem($cryptedFullFieldName);
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = '';
        } else {
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = $this->renderItem($cryptedFullFieldName);
        }
    }

    /**
     * Initializes the rich text editor
     *
     * @param boolean $richTextEditorIsInitialized
     *            Flag
     *
     * @return void
     */
    public function initializeRichTextEditor($richTextEditorIsInitialized = true)
    {
        $this->richTextEditorIsInitialized = $richTextEditorIsInitialized;
    }

    /**
     * Returns true if the each tech editor is initialized
     *
     * @return boolean
     */
    public function isRichTextEditorInitialized()
    {
        return $this->richTextEditorIsInitialized;
    }

    /**
     * Returns a logger.
     *
     * @return Logger
     */
    protected static function getLogger()
    {
        /** @var Logger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
        return $logger;
    }

    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
?>