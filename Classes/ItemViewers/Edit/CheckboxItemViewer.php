<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\Edit;

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

use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\LibraryConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * Edit Checkbox item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
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
        $content = HtmlElements::htmlDivElement(array(
            HtmlElements::htmlAddAttribute('class', 'checkbox')
        ), $content);

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
        $content .= HtmlElements::htmlInputHiddenElement(array(
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
            HtmlElements::htmlAddAttribute('value', '0')
        ));

        // Adds the checkbox input element
        $content .= HtmlElements::htmlInputCheckBoxElement(array(
            HtmlElements::htmlAddAttribute('name', $this->getItemConfiguration('itemName')),
            HtmlElements::htmlAddAttribute('value', '1'),
            HtmlElements::htmlAddAttributeIfNotNull('checked', $this->getCheckedAttribute()),
            HtmlElements::htmlAddAttribute('onchange', 'document.changed=1;')
        ));

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
        if (empty($fieldForCheckMail) === TRUE) {
            FlashMessages::addError('error.noAttributeInField', array(
                'fieldForCheckMail',
                $this->getItemConfiguration('fieldName')
            ));
            return '';
        }

        // Gets the value associated with the field
        $querier = $this->getController()->getQuerier();
        $valueForChecking = $querier->getFieldValue($querier->buildFullFieldName($fieldForCheckMail));

        // Adds the image
        if (empty($valueForChecking) === FALSE) {
            if ($this->getItemConfiguration('value')) {
                // Adds an image element
                $content = HtmlElements::htmlImgElement(array(
                    HtmlElements::htmlAddAttribute('class', 'mailButton'),
                    HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('newMailOff')),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail'))
                ));
            } else {
                // Adds an input image element
                $content = HtmlElements::htmlInputImageElement(array(
                    HtmlElements::htmlAddAttribute('class', 'mailButton'),
                    HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('newMail')),
                    HtmlElements::htmlAddAttribute('name', AbstractController::getFormName() . '[formAction][saveAndSendMail][' . $this->getCryptedFullFieldName() . ']'),
                    HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                    HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail')),
                    HtmlElements::htmlAddAttribute('onclick', 'return update(\'' . AbstractController::getFormName() . '\');')
                ));
            }
        } else {
            $content = HtmlElements::htmlImgElement(array(
                HtmlElements::htmlAddAttribute('class', 'mailButton'),
                HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath('newMailOff')),
                HtmlElements::htmlAddAttribute('title', FlashMessages::translate('button.mail')),
                HtmlElements::htmlAddAttribute('alt', FlashMessages::translate('button.mail'))
            ));
        }

        // Adds the checkbox
        $content .= $this->renderSingleCheckbox();

        return $content;
    }
}
?>
