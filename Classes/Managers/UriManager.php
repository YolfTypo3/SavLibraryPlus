<?php
namespace YolfTypo3\SavLibraryPlus\Managers;

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
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Uri manager
 *
 * @package SavLibraryPlus
 */
class UriManager extends AbstractManager
{

    /**
     * The POST variables
     *
     * @var array
     */
    protected $postVariables = [];

    /**
     * The compressed parameters
     *
     * @var string
     */
    protected static $compressedParameters;

    /**
     * The uncompressed GET variables
     *
     * @var array
     */
    protected static $uncompressedGetVariables;

    /**
     * Sets the GET variables
     *
     * @return void
     */
    public static function setGetVariables()
    {
        self::setCompressedParameters(GeneralUtility::_GET(AbstractController::LIBRARY_NAME));
    }

    /**
     * Sets the POST variables
     *
     * @return void
     */
    public function setPostVariables()
    {
        $piVars = $this->getController()->getExtensionConfigurationManager()->getPiVars();
        $formName = AbstractController::getFormName();
        if (isset($piVars[$formName])) {
            $this->postVariables = $piVars[$formName];
        }
    }

    /**
     * Gets the POST variables
     *
     * @return array
     */
    public function getPostVariables()
    {
        return $this->postVariables;
    }

    /**
     * Gets the form action
     *
     * @return integer
     */
    public static function getFormAction()
    {
        if (isset(self::$uncompressedGetVariables['formAction'])) {
            return self::$uncompressedGetVariables['formAction'];
        } else {
            return null;
        }
    }

    /**
     * Gets the folder key
     *
     * @return integer
     */
    public static function getFolderKey()
    {
        if (isset(self::$uncompressedGetVariables['folderKey'])) {
            return self::$uncompressedGetVariables['folderKey'];
        } else {
            return null;
        }
    }

    /**
     * Gets the uid
     *
     * @return integer
     */
    public static function getUid()
    {
        if (isset(self::$uncompressedGetVariables['uid'])) {
            return intval(self::$uncompressedGetVariables['uid']);
        } else {
            return 0;
        }
    }

    /**
     * Gets the subform Uid Foreign
     *
     * @return integer
     */
    public static function getSubformUidForeign()
    {
        if (isset(self::$uncompressedGetVariables['subformUidForeign'])) {
            return intval(self::$uncompressedGetVariables['subformUidForeign']);
        } else {
            return 0;
        }
    }

    /**
     * Gets the subform Uid Foreign in link
     *
     * @return integer
     */
    public static function getSubformUidForeignInLink()
    {
        if (isset(self::$uncompressedGetVariables['subformUidForeignInLink'])) {
            return intval(self::$uncompressedGetVariables['subformUidForeignInLink']);
        } else {
            return 0;
        }
    }

    /**
     * Gets the subform Uid Local
     *
     * @return integer
     */
    public static function getSubformUidLocal()
    {
        if (isset(self::$uncompressedGetVariables['subformUidLocal'])) {
            return intval(self::$uncompressedGetVariables['subformUidLocal']);
        } else {
            return 0;
        }
    }

    /**
     * Gets the subform Uid Local
     *
     * @return integer
     */
    public static function getSubformFieldKey()
    {
        if (isset(self::$uncompressedGetVariables['subformFieldKey'])) {
            return self::$uncompressedGetVariables['subformFieldKey'];
        } else {
            return 0;
        }
    }

    /**
     * Gets the page
     *
     * @return integer
     */
    public static function getPage()
    {
        if (isset(self::$uncompressedGetVariables['page'])) {
            return self::$uncompressedGetVariables['page'];
        } else {
            return 0;
        }
    }

    /**
     * Gets the page in subform
     *
     * @return integer
     */
    public static function getPageInSubform()
    {
        if (isset(self::$uncompressedGetVariables['pageInSubform'])) {
            return self::$uncompressedGetVariables['pageInSubform'];
        } else {
            return 0;
        }
    }

    /**
     * Gets the view identifier
     *
     * @return integer
     */
    public static function getViewId()
    {
        if (isset(self::$uncompressedGetVariables['viewId'])) {
            return self::$uncompressedGetVariables['viewId'];
        } else {
            return 0;
        }
    }

    /**
     * Gets the whereTag key
     *
     * @return string
     */
    public static function getWhereTagKey()
    {
        if (isset(self::$uncompressedGetVariables['whereTagKey'])) {
            return self::$uncompressedGetVariables['whereTagKey'];
        } else {
            return '';
        }
    }

    /**
     * Gets an item from the POST variables
     *
     * @param string $itemKey
     *
     * @return string
     */
    public function getPostVariablesItem($itemKey)
    {
        return $this->postVariables[$itemKey];
    }

    /**
     * Gets the form action from the POST variables
     *
     * @return string
     */
    public function getFormActionFromPostVariables()
    {
        $piVars = $this->getController()->getExtensionConfigurationManager()->getPiVars();
        $formName = AbstractController::getFormName();
        if (isset($piVars[$formName])) {
            return $piVars[$formName]['formAction'];
        } else {
            return '';
        }
    }

    /**
     * Gets the compressed parameters
     *
     * @return string
     */
    public static function getCompressedParameters()
    {
        return self::$compressedParameters;
    }

    /**
     * Sets the compressed parameters
     *
     * @param string $compressedParameters
     *
     * @return void
     */
    public static function setCompressedParameters($compressedParameters)
    {
        self::$compressedParameters = $compressedParameters;
        self::$uncompressedGetVariables = AbstractController::uncompressParameters(self::$compressedParameters);
    }

    /**
     * Returns true if parameters are those of the form.
     * The uncompressed GET variables is null vhen the parameters are not those of the active form
     *
     * @return boolean
     */
    public static function isActiveForm()
    {
        return is_null(self::$uncompressedGetVariables) ? false : true;
    }

    /**
     * Returns true is the URI contains the library parameter
     *
     * @return boolean
     */
    public static function hasLibraryParameter()
    {
        return (GeneralUtility::_GP(AbstractController::LIBRARY_NAME) ? true : false);
    }

    /**
     * Returns true is the URI contains a cHash parameter
     *
     * @return boolean
     */
    public static function hasCacheHashParameter()
    {
        return (GeneralUtility::_GP('cHash') ? true : false);
    }

    /**
     * Returns true is the URI contains the no_cache parameter
     *
     * @return boolean
     */
    public static function hasNoCacheParameter()
    {
        return (GeneralUtility::_GP('no_cache') ? true : false);
    }

    /**
     * Returns true is the URI is verified
     *
     * @return boolean
     */
    public static function uriIsVerified()
    {
        if (self::hasLibraryParameter()) {
            if (self::hasCacheHashParameter()) {
                // Gets the GET parameters
                $getParameters = GeneralUtility::_GET();
                $cacheHashParameter = $getParameters['cHash'];
                unset($getParameters['cHash']);

                // Adds the page id
                $frontendController = $GLOBALS['TSFE'];
                $getParameters['id'] = $frontendController->id;

                // Computes the cHash from the GET parameters
                $cacheCacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
                $queryString = HttpUtility::buildQueryString($getParameters, '&');
                $calculatedCacheHashParameter = $cacheCacheHashCalculator->generateForParameters($queryString);

                // Returns true if the chash parameter is equal to the calculated one
                return $calculatedCacheHashParameter === $cacheHashParameter;
            }
            return false;
        }
        return true;
    }
}

?>