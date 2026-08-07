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

namespace YolfTypo3\SavLibraryPlus\Hooks;

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Abstract hook
 */
abstract class AbstractHook
{

    /**
     * Controller
     *
     * @var AbstractController
     */
    protected AbstractController $controller;

    /**
     * Parameters
     *
     * @var array
     */
    protected array $parameters;

    /**
     * Current row
     *
     * @var array
     */
    protected array $row;

    /**
     * Constructor
     *
     * @param AbstractController $controller
     *
     * @return void
     */
    public function __construct(AbstractController $controller)
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
    public function renderHook(array $parameters): string
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
    protected function getFormActionName(): string
    {
        $actionName = '';

        // Gets the form action
        $uriManager = $this->controller->getUriManager();
        if ($uriManager->hasLibraryParameter()) {
            // Sets the GET variables
            $uriManager->setGetVariables();

            // Retrieves the action from the URI if it is the active form
            if ($uriManager->isActiveForm() === true) {
                $actionName = $uriManager->getFormAction();
            }
        }

        return $actionName;
    }
}
