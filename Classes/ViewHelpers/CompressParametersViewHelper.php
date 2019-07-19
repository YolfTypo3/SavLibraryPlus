<?php
namespace YolfTypo3\SavLibraryPlus\ViewHelpers;

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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Compresses parameters
 *
 * @package SavLibraryPlus
 */
class CompressParametersViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;
    
    /**
     * Initializes arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('arguments', 'array', 'Arguments', false, null);
    }

    /**
     * Renders the viewhelper.
     * 
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string The compressed parameters
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        // Gets the arguments
        $arguments = $arguments['arguments'];

        $compressedParameters = AbstractController::compressParameters($arguments);

        return $compressedParameters;
    }
}
?>
