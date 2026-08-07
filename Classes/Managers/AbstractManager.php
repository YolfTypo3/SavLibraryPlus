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

namespace YolfTypo3\SavLibraryPlus\Managers;

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier;
use YolfTypo3\SavLibraryPlus\Viewers\AbstractViewer;

/**
 * Abstract manager.
 *
 * @package SavLibraryPlus
 */
abstract class AbstractManager
{

    /**
     * The controller
     *
     * @var AbstractController
     */
    protected AbstractController $controller;
        
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(AbstractController $controller) {
        $this->controller = $controller;
    }
    

    /**
     * Gets the querier from the controller
     *
     * @return AbstractQuerier
     */
    protected function getQuerier(): AbstractQuerier
    {
        return $this->controller->getQuerier();
    }

    /**
     * Gets the viewer from the controller
     *
     * @return AbstractViewer
     */
    protected function getViewer(): AbstractViewer
    {
        return $this->controller->getViewer();
    }
    
}
