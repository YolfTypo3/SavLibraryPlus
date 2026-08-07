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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Uri manager
 *
 * @package SavLibraryPlus
 */
final class UriManager extends AbstractManager
{
    
    /**
     * The POST variables
     *
     * @var array
     */
    protected array $postVariables = [];

    /**
     * The compressed parameters
     *
     * @var string
     */
    protected string $compressedParameters;

    /**
     * The uncompressed GET variables
     *
     * @var array
     */
    protected array $uncompressedGetVariables;

    
    /**
     * Sets the GET variables
     *
     * @return void
     */
    public function setGetVariables(): void
    {
        $compressedParameters = $this->controller->getRequest()->getQueryParams()[AbstractController::LIBRARY_NAME] ?? null;
        if (is_null($compressedParameters)) {
            $compressedParameters = $this->controller->getExtensionConfigurationManager()->getPiVars()[AbstractController::LIBRARY_NAME];
        }   
        $this->setCompressedParameters($compressedParameters);
    }

    /**
     * Sets the POST variables
     *
     * @return void
     */
    public function setPostVariables(): void
    {
        $piVars = $this->controller->getExtensionConfigurationManager()->getPiVars();
        $formName = $this->controller->getFormName();
        if (isset($piVars[$formName])) {
            $this->postVariables = $piVars[$formName];
        }
    }

    /**
     * Gets the POST variables
     *
     * @return array
     */
    public function getPostVariables(): array
    {
        return $this->postVariables;
    }

    /**
     * Gets the form action
     *
     * @return string|null
     */
    public function getFormAction(): ?string
    {
        return $this->uncompressedGetVariables['formAction'] ?? null;
    }

    /**
     * Gets the folder key
     *
     * @return string|null
     */
    public function getFolderKey(): ?string
    {
        return $this->uncompressedGetVariables['folderKey'] ?? null;
    }

    /**
     * Gets the uid
     *
     * @return int
     */
    public function getUid(): int
    {
        return intval($this->uncompressedGetVariables['uid'] ?? 0);
    }

    /**
     * Gets the subform Uid Foreign
     *
     * @return int
     */
    public function getSubformUidForeign(): int
    {
        return intval($this->uncompressedGetVariables['subformUidForeign'] ?? 0);
    }

    /**
     * Gets the subform Uid Foreign in link
     *
     * @return int
     */
    public function getSubformUidForeignInLink(): int
    {
        return intval($this->uncompressedGetVariables['subformUidForeignInLink'] ?? 0);
    }

    /**
     * Gets the subform Uid Local
     *
     * @return int
     */
    public function getSubformUidLocal(): int
    {
        return intval($this->uncompressedGetVariables['subformUidLocal'] ?? 0);
    }

    /**
     * Gets the subform Uid Local
     *
     * @return string
     */
    public function getSubformFieldKey(): string
    {
        return $this->uncompressedGetVariables['subformFieldKey'] ?? '';
    }

    /**
     * Gets the page
     *
     * @return int
     */
    public function getPage(): int
    {
        return intval($this->uncompressedGetVariables['page'] ?? 0);
    }

    /**
     * Gets the page in subform
     *
     * @return int
     */
    public function getPageInSubform(): int
    {
        return intval($this->uncompressedGetVariables['pageInSubform'] ?? 0);
    }

    /**
     * Gets the view identifier
     *
     * @return int
     */
    public function getViewId(): int
    {
        return intval($this->uncompressedGetVariables['viewId'] ?? 0);
    }

    /**
     * Gets the whereTag key
     *
     * @return string
     */
    public function getWhereTagKey(): string
    {
        return $this->uncompressedGetVariables['whereTagKey'] ?? '';
    }

    /**
     * Gets an item from the POST variables
     *
     * @param string $itemKey
     *
     * @return mixed
     */
    public function getPostVariablesItem(string $itemKey): mixed
    {
        return $this->postVariables[$itemKey] ?? null;
    }

    /**
     * Gets the form action from the POST variables
     *
     * @return array
     */
    public function getFormActionFromPostVariables(): array
    {
        $piVars = $this->controller->getExtensionConfigurationManager()->getPiVars();
        $formName = $this->controller->getFormName();
        if (isset($piVars[$formName])) {
            return $piVars[$formName]['formAction'] ?? [];
        } else {
            return [];
        }
    }

    /**
     * Gets the compressed parameters
     *
     * @return string
     */
    public function getCompressedParameters(): string
    {
        return $this->compressedParameters ?? '';
    }

    /**
     * Sets the compressed parameters
     *
     * @param string $compressedParameters
     *
     * @return void
     */
    public function setCompressedParameters(string $compressedParameters): void
    {
        $this->compressedParameters = $compressedParameters;
        $this->uncompressedGetVariables = $this->controller->uncompressParameters($this->compressedParameters);
    }

    /**
     * Returns true if parameters are those of the form.
     * The uncompressed GET variables is null vhen the parameters are not those of the active form
     *
     * @return bool
     */
    public function isActiveForm(): bool
    {
        return is_null($this->uncompressedGetVariables) ? false : true;
    }

    /**
     * Returns true is the URI contains the library parameter
     *
     * @return bool
     */
    public function hasLibraryParameter(): bool
    {
        $libraryParameter = $this->controller->getRequest()->getQueryParams()[AbstractController::LIBRARY_NAME] ?? null;
        if (is_null($libraryParameter)) {
            $libraryParameter = $this->controller->getExtensionConfigurationManager()->getPiVars()[AbstractController::LIBRARY_NAME] ?? null;
        }
        return ! is_null($libraryParameter);
    }

    /**
     * Returns true is the URI contains a cHash parameter
     *
     * @return bool
     */
    public function hasCacheHashParameter(): bool
    {
        $cacheHashParameter = $this->controller->getRequest()->getQueryParams()['cHash'] ?? null;
        return ! is_null($cacheHashParameter);
    }

    /**
     * Returns true is the URI is verified
     *
     * @return bool
     */
    public function uriIsVerified(): bool
    {
        if ($this->hasLibraryParameter()) {
            if ($this->hasCacheHashParameter()) {
                // Gets the GET parameters
                $getParameters = $this->controller->getRequest()->getQueryParams();
                $cacheHashParameter = $getParameters['cHash'] ?? null;
                unset($getParameters['cHash']);

                // Adds the page id
                $getParameters['id'] = $this->controller->getPageId();

                // Computes the cHash from the GET parameters
                $cacheCacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
                $queryString = HttpUtility::buildQueryString($getParameters, '&');
                $calculatedCacheHashParameter = $cacheCacheHashCalculator->generateForParameters($queryString);
                // Returns true if the chash parameter is equal to the calculated one
                return $calculatedCacheHashParameter === $cacheHashParameter;
            } 
            return false;
        }
        return true;
    }

}
