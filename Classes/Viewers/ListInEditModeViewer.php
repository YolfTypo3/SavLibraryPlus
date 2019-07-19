<?php
namespace YolfTypo3\SavLibraryPlus\Viewers;

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

/**
 * Default List Viewer in Edit mode.
 *
 * @package SavLibraryPlus
 */
class ListInEditModeViewer extends ListViewer
{
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
     * @return void
     */
    protected function additionalListItemConfiguration()
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
                ->userIsAllowedToChangeData(),
            'deleteButtonIsAllowed' => $deleteButtonIsAllowed && $this->getController()
                ->getUserManager()
                ->userIsAllowedToChangeData()
        ];

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
?>
