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

/**
 * Abstract manager.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
abstract class AbstractManager
{

    /**
     * The controller
     *
     * @var \YolfTypo3\SavLibraryPlus\Controller\AbstractController
     */
    private $controller;

    /**
     * Injects the controller
     *
     * @param \YolfTypo3\SavLibraryPlus\Controller\AbstractController $controller
     *
     * @return none
     */
    public function injectController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Gets the controller
     *
     * @return \YolfTypo3\SavLibraryPlus\Controller\AbstractController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Gets the querier from the controller
     *
     * @return \YolfTypo3\SavLibraryPlus\Queriers\AbstractQuerier
     */
    public function getQuerier()
    {
        return $this->controller->getQuerier();
    }

    /**
     * Gets the viewer from the controller
     *
     * @return \YolfTypo3\SavLibraryPlus\Queriers\AbstractViewier
     */
    public function getViewer()
    {
        return $this->controller->getViewer();
    }
}

?>