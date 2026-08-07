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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;

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
    protected static array $allowedFunctionNames = [
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
     * @var AbstractController
     */
    protected AbstractController $controller;

    /**
     *
     * @var int
     */
    protected int $itemViewerType = self::DEFAULT_ITEM_VIEWER;

    /**
     *
     * @var array
     */
    protected array $itemConfiguration;
    
    /**
     * Constructor
     * 
     * @param AbstractController $controller
     *
     * @return void
     */
    public function __construct(AbstractController $controller)
    {
        $this->controller = $controller;
    }
    
    /**
     * Sets the item configuration
     *
     * @param array $itemConfiguration
     *
     * @return void
     */
    public function setItemConfiguration(array &$itemConfiguration): void
    {
        $this->itemConfiguration = $itemConfiguration;
    }

    /**
     * Checks if the item is an edit item viewer
     *
     * @return bool
     */
    public function isEditItemViewer(): bool
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
    public function getItemConfigurationAttribute(string $key): mixed
    {
        return $this->itemConfiguration[$key] ?? null;
    }

    /**
     * Sets the item configuration attribute
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setItemConfigurationAttribute(?string $key = null, mixed $value = null): void
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
     * Returns true if the item configuration for a given key is not set
     *
     * @param string $key
     *            The key
     *
     * @return bool
     */
    public function itemConfigurationAttributeNotSet(string $key): bool
    {
        return isset($this->itemConfiguration[$key]) ? false : true;
    }

    /**
     * Gets the crypted full field name
     *
     * @return string The crypted full field name
     */
    public function getCryptedFullFieldName(): string
    {
        return AbstractController::cryptTag($this->getItemConfigurationAttribute('tableName') . '.' . $this->getItemConfigurationAttribute('fieldName'));
    }

    /**
     * Renders an item
     *
     * @return string the rendered item
     */
    public function render(): string
    {
        // Returns nothing if the value is in a hidden field. The hidden is processed in AbstractViewer renderItem()
        if ($this->getItemConfigurationAttribute('hiddenvalue') && $this->getItemConfigurationAttribute('renderonlyhiddenvalue')) {
            return '';
        }

        // Checks if the item is cut
        if ($this->getItemConfigurationAttribute('cutDivItemInner') && empty($this->getItemConfigurationAttribute('renderifcut'))) {
            return '';
        }

        // Checks if a hook is set
        $hookName = $this->getItemConfigurationAttribute('hookname');
        $reqValueAttribute = $this->getItemConfigurationAttribute('reqvalue');
        $renderReqValueAttribute = $this->getItemConfigurationAttribute('renderreqvalue');

        if (! empty($hookName)) {
            // Gets the class from the hook
            $hookFound = false;
            // @extensionScannerIgnoreLine
            $savLibraryPlusHooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sav_library_plus']['hooks'] ?? [];          
            foreach ($savLibraryPlusHooks as $key => $classRef) {
                if ($key == $hookName) {
                    $hookObject = GeneralUtility::makeInstance($classRef, $this->controller);
                    $hookFound = true;
                }
            }

            if ($hookFound === false) {
                FlashMessages::addError('error.unknownHook', [
                    $hookName
                ]);
                return '';
            }

            // Renders the hooks
            $hookParameters = $this->getItemConfigurationAttribute('hookparameters');
            $hookParameters = $this->controller
                ->getQuerier()
                ->parseLocalizationTags($hookParameters);
            $hookParameters = $this->controller
                ->getQuerier()
                ->parseFieldTags($hookParameters);
            $hookParameters = json_decode($hookParameters, true);
            $content = $hookObject->renderHook($hookParameters);
        } elseif (! empty($reqValueAttribute) && empty($renderReqValueAttribute)) {
            // Renders the item if the value is not obtained from a reqValue attribute
            $content = $this->getItemConfigurationAttribute('value');
        } else {
            $content = $this->renderItem();

            // Applies a function if not in edit mode and if any
            if ($this->isEditItemViewer() === false) {
                // Checks if a function should be applied
                if (! $this->getItemConfigurationAttribute('applyfunctorecords')) {
                    $content = $this->processFuncAttribute($content);
                }
            }
        }

        $content = $this->getLeftValue() . $content . $this->getRightValue();

        // Adds the new icon if required
        if ($this->getItemConfigurationAttribute('addnewicon')) {
            $querier = $this->controller->getQuerier();
            if ($querier !== null) {
                $fullFieldName = $querier->buildFullFieldName('crdate');
                if ($querier->fieldExists($fullFieldName)) {
                    $crdate = $querier->getFieldValue($fullFieldName);
                    $date = new \DateTime('now - ' . $this->getItemConfigurationAttribute('addnewicon') . ' days');
                    if ($date->format('U') - $crdate < 0) {
                        $iconPath = 'EXT:sav_library_plus/Resources/Public/Icons/newicon.gif';
                        $iconWebPath = PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($iconPath));
                        $content = HtmlElements::htmlImgElement([
                            HtmlElements::htmlAddAttribute('src', $iconWebPath),
                            HtmlElements::htmlAddAttribute('alt', 'new icon '),
                            HtmlElements::htmlAddAttribute('class', 'newIcon ')
                        ]) . $content;
                    }
                }
            }
        }

        // Applies a TypoScript StdWrap to the item, if any
        $stdWrapItem = $this->getItemConfigurationAttribute('stdwrapitem');
     
        if (! empty($stdWrapItem)) {
            $configuration = $this->controller
                ->getQuerier()
                ->parseLocalizationTags($stdWrapItem);
            $configuration = $this->controller
                ->getQuerier()
                ->parseFieldTags($configuration);

            /** @var TypoScriptStringFactory $typoScriptStringFactory */
            $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
            $parsedTypoScript = $typoScriptStringFactory->parseFromString($configuration, new AstBuilder(new NoopEventDispatcher()));
            $contentObjectRenderer = $this->controller->getContentObjectRenderer();
            $content = $contentObjectRenderer->stdWrap($content, $parsedTypoScript->toArray());
        }

        return $content;
    }

    /**
     * Processes func attribute
     *
     * @param string|null $content
     *
     * @return string the rendered item
     */
    public function processFuncAttribute(?string $content): string
    {
        $functionName = $this->getItemConfigurationAttribute('func');
        if (empty($functionName) === false) {
            if (in_array($functionName, self::$allowedFunctionNames)) {
                // Adds the function letf and right content if any.
                if (empty($content)) {
                    $content = $this->getItemConfigurationAttribute('funcaddleftifnull') . $content . $this->getItemConfigurationAttribute('funcaddrighttifnull');
                } else {
                    $content = $this->getItemConfigurationAttribute('funcaddleftifnotnull') . $content . $this->getItemConfigurationAttribute('funcaddrighttifnotnull');
                }
                // Calls the function
                $content = $this->$functionName($content);
            } else {
                FlashMessages::addError('error.unknownFunction', [
                    $functionName
                ]);
            }
        }
        return $content ?? '';
    }

    /**
     * Builds the right value content.
     *
     * @return string
     */
    protected function getRightValue(): string
    {
        // Gets the value
        $value = $this->getItemConfigurationAttribute('value');

        // Gets the right part
        if (empty($value)) {
            $content = $this->getItemConfigurationAttribute('addrightifnull');
        } else {
            $content = $this->getItemConfigurationAttribute('addrightifnotnull');
        }
        $content = $content ?? '';

        // Evaluates the function if necessary
        $functionName = $this->getItemConfigurationAttribute('funcright');
        if (! empty($functionName)) {
            $this->setItemConfigurationAttribute('funcspecial', 'right');
            if (in_array($functionName, self::$allowedFunctionNames)) {
                $content = $this->$functionName($content);
            }
        }

        if (! empty($content)) {
            $content = $this->controller
                ->getQuerier()
                ->parseLocalizationTags($content);
            $content = $this->controller
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
    protected function getLeftValue(): string
    {
        // Gets the value
        $value = $this->getItemConfigurationAttribute('value');

        // Gets the left part
        if (empty($value)) {
            $content = $this->getItemConfigurationAttribute('addleftifnull');
        } else {
            $content = $this->getItemConfigurationAttribute('addleftifnotnull');
        }
        $content = $content ?? '';
        
        // Evaluates the function if necessary
        $functionName = $this->getItemConfigurationAttribute('funcleft');
        if (empty($functionName) === false) {
            $this->setItemConfigurationAttribute('funcspecial', 'left');
            if (in_array($functionName, self::$allowedFunctionNames)) {
                $content = $this->$functionName($content);
            }
        }

        if (empty($content) === false) {
            $content = $this->controller
                ->getQuerier()
                ->parseLocalizationTags($content);
            $content = $this->controller
                ->getQuerier()
                ->parseFieldTags($content);
        }

        return $content;
    }

    /**
     * Transforms an array of HTML code into HTML code
     *
     * @param array $htmlArray
     * @param bool $noHTMLprefix
     *
     * @return string
     */
    protected function arrayToHTML(array $htmlArray, bool $noHTMLprefix = false): string
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
    protected function makeItemLink(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the formAction
        if ($this->getItemConfigurationAttribute('updateform' . $special) || $this->getItemConfigurationAttribute('formadmin' . $special)) {
            $formAction = 'formAdmin';
        } elseif ($this->getItemConfigurationAttribute('inputform' . $special) || $this->getItemConfigurationAttribute('edit' . $special)) {
            $formAction = 'edit';
        } else {
            $formAction = 'single';
        }

        // Builds the uid
        if ($this->getItemConfigurationAttribute('setuid' . $special)) {
            $uid = $this->controller
                ->getQuerier()
                ->parseFieldTags($this->getItemConfigurationAttribute('setuid' . $special));
        } elseif ($this->getItemConfigurationAttribute('valueisuid' . $special) || $this->getItemConfigurationAttribute('setuid' . $special) == 'this') {
            $uid = $this->getItemConfigurationAttribute('value');
        } else {
            $uid = $this->controller
                ->getQuerier()
                ->getFieldValueFromCurrentRow('uid');
        }

        // Builds the parameters
        $formParameters = [
            'formAction' => $formAction,
            'uid' => $uid
        ];

        // Adds parameter to access to a folder tab (page is an alias)
        if ($this->getItemConfigurationAttribute('page' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfigurationAttribute('page' . $special));
        }
        if ($this->getItemConfigurationAttribute('foldertab' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfigurationAttribute('foldertab' . $special));
        }

        // Adds parameter the subformUidForeign if any
        if ($this->getItemConfigurationAttribute('subformuidforeigninlink' . $special)) {
            $formParameters['subformUidForeignInLink'] = $this->controller
                ->getQuerier()
                ->parseFieldTags($this->getItemConfigurationAttribute('subformuidforeigninlink' . $special));
        }

        // Sets the cache hash flag
        $cacheHash = ($this->controller->getExtensionConfigurationManager()->isCacheHashRequired() ? 1 : 0);

        return $this->controller->buildLinkToPage($value, $formParameters, $cacheHash);
    }

    /**
     * Creates an extension link
     *
     * @param string $value
     *            Value to display
     *
     * @return string The link
     */
    public function makeExtLink(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the formAction
        if ($this->getItemConfigurationAttribute('inputform' . $special) || $this->getItemConfigurationAttribute('edit' . $special)) {
            $formAction = 'edit';
        } else {
            $formAction = 'single';
        }

        // Gets the content id
        $contentId = $this->getItemConfigurationAttribute('contentid' . $special);

        // Gets the message and processes it
        $message = ($this->getItemConfigurationAttribute('message' . $special) ? $this->getItemConfigurationAttribute('message' . $special) : $value);
        $message = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->controller
            ->getQuerier()
            ->parseFieldTags($message);

        // Builds the form name
        $formName = $this->getItemConfigurationAttribute('ext' . $special) . ($contentId ? '_' . $contentId : '');

        // Builds the uid
        if ($this->getItemConfigurationAttribute('setuid' . $special)) {
            $uid = $this->controller
                ->getQuerier()
                ->parseFieldTags($this->getItemConfigurationAttribute('setuid' . $special));
        } elseif ($this->getItemConfigurationAttribute('valueisuid' . $special) || $this->getItemConfigurationAttribute('setuid' . $special) == 'this') {
            $uid = $this->getItemConfigurationAttribute('value');
        } else {
            $uid = $this->getItemConfigurationAttribute('uid');
        }

        // Builds the parameters
        $formParameters = [
            'formName' => $formName,
            'formAction' => $formAction,
            'uid' => intval($uid),
            'pageId' => $this->getItemConfigurationAttribute('pageid' . $special)
        ];

        // Adds parameter to access to a folder tab (page is an alias)
        if ($this->getItemConfigurationAttribute('page' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfigurationAttribute('page' . $special));
        }
        if ($this->getItemConfigurationAttribute('foldertab' . $special)) {
            $formParameters['folderKey'] = AbstractController::cryptTag($this->getItemConfigurationAttribute('foldertab' . $special));
        }

        // Adds parameter the subformUidForeign if any
        if ($this->getItemConfigurationAttribute('subformuidforeigninlink' . $special)) {
            $formParameters['subformUidForeignInLink'] = $this->controller
                ->getQuerier()
                ->parseFieldTags($this->getItemConfigurationAttribute('subformuidforeigninlink' . $special));
        }

        // Adds the linkAccessRestrictedPages parameter
        if ($this->getItemConfigurationAttribute('linkaccessrestrictedpages')) {
            $formParameters['linkAccessRestrictedPages'] = $this->getItemConfigurationAttribute('linkaccessrestrictedpages');
        }

        // Adds the forceAbsoluteUrl parameter
        if ($this->getItemConfigurationAttribute('forceabsoluteurl')) {
            $formParameters['forceAbsoluteUrl'] = $this->getItemConfigurationAttribute('forceabsoluteurl');
            if ($this->getItemConfigurationAttribute('forceabsoluteurl.scheme')) {
                $formParameters['forceAbsoluteUrl.'] = [
                    'scheme' => $this->getItemConfigurationAttribute('forceabsoluteurl.scheme')
                ];
            }
        }

        // Checks if the link should be displayed
        if ($this->getItemConfigurationAttribute('restrictlinkto' . $special)) {
            $match = [];
            if (preg_match('/###usergroup\s*(!?)=\s*(.*?)###/', $this->getItemConfigurationAttribute('restrictlinkto' . $special), $match)) {
                $rows = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTgetRows(
                    /* SELECT   */	'uid,title',
                    /* FROM     */	'fe_groups',
                    /* WHERE    */	'title=\'' . $match[2] . '\'' . $this->controller->getQuerier()->getEnableFields('fe_groups'));
                $cond = (bool) $match[1] ^ in_array($rows[0]['uid'], explode(',', $this->controller->getUserManager()->getFrontendUser()->user['usergroup']));
                return ($cond ? $this->controller->buildLinkToPage($message, $formParameters) : $value);
            } else {
                return $this->controller->buildLinkToPage($message, $formParameters);
            }
        } else {
            return $this->controller->buildLinkToPage($message, $formParameters);
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
    protected function makeLink(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the folder
        $folder = ($this->getItemConfigurationAttribute('folder' . $special) ? $this->getItemConfigurationAttribute('folder' . $special) : '.');

        // Gets the message and processes it
        $message = ($this->getItemConfigurationAttribute('message' . $special) ? $this->getItemConfigurationAttribute('message' . $special) : $value);
        $message = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->controller
            ->getQuerier()
            ->parseFieldTags($message);

        // Builds the parameter attribute
        if (empty($message) === false) {
            if ($this->getItemConfigurationAttribute('setuid' . $special)) {
                $parameter = $this->controller
                    ->getQuerier()
                    ->parseFieldTags($this->getItemConfigurationAttribute('setuid' . $special));
            } elseif ($this->getItemConfigurationAttribute('valueisuid' . $special)) {
                $parameter = $this->getItemConfigurationAttribute('value');
            } else {
                $parameter = $folder . '/' . rawurlencode($value);
            }
        } else {
            $parameter = '';
        }

        // Builds the typoScript configuration
        $typoScriptConfiguration = [
            'parameter' => $parameter,
            'target' => $this->getItemConfigurationAttribute('target' . $special),
            'ATagParams' => ($this->getItemConfigurationAttribute('class' . $special) ? 'class="' . $this->getItemConfigurationAttribute('class' . $special) . '" ' : '')
        ];

        // Gets the content object renderer
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();

        return $contentObjectRenderer->typolink($message, $typoScriptConfiguration);
    }

    /**
     * Creates a link and open in a new window
     *
     * @param string $value
     *
     * @return string (link)
     */
    protected function makeNewWindowLink(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the message and processes it
        $message = ($this->getItemConfigurationAttribute('message' . $special) ? $this->getItemConfigurationAttribute('message' . $special) : $value);
        $message = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->controller
            ->getQuerier()
            ->parseFieldTags($message);

        // Gets the window url
        $windowUrl = $this->getItemConfigurationAttribute('windowurl' . $special);
        $windowUrl = $this->controller
            ->getQuerier()
            ->parseFieldTags($windowUrl);

        // Returns the message if the window url is not a file
        if (is_file($windowUrl ?? '') === false) {
            return $message;
        }

        // Gets the window text
        $windowText = $this->getItemConfigurationAttribute('windowtext' . $special);
        $windowText = $this->controller
            ->getQuerier()
            ->parseFieldTags($windowText);

        // Gets the window style
        $windowBodyStyle = ($this->getItemConfigurationAttribute('windowbodystyle' . $special) ? ' style="' . $this->getItemConfigurationAttribute('windowbodystyle' . $special) . '"' : '');

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

        // Gets the content object renderer
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();

        return $contentObjectRenderer->imageLinkWrap($message, $windowUrl, $typoScriptConfiguration);
    }

    /**
     * Creates an email link
     *
     * @param string $value
     *
     * @return string (link)
     */
    protected function makeEmailLink(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the message and processes it
        $message = ($this->getItemConfigurationAttribute('message' . $special) ? $this->getItemConfigurationAttribute('message' . $special) : $value);
        $message = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->controller
            ->getQuerier()
            ->parseFieldTags($message);

        $typoScriptConfiguration = [
            'parameter' => ($this->getItemConfigurationAttribute('link') ? $this->getItemConfigurationAttribute('link') : $value)
        ];

        // Gets the content object renderer
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();

        return $contentObjectRenderer->typolink($message, $typoScriptConfiguration);
    }

    /**
     * Creates a link for an external url
     *
     * @param string $value
     *            (value to display)
     *
     * @return string (link)
     */
    protected function makeUrlLink(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the message and processes it
        $message = ($this->getItemConfigurationAttribute('message' . $special) ? $this->getItemConfigurationAttribute('message' . $special) : $value);
        $message = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($message);
        $message = $this->controller
            ->getQuerier()
            ->parseFieldTags($message);

        $typoScriptConfiguration = [
            'parameter' => ($this->getItemConfigurationAttribute('link') ? $this->getItemConfigurationAttribute('link') : $value),
            'extTarget' => ($this->getItemConfigurationAttribute('exttarget') ? $this->getItemConfigurationAttribute('exttarget') : '_blank')
        ];

        // Gets the content object renderer
        $contentObjectRenderer = $this->controller->getContentObjectRenderer();
        
        return $contentObjectRenderer->typolink($message, $typoScriptConfiguration);
    }

    /**
     * Generates the xml label
     *
     * @param string $value
     *            (value to display)
     *
     * @return string (xml label)
     */
    protected function makeXmlLabel(string $value): string
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');
        if ($this->getItemConfigurationAttribute('rawvalue' . $special)) {
            $value = $this->getItemConfigurationAttribute('value');
        }
        return $this->controller->getLanguageService()->sL($this->getItemConfigurationAttribute('xmllabel' . $special) . $value);
    }

    /**
     * Formats a timestamp date according to the configuration
     *
     * @param integer $timeStamp
     *
     * @return string
     */
    protected function makeDateFormat(string $timeStamp)
    {
        // Gets the funcspecial attribute
        $special = $this->getItemConfigurationAttribute('funcspecial');

        // Gets the format
        $dateFormat = $this->getItemConfigurationAttribute('dateformat' . $special);

        if (empty($dateFormat) === true) {
            $dateFormat = ($this->getItemConfigurationAttribute('eval' . $special) == 'datetime' ? $this->controller->getDefaultDateTimeFormat() : $this->controller->getDefaultDateFormat());
        }
        /** @var DateFormatter $dateFormatter */
        $dateFormatter = GeneralUtility::makeInstance(DateFormatter::class);

        return $dateFormatter->strftime($dateFormat, (int) $timeStamp);
    }
    
    /**
     * Gets the file identifier
     *
     * @param string $filename
     *
     * @return string 
     */
    protected function getResourceWebPath(string $filename): string
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $fileIdentifier = $resourceFactory->retrieveFileOrFolderObject($filename)->getProperty('identifier');
        
        return $fileIdentifier;
    }
}
