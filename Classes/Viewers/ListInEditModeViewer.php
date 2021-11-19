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
     * @return boolean
     */
    public function viewCanBeRendered()
    {
        $userManager = $this->getController()->getUserManager();
        $result = $userManager->userIsAllowedToInputData();

        return $result;
    }

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'ListInEditMode.html';

    /**
     * Edit mode flag
     *
     * @var boolean
     */
    protected $inEditMode = true;

    /**
     * Adds elements to the item list configuration
     *
     * @param integer $uid
     *
     * @return void
     */
    protected function additionalListItemConfiguration($uid)
    {
        // Sets the edit button flags
        $noEditButton = $this->getController()
            ->getExtensionConfigurationManager()
            ->getNoEditButton();
        $noDeleteButton = $this->getController()
            ->getExtensionConfigurationManager()
            ->getNoDeleteButton();

        // Sets the delete button flag
        $deleteButtonOnlyForCreationUser = $this->getController()
            ->getExtensionConfigurationManager()
            ->getDeleteButtonOnlyForCreationUser();
        $deleteButtonIsAllowed = ! $noDeleteButton && ! ($deleteButtonOnlyForCreationUser && $this->getController()
            ->getQuerier()
            ->getFieldValueFromCurrentRow('cruser_id') != $this->getTypoScriptFrontendController()->fe_user->user['uid']);

        // Adds the button to the configuration
        $additionalListItemConfiguration = [
            'editButtonIsAllowed' => ! $noEditButton && $this->getController()
                ->getUserManager()
            ->userIsAllowedToChangeData($uid),
            'deleteButtonIsAllowed' => $deleteButtonIsAllowed && $this->getController()
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
    protected function additionalViewConfiguration()
    {
        $noNewButton = $this->getController()
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
