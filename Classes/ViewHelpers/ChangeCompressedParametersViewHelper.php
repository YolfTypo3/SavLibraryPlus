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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Changes a compressed parameter a string
 *
 * @package SavLibraryPlus
 */
final class ChangeCompressedParametersViewHelper extends AbstractViewHelper
{
    
    /**
     * Initializes arguments.
     * 
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('arguments', 'array', 'Arguments', false, null);
        $this->registerArgument('additionalParams', 'array', 'Additional parameters', false, []);        
    }

    /**
     * Renders the view helper.
     *
     * @return array The compressed parameters
     */
    public function render(): array
    {
        // Gets the arguments
        $additionalParams = $this->arguments['additionalParams'];
        $arguments = $this->arguments['arguments'];

        if ($arguments === null) {
            $arguments = $this->renderChildren() ?? [];
        }

        // Gets and changes the special parameter
        $controller = $this->getRequest()->getAttribute('controller');
        $special = $controller->getUriManager()->getCompressedParameters();
        $special = [
            AbstractController::LIBRARY_NAME => $this->changeCompressedParameters($special, $arguments)
        ];
        
        $result = array_merge($special, $additionalParams);

        return $result;
    }
 
    /**
     * Changes a parameter in the compressed parameters string
     *
     * @param string|null $compressedParameters
     *            The compressed parameters string
     * @param array $newParameters
     *            Array of (key => value) to change
     *
     * @return string The modified compressed parameter string
     */
    public function changeCompressedParameters(?string $compressedParameters, array $newParameters): string
    {
        $controller = $this->getRequest()->getAttribute('controller');
        $uncompressParameters = $controller->uncompressParameters($compressedParameters);
        
        // Modifies the parameters
        foreach ($newParameters as $key => $value) {
            $uncompressParameters[$key] = $value;
        }
        
        return $controller->compressParameters($uncompressParameters);
    }
    
    /**
     * Gets the request.
     *
     * @return ServerRequestInterface|null
     */
    private function getRequest(): ServerRequestInterface|null
    {
        if ((new (Typo3Version::class))->getMajorVersion() <= 12) {
            // Todo: remove on dropping TYPO3 v12 support
            return $this->renderingContext->getRequest();
        }
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        return null;
    }
    
}
