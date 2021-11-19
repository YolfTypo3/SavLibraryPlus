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

namespace YolfTypo3\SavLibraryPlus\Hooks;

use YolfTypo3\SavLibraryPlus\Controller\Controller;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Abstract hook
 */
abstract class AbstractHook
{

    /**
     * Controller
     *
     * @var Controller
     */
    protected $controller;

    /**
     * Parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * Current row
     *
     * @var array
     */
    protected $row;

    /**
     * Injects the controller
     *
     * @param Controller $controller
     *
     * @return void
     */
    public function injectController(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Renders the hook
     *
     * @param array $parameters
     *
     * @return string
     */
    public function renderHook($parameters)
    {
        // Sets the global variables
        $this->parameters = $parameters;
        $this->row = $this->controller->getQuerier()->getRows()[$this->controller->getQuerier()->getCurrentRowId()];

        return '';
    }

    /**
     * Gets the form action name used by SAV Library plus
     *
     * @return string
     */
    protected function getFormActionName()
    {
        $actionName = '';

        // Gets the form action
        if (UriManager::hasLibraryParameter()) {
            // Sets the GET variables
            UriManager::setGetVariables();

            // Retrieves the action from the URI if it is the active form
            if (UriManager::isActiveForm() === true) {
                $actionName = UriManager::getFormAction();
            }
        }

        return $actionName;
    }
}
