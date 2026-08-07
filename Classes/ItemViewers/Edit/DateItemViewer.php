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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
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
    protected function renderItem(): string
    {
        $htmlArray = [];

        // Sets the format
        $dateFormat = ($this->getItemConfigurationAttribute('dateformat') ? $this->getItemConfigurationAttribute('dateformat') : $this->controller->getDefaultDateFormat());

        // Sets the value
        if ($this->getItemConfigurationAttribute('error')) {
            $value = $this->getItemConfiguration('value');
            if (empty($value) && $this->getItemConfiguration('nodefault')) {
                $value = '';
            }
        } else {
            /** @var DateFormatter $dateFormatter */
            $dateFormatter = GeneralUtility::makeInstance(DateFormatter::class);
            $value = ($this->getItemConfigurationAttribute('value') ? $dateFormatter->strftime($dateFormat, intval($this->getItemConfigurationAttribute('value'))) : ($this->getItemConfigurationAttribute('nodefault') ? '' : $dateFormatter->strftime($dateFormat, 'now')));
        }

        $htmlArray[] = HtmlElements::htmlInputTextElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('id', 'input_' . strtr($this->getItemConfigurationAttribute('itemName'), '[]', '__')),
                HtmlElements::htmlAddAttribute('value', $value),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ]
        );
        $htmlArray[] = HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('id', 'hidden_' . strtr($this->getItemConfigurationAttribute('itemName'), '[]', '__')),
                HtmlElements::htmlAddAttribute('value', ''),
            ]
        );

        // Creates the date picker
        $datePicker = new (DatePicker::class)($this->controller);

        $fieldSetDate = $this->getItemConfigurationAttribute('fieldsetdate');
        if (! empty($fieldSetDate)) {
            $fieldSetDate = preg_replace('/\[a\w+\]/', '[' . $this->controller->cryptTag($fieldSetDate) . ']', $this->getItemConfigurationAttribute('itemName'));
            $fieldSetDate = 'hidden_' . str_replace(['[',']'], '_',  $fieldSetDate);
        }

        // Renders the date picker
        $htmlArray[] = $datePicker->renderDatePicker([
                'fieldSetDate' =>  ($this->getItemConfigurationAttribute('fieldsetdate') ? $fieldSetDate : null),
                'date' => $this->getItemConfigurationAttribute('value'),
                'id' => strtr($this->getItemConfigurationAttribute('itemName'), '[]', '__'),
                'dateFormat' => $dateFormat,
                'showsTime' => true,
                'iconPath' => $this->controller->getLibraryConfigurationManager()->getIconPath('calendar')
            ]
        );

        return $this->arrayToHTML($htmlArray);
    }
}
