<?php

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

namespace YolfTypo3\SavLibraryPlus\Viewers;

/**
 * Default Error Viewer.
 *
 * @package SavLibraryPlus
 */
class ErrorViewer extends AbstractViewer
{
    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'Error.html';

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render()
    {
        return $this->renderView();
    }
}
