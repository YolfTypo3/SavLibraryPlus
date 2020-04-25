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
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Default Edit Viewer.
 *
 * @package SavLibraryPlus
 */
class EditViewer extends AbstractViewer
{

    /**
     * Item viewer directory
     *
     * @var string
     */
    protected $itemViewerDirectory = 'Edit';

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'Edit.html';

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType = 'EditView';

    /**
     * Checks if the view can be rendered
     *
     * @return boolean
     */
    public function viewCanBeRendered()
    {
        // Gets the update record and sets the view to new if errors occur when saving a new reccord
        $updateQuerier = $this->getController()
        ->getQuerier()
        ->getUpdateQuerier();
        if ($updateQuerier !== null && $updateQuerier->isNewRecord() && $updateQuerier->errorDuringUpdate()) {
            $this->isNewView = true;
        }

        $userManager = $this->getController()->getUserManager();
        $result = $userManager->userIsAllowedToInputData() && $userManager->userIsAllowedToDisplayData();
        $result = $result && ($this->isNewView || $userManager->userIsAllowedToChangeData(UriManager::getUid()));

        return $result;
    }

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render()
    {
        // Adds the javascript for the popup to save data when clicking on a folder and data were changed and not saved.
        $this->addJavaScript();

        // Sets the library view configuration
        $this->setLibraryViewConfiguration();

        // Sets the active folder Key
        $this->setActiveFolderKey();

        // Creates the field configuration manager
        $this->createFieldConfigurationManager();

        // Gets the fields configuration for the folder
        $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder());

        // Builds the prefix for the item name
        $extensionPrefixId = $this->getController()->getExtensionConfigurationManager()->getExtensionPrefixId();
        $prefixForItemName = $extensionPrefixId . '[' . AbstractController::getFormName() . ']';

        // Processes the fields
        foreach ($this->folderFieldsConfiguration as $fieldConfigurationKey => $fieldConfiguration) {
            // Adds the item name
            $uid = $this->getController()
                ->getQuerier()
                ->getFieldValueFromCurrentRow('uid');
            $itemKey = '[' . $fieldConfigurationKey . '][' . intval($uid) . ']';
            $itemName = $prefixForItemName . $itemKey;
            $this->folderFieldsConfiguration[$fieldConfigurationKey]['itemName'] = $itemName;
            $this->folderFieldsConfiguration[$fieldConfigurationKey]['itemKey'] = $itemKey;

            // Processes the field
            $this->processField($fieldConfigurationKey);
        }

        // Adds the folders configuration
        $this->addToViewConfiguration('folders', $this->getFoldersConfiguration());

        // Adds the fields configuration
        $this->addToViewConfiguration('fields', $this->folderFieldsConfiguration);

        // Adds information to the view configuration
        $this->addToViewConfiguration('general', [
            'extensionKey' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getExtensionKey(),
            'extensionName' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getExtensionName(),
            'hideExtension' => 0,
            'helpPage' => $this->getController()
                ->getExtensionConfigurationManager()
                ->getHelpPageForEditView(),
            'activeFolderKey' => $this->getActiveFolderKey(),
            'formName' => AbstractController::getFormName(),
            'title' => $this->processTitle($this->getActiveFolderTitle()),
            'saveAndNew' => array_key_exists($this->getController()
                ->getQuerier()
                ->getQueryConfigurationManager()
                ->getMainTable(), $this->getController()
                ->getLibraryConfigurationManager()
                ->getGeneralConfigurationField('saveAndNew')),
            'isNewView' => $this->isNewView,
            'viewIdentifier' => $this->viewIdentifier
        ]);

        // Renders the view
        return $this->renderView();
    }

    /**
     * Adds javaScript for the popup
     *
     * @return void
     */
    protected function addJavaScript()
    {
        if ($this->getController()
            ->getQuerier()
            ->errorDuringUpdate() === true) {
            $javaScript = 'document.changed = true;';
        } else {
            $javaScript = '';
        }
        AdditionalHeaderManager::addJavaScript('documentChanged', $javaScript);
    }
}
?>
