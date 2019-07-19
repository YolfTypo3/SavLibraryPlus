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

/**
 * Merges two arrays
 *
 * @package SavLibraryPlus
 */
class MergeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('array1', 'array', 'Argument 1', false, []);
        $this->registerArgument('array2', 'array', 'Argument 2', false, []);
    }

    /**
     * Renders the viewhelper
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return array Merged array
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        // Gets the arguments
        $array1 = $arguments['array1'];
        $array2 = $arguments['array2'];

        return array_merge($array1, $array2);
    }
}
?>
