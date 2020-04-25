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

use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

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
    protected function renderItem()
    {
        // Checks if it is associated with a mail
        if ($this->getItemConfiguration('mail')) {
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
    protected function getCheckedAttribute()
    {
        if ($this->getItemConfiguration('value') == 1) {
            $checked = 'checked';
        } else {
            if ($this->getItemConfiguration('uid')) {
                $checked = '';
            } else {
                $checked = ($this->getItemConfiguration('default') ? 'checked' : '');
            }
        }

        return $checked;
    }

    /**
     * Renders a single checkbox.
     *
     * @return string
     */
    protected function renderSingleCheckbox()
    {
        $content = '';

        // Adds the hidden input element
        $content .= HtmlElements::htmlInputHiddenElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
                HtmlElements::htmlAddAttribute('value', '0')
            ]
        );

        // Adds the checkbox input element
        $content .= HtmlElements::htmlInputCheckBoxElement([
                HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
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
    protected function renderSingleMailCheckbox()
    {
        // Gets the value to check for mail
        $fieldForCheckMail = $this->getItemConfiguration('fieldforcheckmail');
        if (empty($fieldForCheckMail) === true) {
            FlashMessages::addError(
                'error.noAttributeInField',
                [
                    'fieldForCheckMail',
                    $this->getItemConfiguration('fieldName')
                ]
            );
            return '';
        }

        // Gets the value associated with the field
        $querier = $this->getController()->getQuerier();
        $valueForChecking = $querier->getFieldValue($querier->buildFullFieldName($fieldForCheckMail));

        // Adds the image
        if (empty($valueForChecking) === false) {
            if ($this->getItemConfiguration('value')) {
                // Adds an image element
                $content = HtmlElements::htmlImgElement([
                        HtmlElements::htmlAddAttribute('class', 'mailButton'),
                        HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('newMailOff')),
                        HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                        HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail'))
                    ]
                );
            } else {
                // Adds an input image element

                // Builds the prefix for the item name
                $extensionPrefixId = $this->getController()->getExtensionConfigurationManager()->getExtensionPrefixId();
                $prefixForItemName = $extensionPrefixId . '[' . AbstractController::getFormName() . ']';

                $content = HtmlElements::htmlInputImageElement([
                        HtmlElements::htmlAddAttribute('class', 'mailButton'),
                        HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('newMail')),
                        HtmlElements::htmlAddAttribute('name', $prefixForItemName . '[formAction][saveAndSendMail]' . $this->getItemConfiguration('itemKey')),
                        HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                        HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail')),
                        HtmlElements::htmlAddAttribute('onclick', 'return update(\'' . AbstractController::getFormName() . '\');')
                    ]
                );
            }
        } else {
            $content = HtmlElements::htmlImgElement([
                    HtmlElements::htmlAddAttribute('class', 'mailButton'),
                    HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('newMailOff')),
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
?>
