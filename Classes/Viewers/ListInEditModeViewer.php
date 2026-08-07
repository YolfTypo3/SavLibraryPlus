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

namespace YolfTypo3\SavLibraryPlus\Viewers;

use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;

/**
 * Default List Viewer in Edit mode.
 *
 * @package SavLibraryPlus
 */
class ListInEditModeViewer extends ListViewer
{

    /**
     * Checks if the view can be rendered
     *
     * @return bool
     */
    public function viewCanBeRendered(): bool
    {
        $userManager = $this->controller->getUserManager();
        $result = $userManager->userIsAllowedToInputData();

        return $result;
    }

    /**
     * The template file
     *
     * @var string
     */
    protected string $templateFile = 'ListInEditMode.html';

    /**
     * Edit mode flag
     *
     * @var bool
     */
    protected bool $inEditMode = true;

    /**
     * Adds elements to the item list configuration
     *
     * @param int $uid
     *
     * @return array
     */
    protected function additionalListItemConfiguration(int $uid): array
    {
        // Sets the edit button flags
        $noEditButton = $this->controller
            ->getExtensionConfigurationManager()
            ->getNoEditButton();
        $noDeleteButton = $this->controller
            ->getExtensionConfigurationManager()
            ->getNoDeleteButton();

        // Sets the delete button flag
        $deleteButtonOnlyForCreationUser = $this->controller
            ->getExtensionConfigurationManager()
            ->getDeleteButtonOnlyForCreationUser();
        $deleteButtonIsAllowed = ! $noDeleteButton && ! ($deleteButtonOnlyForCreationUser && $this->controller
            ->getQuerier()
            ->getFieldValueFromCurrentRow('cruser_id') != $this->controller->getUserManager()->getUserId());

        // Adds the button to the configuration
        $additionalListItemConfiguration = [
            'editButtonIsAllowed' => ! $noEditButton && $this->controller
                ->getUserManager()
            ->userIsAllowedToChangeData($uid),
            'deleteButtonIsAllowed' => $deleteButtonIsAllowed && $this->controller
                ->getUserManager()
            ->userIsAllowedToChangeData($uid)
        ];

        // Adds the javascript to confirm the delete action
        if ($deleteButtonIsAllowed) {
            AdditionalHeaderManager::addConfirmDeleteJavaScript('item');
        }

        return $additionalListItemConfiguration;
    }

    /**
     * Adds additional elements to the view configuration
     *
     * @return void
     */
    protected function additionalViewConfiguration(): void
    {
        $noNewButton = $this->controller
            ->getExtensionConfigurationManager()
            ->getNoNewButton();

        $this->addToViewConfiguration(
            'general',
            [
                'newButtonIsAllowed' => ! $noNewButton,
                'showFirstLastButtons' => true
            ]
        );
    }
}
