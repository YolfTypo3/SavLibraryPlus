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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General RelationOneToManyAsSelectorbox item Viewer.
 *
 * @package SavLibraryPlus
 */
class RelationOneToManyAsSelectorboxItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        // Gets the label
        $labelSelect = $this->getItemConfigurationAttribute('labelselect');
        if (empty($labelSelect) === false) {
            // Checks if this label comes from an aliasSelect attribute
            $aliasSelect = $this->getItemConfigurationAttribute('aliasselect') ?? '';
            if (preg_match('/(?:AS|as) ' . $labelSelect . '/', $aliasSelect)) {
                // Uses the alias
                $label = $labelSelect;
                $labelSelect = '';
            } else {
                // Builds a full field name
                $label = $this->getItemConfigurationAttribute('foreign_table') . '.' . $labelSelect;
                $labelSelect = ',' . $label;
            }
        } else {
            // Gets the label from the TCA
            $tcaCtrlFieldLabel = $this->controller->getTcaConfigurationManager()->getTcaCtrlField($this->getItemConfigurationAttribute('foreign_table'), 'label');
            $label = $this->getItemConfigurationAttribute('foreign_table') . '.' . $tcaCtrlFieldLabel;            
        }

        // Sets the SELECT Clause
        $this->itemConfiguration['selectclause'] = $this->getItemConfigurationAttribute('foreign_table') . '.uid,' . $label;
        // Builds the querier
        $querierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\ForeignTableSelectQuerier';
        $querier = GeneralUtility::makeInstance($querierClassName, $this->controller);
        $querier->buildQueryConfigurationForOneToManyRelation($this->itemConfiguration);
        $querier->setQueryConfiguration();
        $querier->processQuery();

        // Gets the rows
        $rows = $querier->getRows();

        // Processes the row
        $row = $rows[0] ?? null;
        $specialFields = str_replace(' ', '', $this->getItemConfigurationAttribute('specialfields') ?? '');
        if (! empty($row)) {
            // Sets the special markers
            $specialFieldsArray = explode(',', $specialFields);
            foreach ($row as $fieldKey => $field) {
                if (in_array($fieldKey, $specialFieldsArray)) {
                    $this->controller
                        ->getQuerier()
                        ->setAdditionalMarkers([
                        '###special[' . $fieldKey . ']###' => $field
                    ]);
                }
            }
            // Gets the selected element
            $content = stripslashes($row[$label]);
            $content = $querier->parseLocalizationTags($content);
            $content = $querier->parseFieldTags($content);
        } else {
            $content = '';
        }

        return $content;
    }
}
