<?php

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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\DatePicker\DatePicker;

/**
 * General Date item Viewer.
 *
 * @package SavLibraryPlus
 */
class DateItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        $htmlArray = [];

        // Sets the format
        $format = ($this->getItemConfiguration('format') ? $this->getItemConfiguration('format') : $this->getController()->getDefaultDateFormat());

        // Sets the value
        if ($this->getItemConfiguration('error')) {
            $value = $this->getItemConfiguration('value');
        } else {
            // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
            $value = ($this->getItemConfiguration('value') ? @strftime($format, $this->getItemConfiguration('value')) : ($this->getItemConfiguration('nodefault') ? '' : @strftime($format)));
        }

        $htmlArray[] = HtmlElements::htmlInputTextElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('id', 'input_' . strtr($this->getItemConfiguration('itemName'), '[]', '__')),
                HtmlElements::htmlAddAttribute('value', $value),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ]
        );
        $htmlArray[] = HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('id', 'hidden_' . strtr($this->getItemConfiguration('itemName'), '[]', '__')),
                HtmlElements::htmlAddAttribute('value', ''),
            ]
        );

        // Creates the date picker
        $datePicker = GeneralUtility::makeInstance(DatePicker::class);

        $fieldSetDate = $this->getItemConfiguration('fieldsetdate');
        if (! empty($fieldSetDate)) {
            $fieldSetDate = preg_replace('/\[a\w+\]/', '[' . $this->getController()->cryptTag($fieldSetDate) . ']', $this->getItemConfiguration('itemName'));
            $fieldSetDate = 'hidden_' . str_replace(['[',']'], '_',  $fieldSetDate);
        }

        // Renders the date picker
        $htmlArray[] = $datePicker->renderDatePicker([
                'fieldSetDate' =>  ($this->getItemConfiguration('fieldsetdate') ? $fieldSetDate : null),
                'date' => $this->getItemConfiguration('value'),
                'id' => strtr($this->getItemConfiguration('itemName'), '[]', '__'),
                'format' => $format,
                'showsTime' => true,
                'iconPath' => LibraryConfigurationManager::getIconPath('calendar')
            ]
        );

        return $this->arrayToHTML($htmlArray);
    }
}
