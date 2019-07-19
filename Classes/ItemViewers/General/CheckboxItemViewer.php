<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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
    protected function renderItem()
    {
        if ($this->itemConfigurationNotSet('displayasimage') || $this->getItemConfiguration('displayasimage')) {
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
            $renderIfNotChecked = ($this->getItemConfiguration('donotdisplayifnotchecked') ? '' : FlashMessages::translate('itemviewer.no'));
        }

        // Gets the value
        $value = $this->getItemConfiguration('value');

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
    protected function renderCheckedAsImage()
    {
        // Gets the image file name
        $imageFileName = $this->getItemConfiguration('checkboxselectedimage');
  
        if (empty($imageFileName)) {
            $imageFileName = 'checkboxSelected';
        } else {
            $imageFileName = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($imageFileName);
            $imageFileName = $this->getController()
            ->getQuerier()
            ->parseFieldTags($imageFileName);
        }

        // Gets the title if any
        $imageTitleKey = $this->getItemConfiguration('checkboxnotselectedtitle');
        if (empty ($imageTitleKey)) {
            $imageTitleKey ='itemviewer.checkboxSelected';
        } else {
            $imageTitleKey = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($imageTitleKey);
            $imageTitleKey = $this->getController()
            ->getQuerier()
            ->parseFieldTags($imageTitleKey);
        }
        
        // Renders the content
        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'checkboxSelected'),
                HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath($imageFileName)),
            HtmlElements::htmlAddAttribute('title',FlashMessages::translate($imageTitleKey)),
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
    protected function renderNotCheckedAsImage()
    {
        // Gets the image file name
        if ($this->getItemConfiguration('donotdisplayifnotchecked')) {
            $imageFileName = 'clear';
        } else {
            $imageFileName = $this->getItemConfiguration('checkboxnotselectedimage');
            if (empty($imageFileName)) {
                $imageFileName = 'checkboxNotSelected';
            } else {
                $imageFileName = $this->getController()
                ->getQuerier()
                ->parseLocalizationTags($imageFileName);
                $imageFileName = $this->getController()
                ->getQuerier()
                ->parseFieldTags($imageFileName);
            }
        }
    
        // Gets the title if any
        $imageTitleKey = $this->getItemConfiguration('checkboxnotselectedtitle');
        if (empty ($imageTitleKey)) {
            $imageTitleKey ='itemviewer.checkboxNotSelected';
        } else {
            $imageTitleKey = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($imageTitleKey);
            $imageTitleKey = $this->getController()
            ->getQuerier()
            ->parseFieldTags($imageTitleKey);
        }
    
        // Renders the content
        $content = HtmlElements::htmlImgElement([
                HtmlElements::htmlAddAttribute('class', 'checkboxNotSelected'),
                HtmlElements::htmlAddAttribute('src', LibraryConfigurationManager::getIconPath($imageFileName)),
            HtmlElements::htmlAddAttribute('title', FlashMessages::translate($imageTitleKey)),
            HtmlElements::htmlAddAttribute('alt', FlashMessages::translate($imageTitleKey))
            ]
        );

        return $content;
    }
}
?>
