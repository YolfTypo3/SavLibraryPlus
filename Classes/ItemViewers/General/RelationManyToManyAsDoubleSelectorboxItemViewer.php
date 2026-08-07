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
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Queriers\ForeignTableSelectQuerier;

/**
 * General RelationManyToManyAsDoubleSelectorbox item Viewer.
 *
 * @package SavLibraryPlus
 */
class RelationManyToManyAsDoubleSelectorboxItemViewer extends AbstractItemViewer
{

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
    protected function renderItem(): string
    {
        if ($this->getItemConfigurationAttribute('MM')) {
            $this->setForeignTableSelectQuerier('buildQueryConfigurationForTrueManyToManyRelation');
        } else {
            $this->setForeignTableSelectQuerier('buildQueryConfigurationForCommaListManyToManyRelation');
        }
        return $this->renderDoubleSelectorbox();
    }

    /**
     * Sets the Foreign Table Select Querier.
     *
     * @param string $getQuerierMethod
     *            The method name to get the querier
     *
     * @return void
     */
    protected function setForeignTableSelectQuerier(string $buildQueryConfigurationMethod): void
    {
        $querierClassName = ForeignTableSelectQuerier::class;
        $this->foreignTableSelectQuerier = GeneralUtility::makeInstance($querierClassName, $this->controller);

        $this->itemConfiguration['uidLocal'] = $this->itemConfiguration['uid'] ?? null;
        $this->foreignTableSelectQuerier->$buildQueryConfigurationMethod($this->itemConfiguration);
        $this->foreignTableSelectQuerier->setQueryConfiguration();
    }

    /**
     * Renders the double selector box content.
     *
     * @return string
     */
    protected function renderDoubleSelectorbox(): string
    {
        $htmlArray = [];

        // Gets the rows
        $this->foreignTableSelectQuerier->processQuery();
        $rows = $this->foreignTableSelectQuerier->getRows();

        // Gets the label for the foreign_table
        $label = $this->getItemConfigurationAttribute('labelselect');
        if (! empty($label)) {
            // Checks if it is an alias
            if (! $this->foreignTableSelectQuerier->fieldExistsInCurrentRow($label)) {
                $label = $this->getItemConfigurationAttribute('foreign_table') . '.' . $label;
            }
        } else {
            $tcaCtrlFieldLabel = $this->controller->getTcaConfigurationManager()->getTcaCtrlField($this->getItemConfigurationAttribute('foreign_table'), 'label');
            $label = $this->getItemConfigurationAttribute('foreign_table') . '.' . $tcaCtrlFieldLabel;
        }

        // Processes the rows
        $maxCount = count($rows) - 1;
        if (is_array($rows)) {
            foreach ($rows as $rowKey => $row) {
                $content = $row[$label];
                // Applies the function if any and allowed
                if ($this->getItemConfigurationAttribute('func') && $this->getItemConfigurationAttribute('applyfunctorecords')) {
                    // Sets the special markers
                    $specialFields = str_replace(' ', '', $this->getItemConfigurationAttribute('specialfields'));
                    if (! empty($specialFields)) {
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
                    }
                    $content = $this->processFuncAttribute($content);
                }
                $content .= ($rowKey < $maxCount ? $this->getItemConfigurationAttribute('separator') : '');

                $htmlArray[] = HtmlElements::htmlDivElement([
                    HtmlElements::htmlAddAttribute('class', 'doubleSelectorbox item' . $row['uid'])
                ], $content);
            }
        }

        return $this->arrayToHTML($htmlArray);
    }
}
