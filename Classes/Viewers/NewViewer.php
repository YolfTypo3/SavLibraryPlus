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
 * Default New Viewer.
 *
 * @package SavLibraryPlus
 */
class NewViewer extends EditViewer
{
    /**
     * The new view flag
     *
     * @var boolean
     */
    protected $isNewView = true;

    /**
     * Checks if the view can be rendered
     *
     * @return boolean
     */
    public function viewCanBeRendered()
    {
        $userManager = $this->getController()->getUserManager();
        $result = $userManager->userIsAllowedToInputData();

        return $result;
    }

}
