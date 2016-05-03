<?php
namespace SAV\SavLibraryPlus\Viewers;

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

use SAV\SavLibraryPlus\Controller\AbstractController;

/**
 * Subform Edit Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class SubformEditViewer extends EditViewer
{

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'SubformEdit.html';

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType = 'EditView';

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
        $configurationRows = array();
        if ($this->errorsInNewRecord()) {
            $rowsCount = 1;
        } else {
            $rowsCount = $this->getController()
                ->getQuerier()
                ->getRowsCount();
        }

        for ($rowKey = 0; $rowKey < $rowsCount; $rowKey ++) {
            $this->getController()
                ->getQuerier()
                ->setCurrentRowId($rowKey);

            // Gets the fields configuration for the folder
            $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder());

            $isFirstField = TRUE;
            // Processes the fields
            foreach ($this->folderFieldsConfiguration as $fieldConfigurationKey => $fieldConfiguration) {
                // Adds the item name
                if ($this->errorsInNewRecord()) {
                    $uid = 0;
                } else {
                    $uid = $this->getController()
                        ->getQuerier()
                        ->getFieldValueFromCurrentRow('uid');
                }
                $itemName = AbstractController::getFormName() . '[' . $fieldConfigurationKey . '][' . intval($uid) . ']';
                $this->folderFieldsConfiguration[$fieldConfigurationKey]['itemName'] = $itemName;

                // Processes the field
                $this->processField($fieldConfigurationKey);
                // Set the isFirstField flag
                if ($isFirstField === TRUE) {
                    $this->folderFieldsConfiguration[$fieldConfigurationKey]['isFirstField'] = TRUE;
                    $isFirstField = FALSE;
                }
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
        $pagesInSubform = array();
        for ($i = min($pageInSubform, max(0, $lastPageInSubform - $maxPagesInSubform)); $i <= min($lastPageInSubform, $pageInSubform + $maxPagesInSubform - 1); $i ++) {
            $pagesInSubform[$i] = $i + 1;
        }

        // Adds information to the view configuration
        $this->addToViewConfiguration('general', array(
            'lastPageInSubform' => $lastPageInSubform,
            'pagesInSubform' => $pagesInSubform,
            'formName' => AbstractController::getFormName()
        ));

        // Renders the view
        return $this->renderView();
    }

    /**
     * Checks if errors occured in a new record
     *
     * @return boolean
     */
    public function errorsInNewRecord()
    {
        $updateQuerier = $this->getController()
            ->getQuerier()
            ->getUpdateQuerier();
        return $this->getController()
            ->getQuerier()
            ->errorDuringUpdate() && $updateQuerier !== NULL && $updateQuerier->isNewRecord();
    }
}
?>
