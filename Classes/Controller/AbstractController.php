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

namespace YolfTypo3\SavLibraryPlus\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager; 
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\PageTypoScriptConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
use YolfTypo3\SavLibraryPlus\Managers\TcaConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\UserManager;
use YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier;
use YolfTypo3\SavLibraryPlus\Queriers\UpdateQuerier;
use YolfTypo3\SavLibraryPlus\Viewers\AbstractViewer;
use YolfTypo3\SavLibraryPlus\Viewers\ErrorViewer;

/**
 * Abstract controller.
 *
 * @package SavLibraryPlus
 */
abstract class AbstractController
{

    // Constants
    const LIBRARY_NAME = 'sav_library_plus';

    /**
     * Variable to encode/decode form parameters
     *
     * @var array
     */
    protected array $formParameters = [
        'folderKey',
        'formAction',
        'formName',
        'page',
        'pageInSubform',
        'subformFieldKey',
        'subformUidForeign',
        'subformUidForeignInLink',
        'subformUidLocal',
        'uid',
        'viewId',
        'whereTagKey'
    ];

    /**
     * Variable to encode/decode form actions
     *
     * @var array
     */
    protected array $formActions = [
        'changeFolderTab',
        'changePageInSubform',
        'changePageInSubformInEditMode',
        'close',
        'closeInEditMode',
        'delete',
        'deleteFile',
        'deleteInSubform',
        'downInSubform',
        'edit',
        'export',
        'exportExecute',
        'exportLoadConfiguration',
        'exportSaveConfiguration',
        'exportDeleteConfiguration',
        'exportSubmit',
        'exportToggleDisplay',
        'firstPage',
        'firstPageInEditMode',
        'firstPageInSubform',
        'firstPageInSubformInEditMode',
        'formAdmin',
        'new',
        'newInSubform',
        'nextPage',
        'nextPageInEditMode',
        'nextPageInSubform',
        'nextPageInSubformInEditMode',
        'noDisplay',
        'lastPage',
        'lastPageInEditMode',
        'lastPageInSubform',
        'lastPageInSubformInEditMode',
        'list',
        'listInEditMode',
        'previousPage',
        'previousPageInEditMode',
        'previousPageInSubform',
        'previousPageInSubformInEditMode',
        'printInList',
        'printInSingle',
        'save',
        'saveForm',
        'saveFormAdmin',
        'single',
        'upInSubform'
    ];

    /**
     * Variable to provide alternative form action when the user is not allowed to input data
     *
     * @var array
     */
    protected array $formActionsWhenUserIsNotAllowedToInputData = [
        'changePageInSubformInEditMode' => 'single',
        'closeInEditMode' => 'list',
        'delete' => 'error',
        'deleteInSubform' => 'error',
        'downInSubform' => 'single',
        'edit' => 'single',
        'export' => 'error',
        'exportExecute' => 'error',
        'exportLoadConfiguration' => 'error',
        'exportSaveConfiguration' => 'error',
        'exportDeleteConfiguration' => 'error',
        'exportSubmit' => 'error',
        'exportToggleDisplay' => 'error',
        'firstPageInSubformInEditMode' => 'single',
        'formAdmin' => 'error',
        'new' => 'list',
        'newInSubform' => 'single',
        'nextPageInEditMode' => 'list',
        'nextPageInSubformInEditMode' => 'single',
        'lastPageInEditMode' => 'list',
        'lastPageInSubformInEditMode' => 'single',
        'listInEditMode' => 'list',
        'previousPageInEditMode' => 'list',
        'previousPageInSubformInEditMode' => 'single',
        'save' => 'error',
        'saveFormAdmin' => 'error',
        'upInSubform' => 'list'
    ];

    /**
     * The library configuration manager
     *
     * @var LibraryConfigurationManager
     */
    protected LibraryConfigurationManager $libraryConfigurationManager;

    /**
     * The extension configuration manager
     *
     * @var ExtensionConfigurationManager
     */
    protected ExtensionConfigurationManager $extensionConfigurationManager;

    /**
     * The uri manager
     *
     * @var UriManager
     */
    protected UriManager $uriManager;

    /**
     * The user manager
     *
     * @var UserManager
     */
    protected UserManager $userManager;

    /**
     * The session manager
     *
     * @var SessionManager
     */
    protected SessionManager $sessionManager;

    /**
     * The page TypoScript manager
     *
     * @var PageTypoScriptConfigurationManager
     */
    protected PageTypoScriptConfigurationManager $pageTypoScriptConfigurationManager;

    /**
     * The TCA configuration manager
     *
     * @var TcaConfigurationManager
     */
    protected TcaConfigurationManager $tcaConfigurationManager;
    
    /**
     * The request
     * 
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * The querier
     *
     * @var AbstractQuerier
     */
    protected ?AbstractQuerier $querier = null;

    /**
     * The viewer
     *
     * @var AbstractViewer
     */
    protected ?AbstractViewer $viewer = null;

    /**
     * Debug flag
     *
     * @var int
     */
    protected int $debugFlag = 0;

    /**
     * The form name
     *
     * @var string
     */
    protected string $formName;

    /**
     * The short form name (without the content id)
     *
     * @var string
     */
    protected string $shortFormName;

    /**
    * The back-reference to the mother cObj object set at call time
    * 
    * @var ContentObjectRenderer
    */
    protected ContentObjectRenderer $contentObjectRenderer;
   
    /**
     * This setter is called when the plugin is called from UserContentObject (USER)
     * via ContentObjectRenderer->callUserFunction().
     *
     * @param ContentObjectRenderer $cObj
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->contentObjectRenderer = $cObj;
    }

    /**
     * Gets the content object renderer.
     *
     * @return ContentObjectRenderer
     */
    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }

    /**
     * Gets the content object renderer data.
     * 
     * @param string key
     *
     * @return mixed|null
     */
    public function getContentObjectRendererDataAttribute(string $key): mixed
    {
        // @extensionScannerIgnoreLine
        return $this->contentObjectRenderer->data[$key] ?? null;
    }
    
    /**
     * Sets the content object renderer data.
     *
     * @param string key
     * @param mixed $value
     *
     * @return void
     */
    public function setContentObjectRendererDataAttribute(string $key, mixed $value): void
    {
        // @extensionScannerIgnoreLine
        $this->contentObjectRenderer->data[$key] = $value;
    }
    
    /**
     * Gets the extension key.
     *
     * @return string
     */
    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }
  
    /**
     * Gets the extension prefix id.
     *
     * @return string
     */
    public function getExtensionPrefixId(): string
    {
        return $this->prefixId;
    }
    
    /**
     * Gets the extension Name, i.e.
     * extension key converted to upercamelcase.
     *
     * @return string
     */
    public function getExtensionName()
    {
        return GeneralUtility::underscoredToUpperCamelCase($this->extensionKey);
    }
    
    /**
     * Renders the controller action
     *
     * @param array $configuration
     * 
     * @return string (the whole content result, wraped as plugin)
     */
    public function render(array $configuration): string
    {       
        // Initializes the controller
        if ($this->initialize($configuration) === false) {
            $this->viewer = new (ErrorViewer::class)($this);
            $content = $this->viewer->render();
            return ($content === null ? '' : $content);
        }

        // Sets the plugin type
        if ($this->setPluginType() === false)
            return '';
        // Loads the sessions
        $this->getSessionManager()->loadSession();

        // Gets the action name.
        $actionName = $this->getActionName();

        // Executes the action
        if (! method_exists($this, $actionName)) {
            $content = $this->errorAction();
        } else {
            $content = $this->$actionName();
        }

        // Saves the sessions
        $this->getSessionManager()->saveSession();

        // Adds the javaScript header if required
        AdditionalHeaderManager::addAdditionalJavaScriptHeader();

        return ($content === null ? '' : $content);
    }
    
    /**
     * Initializes the controller
     *
     * @param array $configuration
     * 
     * @return bool (true if no error occurs)
     */
    public function initialize(array $configuration): bool
    {
        // Sets the request
        $extensionName = $this->getExtensionName();
        $pluginName = 'Pi1';
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        // @extensionScannerIgnoreLine
        $configurationManager->setConfiguration([
            'extensionName' => $extensionName,
            'pluginName' => $pluginName,
        ]);
        if (! isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'] = [
                get_class($this) => [
                    'className' => get_class($this),
                    'alias' => 'Default',
                    'actions' => $this->formActions
                ],
            ];
            $requestBuilder = GeneralUtility::makeInstance(RequestBuilder::class);
            $this->request = $requestBuilder->build($GLOBALS['TYPO3_REQUEST'])
                ->withAttribute('currentContentObject', $this->contentObjectRenderer)
                ->withAttribute('controller', $this);
        }
               
        // Initializes the managers
        if ($this->initializeManagers($configuration) === false) {
            return false;
        }
        
        // Sets debug
        if ($this->debugFlag) {
            DatabaseCompatibility::getDatabaseConnection()->debugOutput = $this->debugFlag;
            DatabaseCompatibility::getDatabaseConnection()->extensionKey = $this->extensionKey;
        }
        
        // Sets the form names
        $formTitle = $this->libraryConfigurationManager->getFormTitle();
        $this->shortFormName = $this->extensionKey . '_' . strtr(strtolower($formTitle), ' -', '__');
        $this->formName = $this->shortFormName . '_' . $this->getContentObjectRendererDataAttribute('uid');
       
        return true;
    }
    
    /**
     * Initializes the managers
     *
     * @param array $configuration
     * 
     * @return bool
     */
    protected function initializeManagers(array $configuration): bool
    {
        $result = true;
        
        // Creates the user manager
        $this->userManager = new (UserManager::class)($this);
                 
        // Creates the extension configuration manager
        $this->extensionConfigurationManager = new (ExtensionConfigurationManager::class)($this);
        $result = $result && $this->extensionConfigurationManager->initialize($configuration);

        // Creates the library configuration manager
        $this->libraryConfigurationManager = new (LibraryConfigurationManager::class)($this);
        $result = $result && $this->libraryConfigurationManager->initialize();

        // Creates the session manager
        $this->sessionManager = new (SessionManager::class)($this);
        
        // Creates the URI manager
        $this->uriManager = new (UriManager::class)($this);
        
        // Creates the page TypoScript manager
        $this->pageTypoScriptConfigurationManager = new (PageTypoScriptConfigurationManager::class)($this);
        
        // Creates the TCA configuration manager
        $this->tcaConfigurationManager = new (TcaConfigurationManager::class)($this);
        
        return $result;
    }
    
    /**
     * Sets the plugin type.
     *
     * @return bool
     */
    protected function setPluginType(): bool
    {

        // Gets the user plugin flag
        $userPluginFlag = $this->libraryConfigurationManager->getUserPluginFlag();
        if (! $userPluginFlag) {
            // Converts the plugin to the USER_INT type
            if ($this->contentObjectRenderer->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER) {
                $this->contentObjectRenderer->convertToUserIntObject();
                return false;
            }
        }
        return true;
    }
    
    /**
     * Sets the debug variable
     *
     * @param integer $debug
     *
     * @return void
     */
    public function setDebug(int $debug): void
    {
        $this->debugFlag = $debug;
    }

    /**
     * Gets the debug variable
     *
     * @return int
     */
    public function getDebug(): int
    {
        return $this->debugFlag;
    }

    /**
     * Gets the request
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
    
    /**
     * Sets the request
     *
     * @param ServerRequestInterface $request
     * 
     * @ return void
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
    
    
    /**
     * Gets the form name
     *
     * @return string
     */
    public function getFormName(): string
    {
        return $this->formName;
    }

    /**
     * Gets the short form name
     *
     * @return string|null
     */
    public function getShortFormName(): ?string
    {
        return $this->shortFormName ?? null;
    }

    /**
     * Gets the Library Configuration manager
     *
     * @return LibraryConfigurationManager
     */
    public function getLibraryConfigurationManager(): LibraryConfigurationManager
    {
        return $this->libraryConfigurationManager;
    }

    /**
     * Gets the extension configuration manager.
     *
     * @return ExtensionConfigurationManager
     */
    public function getExtensionConfigurationManager(): ExtensionConfigurationManager
    {
        return $this->extensionConfigurationManager;
    }

    /**
     * Gets the uri manager.
     *
     * @return UriManager
     */
    public function getUriManager(): UriManager
    {
        return $this->uriManager;
    }

    /**
     * Gets the user manager.
     *
     * @return UserManager
     */
    public function getUserManager(): UserManager
    {
        return $this->userManager;
    }

    /**
     * Gets the session manager.
     *
     * @return SessionManager
     */
    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    /**
     * Gets the page TypoScript configuration manager.
     *
     * @return PageTypoScriptConfigurationManager
     */
    public function getPageTypoScriptConfigurationManager(): PageTypoScriptConfigurationManager
    {
        return $this->pageTypoScriptConfigurationManager;
    }

    /**
     * Gets the TCA configuration manager.
     *
     * @return TcaConfigurationManager
     */
    public function getTcaConfigurationManager(): TcaConfigurationManager
    {
        return $this->tcaConfigurationManager;
    }
      
    /**
     * Sets the querier
     *
     * @param AbstractQuerier $querier
     *
     * @return void
     */
    public function setQuerier(AbstractQuerier $querier): void
    {
        $this->querier = $querier;
    }

    /**
     * Gets the querier
     *
     * @return AbstractQuerier|null
     */
    public function getQuerier(): ?AbstractQuerier
    {
        return $this->querier;
    }

    /**
     * Sets the viewer
     *
     * @param AbstractViewer $viewer
     *
     * @return void
     */
    public function setViewer(AbstractViewer $viewer): void
    {
        $this->viewer = $viewer;
    }

    /**
     * Gets the viewer
     *
     * @return AbstractViewer
     */
    public function getViewer(): ?AbstractViewer
    {
        return $this->viewer;
    }

    /**
     * Gets the action name
     *
     * @return string
     */
    public function getActionName(): string
    {
        // Checks if the URI is verified
        if ($this->uriManager->uriIsVerified() === false) {
            return 'errorAction';
        }

        // Default action name.
        $actionName = 'list';

        // Processes the filter if selected
        $selectedFilterKey = $this->sessionManager->getSelectedFilterKey();
        if (! empty($selectedFilterKey)) {
            // Gets search tag if any
            $filterSearchTag = $this->sessionManager->getFilterField($selectedFilterKey, 'searchTag');
            if (! empty($filterSearchTag)) {
                $this->sessionManager->setFieldFromSession('tagInSession', $filterSearchTag);
            }

            // Gets the action from the filter if any
            $filterActionName = $this->sessionManager->getFilterField($selectedFilterKey, 'formAction');
            if (! empty($filterActionName)) {
                $actionName = $filterActionName;
            }
        }

        // Gets the action
        if ($this->uriManager->hasLibraryParameter()) {
            // Sets the GET variables
            $this->uriManager->setGetVariables();

            // Retrieves the action from the URI if it is the active form
            if ($this->uriManager->isActiveForm() === true) {
                $actionName = $this->uriManager->getFormAction();
            } else {
                // Retreieves the action from the
                $compressedParameters = $this->sessionManager->getFieldFromSession('compressedParameters');

                if (! empty($compressedParameters)) {
                    $this->uriManager->setCompressedParameters($compressedParameters);
                    if ($this->uriManager->isActiveForm() === true) {
                        $actionName = $this->uriManager->getFormAction();
                    }
                }
            }
        }

        // Checks if the user is allowed to input data
        if ($this->userManager->userIsAllowedToInputData() === false) {
            $actionName = $this->getFormActionWhenUserIsNotAllowedToInputData($actionName);
        }

        return $actionName . 'Action';
    }

    /**
     * Builds a string to compress parameters which will be used with the
     * extension.
     * Mainly, the method replaces the form parameter by
     * an integer. Same process occurs for form actions
     *
     * @param array $parameters
     *            (parameter array)
     *
     * @return string (compressed parameter string)
     */
    public function compressParameters(array $parameters): string
    {
        $out = '';
        foreach ($parameters as $parameterKey => $parameter) {
            $key = array_search($parameterKey, $this->formParameters);
            if ($key === false) {
                FlashMessages::addError('error.unknownFormParam', [
                    $parameterKey
                ]);
                return '';
            } else {
                $out .= dechex($key);
            }
            switch ($parameterKey) {
                case 'formAction':
                    $key = array_search($parameter, $this->formActions);
                    if ($key === false) {
                        FlashMessages::addError('error.unknownFormAction', [
                            $parameter
                        ]);
                        return '';
                    } else {
                        $out .= sprintf('%02x%s', strlen(strval($key)), $key);
                    }
                    break;
                case 'formName':
                    if (empty($parameter)) {
                        $parameter = $this->getFormName();
                    }
                    $parameter = hash($this->extensionConfigurationManager->getFormNameHashAlgorithm(), $parameter);
                    $out .= sprintf('%02x%s', strlen($parameter), $parameter);
                    break;
                default:
                    $out .= sprintf('%02x%s', strlen(strval($parameter ?? '')), strval($parameter ?? ''));
                    break;
            }
        }

        return $out;
    }

    /**
     * Builds an array from a compressed string
     * Mainly, the method splits the string to recover the parameter and its value
     *
     * @param string $compressedString
     *            (compressed string)
     *
     * @return array|null (parameter array)
     */
    public function uncompressParameters(string $compressedString, $formName = null): ?array
    {
        // Checks if there is a fragment in the link
        $fragmentPosition = strpos($compressedString ?? '', '#');
        if ($fragmentPosition !== false) {
            $compressedString = substr($compressedString, 0, $fragmentPosition);
        }
        $out = [];

        while ($compressedString) {
            // Reads the form param index
            list ($parameter) = sscanf($compressedString, '%1x');
            $formParameter = $this->formParameters[$parameter];
            if (empty($formParameter)) {
                FlashMessages::addError('error.unknownFormParam', [
                    $parameter
                ]);
            }
            $compressedString = substr($compressedString, 1);

            // Reads the length
            list ($length) = sscanf($compressedString, '%2x');
            $compressedString = substr($compressedString, 2);
            // Reads the value
            list ($value) = sscanf($compressedString, '%' . $length . 's');
            $compressedString = substr($compressedString, $length);
            switch ($formParameter) {
                case 'formAction':
                    $out[$formParameter] = $this->formActions[$value];
                    if (empty($out[$formParameter])) {
                        FlashMessages::addError('error.unknownFormAction', [
                            $value
                        ]);
                    }
                    break;
                case 'formName':
                    if ($formName === null) {
                        $formName = $this->getFormName();
                    }
                    if ($value != hash(($this->extensionConfigurationManager->getFormNameHashAlgorithm()), $formName)) {
                        return null;
                    }
                    $out[$formParameter] = $formName;
                    break;
                default:
                    $out[$formParameter] = $value;
                    break;
            }
        }

        return $out;
    }

    /**
     * Changes a parameter in the compressed parameters string
     *
     * @param string $compressedParameters
     *            The compressed parameters string
     * @param string $key
     *            The key of the parameter to change
     * @param mixed $value
     *            The value of the parameter to change
     *
     * @return string The modified compressed parameter string
     */
    public function changeCompressedParameters(?string $compressedParameters, string $key, mixed $value): string
    {
        $uncompressParameters = $this->uncompressParameters($compressedParameters);
        $uncompressParameters[$key] = $value;

        return $this->compressParameters($uncompressParameters);
    }

    /**
     * Builds a link to the current page.
     *
     * @param string $str
     *            (string associated with the link)
     * @param array $formParameters
     *            (form parameters)
     * @param int $cache
     *            (set to 1 if the page should be cached)
     * @param array $additionalParameters
     *            (if true, phash is added to the form parameters)
     *
     * @return string (link)
     */
    public function buildLinkToPage(string $str, array $formParameters, int $cache = 0, array $additionalParameters = []): string
    {
        // Gets the page id
        $pageId = $formParameters['pageId'] ?? null;
        if (! empty($pageId)) {
            unset($formParameters['pageId']);
        } else {
            $pageId = $this->getPageId();
        }

        // Gets the form name
        $formName = (($formParameters['formName'] ?? false) ? $formParameters['formName'] : $this->getFormName());

        // Builds the form parameters
        $formParameters = array_merge([
            'formName' => $formName
        ], $formParameters);

        // Adds the additional parameters in link configuration if any
        $viewer = $this->getViewer();
        if ($viewer !== null) {
            $linkConfiguration = $viewer->getLinkConfiguration();
        }

        if (! empty($linkConfiguration['additionalParams'])) {
            $additionalParameters = array_merge($additionalParameters, $this->convertLinkAdditionalParametersToArray($linkConfiguration['additionalParams']));
        }

        // Creates the link
        $conf = [];

        // Adds the page Id as parameter
        $conf['parameter'] = $pageId;
        if ($formParameters['target'] ?? false) {
            $conf['target'] = $formParameters['target'];
            unset($formParameters['target']);
        }

        // Adds the linkAccessRestrictedPages attribute
        if ($formParameters['linkAccessRestrictedPages'] ?? false) {
            $conf['linkAccessRestrictedPages'] = true;
            unset($formParameters['linkAccessRestrictedPages']);
        }

        // Adds the forceAbsoluteUrl attribute
        if ($formParameters['forceAbsoluteUrl'] ?? false) {
            $conf['forceAbsoluteUrl'] = $formParameters['forceAbsoluteUrl'];
            unset($formParameters['forceAbsoluteUrl']);
            if (isset($formParameters['forceAbsoluteUrl.'])) {
                $conf['forceAbsoluteUrl.'] = $formParameters['forceAbsoluteUrl.'];
                unset($formParameters['forceAbsoluteUrl.']);
            }
        }

        // Builds the url parameter
        $urlParameters = [
            'sav_library_plus' => $this->compressParameters($formParameters), 
            $this->prefixId . '[controller]' => 'Default',
            $this->prefixId . '[action]' => $formParameters['formAction'],
        ];
        $urlParameters = array_merge($urlParameters, $additionalParameters);
        if (! empty($urlParameters)) {
            $conf['additionalParams'] = HttpUtility::buildQueryString($urlParameters, '&');
        }

        $out = $this->contentObjectRenderer->typoLink($str, $conf);

        return $out;
    }

    /**
     * Gets the form action code.
     *
     * @param string $formAction
     *            The form action
     *
     * @return int The form action code
     */
    public function getFormActionCode(string $formAction): int
    {
        return array_search($formAction, $this->formActions);
    }

    /**
     * Gets the form action when the user is not allowed to input data.
     *
     * @param string $formAction
     *            The form action
     *
     * @return string
     */
    public function getFormActionWhenUserIsNotAllowedToInputData($formAction): string
    {
        if (isset($this->formActionsWhenUserIsNotAllowedToInputData[$formAction])) {
            return $this->formActionsWhenUserIsNotAllowedToInputData[$formAction];
        } else {
            return $formAction;
        }
    }

    /**
     * Crypts a tag.
     *
     * @param string $tag
     *            The tag
     *
     * @return string The crypted tag
     */
    public static function cryptTag(string $tag): string
    {
        return 'a' . GeneralUtility::md5int($tag);
    }

    /**
     * Generates the form
     *
     * @param string $formAction
     *            (The form action)
     *
     * @return string (the whole content result, wraped as plugin)
     */
    public function renderForm(string $formAction): string
    {
        // Checks if an update query was performed
        $updateQuerier = ($this->querier instanceof UpdateQuerier ? $this->querier : null);

        // Calls the querier
        $querierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\' . ucfirst($formAction) . 'SelectQuerier';
        $this->querier = new ($querierClassName)($this);
        $this->querier->setQueryConfiguration();
        $this->querier->setUpdateQuerier($updateQuerier);
        $queryResult = $this->querier->processQuery();

        // Calls the viewer
        if ($queryResult === false) {
            $viewerClassName = ErrorViewer::class;
        } else {
            $viewerClassName = 'YolfTypo3\\SavLibraryPlus\\Viewers\\' . ucfirst($formAction) . 'Viewer';
        }

        $this->viewer = new ($viewerClassName)($this);
        $this->viewer->setViewLinkConfigurationFromTypoScriptConfiguration();

        if ($this->viewer->viewCanBeRendered() === false) {
            $content = $this->errorAction();
        } else {
            $content = $this->viewer->render();
        }

        return $content;
    }

    /**
     * Generates the default date format
     *
     * @return string
     */
    public function getDefaultDateFormat(): string
    {
        // Gets the default formats
        $extensionDefaultDateFormat = $this->extensionConfigurationManager->getDefaultDateFormat();
        $libraryDefaultDateFormat = $this->libraryConfigurationManager->getDefaultDateFormat();

        // Defines which format to return
        if ($extensionDefaultDateFormat !== null) {
            $defaultDateFormat = $extensionDefaultDateFormat;
        } elseif ($libraryDefaultDateFormat !== null) {
            $defaultDateFormat = $libraryDefaultDateFormat;
        } else {
            $defaultDateFormat = '%d/%m/%Y';
        }
        return $defaultDateFormat;
    }

    /**
     * Generates the default date and time format
     *
     * @return string
     */
    public function getDefaultDateTimeFormat(): string
    {
        // Gets the default formats
        $extensionDefaultDateTimeFormat = $this->extensionConfigurationManager->getDefaultDateTimeFormat();
        $libraryDefaultDateTimeFormat = $this->libraryConfigurationManager->getDefaultDateTimeFormat();

        // Defines which format to return
        if ($extensionDefaultDateTimeFormat !== null) {
            $defaultDateTimeFormat = $extensionDefaultDateTimeFormat;
        } elseif ($libraryDefaultDateTimeFormat !== null) {
            $defaultDateTimeFormat = $libraryDefaultDateTimeFormat;
        } else {
            $defaultDateTimeFormat = '%d/%m/%Y %H:%M';
        }
        return $defaultDateTimeFormat;
    }

    /**
     * Converts additional parameters for a link into an array
     *
     * @param string $additionalParameters
     *
     * @return array
     */
    public static function convertLinkAdditionalParametersToArray(string $additionalParameters): array
    {
        $parameters = explode('&', $additionalParameters);
        $parameterArray = [];
        foreach ($parameters as $parameter) {
            if (! empty($parameter)) {
                $parameterParts = explode('=', $parameter);
                $parameterArray[$parameterParts[0]] = $parameterParts[1];
            }
        }
        return $parameterArray;
    }
 
    /**
     * Gets the page id
     *
     * @return int|null
     */
    public function getPageId(): ?int
    {
        /** @var \TYPO3\CMS\Frontend\Page\PageInformation $pageInformation */
        $pageInformation = $this->request->getAttribute('frontend.page.information');
        return $pageInformation->getId();
    }

    /**
     * Gets the TypoScript configuration
     *
     * @return int
     */
    public function getTypoScriptConfigArray(): array
    {
        $configArray = $this->request->getAttribute('frontend.typoscript')->getConfigArray();
        return $configArray;
    }
    
    /**
     * Gets the root line
     *
     * @return array
     */
    public function getRootLine(): array
    {
        /** @var \TYPO3\CMS\Frontend\Page\PageInformation $pageInformation */
        $pageInformation = $this->request->getAttribute('frontend.page.information');
        return $pageInformation->getRootLine();
    }
    
    /**
     * Returns the language service instance
     *
     * @return LanguageService
     */
    public function getLanguageService(): LanguageService
    {
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
        ->createFromSiteLanguage($this->request->getAttribute('language'));
        
        return $languageService;
    }
    
    /**
     * Gets the plugin TypoScript configuration.
     *
     * @param string $extensionKey
     * @param string $plugin 
     *
     * @return array|null
     */
    public function getPluginTypoScriptConfiguration(string $extensionKey, string $plugin ='_pi1'): ?array
    {
        $key = 'tx_' . str_replace('_', '', $extensionKey) . $plugin . '.';
        $typoScriptConfiguration = $this->request->getAttribute('frontend.typoscript')
        ->getSetupArray()['plugin.'][$key] ?? null;
        if (is_array($typoScriptConfiguration)) {
            return $typoScriptConfiguration;
        } else {
            return null;
        }
    }

}
