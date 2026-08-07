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

use TYPO3\CMS\Core\Utility\PathUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Edit Checkbox item Viewer.
 *
 * @package SavLibraryPlus
 */
class CheckboxItemViewer extends AbstractItemViewer
{
    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem(): string
    {
        // Checks if it is associated with a mail
        if ($this->getItemConfigurationAttribute('mail')) {
            $content = $this->renderSingleMailCheckbox();
        } else {
            $content = $this->renderSingleCheckbox();
        }

        // Adds a DIV element
        $content = HtmlElements::htmlDivElement([
                HtmlElements::htmlAddAttribute('class', 'checkbox')
            ],
            $content
        );

        return $content;
    }

    /**
     * Gets the checked attribute.
     *
     * @return string
     */
    protected function getCheckedAttribute(): string
    {
        if ($this->getItemConfigurationAttribute('value') == 1) {
            $checked = 'checked';
        } else {
            if ($this->getItemConfigurationAttribute('uid')) {
                $checked = '';
            } else {
                $checked = ($this->getItemConfigurationAttribute('default') ? 'checked' : '');
            }
        }

        return $checked;
    }

    /**
     * Renders a single checkbox.
     *
     * @return string
     */
    protected function renderSingleCheckbox(): string
    {
        $content = '';

        // Adds the hidden input element
        $content .= HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('value', '0')
            ]
        );

        // Adds the checkbox input element
        $content .= HtmlElements::htmlInputCheckBoxElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfigurationAttribute('itemName')),
                HtmlElements::htmlAddAttribute('value', '1'),
                HtmlElements::htmlAddAttributeIfNotNull('checked', $this->getCheckedAttribute()),
                HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
            ]
        );

        return $content;
    }

    /**
     * Renders a single mail checkbox.
     *
     * @return string
     */
    protected function renderSingleMailCheckbox(): string
    {
        // Gets the value to check for mail
        $fieldForCheckMail = $this->getItemConfigurationAttribute('fieldforcheckmail');
        if (empty($fieldForCheckMail) === true) {
            FlashMessages::addError(
                'error.noAttributeInField',
                [
                    'fieldForCheckMail',
                    $this->getItemConfigurationAttribute('fieldName')
                ]
            );
            return '';
        }

        // Gets the value associated with the field
        $querier = $this->controller->getQuerier();
        $valueForChecking = $querier->getFieldValue($querier->buildFullFieldName($fieldForCheckMail));

        // Adds the image
        $libraryConfigurationManager = $this->controller->getLibraryConfigurationManager();
        if (empty($valueForChecking) === false) {
            if ($this->getItemConfigurationAttribute('value')) {
                // Adds an image element
                $iconPath = $libraryConfigurationManager->getIconPath('newMailOff');
                $src = $this->getResourceWebPath($iconPath);
                
                $content = HtmlElements::htmlImgElement([
                        HtmlElements::htmlAddAttribute('class', 'mailButton'),
                        HtmlElements::htmlAddAttribute('src', $src),
                        HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                        HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail'))
                    ]
                );
            } else {
                // Adds an input image element

                // Builds the prefix for the item name
                $extensionPrefixId = $this->controller->getExtensionPrefixId();
                $prefixForItemName = $extensionPrefixId . '[' . $this->controller->getFormName() . ']';

                $iconPath = $libraryConfigurationManager->getIconPath('newMail');
                $src = $this->getResourceWebPath($iconPath);                
                $content = HtmlElements::htmlInputImageElement([
                        HtmlElements::htmlAddAttribute('class', 'mailButton'),
                        HtmlElements::htmlAddAttribute('src', $src),
                        HtmlElements::htmlAddAttribute('name', $prefixForItemName . '[formAction][saveAndSendMail]' . $this->getItemConfigurationAttribute('itemKey')),
                        HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                        HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail')),
                    HtmlElements::htmlAddAttribute('onclick', 'return update(\'' . $this->controller->getFormName() . '\');')
                    ]
                );
            }
        } else {
            $iconPath = $libraryConfigurationManager->getIconPath('newMailOff');
            $src = $this->getResourceWebPath($iconPath);
            
            $content = HtmlElements::htmlImgElement([
                    HtmlElements::htmlAddAttribute('class', 'mailButton'),
                    HtmlElements::htmlAddAttribute('src', $src),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail'))
                ]
            );
        }

        // Adds the checkbox
        $content .= $this->renderSingleCheckbox();

        return $content;
    }
}
