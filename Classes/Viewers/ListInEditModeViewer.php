<?php
namespace YolfTypo3\SavLibraryPlus\Viewers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
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
 * Default List Viewer in Edit mode.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
    protected $inEditMode = TRUE;

    /**
     * Adds elements to the item list configuration
     *
     * @return none
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
            ->getFieldValueFromCurrentRow('cruser_id') != $GLOBALS['TSFE']->fe_user->user['uid']);

        // Adds the button to the configuration
        $additionalListItemConfiguration = array(
            'editButtonIsAllowed' => ! $noEditButton && $this->getController()
                ->getUserManager()
                ->userIsAllowedToChangeData(),
            'deleteButtonIsAllowed' => $deleteButtonIsAllowed && $this->getController()
                ->getUserManager()
                ->userIsAllowedToChangeData()
        );

        return $additionalListItemConfiguration;
    }

    /**
     * Adds additional elements to the view configuration
     *
     * @return none
     */
    protected function additionalViewConfiguration()
    {
        $noNewButton = $this->getController()
            ->getExtensionConfigurationManager()
            ->getNoNewButton();

        $this->addToViewConfiguration('general', array(
            'newButtonIsAllowed' => ! $noNewButton,
            'showFirstLastButtons' => TRUE
        ));
    }
}
?>
