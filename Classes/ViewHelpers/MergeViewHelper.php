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

namespace YolfTypo3\SavLibraryPlus\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Merges two arrays
 *
 * @package SavLibraryPlus
 */
class MergeViewHelper extends AbstractViewHelper
{

    /**
     * Initializes arguments.
     * 
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('array1', 'array', 'Argument 1', false, []);
        $this->registerArgument('array2', 'array', 'Argument 2', false, []);
    }

    /**
     * Renders the view helper
     *
     * @return array Merged array
     */
    public function render(): array
    {
        // Gets the arguments
        $array1 = $this->arguments['array1'];
        $array2 = $this->arguments['array2'];

        return array_merge($array1, $array2);
    }
}
