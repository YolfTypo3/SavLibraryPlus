<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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
use YolfTypo3\SavLibraryPlus\Managers\TcaConfigurationManager;

/**
 * General RelationOneToManyAsSelectorbox item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class RelationOneToManyAsSelectorboxItemViewer extends AbstractItemViewer
{

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        // Gets the label
        $labelSelect = $this->getItemConfiguration('labelselect');
        if (empty($labelSelect) === FALSE) {
            // Checks if this label comes from an aliasSelect attribute
            $aliasSelect = $this->getItemConfiguration('aliasselect');
            if (preg_match('/(?:AS|as) ' . $labelSelect . '/', $aliasSelect)) {
                // Uses the alias
                $label = $labelSelect;
                $labelSelect = '';
            } else {
                // Builds a full field name
                $label = $this->getItemConfiguration('foreign_table') . '.' . $labelSelect;
                $labelSelect = ',' . $label;
            }
        } else {
            // Gets the label from the TCA
            $label = $this->getItemConfiguration('foreign_table') . '.' . TcaConfigurationManager::getTcaCtrlField($this->getItemConfiguration('foreign_table'), 'label');
        }

        // Sets the SELECT Clause
        $this->itemConfiguration['selectclause'] = $this->getItemConfiguration('foreign_table') . '.uid,' . $label;
        // Builds the querier
        $querierClassName = 'YolfTypo3\\SavLibraryPlus\\Queriers\\ForeignTableSelectQuerier';
        $querier = GeneralUtility::makeInstance($querierClassName);
        $querier->injectController($this->getController());
        $querier->buildQueryConfigurationForOneToManyRelation($this->itemConfiguration);
        $querier->injectQueryConfiguration();
        $querier->processQuery();

        // Gets the rows
        $rows = $querier->getRows();

        // Processes the row
        $row = $rows[0];
        $specialFields = str_replace(' ', '', $this->getItemConfiguration('specialfields'));
        if (! empty($row)) {
            // Injects the special markers
            $specialFieldsArray = explode(',', $specialFields);
            foreach ($row as $fieldKey => $field) {
                if (in_array($fieldKey, $specialFieldsArray)) {
                    $this->getController()
                        ->getQuerier()
                        ->injectAdditionalMarkers(array(
                        '###special[' . $fieldKey . ']###' => $field
                    ));
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
?>
