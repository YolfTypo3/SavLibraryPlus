<?php
namespace YolfTypo3\SavLibraryPlus\Managers;

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
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Uri manager
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class UriManager extends AbstractManager
{

    /**
     * The POST variables
     *
     * @var array
     */
    protected $postVariables;

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
     * @return none
     */
    public static function setGetVariables()
    {
        self::setCompressedParameters(GeneralUtility::_GET(AbstractController::LIBRARY_NAME));
    }

    /**
     * Sets the POST variables
     *
     * @return none
     */
    public function setPostVariables()
    {
        $formName = AbstractController::getFormName();
        $this->postVariables = GeneralUtility::_POST($formName);
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
            return NULL;
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
            return NULL;
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
        return $this->getPostVariablesItem('formAction');
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
     * @return none
     */
    public static function setCompressedParameters($compressedParameters)
    {
        self::$compressedParameters = $compressedParameters;
        self::$uncompressedGetVariables = AbstractController::uncompressParameters(self::$compressedParameters);
    }

    /**
     * Returns TRUE if parameters are those of the form.
     * The uncompressed GET variables is NULL vhen the parameters are not those of the active form
     *
     * @return boolean
     */
    public static function isActiveForm()
    {
        return is_null(self::$uncompressedGetVariables) ? FALSE : TRUE;
    }

    /**
     * Returns TRUE is the URI contains the library parameter
     *
     * @return boolean
     */
    public static function hasLibraryParameter()
    {
        return (GeneralUtility::_GP(AbstractController::LIBRARY_NAME) ? TRUE : FALSE);
    }

    /**
     * Returns TRUE is the URI contains the no_cache parameter
     *
     * @return boolean
     */
    public static function hasNoCacheParameter()
    {
        return (GeneralUtility::_GP('no_cache') ? TRUE : FALSE);
    }
}

?>