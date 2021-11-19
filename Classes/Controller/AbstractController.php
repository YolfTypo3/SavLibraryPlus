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

namespace YolfTypo3\SavLibraryPlus\Controller;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\FormConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\PageTypoScriptConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\UserManager;
use YolfTypo3\SavLibraryPlus\Managers\SessionManager;
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

    // Debug constants
    const DEBUG_NONE = 0;

    const DEBUG_QUERY = 1;

    const DEBUG_PERFORMANCE = 2;

    /**
     * Variable to encode/decode form parameters
     *
     * @var array
     */
    private static $formParameters = [
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
    private static $formActions = [
        'changeFolderTab',
        'changePageInSubform',
        'changePageInSubformInEditMode',
        'close',
        'closeInEditMode',
        'delete',
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
    private static $formActionsWhenUserIsNotAllowedToInputData = [
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
    private $libraryConfigurationManager;

    /**
     * The extension configuration manager
     *
     * @var ExtensionConfigurationManager
     */
    private $extensionConfigurationManager;

    /**
     * The uri manager
     *
     * @var UriManager
     */
    private $uriManager;

    /**
     * The user manager
     *
     * @var UserManager
     */
    private $userManager;

    /**
     * The session manager
     *
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * The page TypoScript manager
     *
     * @var PageTypoScriptConfigurationManager
     */
    private $pageTypoScriptConfigurationManager;

    /**
     * The querier
     *
     * @var AbstractQuerier
     */
    protected $querier = null;

    /**
     * The viewer
     *
     * @var AbstractViewer
     */
    protected $viewer = null;

    /**
     * Debug flag
     *
     * @var int
     */
    private $debugFlag;

    /**
     * The form name
     *
     * @var string
     */
    private static $formName;

    /**
     * The short form name (without the content id)
     *
     * @var string
     */
    private static $shortFormName;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        // Creates the library configuration manager
        $this->libraryConfigurationManager = GeneralUtility::makeInstance(LibraryConfigurationManager::class);
        $this->libraryConfigurationManager->injectController($this);

        // Creates the extension configuration manager
        $this->extensionConfigurationManager = GeneralUtility::makeInstance(ExtensionConfigurationManager::class);
        $this->extensionConfigurationManager->injectController($this);

        // Creates the URI manager
        $this->uriManager = GeneralUtility::makeInstance(UriManager::class);
        $this->uriManager->injectController($this);

        // Creates the user manager
        $this->userManager = GeneralUtility::makeInstance(UserManager::class);
        $this->userManager->injectController($this);

        // Creates the session manager
        $this->sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        $this->sessionManager->injectController($this);

        // Creates the page TypoScript manager
        $this->pageTypoScriptConfigurationManager = GeneralUtility::makeInstance(PageTypoScriptConfigurationManager::class);
        $this->pageTypoScriptConfigurationManager->injectController($this);
    }

    /**
     * Renders the controller action
     *
     * @return string (the whole content result, wraped as plugin)
     */
    public function render(): string
    {
        // Sets the plugin type
        if ($this->setPluginType() === false)
            return '';

        // Initializes the controller
        if ($this->initialize() === false) {
            $this->viewer = GeneralUtility::makeInstance(ErrorViewer::class);
            $this->viewer->injectController($this);
            $content = $this->viewer->render();
            return ($content === null ? '' : $content);
        }

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
     * Sets the plugin type.
     *
     * @return boolean
     */
    protected function setPluginType()
    {
        // Gets the content object
        $contentObject = ExtensionConfigurationManager::getExtensionContentObject();

        // Gets the user plugin flag
        $userPluginFlag = FormConfigurationManager::getUserPluginFlag();

        if (empty($userPluginFlag) || UriManager::hasNoCacheParameter() === true) {
            // Converts the plugin to the USER_INT type
            if ($contentObject->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER) {
                $contentObject->convertToUserIntObject();
                return false;
            }
        }
        return true;
    }

    /**
     * Initializes the controller
     *
     * @return boolean (true if no error occurs)
     */
    public function initialize()
    {
        // Sets debug
        if ($this->debugFlag & self::DEBUG_QUERY) {
            DatabaseCompatibility::getDatabaseConnection()->debugOutput = true;
        }

        // Initializes the library configuration manager
        if ($this->getLibraryConfigurationManager()->initialize() === false) {
            return false;
        }

        // Sets the form name
        $this->setFormName();
        return true;
    }

    /**
     * Sets the debug variable
     *
     * @param integer $debug
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debugFlag = $debug;
    }

    /**
     * Gets the debug variable
     *
     * @return integer
     */
    public function getDebug()
    {
        return $this->debugFlag;
    }

    /**
     * Gets the form name
     *
     * @return string
     */
    public static function getFormName()
    {
        return self::$formName;
    }

    /**
     * Gets the short form name
     *
     * @return string
     */
    public static function getShortFormName()
    {
        return self::$shortFormName;
    }

    /**
     * Sets the form name
     *
     * @return string
     */
    public function setFormName()
    {
        $formTitle = FormConfigurationManager::getFormTitle();
        self::$shortFormName = $this->getExtensionConfigurationManager()->getExtensionKey() . '_' . strtr(strtolower($formTitle), ' -', '__');
        self::$formName = self::$shortFormName . '_' . $this->getExtensionConfigurationManager()->getContentIdentifier();
    }

    /**
     * Gets the Library Configuration manager
     *
     * @return LibraryConfigurationManager
     */
    public function getLibraryConfigurationManager()
    {
        return $this->libraryConfigurationManager;
    }

    /**
     * Gets the extension configuration manager.
     *
     * @return ExtensionConfigurationManager
     */
    public function getExtensionConfigurationManager()
    {
        return $this->extensionConfigurationManager;
    }

    /**
     * Gets the uri manager.
     *
     * @return UriManager
     */
    public function getUriManager()
    {
        return $this->uriManager;
    }

    /**
     * Gets the user manager.
     *
     * @return UserManager
     */
    public function getUserManager()
    {
        return $this->userManager;
    }

    /**
     * Gets the session manager.
     *
     * @return SessionManager
     */
    public function getSessionManager()
    {
        return $this->sessionManager;
    }

    /**
     * Gets the page TypoScript configuration manager.
     *
     * @return PageTypoScriptConfigurationManager
     */
    public function getPageTypoScriptConfigurationManager()
    {
        return $this->pageTypoScriptConfigurationManager;
    }

    /**
     * Injects the querier
     *
     * @param AbstractQuerier $querier
     *
     * @return void
     */
    public function injectQuerier($querier)
    {
        $this->querier = $querier;
    }

    /**
     * Gets the querier
     *
     * @return AbstractQuerier
     */
    public function getQuerier()
    {
        return $this->querier;
    }

    /**
     * Injects the viewer
     *
     * @param AbstractViewer $viewer
     *
     * @return void
     */
    public function injectViewer($viewer)
    {
        $this->viewer = $viewer;
    }

    /**
     * Gets the viewer
     *
     * @return AbstractViewer
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * Gets the action name
     *
     * @return string
     */
    public function getActionName()
    {
        // Checks if the URI is verified
        if (UriManager::uriIsVerified() === false) {
            return 'errorAction';
        }

        // Default action name.
        $actionName = 'list';

        // Processes the filter if selected
        $selectedFilterKey = SessionManager::getSelectedFilterKey();
        if (! empty($selectedFilterKey)) {
            // Gets search tag if any
            $filterSearchTag = SessionManager::getFilterField($selectedFilterKey, 'searchTag');
            if (! empty($filterSearchTag)) {
                SessionManager::setFieldFromSession('tagInSession', $filterSearchTag);
            }

            // Gets the action from the filter if any
            $filterActionName = SessionManager::getFilterField($selectedFilterKey, 'formAction');
            if (! empty($filterActionName)) {
                $actionName = $filterActionName;
            }
        }

        // Gets the action
        if (UriManager::hasLibraryParameter()) {
            // Sets the GET variables
            UriManager::setGetVariables();

            // Retrieves the action from the URI if it is the active form
            if (UriManager::isActiveForm() === true) {
                $actionName = UriManager::getFormAction();
            } else {
                // Retreieves the action from the
                $compressedParameters = SessionManager::getFieldFromSession('compressedParameters');

                if (! empty($compressedParameters)) {
                    UriManager::setCompressedParameters($compressedParameters);
                    if (UriManager::isActiveForm() === true) {
                        $actionName = UriManager::getFormAction();
                    }
                }
            }
        }

        // Checks if the user is allowed to input data
        if ($this->getUserManager()->userIsAllowedToInputData() === false) {
            $actionName = self::getFormActionWhenUserIsNotAllowedToInputData($actionName);
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
    public static function compressParameters($parameters)
    {
        $out = '';
        foreach ($parameters as $parameterKey => $parameter) {
            $key = array_search($parameterKey, self::$formParameters);
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
                    $key = array_search($parameter, self::$formActions);
                    if ($key === false) {
                        FlashMessages::addError('error.unknownFormAction', [
                            $parameter
                        ]);
                        return '';
                    } else {
                        $out .= sprintf('%02x%s', strlen($key), $key);
                    }
                    break;
                case 'formName':
                    if (empty($parameter)) {
                        $parameter = self::getFormName();
                    }
                    $parameter = hash(ExtensionConfigurationManager::getFormNameHashAlgorithm(), $parameter);
                    $out .= sprintf('%02x%s', strlen($parameter), $parameter);
                    break;
                default:
                    $out .= sprintf('%02x%s', strlen($parameter), $parameter);
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
     * @return array (parameter array)
     */
    public static function uncompressParameters($compressedString, $formName = null)
    {
        // Checks if there is a fragment in the link
        $fragmentPosition = strpos($compressedString, '#');
        if ($fragmentPosition !== false) {
            $compressedString = substr($compressedString, 0, $fragmentPosition);
        }
        $out = [];

        while ($compressedString) {
            // Reads the form param index
            list ($parameter) = sscanf($compressedString, '%1x');
            $formParameter = self::$formParameters[$parameter];
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
                    $out[$formParameter] = self::$formActions[$value];
                    if (empty($out[$formParameter])) {
                        FlashMessages::addError('error.unknownFormAction', [
                            $value
                        ]);
                    }
                    break;
                case 'formName':
                    if ($formName === null) {
                        $formName = self::getFormName();
                    }
                    if ($value != hash((ExtensionConfigurationManager::getFormNameHashAlgorithm()), $formName)) {
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
    public static function changeCompressedParameters($compressedParameters, $key, $value)
    {
        $uncompressParameters = self::uncompressParameters($compressedParameters);
        $uncompressParameters[$key] = $value;
        return self::compressParameters($uncompressParameters);
    }

    /**
     * Gets the relative web path of a given extension.
     *
     * @param string $extension
     *            The extension
     *
     * @return string The relative web path
     */
    public static function getExtensionWebPath($extension)
    {
        $extensionWebPath = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath($extension));
        if ($extensionWebPath[0] === '/') {
            // Makes the path relative
            $extensionWebPath = substr($extensionWebPath, 1);
        }
        return $extensionWebPath;
    }

    /**
     * Builds a link to the current page.
     *
     * @param string $str
     *            (string associated with the link)
     * @param array $formParameters
     *            (form parameters)
     * @param integer $cache
     *            (set to 1 if the page should be cached)
     * @param boolean $additionalParameters
     *            (if true, phash is added to the form parameters)
     *
     * @return string (link)
     */
    public function buildLinkToPage($str, $formParameters, $cache = 0, $additionalParameters = [])
    {
        // Gets the page id
        $pageId = $formParameters['pageId'];
        if (! empty($pageId)) {
            unset($formParameters['pageId']);
        } else {
            $pageId = $this->getPageId();
        }

        // Gets the form name
        $formName = ($formParameters['formName'] ? $formParameters['formName'] : self::getFormName());

        // Builds the form parameters
        $formParameters = array_merge([
            'formName' => $formName
        ], $formParameters);

        // Adds the additional parameters in link configuration if any
        $viewer = $this->getViewer();
        if ($viewer !== null) {
            $linkConfiguration = $this->getViewer()->getLinkConfiguration();
        }

        if (! empty($linkConfiguration['additionalParams'])) {
            $additionalParameters = array_merge($additionalParameters, self::convertLinkAdditionalParametersToArray($linkConfiguration['additionalParams']));
        }

        // Creates the link
        $conf = [];

        // Adds the page Id as parameter
        $conf['parameter'] = $pageId;
        if ($formParameters['target']) {
            $conf['target'] = $formParameters['target'];
            unset($formParameters['target']);
        }

        // Adds the linkAccessRestrictedPages attribute
        if ($formParameters['linkAccessRestrictedPages']) {
            $conf['linkAccessRestrictedPages'] = true;
            unset($formParameters['linkAccessRestrictedPages']);
        }

        // Builds the url parameter
        $urlParameters = [
            'sav_library_plus' => self::compressParameters($formParameters)
        ];
        $urlParameters = array_merge($urlParameters, $additionalParameters);
        if (! empty($urlParameters)) {
            $conf['additionalParams'] = HttpUtility::buildQueryString($urlParameters, '&');
        }

        $out = ExtensionConfigurationManager::getExtensionContentObject()->typoLink($str, $conf);
        return $out;
    }

    /**
     * Gets the form action code.
     *
     * @param string $formAction
     *            The form action
     *
     * @return integer The form action code
     */
    public static function getFormActionCode($formAction)
    {
        return array_search($formAction, self::$formActions);
    }

    /**
     * Gets the form action when the user is not allowed to input data.
     *
     * @param string $formAction
     *            The form action
     *
     * @return string
     */
    public static function getFormActionWhenUserIsNotAllowedToInputData($formAction)
    {
        if (isset(self::$formActionsWhenUserIsNotAllowedToInputData[$formAction])) {
            return self::$formActionsWhenUserIsNotAllowedToInputData[$formAction];
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
    public static function cryptTag($tag)
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
    public function renderForm($formAction)
    {
        // Checks if an update query was performed
        $updateQuerier = ($this->querier instanceof UpdateQuerier ? $this->querier : null);

        // Calls the querier
        $querierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\' . ucfirst($formAction) . 'SelectQuerier';
        $this->querier = GeneralUtility::makeInstance($querierClassName);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->injectUpdateQuerier($updateQuerier);
        $queryResult = $this->querier->processQuery();

        // Calls the viewer
        if ($queryResult === false) {
            $viewerClassName = ErrorViewer::class;
        } else {
            $viewerClassName = 'YolfTypo3\\SavLibraryPlus\\Viewers\\' . ucfirst($formAction) . 'Viewer';
        }

        $this->viewer = GeneralUtility::makeInstance($viewerClassName);
        $this->viewer->injectController($this);
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
    public function getDefaultDateFormat()
    {
        // Gets the default formats
        $extensionDefaultDateFormat = ExtensionConfigurationManager::getDefaultDateFormat();
        $libraryDefaultDateFormat = LibraryConfigurationManager::getDefaultDateFormat();

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
    public function getDefaultDateTimeFormat()
    {
        // Gets the default formats
        $extensionDefaultDateTimeFormat = ExtensionConfigurationManager::getDefaultDateTimeFormat();
        $libraryDefaultDateTimeFormat = LibraryConfigurationManager::getDefaultDateTimeFormat();

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
    public static function convertLinkAdditionalParametersToArray($additionalParameters)
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
     * Gets the TypoScript Frontend Controller
     *
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Gets the page id
     *
     * @return integer
     */
    protected function getPageId()
    {
        // @extensionScannerIgnoreLine
        return $this->getTypoScriptFrontendController()->id;
    }

}
