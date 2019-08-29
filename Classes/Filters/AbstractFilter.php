<?php
namespace YolfTypo3\SavLibraryPlus\Filters;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * SAV Library Filter: Common code for filters to be used in SAV extensions
 *
 * @package SavLibraryPlus
 * @deprecated Will be removed in TYPO3 10.
 */
abstract class AbstractFilter extends AbstractPlugin
{

    abstract protected function SetSessionField_addWhere();

    /**
     * End of line for HTML output
     *
     * @var string
     */
    protected $EOL = '';

    /**
     * Tabulation
     *
     * @var string
     */
    protected $TAB = '';

    /**
     * Space before element
     *
     * @var string
     */
    protected $SPACE = '';

    /**
     * String before wrapping
     *
     * @var string
     */
    protected $WRAP = '';

    /**
     * Configuration from the flexform
     *
     * @var array
     */
    protected $flexConf = [];

    /**
     * Additional setter list
     *
     * @var array
     */
    protected $setterList = [];

    /**
     * he extension key with content Id
     *
     * @var string
     */
    protected $extKeyWithId;

    /**
     * Errors list
     *
     * @var array
     */
    protected $errors;

    /**
     * Messages list
     *
     * @var array
     */
    protected $messages;

    /**
     * True if piVars are reloaded from the session
     *
     * @var bool
     */
    protected $piVarsReloaded = false;

    /**
     * Debug the query if set to true.
     * FOR DEVELLOPMENT ONLY !!!
     *
     * @var bool
     */
    protected $debugQuery = false;

    /**
     * Force the execution of setSessionFields
     *
     * @var bool
     */
    protected $forceSetSessionFields = false;

    /**
     * If false the filter is not selected
     *
     * @var bool
     */
    protected $setFilterSelected = true;

    /**
     * The iconRootPath if any.
     *
     * @var string
     */
    protected $iconRootPath;

    /**
     * Filters data
     *
     * @var array
     */
    protected $sessionFilter = [];

    /**
     * Selected filter key.
     *
     * @var string
     */
    protected $sessionFilterSelected = '';

    /**
     * Authentication data
     *
     * @var array
     */
    protected $sessionAuth = [];

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->EOL = chr(10);
        $this->TAB = chr(9);
        $this->SPACE = '    ';
        $this->WRAP = $this->EOL . $this->TAB . $this->TAB;
    }

    /**
     * Initializes the filter
     *
     * @return boolean (false if a problem occured)
     */
    protected function init()
    {
        // Gets the session variables
        $this->sessionFilter = $this->getTypoScriptFrontendController()->fe_user->getKey('ses', 'filters');
        $this->sessionFilterSelected = $this->getTypoScriptFrontendController()->fe_user->getKey('ses', 'selectedFilterKey');
        $this->sessionAuth = $this->getTypoScriptFrontendController()->fe_user->getKey('ses', 'auth');

        // Sets debug
        if ($this->debugQuery) {
            DatabaseCompatibility::getDatabaseConnection()->debugOutput = true;
        }

        // Sets the pageId
        $this->extKeyWithId = $this->extKey . '_' . $this->getContentObjectRenderer()->data['uid'];
        if ($this->sessionFilter[$this->extKeyWithId]['pageId'] != $this->getTypoScriptFrontendController()->id && $this->sessionFilterSelected == $this->extKeyWithId) {
            unset($this->sessionFilterSelected);
        }
        $this->sessionFilter[$this->extKeyWithId]['pageId'] = $this->getTypoScriptFrontendController()->id;
        $this->sessionFilter[$this->extKeyWithId]['contentUid'] = $this->getContentObjectRenderer()->data['uid'];
        $this->sessionFilter[$this->extKeyWithId]['tstamp'] = time();

        // Recovers the piVars in the session
        if (! count($this->piVars) && (GeneralUtility::_GP('sav_library') || GeneralUtility::_GP('sav_library_plus')) && isset($this->sessionFilter[$this->extKeyWithId]['piVars'])) {
            $this->piVars = $this->sessionFilter[$this->extKeyWithId]['piVars'];
            $this->sessionFilterSelected = $this->extKeyWithId;
            $this->piVarsReloaded = true;
        } elseif ($this->piVars['logout']) {
            unset($this->sessionFilter[$this->extKeyWithId]['piVars']);
            unset($this->sessionAuth[$this->extKeyWithId]);
        } elseif ($this->piVars['logoutReloadPage']) {
            unset($this->sessionFilter[$this->extKeyWithId]);
            unset($this->sessionAuth[$this->extKeyWithId]);
            header('Location: ' . GeneralUtility::locationHeaderUrl($this->pi_getPageLink($this->getTypoScriptFrontendController()->id)));
        } elseif ($this->sessionAuth[$this->extKeyWithId]['authenticated']) {
            if ($this->sessionFilter[$this->extKeyWithId]['piVars']) {
                $this->piVars = $this->sessionFilter[$this->extKeyWithId]['piVars'];
            }
            $this->sessionFilterSelected = $this->extKeyWithId;
        }

        // Initializes the FlexForm configuration for plugin and gets the configuration fields
        $this->pi_initPIflexForm();
        if (! isset($this->getContentObjectRenderer()->data['pi_flexform']['data'])) {
            $this->addError('error.incorrectPluginConfiguration_1', $this->extKey);
            $this->addError('error.incorrectPluginConfiguration_2');
            return $this->pi_wrapInBaseClass($this->showErrors());
        }
        foreach ($this->getContentObjectRenderer()->data['pi_flexform']['data']['sDEF']['lDEF'] as $key => $value) {
            $flexformValue = $this->pi_getFFvalue($this->getContentObjectRenderer()->data['pi_flexform'], $key);
            // Keeps the TS configuration for the stylesheet is the flexform field is empty
            if ($key != 'stylesheet' || ! empty($flexformValue)) {
                $this->flexConf[$key] = $flexformValue;
            }
        }

        // Merges the flexform configuration with the plugin configuration
        $this->conf = array_merge($this->conf, $this->flexConf);

        // Includes the default style sheet if none was provided.
        // stylesheet is the new configuration attribute, fileCSS is kept for compatibility.
        if (! $this->conf['fileCSS'] && ! $this->conf['stylesheet']) {
            if (file_exists(ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Public/Css/' . $this->extKey . '.css')) {
                $extensionWebPath = AbstractController::getExtensionWebPath($this->extKey);
                $cascadingStyleSheet = $extensionWebPath . 'Resources/Public/Css/' . $this->extKey . '.css';
            } elseif (file_exists(ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Public/Styles/' . $this->extKey . '.css')) {
                $extensionWebPath = AbstractController::getExtensionWebPath($this->extKey);
                $cascadingStyleSheet = $extensionWebPath . 'Resources/Public/Styles/' . $this->extKey . '.css';
            } elseif (file_exists(ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Styles/' . $this->extKey . '.css')) {
                $extensionWebPath = AbstractController::getExtensionWebPath($this->extKey);
                $cascadingStyleSheet = $extensionWebPath . 'Resources/Private/Styles/' . $this->extKey . '.css';
            } else {
                $this->addError('error.incorrectCSS');
                return false;
            }
        } elseif (file_exists($this->conf['fileCSS'])) {
            $cascadingStyleSheet = $this->conf['fileCSS'];
        } elseif (file_exists($this->conf['stylesheet'])) {
            $cascadingStyleSheet = $this->conf['stylesheet'];
        } else {
            $this->addError('error.incorrectCSS');
            return false;
        }
        AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);

        // Sets the icon root path if any
        if (empty($this->conf['iconRootPath']) === false) {
            $this->iconRootPath = $this->conf['iconRootPath'];
        }

        // Replaces the pid list by the Record page storage if any
        if (! empty($this->getContentObjectRenderer()->data['pages'])) {
            $this->conf['pidList'] = $this->getContentObjectRenderer()->data['pages'];
        }

        return true;
    }

    /**
     * Calls the various setters for the session
     *
     * @return void
     */
    protected function SetSessionFields()
    {
        if ((count($this->piVars) && ! $this->piVarsReloaded) || $this->forceSetSessionFields) {
            $this->SetSessionField_tablename();
            $this->SetSessionField_fieldName();
            $this->SetSessionField_addWhere();
            $this->SetSessionField_search();
            $this->SetSessionField_searchOrder();
            $this->SetSessionField_pidList();
            $this->SetSessionField_enableFields();

            // Sets the filterSelected with the current extension
            if ($this->setFilterSelected) {
                $this->sessionFilterSelected = $this->extKeyWithId;
            }

            // Adds the piVars
            $this->sessionFilter[$this->extKeyWithId]['piVars'] = $this->piVars;

            // Adds setter
            foreach ($this->setterList as $setter) {
                if (method_exists($this, $setter)) {
                    $this->$setter();
                }
            }
        }

        // Sets session data
        $this->getTypoScriptFrontendController()->fe_user->setKey('ses', 'filters', $this->sessionFilter);
        $this->getTypoScriptFrontendController()->fe_user->setKey('ses', 'selectedFilterKey', $this->sessionFilterSelected);
        $this->getTypoScriptFrontendController()->fe_user->setKey('ses', 'auth', $this->sessionAuth);
        $this->getTypoScriptFrontendController()->fe_user->storeSessionData();
    }

    /**
     * Default setters
     */

    /**
     * Setter for tableName
     *
     * @return void
     */
    protected function SetSessionField_tableName()
    {
        $this->sessionFilter[$this->extKeyWithId]['tableName'] = '';
    }

    /**
     * Setter for fieldName
     *
     * @return void
     */
    protected function SetSessionField_fieldName()
    {
        $this->sessionFilter[$this->extKeyWithId]['fieldName'] = '';
    }

    /**
     * Setter for search
     *
     * @return void
     */
    protected function SetSessionField_search()
    {
        $this->sessionFilter[$this->extKeyWithId]['search'] = false;
    }

    /**
     * Setter for order
     *
     * @return void
     */
    protected function SetSessionField_searchOrder()
    {
        $this->sessionFilter[$this->extKeyWithId]['searchOrder'] = '';
    }

    /**
     * Setter for pidList
     *
     * @return void
     */
    protected function SetSessionField_pidList()
    {
        $this->sessionFilter[$this->extKeyWithId]['pidList'] = ($this->conf['pidList'] ? ' AND pid IN (' . $this->conf['pidList'] . ')' : '');
    }

    /**
     * Setter for enableFields
     *
     * @return void
     */
    protected function SetSessionField_enableFields()
    {
        $this->sessionFilter[$this->extKeyWithId]['enableFields'] = '';
        $tables = explode(',', $this->conf['tableName']);
        foreach ($tables as $table) {
            if (isset($GLOBALS['TCA'][$table])) {
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $this->sessionFilter[$this->extKeyWithId]['enableFields'] .= $pageRepository->enableFields($table);
            }
        }
    }

    /**
     * Wraps views to get a form with name
     *
     * @param string $content
     *            (content of the form)
     * @param string $hidden
     *            (hidden fields for the form)
     * @param string $name
     *            (name of the form)
     *
     * @return string (the whole content result)
     */
    protected function wrapForm($content, $hidden = '', $name = '', $url = '#')
    {
        $htmlArray = [];
        $htmlArray[] = '<div class="savFilter">';

        if ($this->errors) {
            $htmlArray[] = $this->showErrors();
        }

        if ($this->messages) {
            $htmlArray[] = $this->showMessages();
        }

        if (! $this->conf['noForm']) {
            $htmlArray[] = '<form method="post" name="' . $name . '" enctype="multipart/form-data" action="' . $url . '" title="' . $this->getTypoScriptFrontendController()->sL('LLL:EXT:' . $this->extKey . '/locallang.xml:pi1_plus_wiz_description') . '">';
        }
        $htmlArray[] = '  <div class="container-' . str_replace('_', '', $this->extKey) . '">';
        if ($hidden) {
            $htmlArray[] = array_merge($htmlArray, explode($this->EOL, '    ' . implode($this->EOL . '    ', explode($this->EOL, $hidden))));
        }
        $htmlArray = array_merge($htmlArray, explode($this->EOL, '    ' . implode($this->EOL . '    ', explode($this->EOL, $content))));
        $htmlArray[] = '  </div>';
        if (! $this->conf['noForm']) {
            $htmlArray[] = '</form>';
        }

        $htmlArray[] = '</div>';

        return implode($this->WRAP, $htmlArray);
    }

    /**
     * Adds a class to a link
     *
     * @param string $x
     *            (string containin the <a> tag)
     * @param string $class
     *            (string containin the class)
     *
     * @return string (string with the class added)
     */
    protected function add_class($x, $class)
    {
        return preg_replace('/^<a/', '<a class="' . $class . '"', $x);
    }

    /**
     * Adds an error to the error list
     *
     * @param string $errorLabel
     *            (error label)
     * @param string $addMessage
     *            (additional message)
     *
     * @return void
     */
    protected function addError($errorLabel, $addMessage = '')
    {
        $this->errors[] = sprintf($this->pi_getLL($errorLabel), $addMessage);
    }

    /**
     * Return the error list
     *
     * @return string (the error content result)
     */
    protected function showErrors()
    {
        if (! $this->errors) {
            return '';
        } else {
            $errorList = '';
            foreach ($this->errors as $error) {
                $errorList .= '<li class="error">' . $error . '</li>';
            }
            return '<ul>' . $errorList . '</ul>';
        }
    }

    /**
     * Adds a message to the messagess array
     *
     * @param string $messageLabel
     *            (message label)
     * @param string $addMessage
     *            (additionalmessage)
     * @param string $class
     *            (class)
     *
     * @return void
     */
    protected function addMessage($messageLabel, $addMessage = '', $class = '')
    {
        $message = [];
        $message['text'] = sprintf($this->pi_getLL($messageLabel), $addMessage);
        $message['class'] = $class;
        $this->messages[] = $message;
    }

    /**
     * Returns the message list
     *
     * @return string (the messgae content result)
     */
    protected function showMessages()
    {
        if (! $this->messages) {
            return '';
        } else {
            $messageList = '';
            foreach ($this->messages as $message) {
                $messageList .= '<li class="' . ($message['class'] ? $message['class'] : 'message') . '">' . $message['text'] . '</li>';
            }
            return '<ul>' . $messageList . '</ul>';
        }
    }

    /**
     * Transforms an array of HTML code into HTML code
     *
     * @param array $htmlArray
     * @param string $space
     *
     * @return string
     */
    protected function arrayToHTML($htmlArray, $space = '')
    {
        return implode($this->EOL . $space, $htmlArray);
    }

    /**
     * Gets the page URI
     *
     * @param integer $pageId
     *
     * @return string
     */
    public function pi_getPageLink($pageId, $target = '', $additionalParameters = [])
    {
        if (is_array($this->conf['link.'])) {
            if (! empty($this->conf['link.']['target'])) {
                $target = $this->conf['link.']['target'];
            }
            if (! empty($this->conf['link.']['additionalParams'])) {
                $additionalParameters = array_merge($additionalParameters, $this->conf['link.']['additionalParams']);
            }
        }

        return parent::pi_getPageLink($pageId, $target, $additionalParameters);
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
     * Gets the content object renderer
     *
     * @return ContentObjectRenderer
     */
    protected function getContentObjectRenderer()
    {
        return $this->cObj;
    }
}
?>
