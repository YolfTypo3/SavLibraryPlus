<?php
namespace YolfTypo3\SavLibraryPlus\Managers;

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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
    private $controller;

    /**
     * Injects the controller
     *
     * @param AbstractController $controller
     *
     * @return void
     */
    public function injectController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Gets the controller
     *
     * @return AbstractController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Gets the querier from the controller
     *
     * @return AbstractQuerier
     */
    public function getQuerier()
    {
        return $this->controller->getQuerier();
    }

    /**
     * Gets the viewer from the controller
     *
     * @return AbstractViewer
     */
    public function getViewer()
    {
        return $this->controller->getViewer();
    }

    /**
     * Gets the TypoScript Frontend Controller
     *
     * @return TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

?>