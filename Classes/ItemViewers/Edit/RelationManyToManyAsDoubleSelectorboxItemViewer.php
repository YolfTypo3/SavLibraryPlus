<?php
namespace SAV\SavLibraryPlus\ItemViewers\Edit;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use SAV\SavLibraryPlus\Utility\HtmlElements;
use SAV\SavLibraryPlus\Managers\AdditionalHeaderManager;
use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\TcaConfigurationManager;
use SAV\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;

/**
 * Edit RelationManyToManyAsDoubleSelectorbox item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class RelationManyToManyAsDoubleSelectorboxItemViewer extends AbstractItemViewer
{

    /**
     * The selected items
     *
     * @var array
     */
    protected $selectedItems;

    /**
     * The Foreign Table Select Querier
     *
     * @var \SAV\SavLibraryPlus\Queriers\ForeignTableSelectQuerier
     */
    protected $foreignTableSelectQuerier;

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        if ($this->getItemConfiguration('MM')) {
            $this->setForeignTableSelectQuerier('buildQueryConfigurationForTrueManyToManyRelation');
            if ($this->getController()
                ->getQuerier()
                ->errorDuringUpdate() === TRUE) {
                $this->setSelectedItemsFromProcessedPostVariable();
            } else {
                $this->setSelectedItems();
            }
        } else {
            $this->setForeignTableSelectQuerier('buildQueryConfigurationForCommaListManyToManyRelation');
            $this->setSelectedItems();
        }
        if ($this->getItemConfiguration('singlewindow')) {
            return $this->renderSingleSelectorbox();
        } else {
            return $this->renderDoubleSelectorbox();
        }
    }

    /**
     * Sets the Foreign Table Select Querier.
     *
     * @param string $buildQueryConfigurationMethod
     *            The method name to get the querier
     *
     * @return string
     */
    protected function setForeignTableSelectQuerier($buildQueryConfigurationMethod)
    {
        $this->foreignTableSelectQuerier = GeneralUtility::makeInstance(ForeignTableSelectQuerier::class);
        $this->foreignTableSelectQuerier->injectController($this->getController());

        $this->itemConfiguration['uidLocal'] = $this->itemConfiguration['uid'];
        $this->foreignTableSelectQuerier->$buildQueryConfigurationMethod($this->itemConfiguration);
        $this->foreignTableSelectQuerier->injectQueryConfiguration();
    }

    /**
     * Sets the selected items
     *
     * @return none
     */
    protected function setSelectedItems()
    {
        // Gets the rows
        $this->foreignTableSelectQuerier->processQuery();
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Builds the selected items
        $this->selectedItems = array();
        if (is_array($rows)) {
            foreach ($rows as $rowKey => $row) {
                $this->selectedItems[] = $row['uid'];
            }
        }
    }

    /**
     * Sets the selected items from the processed post variables in case of errors during update
     *
     * @return none
     */
    protected function setSelectedItemsFromProcessedPostVariable()
    {
        $updateQuerier = $this->getController()
            ->getQuerier()
            ->getUpdateQuerier();
        $uid = $this->itemConfiguration['uid'];
        $fullFieldName = $this->itemConfiguration['MM'] . '.uid_foreign';
        $processedPostVariable = $updateQuerier->getProcessedPostVariable($fullFieldName, $uid);
        $this->selectedItems = $processedPostVariable['value'];
    }

    /**
     * Renders the Double Selectorbox
     *
     * @return string the rendered item
     */
    protected function renderDoubleSelectorbox()
    {
        $htmlArray = array();

        // Gets information from the foreign table
        $this->foreignTableSelectQuerier->buildQueryConfigurationForForeignTable($this->itemConfiguration);
        $this->foreignTableSelectQuerier->injectQueryConfiguration();

        $this->foreignTableSelectQuerier->processQuery();

        // Builds the source and destionation selectorboxes
        $htmlArray[] = $this->buildDestinationSelectorBox();
        $htmlArray[] = $this->buildSourceSelectorBox();

        // Adds the javaScript for the selectorboxes
        AdditionalHeaderManager::addJavaScript('selectAll', 'if (x == \'' . AbstractController::getFormName() . '\')	selectAll(x, \'' . $this->getItemConfiguration('itemName') . '[]\');');

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Renders the Single Selectorbox
     *
     * @return string the rendered item
     */
    protected function renderSingleSelectorbox()
    {
        $htmlArray = array();

        // Gets information from the foreign table
        $this->foreignTableSelectQuerier->buildQueryConfigurationForForeignTable($this->itemConfiguration);
        $this->foreignTableSelectQuerier->injectQueryConfiguration();

        $this->foreignTableSelectQuerier->processQuery();

        // Gets the rows
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Initializes the option element array
        $htmlOptionArray = array();
        $htmlOptionArray[] = '';

        // Checks if the emptyItem attribute is set
        if ($this->getItemConfiguration('emptyitem')) {
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement(array(
                HtmlElements::htmlAddAttribute('class', 'item0'),
                HtmlElements::htmlAddAttribute('value', '0')
            ), '');
        }

        // Gets the label for the foreign_table
        $label = $this->getItemConfiguration('labelselect');
        if (! empty($label)) {
            // Checks if it is an alias
            if (! $this->foreignTableSelectQuerier->fieldExistsInCurrentRow($label)) {
                $label = $this->getItemConfiguration('foreign_table') . '.' . $label;
            }
        } else {
            $label = $this->getItemConfiguration('foreign_table') . '.' . TcaConfigurationManager::getTcaCtrlField($this->getItemConfiguration('foreign_table'), 'label');
        }

        // Adds the option elements
        foreach ($rows as $rowKey => $row) {
            $selected = (in_array($row['uid'], $this->selectedItems) === TRUE ? 'selected ' : '');
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement(array(
                HtmlElements::htmlAddAttribute('class', 'item' . $row['uid']),
                HtmlElements::htmlAddAttribute('value', $row['uid']),
                HtmlElements::htmlAddAttributeIfNotNull('selected', $selected)
            ), stripslashes($row[$label]));
        }

        // Adds the select element
        $htmlArray[] = HtmlElements::htmlSelectElement(array(
            HtmlElements::htmlAddAttribute('multiple', 'multiple'),
            HtmlElements::htmlAddAttribute('class', 'multiple'),
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[]'),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
        ), $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Builds the destination selector box
     *
     * @return string the rendered item
     */
    public function buildDestinationSelectorBox()
    {
        // Gets the rows
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Initializes the option element array
        $htmlOptionArray = array();
        $htmlOptionArray[] = '';

        // Gets the label for the foreign_table
        $label = $this->getItemConfiguration('labelselect');
        if (! empty($label)) {
            // Checks if it is an alias
            if (! $this->foreignTableSelectQuerier->fieldExistsInCurrentRow($label)) {
                $label = $this->getItemConfiguration('foreign_table') . '.' . $label;
            }
        } else {
            $label = $this->getItemConfiguration('foreign_table') . '.' . TcaConfigurationManager::getTcaCtrlField($this->getItemConfiguration('foreign_table'), 'label');
        }

        // Adds the option elements
        foreach ($rows as $rowKey => $row) {
            if (in_array($row['uid'], $this->selectedItems) === TRUE) {
                // Adds the Option element
                $htmlOptionArray[] = HtmlElements::htmlOptionElement(array(
                    HtmlElements::htmlAddAttribute('class', 'item' . $row['uid']),
                    HtmlElements::htmlAddAttribute('value', $row['uid'])
                ), stripslashes($row[$label]));
            }
        }

        // Adds the select element
        $sort = ($this->getItemConfiguration('orderselect') ? 1 : 0);
        $htmlArray[] = HtmlElements::htmlSelectElement(array(
            HtmlElements::htmlAddAttribute('multiple', 'multiple'),
            HtmlElements::htmlAddAttribute('class', 'multiple'),
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[]'),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;'),
            HtmlElements::htmlAddAttribute('ondblclick', 'move(\'' . AbstractController::getFormName() . '\', \'' . $this->getItemConfiguration('itemName') . '[]\', \'' . 'source_' . $this->getItemConfiguration('itemName') . '\',' . $sort . ');')
        ), $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Builds the source selector box
     *
     * @return string the rendered item
     */
    public function buildSourceSelectorBox()
    {
        // Gets the rows
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Initializes the option element array
        $htmlOptionArray = array();
        $htmlOptionArray[] = '';

        // Gets the label for the foreign_table
        $label = $this->getItemConfiguration('labelselect');
        if (! empty($label)) {
            // Checks if it is an alias
            if (! $this->foreignTableSelectQuerier->fieldExistsInCurrentRow($label)) {
                $label = $this->getItemConfiguration('foreign_table') . '.' . $label;
            }
        } else {
            $label = $this->getItemConfiguration('foreign_table') . '.' . TcaConfigurationManager::getTcaCtrlField($this->getItemConfiguration('foreign_table'), 'label');
        }

        // Adds the option elements
        foreach ($rows as $rowKey => $row) {

            if (in_array($row['uid'], $this->selectedItems) === FALSE) {
                // Adds the Option element
                $htmlOptionArray[] = HtmlElements::htmlOptionElement(array(
                    HtmlElements::htmlAddAttribute('class', 'item' . $row['uid']),
                    HtmlElements::htmlAddAttribute('value', $row['uid'])
                ), stripslashes($row[$label]));
            }
        }

        // Adds the select element
        $sort = ($this->getItemConfiguration('orderselect') ? 1 : 0);
        $htmlArray[] = HtmlElements::htmlSelectElement(array(
            HtmlElements::htmlAddAttribute('multiple', 'multiple'),
            HtmlElements::htmlAddAttribute('class', 'multiple'),
            HtmlElements::htmlAddAttribute('name', 'source_' . $this->getItemConfiguration('itemName')),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;'),
            HtmlElements::htmlAddAttribute('ondblclick', 'move(\'' . AbstractController::getFormName() . '\', \'' . 'source_' . $this->getItemConfiguration('itemName') . '\', \'' . $this->getItemConfiguration('itemName') . '[]\',' . $sort . ');')
        ), $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }
}
?>
