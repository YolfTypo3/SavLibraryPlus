<?php
namespace YolfTypo3\SavLibraryPlus\Filters;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * SAV Library Filter: Common code for filters to be used in SAV extensions
 *
 * @author Laurent Foulloy <yolf.typo3@orange.fr>
 *
 */
abstract class AbstractFilter extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

    abstract protected function SetSessionField_addWhere();

    // Variables for HTML outputs
    protected $EOL = '';

     // End of line for HTML output
    protected $TAB = '';

     // Tabulation
    protected $SPACE = '';
 // Space before element
    protected $WRAP = '';
 // String before wrapping

    // General variables for all sav_filter extension
    protected $flexConf = array();
 // Configuration from the flexform
    protected $setterList = array();
 // Additional setter list
    protected $extKeyWithId;
 // The extension key with content Id
    protected $errors;
 // Errors list
    protected $messages;
 // Messages list
    protected $piVarsReloaded = FALSE;
 // True if piVars are reloaded from the session
    protected $debugQuery = FALSE;
 // Debug the query if set to TRUE. FOR DEVELLOPMENT ONLY !!!
    protected $forceSetSessionFields = FALSE;
 // Force the execution of setSessionFields
    protected $setFilterSelected = TRUE;
 // If FALSE the filter is not selected
    protected $iconRootPath;
 // The iconRootPath if any.

    // Session variables
    protected $sessionFilter = array();
 // Filters data
    protected $sessionFilterSelected = '';
 // Selected filter key
    protected $sessionAuth = array();
 // Authentications data

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
     * @return boolean (FALSE if a problem occured)
     */
    protected function init()
    {

        // Checks if a global maintenance is requested. In this case do not display the filtter.
        $temp = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sav_library']);
        $maintenanceAllowedUsers = explode(',', $temp['maintenanceAllowedUsers']);
        if ($temp['maintenance']) {
            if (! in_array($GLOBALS['TSFE']->fe_user->user['uid'], $maintenanceAllowedUsers)) {
                return FALSE;
            }
        }

        // Gets the session variables
        $this->sessionFilter = $GLOBALS['TSFE']->fe_user->getKey('ses', 'filters');
        $this->sessionFilterSelected = $GLOBALS['TSFE']->fe_user->getKey('ses', 'selectedFilterKey');
        $this->sessionAuth = $GLOBALS['TSFE']->fe_user->getKey('ses', 'auth');

        // Sets debug
        if ($this->debugQuery) {
            $GLOBALS['TYPO3_DB']->debugOutput = TRUE;
        }

        // Sets the pageID
        $this->extKeyWithId = $this->extKey . '_' . $this->cObj->data['uid'];
        if ($this->sessionFilter[$this->extKeyWithId]['pageID'] != $GLOBALS['TSFE']->id && $this->sessionFilterSelected == $this->extKeyWithId) {
            unset($this->sessionFilterSelected);
        }
        $this->sessionFilter[$this->extKeyWithId]['pageID'] = $GLOBALS['TSFE']->id;
        $this->sessionFilter[$this->extKeyWithId]['contentID'] = $this->cObj->data['uid'];
        $this->sessionFilter[$this->extKeyWithId]['tstamp'] = time();

        // Recovers the piVars in the session
        if (! count($this->piVars) && (GeneralUtility::_GP('sav_library') || GeneralUtility::_GP('sav_library_plus')) && isset($this->sessionFilter[$this->extKeyWithId]['piVars'])) {
            $this->piVars = $this->sessionFilter[$this->extKeyWithId]['piVars'];
            $this->sessionFilterSelected = $this->extKeyWithId;
            $this->piVarsReloaded = TRUE;
        } elseif ($this->piVars['logout']) {
            unset($this->sessionFilter[$this->extKeyWithId]['piVars']);
            unset($this->sessionAuth[$this->extKeyWithId]);
        } elseif ($this->piVars['logoutReloadPage']) {
            unset($this->sessionFilter[$this->extKeyWithId]);
            unset($this->sessionAuth[$this->extKeyWithId]);
            header('Location: ' . GeneralUtility::locationHeaderUrl($this->pi_getPageLink($GLOBALS['TSFE']->id)));
        } elseif ($this->sessionAuth[$this->extKeyWithId]['authenticated']) {
            if ($this->sessionFilter[$this->extKeyWithId]['piVars']) {
                $this->piVars = $this->sessionFilter[$this->extKeyWithId]['piVars'];
            }
            $this->sessionFilterSelected = $this->extKeyWithId;
        }

        // Initializes the FlexForm configuration for plugin and gets the configuration fields
        $this->pi_initPIflexForm();
        if (! isset($this->cObj->data['pi_flexform']['data'])) {
            $this->addError('error.incorrectPluginConfiguration_1', $this->extKey);
            $this->addError('error.incorrectPluginConfiguration_2');
            return $this->pi_wrapInBaseClass($this->showErrors());
        }
        foreach ($this->cObj->data['pi_flexform']['data']['sDEF']['lDEF'] as $key => $value) {
            $flexformValue = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key);
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
            if (file_exists(ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Public/Css/' . $this->extKey . '.css')) {
                $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Public/Css/' . $this->extKey . '.css';
            } elseif (file_exists(ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Public/Styles/' . $this->extKey . '.css')) {
                $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Public/Styles/' . $this->extKey . '.css';
            } elseif (file_exists(ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Private/Styles/' . $this->extKey . '.css')) {
                $cascadingStyleSheet = ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Private/Styles/' . $this->extKey . '.css';
            } else {
                $this->addError('error.incorrectCSS');
                return FALSE;
            }
        } elseif (file_exists($this->conf['fileCSS'])) {
            $cascadingStyleSheet = $this->conf['fileCSS'];
        } elseif (file_exists($this->conf['stylesheet'])) {
            $cascadingStyleSheet = $this->conf['stylesheet'];
            $css = '<link rel="stylesheet" type="text/css" href="' . $this->conf['stylesheet'] . '" />';
        } else {
            $this->addError('error.incorrectCSS');
            return FALSE;
        }
        AdditionalHeaderManager::addCascadingStyleSheet($cascadingStyleSheet);

        // Sets the icon root path if any
        if (empty($this->conf['iconRootPath']) === FALSE) {
            $this->iconRootPath = $this->conf['iconRootPath'];
        }
        return TRUE;
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
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'filters', $this->sessionFilter);
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'selectedFilterKey', $this->sessionFilterSelected);
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'auth', $this->sessionAuth);
        $GLOBALS['TSFE']->storeSessionData();
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
        $this->sessionFilter[$this->extKeyWithId]['search'] = FALSE;
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
                $this->sessionFilter[$this->extKeyWithId]['enableFields'] .= $this->cObj->enableFields($table);
            }
        }
    }

    /**
     * Wraps views to get a form with name
     *
     * @param string $content (content of the form)
     * @param string $hidden (hidden fields for the form)
     * @param string $name (name of the form)
     *
     * @return string (the whole content result)
     */
    protected function wrapForm($content, $hidden = '', $name = '', $url = '#')
    {
        $htmlArray = array();
        $htmlArray[] = '<div class="savFilter">';

        if ($this->errors) {
            $htmlArray[] = $this->showErrors();
        }

        if ($this->messages) {
            $htmlArray[] = $this->showMessages();
        }

        if (! $this->conf['noForm']) {
            $htmlArray[] = '<form method="post" name="' . $name . '" enctype="multipart/form-data" action="' . $url . '" title="' . $GLOBALS['TSFE']->sL('LLL:EXT:' . $this->extKey . '/locallang.xml:pi1_plus_wiz_description') . '">';
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
     * @return none
     */
    protected function addMessage($messageLabel, $addMessage = '', $class = '')
    {
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
    public function pi_getPageLink($pageId, $target = '', $additionalParameters = array())
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
}
?>
