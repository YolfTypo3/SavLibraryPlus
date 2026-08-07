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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper which builds the src attribute
 *
 * @package SavLibraryPlus
 */
class GetIconSrcViewHelper extends AbstractViewHelper
{

    /**
     * Initializes arguments.
     * 
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('fileName', 'string', 'File name', true);
    }

    /**
     * Renders the content.
     *
     * @return string|null Rendered string
     */
    public function render(): ?string
    {
        // Gets the arguments
        $fileName = $this->arguments['fileName'];

        // Checks if the file Name exists in the SAV Library Plus
        $controller = $this->getRequest()->getAttribute('controller');
        $filePath = $controller->getLibraryConfigurationManager()->getIconPath($fileName);

        if (file_exists(GeneralUtility::getFileAbsFileName($filePath))) {
            return $filePath;
        } else {
            return null;
        }
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
