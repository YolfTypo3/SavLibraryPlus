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

/**
 * Subform Edit Viewer.
 *
 * @package SavLibraryPlus
 */
class SubformEditViewer extends EditViewer
{
    /**
     * The template file
     *
     * @var string
     */
    protected string $templateFile = 'SubformEdit.html';

    /**
     * The view type
     *
     * @var string
     */
    protected string $viewType = 'EditView';

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render(): string
    {
        // Sets the active folder Key
        $this->setActiveFolderKey();

        // Creates the field configuration manager
        $this->createFieldConfigurationManager();

        // Processes the rows
        $configurationRows = [];
        if ($this->errorsInNewRecord() && $this->isNewView) {
            $rowsCount = 1;
        } else {
            $rowsCount = $this->controller
                ->getQuerier()
                ->getRowsCount();
        }

        // Builds the prefix for the item name
        $extensionPrefixId = $this->controller->getExtensionPrefixId();
        $prefixForItemName = $extensionPrefixId . '[' . $this->controller->getFormName() . ']';

        for ($rowKey = 0; $rowKey < $rowsCount; $rowKey ++) {
            $this->controller
                ->getQuerier()
                ->setCurrentRowId($rowKey);

            // Gets the fields configuration for the folder
            $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder());

            $isFirstField = true;
            // Processes the fields
            foreach ($this->folderFieldsConfiguration as $fieldConfigurationKey => $fieldConfiguration) {
                // Adds the item name
                if ($this->errorsInNewRecord()) {
                    $uid = 0;
                } else {
                    $uid = $this->controller
                        ->getQuerier()
                        ->getFieldValueFromCurrentRow('uid');
                }
                $itemKey = '[' . $fieldConfigurationKey . '][' . intval($uid) . ']';
                $itemName = $prefixForItemName . $itemKey;
                $this->folderFieldsConfiguration[$fieldConfigurationKey]['itemName'] = $itemName;
                $this->folderFieldsConfiguration[$fieldConfigurationKey]['itemKey'] = $itemKey;

                // Processes the field
                $this->processField($fieldConfigurationKey);
                // Set the isFirstField flag
                if ($isFirstField === true) {
                    $this->folderFieldsConfiguration[$fieldConfigurationKey]['isFirstField'] = true;
                    $isFirstField = false;
                }
            }
            $configurationRows[] = $this->folderFieldsConfiguration;
        }

        // Adds the fields configuration
        $this->addToViewConfiguration('rows', $configurationRows);

        // Page information for the page browser
        $pageInSubform = $this->getFieldFromGeneralViewConfiguration('pageInSubform');
        $maximumItemsInSubform = $this->getFieldFromGeneralViewConfiguration('maximumItemsInSubform');
        $lastPageInSubform = (empty($maximumItemsInSubform) ? 0 : floor(($this->controller
            ->getQuerier()
            ->getTotalRowsCount() - 1) / $maximumItemsInSubform));
        $maxPagesInSubform = $this->controller
            ->getExtensionConfigurationManager()
            ->getMaxPages();
        $pagesInSubform = [];
        for ($i = min($pageInSubform, max(0, $lastPageInSubform - $maxPagesInSubform)); $i <= min($lastPageInSubform, $pageInSubform + $maxPagesInSubform - 1); $i ++) {
            $pagesInSubform[$i] = $i + 1;
        }

        // Adds information to the view configuration
        $this->addToViewConfiguration(
            'general',
            [
                'lastPageInSubform' => $lastPageInSubform,
                'pagesInSubform' => $pagesInSubform,
                'formName' => $this->controller->getFormName(),
                'prefixForItemName' => $prefixForItemName

            ]
        );

        // Renders the view
        return $this->renderView();
    }

    /**
     * Checks if errors occured in a new record
     *
     * @return bool
     */
    public function errorsInNewRecord(): bool
    {
        $updateQuerier = $this->controller
            ->getQuerier()
            ->getUpdateQuerier();

        return $this->controller
            ->getQuerier()
            ->errorDuringUpdate() && $updateQuerier !== null && $updateQuerier->isNewRecord();
    }
}
