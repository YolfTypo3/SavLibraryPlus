<?php
namespace SAV\SavLibraryPlus\ViewHelpers;

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

use SAV\SavLibraryPlus\Managers\UriManager;
use SAV\SavLibraryPlus\Controller\AbstractController;

/**
 * Compresses parameters
 *
 * @package SavLibraryMvc
 * @version $Id:
 */
class BuildCompressedParametersViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param array $arguments
     *            Arguments
     */
    public function render($arguments)
    {
        // Sets the new action
        $compressedParameters = UriManager::getCompressedParameters();
        $formName = AbstractController::getFormName();
        $compressedParameters = AbstractController::changeCompressedParameters($compressedParameters, 'formName', $formName);

        // Changes the other parameters if any
        foreach ($arguments as $argumentKey => $argument) {
            $compressedParameters = AbstractController::changeCompressedParameters($compressedParameters, $argumentKey, $argument);
        }

        return $compressedParameters;
    }
}
?>
