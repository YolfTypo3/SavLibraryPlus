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
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Default Subform Single Viewer.
 *
 * @package SavLibraryPlus
 */
class SubformSingleViewer extends SingleViewer
{

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'SubformSingle.html';

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
        // Sets the active folder Key
        $this->setActiveFolderKey();

        // Creates the field configuration manager
        $this->createFieldConfigurationManager();

        // Processes the rows
        $configurationRows = [];
        $rowsCount = $this->getController()
            ->getQuerier()
            ->getRowsCount();

        // Builds the prefix for the item name
        $extensionPrefixId = $this->getController()->getExtensionConfigurationManager()->getExtensionPrefixId();
        $prefixForItemName = $extensionPrefixId . '[' . AbstractController::getFormName() . ']';

        for ($rowKey = 0; $rowKey < $rowsCount; $rowKey ++) {
            $this->getController()
                ->getQuerier()
                ->setCurrentRowId($rowKey);

            // Gets the fields configuration for the folder
            $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder());

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

            $configurationRows[] = $this->folderFieldsConfiguration;
        }

        // Adds the fields configuration
        $this->addToViewConfiguration('rows', $configurationRows);

        // Page information for the page browser
        $pageInSubform = $this->getFieldFromGeneralViewConfiguration('pageInSubform');
        $maximumItemsInSubform = $this->getFieldFromGeneralViewConfiguration('maximumItemsInSubform');
        $lastPageInSubform = (empty($maximumItemsInSubform) ? 0 : floor(($this->getController()
            ->getQuerier()
            ->getTotalRowsCount() - 1) / $maximumItemsInSubform));
        $maxPagesInSubform = $this->getController()
            ->getExtensionConfigurationManager()
            ->getMaxPages();
        $pagesInSubform = [];
        for ($i = min($pageInSubform, max(0, $lastPageInSubform - $maxPagesInSubform)); $i <= min($lastPageInSubform, $pageInSubform + $maxPagesInSubform - 1); $i ++) {
            $pagesInSubform[$i] = $i + 1;
        }

        // Adds information to the view configuration
        $this->addToViewConfiguration('general', [
            'lastPageInSubform' => $lastPageInSubform,
            'pagesInSubform' => $pagesInSubform
        ]);

        // Renders the view
        return $this->renderView();
    }
}
?>
