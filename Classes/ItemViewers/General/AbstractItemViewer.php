<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
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
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * This abstract class for an itemViewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
abstract class AbstractItemViewer
{

    // Constant for HTML Output
    const EOL = "\n";
    // End of line for HTML output
    const TAB = "\t";
    // Tabulation
    const SPACE = ' ';
    // Space
    const DEFAULT_ITEM_VIEWER = 0;

    const EDIT_ITEM_VIEWER = 1;

    /**
     * The allowed function names
     *
     * @var array
     */
    protected static $allowedFunctionNames = array(
        'makeItemLink',
        'makeNewWindowLink',
        'makeDateFormat',
        'makeEmailLink',
        'makeUrlLink',
        'makeLink',
        'makeExtLink',
        'makeXmlLabel'
    );

    /**
     * The controller
     *
     * @var \YolfTypo3\SavLibraryPlus\Controller\Controller
     */
    protected $controller;

    /**
     *
     * @var integer
     */
    protected $itemViewerType = self::DEFAULT_ITEM_VIEWER;

    /**
     *
     * @var array
     */
    protected $itemConfiguration;

    /**
     * Injects the controller
     *
     * @param \YolfTypo3\SavLibraryPlus\Controller\AbstractController $controller
     *
     * @return none
     */
    public function injectController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Gets the controller
     *
     * @return \YolfTypo3\SavLibraryPlus\Controller\AbstractController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Injects the item configuration
     *
     * @param array $itemConfiguration
     *
     * @return none
     */
    public function injectItemConfiguration(&$itemConfiguration)
    {
        $this->itemConfiguration = $itemConfiguration;
    }

    /**
     * Injects the item configuration attribute
     *
     * @param string $key
     * @param mixed $value
     *
     * @return none
     */
    public function injectItemConfigurationAttribute($value, $key = NULL)
    {
        if ($key === NULL) {
            if (is_array($value)) {
                $this->itemConfiguration = array_merge($this->itemConfiguration, $value);
            }
        } else {
            $this->itemConfiguration[$key] = $value;
        }
    }

    /**
     * Checks if the item is an edit item viewer
     *
     * @return boolean
     */
    public function isEditItemViewer()
    {
        return ($this->itemViewerType == self::EDIT_ITEM_VIEWER);
    }

    /**
     * Gets the item configuration for a given key
     *
     * @param string $key
     *            The key
     *
     * @return mixed the item configuration
     */
    public function getItemConfiguration($key)
    {
        return $this->itemConfiguration[$key];
    }

    /**
     * Sets the item configuration for a given key
     *
     * @param string $key
     *            The key
     * @param string $value
     *            The value
     *
     * @return none
     */
    public function setItemConfiguration($key, $value)
    {
        $this->itemConfiguration[$key] = $value;
    }

    /**
     * Returns TRUE if the item configuration for a given key is not set
     *
     * @param string $key
     *            The key
     *
     * @return boolean
     */
    public function itemConfigurationNotSet($key)
    {
        return isset($this->itemConfiguration[$key]) ? FALSE : TRUE;
    }

    /**
     * Gets the crypted full field name
     *
     * @return string The crypted full field name
     */
    public function getCryptedFullFieldName()
    {
        return AbstractController::cryptTag($this->getItemConfiguration('tableName') . '.' . $this->getItemConfiguration('fieldName'));
    }

    /**
     * Renders an item
     *
     * @return string the rendered item
     */
    public function render()
    {
        // Renders the item if the value is not obtained from a reqValue attribute
        $reqValueAttribute = $this->getItemConfiguration('reqvalue');
        $renderReqValueAttribute = $this->getItemConfiguration('renderreqvalue');
        if (! empty($reqValueAttribute) && empty($renderReqValueAttribute)) {
            $content = $this->getItemConfiguration('value');
        } else {
            $content = $this->renderItem();

            // Applies a function if not in edit mode and if any
            if ($this->isEditItemViewer() === FALSE) {
                // Checks if a function should be applied
                if (! $this->getItemConfiguration('applyfunctorecords')) {
                    $content = $this->processFuncAttribute($content);
                }
            }
        }

        $content = $this->getLeftValue() . $content . $this->getRightValue();

        // Applies a TypoScript StdWrap to the item, if any
        $stdWrapItem = $this->getItemConfiguration('stdwrapitem');
        if (empty($stdWrapItem) === FALSE) {
            $configuration = $this->getController()
                ->getQuerier()
                ->parseLocalizationTags($stdWrapItem);
            $configuration = $this->getController()
                ->getQuerier()
                ->parseFieldTags($configuration);

            $TSparser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $TSparser->parse($configuration);
            $contentObject = $this->getController()
                ->getExtensionConfigurationManager()
                ->getExtensionContentObject();
            $content = $contentObject->stdWrap($content, $TSparser->setup);
        }

        return $content;
    }

    /**
     * Processes func attribute
     *
     * @param string $content
     *
     * @return string the rendered item
     */
    public function processFuncAttribute($content)
    {
        $functionName = $this->getItemConfiguration('func');
        if (empty($functionName) === FALSE) {
            if (in_array($functionName, self::$allowedFunctionNames)) {
                // Adds the function letf and right content if any.
                if (empty($content)) {
                    $content = $this->getItemConfiguration('funcaddleftifnull') . $content . $this->getItemConfiguration('funcaddrighttifnull');
                } else {
                    $content = $this->getItemConfiguration('funcaddleftifnotnull') . $content . $this->getItemConfiguration('funcaddrighttifnotnull');
                }
                // Calls the function
                $content = $this->$functionName($content);
            } else {
                \YolfTypo3\SavLibraryPlus\Controller\FlashMessages::addError('error.unknownFunction', array(
                    $functionName
                ));
            }
        }
        return $content;
    }

    /**
     * Builds the right value content.
     *
     * @return string
     */
    protected function getRightValue()
    {
        $content = '';

        // Gets the value
        $value = $this->getItemConfiguration('value');

        // Gets the right part
        if (empty($value)) {
            $content = $this->getItemConfiguration('addrightifnull');
        } else {
            $content = $this->getItemConfiguration('addrightifnotnull');
        }

        // Evaluates the function if necessary
        $functionName = $this->getItemConfiguration('funcright');
        if (empty($functionName) === FALSE) {
            $this->setItemConfiguration('funcspecial', 'right');
            if (in_array($functionName, self::$allowedFunctionNames)) {
                $content = $this->$functionName($content);
            }
        }

        if (empty($content) === FALSE) {
            $content = $this->getController()
                ->getQuerier()
                ->parseLocalizationTags($content);
            $content = $this->getController()
                ->getQuerier()
                ->parseFieldTags($content);
        }

        return $content;
    }

    /**
     * Builds the left value content.
     *
     * @return string
     */
    protected function getLeftValue()
    {
        $content = '';

        // Gets the value
        $value = $this->getItemConfiguration('value');

        // Gets the left part
        if (empty($value)) {
            $content = $this->getItemConfiguration('addleftifnull');
        } else {
            $content = $this->getItemConfiguration('addleftifnotnull');
        }

        // Evaluates the function if necessary
        $functionName = $this->getItemConfiguration('funcleft');
        if (empty($functionName) === FALSE) {
            $this->setItemConfiguration('funcspecial', 'left');
            if (in_array($functionName, self::$allowedFunctionNames)) {
                $content = $this->$functionName($content);
            }
        }

        if (empty($content) === FALSE) {
            $content = $this->getController()
                ->getQuerier()
                ->parseLocalizationTags($content);
            $content = $this->getController()
                ->getQuerier()
                ->parseFieldTags($content);
        }

        return $content;
    }

    /**
     * Transforms an array of HTML code into HTML code
     *
     * @param array $htmlArray
     * @param boolean $noHTMLprefix
     *
     * @return string
     */
    protected function arrayToHTML($htmlArray, $noHTMLprefix = FALSE)
    {
        if ($noHTMLprefix) {
            return implode('', $htmlArray);
        } else {
            return implode(self::EOL . self::SPACE, $htmlArray);
        }
    }

    /**
     * Creates an item link
     *
     * @param string $value
     *            Value to display
     *
     * @return string The link
     */
    protected function makeItemLink($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the formAction
        if ($this->getItemConfiguration('updateform' . $special) || $this->getItemConfiguration('formadmin' . $special)) {
            $formAction = 'formAdmin';
        } elseif ($this->getItemConfiguration('inputform' . $special) || $this->getItemConfiguration('edit' . $special)) {
            $formAction = 'edit';
        } else {
            $formAction = 'single';
        }

        // Builds the uid
        if ($this->getItemConfiguration('setuid' . $special)) {
            $uid = $this->getController()
                ->getQuerier()
                ->parseFieldTags($this->getItemConfiguration('setuid' . $special));
        } elseif ($this->getItemConfiguration('valueisuid' . $special) || $this->getItemConfiguration('setuid' . $special) == 'this') {
            $uid = $this->getItemConfiguration('value');
        } else {
            $uid = $this->getController()
                ->getQuerier()
                ->getFieldValueFromCurrentRow('uid');
        }

        // Builds the parameters
        $formParameters = array(
            'formAction' => $formAction,
            'uid' => $uid
        );

        // Adds parameter to access to a folder tab (page is an alias)
        if ($this->getItemConfiguration('page' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfiguration('page' . $special));
        }
        if ($this->getItemConfiguration('foldertab' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfiguration('foldertab' . $special));
        }

        // Sets the cache hash flag
        $cacheHash = (\YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager::isCacheHashRequired() ? 1 : 0);

        // Adds no_cache if required
        $additionalParameters = (\YolfTypo3\SavLibraryPlus\Managers\UriManager::hasNoCacheParameter() ? array(
            'no_cache' => 1
        ) : array());

        return $this->getController()->buildLinkToPage($value, $formParameters, $cacheHash, $additionalParameters);
    }

    /**
     * Creates an extension link
     *
     * @param string $value
     *            Value to display
     *
     * @return string The link
     */
    public function makeExtLink($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the content id
        $contentId = $this->getItemConfiguration('contentid' . $special);

        // Builds the form name
        $formName = $this->getItemConfiguration('ext' . $special) . ($contentId ? '_' . $contentId : '');

        // Builds the uid
        if ($this->getItemConfiguration('setuid' . $special)) {
            $uid = $this->getController()
                ->getQuerier()
                ->parseFieldTags($this->getItemConfiguration('setuid' . $special));
        } elseif ($this->getItemConfiguration('valueisuid' . $special) || $this->getItemConfiguration('setuid' . $special) == 'this') {
            $uid = $this->getItemConfiguration('value');
        } else {
            $uid = $this->getItemConfiguration('uid');
        }

        // Builds the parameters
        $formParameters = array(
            'formName' => $formName,
            'formAction' => 'single',
            'uid' => intval($uid),
            'pageId' => $this->getItemConfiguration('pageid' . $special)
        );

        // Adds parameter to access to a folder tab (page is an alias)
        if ($this->getItemConfiguration('page' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfiguration('page' . $special));
        }
        if ($this->getItemConfiguration('foldertab' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfiguration('foldertab' . $special));
        }

        // Adds parameter the subformUidForeign if any
        if ($this->getItemConfiguration('subformuidforeign' . $special)) {
            $formParameters['subformUidForeign'] = $this->getController()
                ->getQuerier()
                ->parseFieldTags($this->getItemConfiguration('subformuidforeign' . $special));
        }

        // Check if the link should be displayed
        if ($params['restrictlinkto'] . $special) {
            if (preg_match('/###usergroup\s*(!?)=\s*(.*?)###/', $params['restrictlinkto' . $special], $match)) {
                $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				  /* SELECT   */	'uid,title',
				  /* FROM     */	'fe_groups',
	 			  /* WHERE    */	'title=\'' . $match[2] . '\'' . $this->cObj->enableFields('fe_groups'));
                $cond = (bool) $match[1] ^ in_array($rows[0]['uid'], explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']));
                return ($cond ? $this->buildLinkToPage($value, $params['pageid' . $special], '', $formParams) : $value);
            } else {
                return $this->buildLinkToPage($value, $params['pageid' . $special], '', $formParams);
            }
        } else {
            return $this->getController()->buildLinkToPage($value, $formParameters);
        }
    }

    /**
     * Creates an internal link
     *
     * @param string $value
     *            (value to display)
     *
     * @return string (link)
     */
    protected function makeLink($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the folder
        $folder = ($this->getItemConfiguration('folder' . $special) ? $this->getItemConfiguration('folder' . $special) : '.');

        // Gets the message and processes it
        $message = ($this->getItemConfiguration('message' . $special) ? $this->getItemConfiguration('message' . $special) : $value);
        $message = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->getController()
            ->getQuerier()
            ->parseFieldTags($message);

        // Builds the parameter attribute
        if (empty($message) === FALSE) {
            if ($this->getItemConfiguration('setuid' . $special)) {
                $parameter = $this->getController()
                    ->getQuerier()
                    ->parseFieldTags($this->getItemConfiguration('setuid' . $special));
            } elseif ($this->getItemConfiguration('valueisuid' . $special)) {
                $parameter = $this->getItemConfiguration('value');
            } else {
                $parameter = $folder . '/' . rawurlencode($value);
            }
        } else {
            $parameter = '';
        }

        // Builds the typoScript configuration
        $typoScriptConfiguration = array(
            'parameter' => $parameter,
            'target' => $this->getItemConfiguration('target' . $special),
            'ATagParams' => ($this->getItemConfiguration('class' . $special) ? 'class="' . $this->getItemConfiguration('class' . $special) . '" ' : '')
        );

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        return $contentObject->typolink($message, $typoScriptConfiguration);
    }

    /**
     * Creates a link and open in a new window
     *
     * @param string $value
     *
     * @return string (link)
     */
    protected function makeNewWindowLink($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the message and processes it
        $message = ($this->getItemConfiguration('message' . $special) ? $this->getItemConfiguration('message' . $special) : $value);
        $message = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->getController()
            ->getQuerier()
            ->parseFieldTags($message);

        // Gets the window url
        $windowUrl = $this->getItemConfiguration('windowurl' . $special);
        $windowUrl = $this->getController()
            ->getQuerier()
            ->parseFieldTags($windowUrl);

        // Returns the message if the window url is not a file
        if (is_file($windowUrl) === FALSE) {
            return $message;
        }

        // Gets the window text
        $windowText = $this->getItemConfiguration('windowtext' . $special);
        $windowText = $this->getController()
            ->getQuerier()
            ->parseFieldTags($windowText);

        // Gets the window style
        $windowBodyStyle = ($this->getItemConfiguration('windowbodystyle' . $special) ? ' style="' . $this->getItemConfiguration('windowbodystyle' . $special) . '"' : '');

        // Builds the typoScript configuration
        $typoScriptConfiguration = array(
            'bodyTag' => '<body' . $windowBodyStyle . '>' . ($windowText ? $windowText . '<br />' : ''),
            'enable' => 1,
            'JSwindow' => 1,
            'wrap' => '<a href="javascript:close();"> | </a>',
            'JSwindow.' => array(
                'newWindow' => 1,
                'expand' => '20,' . ($windowText ? '40' : '20')
            )
        );

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        return $contentObject->imageLinkWrap($message, $windowUrl, $typoScriptConfiguration);
    }

    /**
     * Creates an email link
     *
     * @param string $value
     *
     * @return string (link)
     */
    protected function makeEmailLink($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the message and processes it
        $message = ($this->getItemConfiguration('message' . $special) ? $this->getItemConfiguration('message' . $special) : $value);
        $message = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->getController()
            ->getQuerier()
            ->parseFieldTags($message);

        $typoScriptConfiguration = array(
            'parameter' => ($this->getItemConfiguration('link') ? $this->getItemConfiguration('link') : $value)
        );

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        return $contentObject->typolink($message, $typoScriptConfiguration);
    }

    /**
     * Creates a link for an external url
     *
     * @param string $value
     *            (value to display)
     *
     * @return string (link)
     */
    protected function makeUrlLink($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the message and processes it
        $message = ($this->getItemConfiguration('message' . $special) ? $this->getItemConfiguration('message' . $special) : $value);
        $message = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->getController()
            ->getQuerier()
            ->parseFieldTags($message);

        $typoScriptConfiguration = array(
            'parameter' => ($this->getItemConfiguration('link') ? $this->getItemConfiguration('link') : $value),
            'extTarget' => ($this->getItemConfiguration('exttarget') ? $this->getItemConfiguration('exttarget') : '_blank')
        );

        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        return $contentObject->typolink($message, $typoScriptConfiguration);
    }

    /**
     * Generates the xml label
     *
     * @param string $value
     *            (value to display)
     *
     * @return string (xml label)
     */
    protected function makeXmlLabel($value)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');
        return $GLOBALS['TSFE']->sL($this->getItemConfiguration('xmllabel' . $special) . $value);
    }

    /**
     * Formats a timestamp date according to the configuration
     *
     * @param integer $timeStamp
     *
     * @return string
     */
    protected function makeDateFormat($timeStamp)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfiguration('funcspecial');

        // Gets the format
        $format = $this->getItemConfiguration('format' . $special);
        if (empty($format) === TRUE) {
            $format = ($this->getItemConfiguration('eval' . $special) == 'datetime' ? $this->getController()->getDefaultDateTimeFormat() : $this->getController()->getDefaultDateFormat());
        }

        return strftime($format, (int) $timeStamp);
    }
}
?>
