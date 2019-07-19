<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Frontend\Page\PageRepository;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * This abstract class for an itemViewer.
 *
 * @package SavLibraryPlus
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
    protected static $allowedFunctionNames = [
        'makeItemLink',
        'makeNewWindowLink',
        'makeDateFormat',
        'makeEmailLink',
        'makeUrlLink',
        'makeLink',
        'makeExtLink',
        'makeXmlLabel'
    ];

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
     * @return void
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
     * @return void
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
     * @return void
     */
    public function injectItemConfigurationAttribute($value, $key = null)
    {
        if ($key === null) {
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
     * @return void
     */
    public function setItemConfiguration($key, $value)
    {
        $this->itemConfiguration[$key] = $value;
    }

    /**
     * Returns true if the item configuration for a given key is not set
     *
     * @param string $key
     *            The key
     *
     * @return boolean
     */
    public function itemConfigurationNotSet($key)
    {
        return isset($this->itemConfiguration[$key]) ? false : true;
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
        // Returns nothing if the value is in a hidden field. The hidden is processed in AbstractViewer renderItem()
        if ($this->getItemConfiguration('hiddenvalue') && $this->getItemConfiguration('renderonlyhiddenvalue')) {
            return '';
        }

        // Checks if the item is cut
        if ($this->getItemConfiguration('cutDivItemInner') && empty($this->getItemConfiguration('renderifcut'))) {
            return '';
        }

        // Checks if a hook is set
        $hookName = $this->getItemConfiguration('hookname');
        $reqValueAttribute = $this->getItemConfiguration('reqvalue');
        $renderReqValueAttribute = $this->getItemConfiguration('renderreqvalue');

        if (! empty($hookName)) {
            // Gets the class from the hook
            $hookFound = false;
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sav_library_plus']['hooks'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sav_library_plus']['hooks'] as $key => $classRef) {
                    if ($key == $hookName) {
                        $hookObject = GeneralUtility::makeInstance($classRef);
                        $hookObject->injectController($this->getController());
                        $hookFound = true;
                    }
                }
            }

            if ($hookFound === false) {
                FlashMessages::addError('error.unknownHook', [
                    $hookName
                ]);
                return '';
            }

            // Renders the hooks
            $hookParameters = $this->getItemConfiguration('hookparameters');
            $hookParameters = $this->getController()
                ->getQuerier()
                ->parseLocalizationTags($hookParameters);
            $hookParameters = $this->getController()
                ->getQuerier()
                ->parseFieldTags($hookParameters);
            $hookParameters = json_decode($hookParameters, true);
            $content = $hookObject->renderHook($hookParameters);
        } elseif (! empty($reqValueAttribute) && empty($renderReqValueAttribute)) {
            // Renders the item if the value is not obtained from a reqValue attribute
            $content = $this->getItemConfiguration('value');
        } else {
            $content = $this->renderItem();

            // Applies a function if not in edit mode and if any
            if ($this->isEditItemViewer() === false) {
                // Checks if a function should be applied
                if (! $this->getItemConfiguration('applyfunctorecords')) {
                    $content = $this->processFuncAttribute($content);
                }
            }
        }

        $content = $this->getLeftValue() . $content . $this->getRightValue();

        // Applies a TypoScript StdWrap to the item, if any
        $stdWrapItem = $this->getItemConfiguration('stdwrapitem');
        if (empty($stdWrapItem) === false) {
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
        if (empty($functionName) === false) {
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
                FlashMessages::addError(
                    'error.unknownFunction',
                    [
                        $functionName
                    ]
                );
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
        if (empty($functionName) === false) {
            $this->setItemConfiguration('funcspecial', 'right');
            if (in_array($functionName, self::$allowedFunctionNames)) {
                $content = $this->$functionName($content);
            }
        }

        if (empty($content) === false) {
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
        if (empty($functionName) === false) {
            $this->setItemConfiguration('funcspecial', 'left');
            if (in_array($functionName, self::$allowedFunctionNames)) {
                $content = $this->$functionName($content);
            }
        }

        if (empty($content) === false) {
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
    protected function arrayToHTML($htmlArray, $noHTMLprefix = false)
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
        $formParameters = [
            'formAction' => $formAction,
            'uid' => $uid
        ];

        // Adds parameter to access to a folder tab (page is an alias)
        if ($this->getItemConfiguration('page' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfiguration('page' . $special));
        }
        if ($this->getItemConfiguration('foldertab' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfiguration('foldertab' . $special));
        }

        // Sets the cache hash flag
        $cacheHash = (ExtensionConfigurationManager::isCacheHashRequired() ? 1 : 0);

        // Adds no_cache if required
        $additionalParameters = (UriManager::hasNoCacheParameter() ? ['no_cache' => 1] : []);

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

        // Gets the formAction
        if ($this->getItemConfiguration('inputform' . $special) || $this->getItemConfiguration('edit' . $special)) {
            $formAction = 'edit';
        } else {
            $formAction = 'single';
        }

        // Gets the content id
        $contentId = $this->getItemConfiguration('contentid' . $special);

        // Gets the message and processes it
        $message = ($this->getItemConfiguration('message' . $special) ? $this->getItemConfiguration('message' . $special) : $value);
        $message = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->getController()
            ->getQuerier()
            ->parseFieldTags($message);

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
        $formParameters = [
            'formName' => $formName,
            'formAction' => $formAction,
            'uid' => intval($uid),
            'pageId' => $this->getItemConfiguration('pageid' . $special)
        ];

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
        if ($this->getItemConfiguration('restrictlinkto' . $special)) {
            $match = [];
            if (preg_match('/###usergroup\s*(!?)=\s*(.*?)###/', $this->getItemConfiguration('restrictlinkto' . $special), $match)) {
                $rows = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTgetRows(
                    /* SELECT   */	'uid,title',
                    /* FROM     */	'fe_groups',
                    /* WHERE    */	'title=\'' . $match[2] . '\'' .
                                    $this->getPageRepository()
                                        ->enableFields('fe_groups')
                );
                $cond = (bool) $match[1] ^ in_array($rows[0]['uid'], explode(',', $this->getTypoScriptFrontendController()->fe_user->user['usergroup']));
                return ($cond ? $this->getController()->buildLinkToPage($message, $formParameters) : $value);
            } else {
                return $this->getController()->buildLinkToPage($message, $formParameters);
            }
        } else {
            return $this->getController()->buildLinkToPage($message, $formParameters);
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
        if (empty($message) === false) {
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
        $typoScriptConfiguration = [
            'parameter' => $parameter,
            'target' => $this->getItemConfiguration('target' . $special),
            'ATagParams' => ($this->getItemConfiguration('class' . $special) ? 'class="' . $this->getItemConfiguration('class' . $special) . '" ' : '')
        ];

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
        if (is_file($windowUrl) === false) {
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
        $typoScriptConfiguration = [
            'bodyTag' => '<body' . $windowBodyStyle . '>' . ($windowText ? $windowText . '<br />' : ''),
            'enable' => 1,
            'JSwindow' => 1,
            'wrap' => '<a href="javascript:close();"> | </a>',
            'JSwindow.' => [
                'newWindow' => 1,
                'expand' => '20,' . ($windowText ? '40' : '20')
            ]
        ];

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

        $typoScriptConfiguration = [
            'parameter' => ($this->getItemConfiguration('link') ? $this->getItemConfiguration('link') : $value)
        ];

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

        $typoScriptConfiguration = [
            'parameter' => ($this->getItemConfiguration('link') ? $this->getItemConfiguration('link') : $value),
            'extTarget' => ($this->getItemConfiguration('exttarget') ? $this->getItemConfiguration('exttarget') : '_blank')
        ];

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
        return $this->getTypoScriptFrontendController()->sL($this->getItemConfiguration('xmllabel' . $special) . $value);
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
        if (empty($format) === true) {
            $format = ($this->getItemConfiguration('eval' . $special) == 'datetime' ? $this->getController()->getDefaultDateTimeFormat() : $this->getController()->getDefaultDateFormat());
        }

        return strftime($format, (int) $timeStamp);
    }

    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Gets the Page Repository
     *
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepository(): PageRepository
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        return $pageRepository;
    }
}
?>
