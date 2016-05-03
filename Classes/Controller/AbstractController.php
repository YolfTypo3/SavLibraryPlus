<?php
namespace SAV\SavLibraryPlus\Controller;

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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use SAV\SavLibraryPlus\Controller\FlashMessages;
use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use SAV\SavLibraryPlus\Managers\FormConfigurationManager;
use SAV\SavLibraryPlus\Managers\LibraryConfigurationManager;
use SAV\SavLibraryPlus\Managers\PageTypoScriptConfigurationManager;
use SAV\SavLibraryPlus\Managers\UriManager;
use SAV\SavLibraryPlus\Managers\UserManager;
use SAV\SavLibraryPlus\Managers\SessionManager;
use SAV\SavLibraryPlus\Viewers\ErrorViewer;
use SAV\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * Abstract controller.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
    private static $formParameters = array(
        'folderKey',
        'formAction',
        'formName',
        'page',
        'pageInSubform',
        'subformFieldKey',
        'subformUidForeign',
        'subformUidLocal',
        'uid',
        'viewId',
        'whereTagKey'
    );

    /**
     * Variable to encode/decode form actions
     *
     * @var array
     */
    private static $formActions = array(
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
    );

    /**
     * Variable to provide alternative form action when the user is not authenticated
     *
     * @var array
     */
    private static $formActionsWhenUserIsNotAuthenticated = array(
        'edit' => 'single',
        'listInEditMode' => 'list',
        'new' => 'list',
        'newInSubform' => 'single',
        'upInSubform' => 'single',
        'downInSubform' => 'single',
        'deleteInSubform' => 'single',
        'changePageInSubformInEditMode' => 'single',
        'firstPageInSubformInEditMode' => 'single',
        'previousPageInSubformInEditMode' => 'single',
        'nextPageInSubformInEditMode' => 'single',
        'lastPageInSubformInEditMode' => 'single'
    );

    /**
     * Variable for the comptability with the SAV Library
     *
     * @var array
     */
    private static $formActionsCompatibility = array(
        'updateFormAction' => 'formAction'
    );

    /**
     * The library configuration manager
     *
     * @var \SAV\SavLibraryPlus\Managers\LibraryConfigurationManager
     */
    private $libraryConfigurationManager;

    /**
     * The extension configuration manager
     *
     * @var \SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager
     */
    private $extensionConfigurationManager;

    /**
     * The uri manager
     *
     * @var \SAV\SavLibraryPlus\Managers\UriManager
     */
    private $uriManager;

    /**
     * The user manager
     *
     * @var \SAV\SavLibraryPlus\Managers\UserManager
     */
    private $userManager;

    /**
     * The session manager
     *
     * @var \SAV\SavLibraryPlus\Managers\SessionManager
     */
    private $sessionManager;

    /**
     * The page TypoScript manager
     *
     * @var \SAV\SavLibraryPlus\Managers\PageTypoScriptConfigurationManager
     */
    private $pageTypoScriptConfigurationManager;

    /**
     * The querier
     *
     * @var \SAV\SavLibraryPlus\Queriers\AbstractQuerier
     */
    protected $querier = NULL;

    /**
     * The viewer
     *
     * @var \SAV\SavLibraryPlus\Queriers\AbstractViewer
     */
    protected $viewer = NULL;

    /**
     * Debug flag
     *
     * @var boolean
     */
    private $debug;

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
     * @return none
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
     *
     * @return string (the whole content result, wraped as plugin)
     */
    public function render()
    {

        // Sets the plugin type
        if ($this->setPluginType() === FALSE)
            return;

            // Initializes the controller
        if ($this->initialize() === FALSE) {
            $this->viewer = GeneralUtility::makeInstance(ErrorViewer::class);
            $this->viewer->injectController($this);
            $content = $this->viewer->render();
            return $content;
        }

        // Loads the sessions
        $this->getSessionManager()->loadSession();

        // Gets the action name.
        $actionName = $this->getActionName();

        // Executes the action
        $content = $this->$actionName();

        // Saves the sessions
        $this->getSessionManager()->saveSession();

        // Adds the javaScript header if required
        AdditionalHeaderManager::addAdditionalJavaScriptHeader();

        return $content;
    }

    /**
     * Sets the plugin type.
     *
     * @return boolean
     */
    protected function setPluginType()
    {
        // Gets the extension
        $extension = $this->getExtensionConfigurationManager()->getExtension();

        // Gets the content object
        $contentObject = ExtensionConfigurationManager::getExtensionContentObject();

        // Gets the user plugin flag
        $userPluginFlag = FormConfigurationManager::getUserPluginFlag();

        if (empty($userPluginFlag) || UriManager::hasNoCacheParameter() === TRUE) {
            // Converts the plugin to the USER_INT type
            if ($contentObject->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER) {
                $contentObject->convertToUserIntObject();
                return FALSE;
            }
            $extension->pi_checkCHash = FALSE;
            $extension->pi_USER_INT_obj = 1;
        } else {
            // USER plugin
            $extension->pi_checkCHash = TRUE;
            $extension->pi_USER_INT_obj = 0;
        }
        return TRUE;
    }

    /**
     * Initializes the controller
     *
     * @return boolean (TRUE if no error occurs)
     */
    public function initialize()
    {
        // Sets debug
        if ($this->debug & self::DEBUG_QUERY) {
            $GLOBALS['TYPO3_DB']->debugOutput = TRUE;
        }

        // Initializes the library configuration manager
        if ($this->getLibraryConfigurationManager()->initialize() === FALSE) {
            return FlashMessages::addError('fatal.incorrectConfiguration');
        }

        // Sets the form name
        $this->setFormName();
    }

    /**
     * Sets the debug variable
     *
     * @param integer $debug
     *
     * @return none
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Gets the debug variable
     *
     * @return integer
     */
    public function getDebug()
    {
        return $this->debug;
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
     * @return \SAV\SavLibraryPlus\Managers\LibraryConfigurationManager
     */
    public function getLibraryConfigurationManager()
    {
        return $this->libraryConfigurationManager;
    }

    /**
     * Gets the extension configuration manager.
     *
     * @return \SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager
     */
    public function getExtensionConfigurationManager()
    {
        return $this->extensionConfigurationManager;
    }

    /**
     * Gets the uri manager.
     *
     * @return \SAV\SavLibraryPlus\Managers\UriManager
     */
    public function getUriManager()
    {
        return $this->uriManager;
    }

    /**
     * Gets the user manager.
     *
     * @return \SAV\SavLibraryPlus\Managers\UserManager
     */
    public function getUserManager()
    {
        return $this->userManager;
    }

    /**
     * Gets the session manager.
     *
     * @return \SAV\SavLibraryPlus\Managers\SessionManager
     */
    public function getSessionManager()
    {
        return $this->sessionManager;
    }

    /**
     * Gets the page TypoScript configuration manager.
     *
     * @return \SAV\SavLibraryPlus\Managers\PageTypoScriptConfigurationManager
     */
    public function getPageTypoScriptConfigurationManager()
    {
        return $this->pageTypoScriptConfigurationManager;
    }

    /**
     * Injects the querier
     *
     * @param \SAV\SavLibraryPlus\Queriers\AbstractQuerier $querier
     *
     * @return none
     */
    public function injectQuerier($querier)
    {
        $this->querier = $querier;
    }

    /**
     * Gets the querier
     *
     * @return \SAV\SavLibraryPlus\Queriers\AbstractQuerier
     */
    public function getQuerier()
    {
        return $this->querier;
    }

    /**
     * Injects the viewer
     *
     * @param \SAV\SavLibraryPlus\Queriers\AbstractViewer $viewer
     *
     * @return none
     */
    public function injectViewer($viewer)
    {
        $this->viewer = $viewer;
    }

    /**
     * Gets the viewer
     *
     * @return \SAV\SavLibraryPlus\Queriers\AbstractViewier
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
        // Default action name.
        $actionName = 'listAction';

        // Gets the action from the filter if any
        $selectedFilterKey = SessionManager::getSelectedFilterKey();
        if (empty($selectedFilterKey) === FALSE) {
            $filterActionName = SessionManager::getFilterField($selectedFilterKey, 'formAction');
            if (empty($filterActionName) === FALSE) {
                $actionName = $filterActionName . 'Action';
            }
        }

        // Gets the action
        if (UriManager::hasLibraryParameter()) {
            // Sets the GET variables
            UriManager::setGetVariables();

            // Retrieves the action from the URI if it is the active form
            if (UriManager::isActiveForm() === TRUE) {
                $actionName = UriManager::getFormAction() . 'Action';
            } else {
                // Retreieves the action from the
                $compressedParameters = SessionManager::getFieldFromSession('compressedParameters');

                if (! empty($compressedParameters)) {
                    UriManager::setCompressedParameters($compressedParameters);
                    if (UriManager::isActiveForm() === TRUE) {
                        $actionName = UriManager::getFormAction() . 'Action';
                    }
                }
            }
        }

        // If needed, the action name is changed (compatibility with SAV Library)
        if (array_key_exists($actionName, self::$formActionsCompatibility)) {
            $actionName = self::$formActionsCompatibility[$actionName];
        }

        return $actionName;
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
            if ($key === FALSE) {
                FlashMessages::addError('error.unknownFormParam', array(
                    $parameterKey
                ));
                return '';
            } else {
                $out .= dechex($key);
            }
            switch ($parameterKey) {
                case 'formAction':
                    $key = array_search($parameter, self::$formActions);
                    if ($key === FALSE) {
                        FlashMessages::addError('error.unknownFormAction', array(
                            $parameter
                        ));
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
    public static function uncompressParameters($compressedString)
    {

        // Checks if there is a fragment in the link
        $fragmentPosition = strpos($compressedString, '#');
        if ($fragmentPosition !== FALSE) {
            $compressedString = substr($compressedString, 0, $fragmentPosition);
        }
        $out = array();

        while ($compressedString) {
            // Reads the form param index
            list ($parameter) = sscanf($compressedString, '%1x');
            $formParameter = self::$formParameters[$parameter];
            if (empty($formParameter)) {
                FlashMessages::addError('error.unknownFormParam', array(
                    $parameter
                ));
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
                        FlashMessages::addError('error.unknownFormAction', array(
                            $value
                        ));
                    }
                    break;
                case 'formName':
                    $formName = self::getFormName();
                    if ($value != hash((ExtensionConfigurationManager::getFormNameHashAlgorithm()), $formName)) {
                        return NULL;
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
     * Builds a link to the current page.
     *
     * @param string $str
     *            (string associated with the link)
     * @param array $formParameters
     *            (form parameters)
     * @param integer $cache
     *            (set to 1 if the page should be cached)
     * @param boolean $additionalParameters
     *            (if TRUE, phash is added to the form parameters)
     *
     * @return string (link)
     */
    public function buildLinkToPage($str, $formParameters, $cache = 0, $additionalParameters = array())
    {
        // Gets the page id
        $pageId = $formParameters['pageId'];
        if (! empty($pageId)) {
            unset($formParameters['pageId']);
        }

        // Gets the form name
        $formName = ($formParameters['formName'] ? $formParameters['formName'] : self::getFormName());

        // Builds the form parameters
        $formParameters = array_merge(array(
            'formName' => $formName
        ), $formParameters);

        // Adds the additional parameters in link configuration if any
        $viewer = $this->getViewer();
        if ($viewer !== NULL) {
            $linkConfiguration = $this->getViewer()->getLinkConfiguration();
        }

        if (! empty($linkConfiguration['additionalParams'])) {
            $additionalParameters = array_merge($additionalParameters, self::convertLinkAdditionalParametersToArray($linkConfiguration['additionalParams']));
        }

        // Builds the parameter array
        $parameters = array(
            'sav_library_plus' => self::compressParameters($formParameters)
        );
        $parameters = array_merge($parameters, $additionalParameters);

        // Creates the link
        if (empty($pageId)) {
            $out = $this->getExtensionConfigurationManager()
                ->getExtension()
                ->pi_linkTP($str, $parameters, $cache);
        } else {
            $out = $this->getExtensionConfigurationManager()
                ->getExtension()
                ->pi_linkToPage($str, $pageId, $formParameters['target'], $parameters);
        }
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
     * Gets the form action when the user is not authenticated.
     *
     * @param string $formAction
     *            The form action
     *
     * @return string
     */
    public static function getFormActionWhenUserIsNotAuthenticated($formAction)
    {
        if (isset(self::$formActionsWhenUserIsNotAuthenticated[$formAction])) {
            return self::$formActionsWhenUserIsNotAuthenticated[$formAction];
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
        // Checks if the user is authenticated
        if ($this->getUserManager()->userIsAuthenticated() === FALSE) {
            $formAction = self::getFormActionWhenUserIsNotAuthenticated($formAction);
        }

        // Checks if an update query was performed
        $updateQuerier = ($this->querier instanceof \SAV\SavLibraryPlus\Queriers\UpdateQuerier ? $this->querier : NULL);

        // Calls the querier
        $querierClassName = 'SAV\\SavLibraryPlus\\Queriers\\' . ucfirst($formAction) . 'SelectQuerier';
        $this->querier = GeneralUtility::makeInstance($querierClassName);
        $this->querier->injectController($this);
        $this->querier->injectQueryConfiguration();
        $this->querier->injectUpdateQuerier($updateQuerier);
        $queryResult = $this->querier->processQuery();

        // Calls the viewer
        if ($queryResult === FALSE) {
            $viewerClassName = 'SAV\\SavLibraryPlus\\Viewers\\ErrorViewer';
        } else {
            $viewerClassName = 'SAV\\SavLibraryPlus\\Viewers\\' . ucfirst($formAction) . 'Viewer';
        }
        $this->viewer = GeneralUtility::makeInstance($viewerClassName);
        $this->viewer->injectController($this);
        $this->viewer->setViewLinkConfigurationFromTypoScriptConfiguration();
        $content = $this->viewer->render();

        return $content;
    }

    public function getDefaultDateFormat()
    {
        // Gets the default formats
        $extensionDefaultDateFormat = ExtensionConfigurationManager::getDefaultDateFormat();
        $libraryDefaultDateFormat = LibraryConfigurationManager::getDefaultDateFormat();

        // Defines which format to return
        if ($extensionDefaultDateFormat !== NULL) {
            $defaultDateFormat = $extensionDefaultDateFormat;
        } elseif ($libraryDefaultDateFormat !== NULL) {
            $defaultDateFormat = $libraryDefaultDateFormat;
        } else {
            $defaultDateFormat = '%d/%m/%Y';
        }
        return $defaultDateFormat;
    }

    public function getDefaultDateTimeFormat()
    {
        // Gets the default formats
        $extensionDefaultDateTimeFormat = ExtensionConfigurationManager::getDefaultDateTimeFormat();
        $libraryDefaultDateTimeFormat = LibraryConfigurationManager::getDefaultDateTimeFormat();

        // Defines which format to return
        if ($extensionDefaultDateTimeFormat !== NULL) {
            $defaultDateTimeFormat = $extensionDefaultDateTimeFormat;
        } elseif ($libraryDefaultDateTimeFormat !== NULL) {
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
        $parameterArray = array();
        foreach ($parameters as $parameter) {
            if (! empty($parameter)) {
                $parameterParts = explode('=', $parameter);
                $parameterArray[$parameterParts[0]] = $parameterParts[1];
            }
        }
        return $parameterArray;
    }
}

?>