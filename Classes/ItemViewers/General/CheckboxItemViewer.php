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
use TYPO3\CMS\Core\Utility\PathUtility;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * General Checkbox item Viewer.
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
        if ($this->itemConfigurationAttributeNotSet('displayasimage') || $this->getItemConfigurationAttribute('displayasimage')) {
            $renderIfChecked = HtmlElements::htmlDivElement([
                    HtmlElements::htmlAddAttribute('class', 'checkbox')
                ],
                $this->renderCheckedAsImage()
            );
            $renderIfNotChecked = HtmlElements::htmlDivElement([
                    HtmlElements::htmlAddAttribute('class', 'checkbox')
                ],
                $this->renderNotCheckedAsImage()
            );
        } else {
            $renderIfChecked = FlashMessages::translate('itemviewer.yes');
            $renderIfNotChecked = ($this->getItemConfigurationAttribute('donotdisplayifnotchecked') ? '' : FlashMessages::translate('itemviewer.no'));
        }

        // Gets the value
        $value = $this->getItemConfigurationAttribute('value');

        if (empty($value)) {
            return $renderIfNotChecked;
        } else {
            return $renderIfChecked;
        }
    }

    /**
     * Renders a checked checkbox as an image.
     *
     * @return string
     */
    protected function renderCheckedAsImage(): string
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfigurationAttribute('checkboxselectedimage');

        if (empty($imageFileName)) {
            $imageFileName = 'checkboxSelected';
        } else {
            $imageFileName = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($imageFileName);
            $imageFileName = $this->controller
            ->getQuerier()
            ->parseFieldTags($imageFileName);
        }

        // Gets the title if any
        $imageTitleKey = $this->getItemConfigurationAttribute('checkboxnotselectedtitle');
        if (empty ($imageTitleKey)) {
            $imageTitleKey ='itemviewer.checkboxSelected';
        } else {
            $imageTitleKey = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($imageTitleKey);
            $imageTitleKey = $this->controller
            ->getQuerier()
            ->parseFieldTags($imageTitleKey);
        }

        // Renders the content
        $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath($imageFileName);
        $src = $this->getResourceWebPath($iconPath);
        
        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'checkboxSelected'),
                HtmlElements::htmlAddAttribute('src', $src),
                HtmlElements::htmlAddAttribute('title', FlashMessages::translate($imageTitleKey)),
                HtmlElements::htmlAddAttribute('alt', FlashMessages::translate($imageTitleKey))
            ]
        );

        return $content;
    }

    /**
     * Renders a unchecked checkbox as an image.
     *
     * @return string
     */
    protected function renderNotCheckedAsImage(): string
    {
        // Gets the image file name
        if ($this->getItemConfigurationAttribute('donotdisplayifnotchecked')) {
            $imageFileName = 'clear';
        } else {
            $imageFileName = $this->getItemConfigurationAttribute('checkboxnotselectedimage');
            if (empty($imageFileName)) {
                $imageFileName = 'checkboxNotSelected';
            } else {
                $imageFileName = $this->controller
                ->getQuerier()
                ->parseLocalizationTags($imageFileName);
                $imageFileName = $this->controller
                ->getQuerier()
                ->parseFieldTags($imageFileName);
            }
        }

        // Gets the title if any
        $imageTitleKey = $this->getItemConfigurationAttribute('checkboxnotselectedtitle');
        if (empty ($imageTitleKey)) {
            $imageTitleKey ='itemviewer.checkboxNotSelected';
        } else {
            $imageTitleKey = $this->controller
            ->getQuerier()
            ->parseLocalizationTags($imageTitleKey);
            $imageTitleKey = $this->controller
            ->getQuerier()
            ->parseFieldTags($imageTitleKey);
        }

        // Renders the content
        $iconPath = $this->controller->getLibraryConfigurationManager()->getIconPath($imageFileName);
        $src = $this->getResourceWebPath($iconPath);
        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'checkboxNotSelected'),
                HtmlElements::htmlAddAttribute('src', $src),
                HtmlElements::htmlAddAttribute('title', FlashMessages::translate($imageTitleKey)),
                HtmlElements::htmlAddAttribute('alt', FlashMessages::translate($imageTitleKey))
            ]
        );

        return $content;
    }
}
