<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\TcaConfigurationManager;
use YolfTypo3\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;

/**
 * Edit RelationManyToManyAsDoubleSelectorbox item Viewer.
 *
 * @package SavLibraryPlus
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
     * @var ForeignTableSelectQuerier
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
                ->errorDuringUpdate() === true) {
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
     * @return void
     */
    protected function setSelectedItems()
    {
        // Gets the rows
        $this->foreignTableSelectQuerier->processQuery();
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Builds the selected items
        $this->selectedItems = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->selectedItems[] = $row['uid'];
            }
        }
    }

    /**
     * Sets the selected items from the processed post variables in case of errors during update
     *
     * @return void
     */
    protected function setSelectedItemsFromProcessedPostVariable()
    {
        $updateQuerier = $this->getController()
            ->getQuerier()
            ->getUpdateQuerier();

        // Sets the uid to 0 is the error occurs with a new record
        if ($updateQuerier->isNewRecord()) {
            $uid = 0;
        } else {
            $uid = $this->itemConfiguration['uid'];
        }
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
        $htmlArray = [];

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
        $htmlArray = [];

        // Gets information from the foreign table
        $this->foreignTableSelectQuerier->buildQueryConfigurationForForeignTable($this->itemConfiguration);
        $this->foreignTableSelectQuerier->injectQueryConfiguration();

        $this->foreignTableSelectQuerier->processQuery();

        // Gets the rows
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Initializes the option element array
        $htmlOptionArray = [];
        $htmlOptionArray[] = '';

        // Checks if the emptyItem attribute is set
        if ($this->getItemConfiguration('emptyitem')) {
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement([
                HtmlElements::htmlAddAttribute('class', 'item0'),
                HtmlElements::htmlAddAttribute('value', '0')
            ], '');
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
        foreach ($rows as $row) {
            $selected = (in_array($row['uid'], $this->selectedItems) === true ? 'selected ' : '');
            // Adds the Option element
            $htmlOptionArray[] = HtmlElements::htmlOptionElement([
                HtmlElements::htmlAddAttribute('class', 'item' . $row['uid']),
                HtmlElements::htmlAddAttribute('value', $row['uid']),
                HtmlElements::htmlAddAttributeIfNotNull('selected', $selected)
            ], stripslashes($row[$label]));
        }

        // Adds the select element
        $htmlArray[] = HtmlElements::htmlSelectElement([
            HtmlElements::htmlAddAttribute('multiple', 'multiple'),
            HtmlElements::htmlAddAttribute('class', 'multiple'),
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[]'),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
        ], $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Builds the destination selector box
     *
     * @return string the rendered item
     */
    public function buildDestinationSelectorBox()
    {
        $htmlArray = [];

        // Gets the rows
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Initializes the option element array
        $htmlOptionArray = [];
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
        foreach ($rows as $row) {
            if (in_array($row['uid'], $this->selectedItems) === true) {
                // Adds the Option element
                $htmlOptionArray[] = HtmlElements::htmlOptionElement([
                    HtmlElements::htmlAddAttribute('class', 'item' . $row['uid']),
                    HtmlElements::htmlAddAttribute('value', $row['uid'])
                ], stripslashes($row[$label]));
            }
        }

        // Adds the select element
        $sort = ($this->getItemConfiguration('orderselect') ? 1 : 0);
        $htmlArray[] = HtmlElements::htmlSelectElement([
            HtmlElements::htmlAddAttribute('multiple', 'multiple'),
            HtmlElements::htmlAddAttribute('class', 'multiple'),
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName') . '[]'),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;'),
            HtmlElements::htmlAddAttribute('ondblclick', 'move(\'' . AbstractController::getFormName() . '\', \'' . $this->getItemConfiguration('itemName') . '[]\', \'' . 'source_' . $this->getItemConfiguration('itemName') . '\',' . $sort . ');')
        ], $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }

    /**
     * Builds the source selector box
     *
     * @return string the rendered item
     */
    public function buildSourceSelectorBox()
    {
        $htmlArray = [];

        // Gets the rows
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Initializes the option element array
        $htmlOptionArray = [];
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
        foreach ($rows as $row) {

            if (in_array($row['uid'], $this->selectedItems) === false) {
                // Adds the Option element
                $htmlOptionArray[] = HtmlElements::htmlOptionElement([
                    HtmlElements::htmlAddAttribute('class', 'item' . $row['uid']),
                    HtmlElements::htmlAddAttribute('value', $row['uid'])
                ], stripslashes($row[$label]));
            }
        }

        // Adds the select element
        $sort = ($this->getItemConfiguration('orderselect') ? 1 : 0);
        $htmlArray[] = HtmlElements::htmlSelectElement([
            HtmlElements::htmlAddAttribute('multiple', 'multiple'),
            HtmlElements::htmlAddAttribute('class', 'multiple'),
            HtmlElements::htmlAddAttribute('name', 'source_' . $this->getItemConfiguration('itemName')),
            HtmlElements::htmlAddAttribute('size', $this->getItemConfiguration('size')),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;'),
            HtmlElements::htmlAddAttribute('ondblclick', 'move(\'' . AbstractController::getFormName() . '\', \'' . 'source_' . $this->getItemConfiguration('itemName') . '\', \'' . $this->getItemConfiguration('itemName') . '[]\',' . $sort . ');')
        ], $this->arrayToHTML($htmlOptionArray));

        return $this->arrayToHTML($htmlArray);
    }
}
?>
