<?php
namespace SAV\SavLibraryPlus\ViewHelpers\Typoscript;

/*
 * This script is part of the TYPO3 project - inspiring people to share! *
 * *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by *
 * the Free Software Foundation. *
 * *
 * This script is distributed in the hope that it will be useful, but *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN- *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General *
 * Public License for more details. *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A view helper for a wrapper.
 *
 *
 * @package SavLibraryMvc
 * @subpackage ViewHelpers
 */
class WrapViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param mixed $data
     *            the data to be used for rendering the cObject. Can be an object, array or string. If this argument is not set, child nodes will be used
     * @param string $configuration
     * @return string Rendered The link
     * @author Laurent Foulloy <yolf.typo3@orange.fr>
     */
    public function render($data = NULL, $configuration = NULL)
    {
        if ($data === NULL) {
            $data = $this->renderChildren();
        }

        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        return $contentObject->dataWrap($data, $configuration);
    }
}

?>
