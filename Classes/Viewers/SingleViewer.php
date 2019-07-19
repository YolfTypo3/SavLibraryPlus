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
 * Default Single Viewer.
 *
 * @package SavLibraryPlus
 */
class SingleViewer extends AbstractViewer
{
    /**
     * Item viewer directory
     *
     * @var string
     */
    protected $itemViewerDirectory = self::DEFAULT_ITEM_VIEWERS_DIRECTORY;

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'Single.html';

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType = 'SingleView';

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render()
    {
        // Sets the library view configuration
        $this->setLibraryViewConfiguration();

        // Renders the list view if the library view configuration is empty
        if(empty($this->libraryViewConfiguration)) {
            return($this->getController()->renderForm('list'));
        }

        // Sets the active folder Key
        $this->setActiveFolderKey();

        // Creates the field configuration manager
        $this->createFieldConfigurationManager();

        // Gets the fields configuration for the folder
        $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder());

        // Processes the fields
        foreach ($this->folderFieldsConfiguration as $fieldConfigurationKey => $fieldConfiguration) {
            // Processes the field
            $this->processField($fieldConfigurationKey);
        }

        // Adds the folders configuration
        $this->addToViewConfiguration('folders', $this->getFoldersConfiguration());

        // Adds the fields configuration
        $this->addToViewConfiguration('fields', $this->folderFieldsConfiguration);

        // Adds general information to the view configuration
        $this->addToViewConfiguration(
            'general',
            [
                'extensionKey' => $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getExtensionKey(),
                'extensionName' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getExtensionName(),
                'hideExtension' => 0,
                'helpPage' => $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getHelpPageForSingleView(),
                'addPrintIcon' => $this->getActiveFolderField('addPrintIcon'),
                'activeFolderKey' => $this->getActiveFolderKey(),
                'userIsAllowedToInputData' => $this->getController()
                    ->getUserManager()
                    ->userIsAllowedToInputData() && $this->getController()
                    ->getUserManager()
                    ->userIsAllowedToChangeData(),
                'title' => $this->processTitle($this->getActiveFolderTitle())
            ]
        );

        // Renders the view
        return $this->renderView();
    }
}
?>
