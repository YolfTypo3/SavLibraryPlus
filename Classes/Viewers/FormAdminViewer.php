<?php
namespace YolfTypo3\SavLibraryPlus\Viewers;

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

use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Default Form Admin Viewer.
 *
 * @package SavLibraryPlus
 */
class FormAdminViewer extends FormViewer
{
    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'FormAdmin.html';

    /**
     * Parses the ###field[]### markers
     *
     * @param string $template
     *
     * @return string
     */
    protected function parseFieldSpecialTags($template)
    {
        // Processes the field marker
        preg_match_all('/###(?<prefix>new|show)?field\[(?<fieldName>[^\],]+)(?<separator>,?)(?<label>[^\]]*)\]###/', $template, $matches);

        foreach ($matches[0] as $matchKey => $match) {

            // Gets the full field name
            $querier = $this->getController()->getQuerier();
            $fullFieldName = $querier->buildFullFieldName($matches['fieldName'][$matchKey]);
            $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

            // Checks if the field can be edited
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['addedit'] || ($this->folderFieldsConfiguration[$cryptedFullFieldName]['addeditifadmin'] && $this->getController()
                ->getUserManager()
                ->userIsAllowedToChangeData(UriManager::getUid(), '+'))) {
                $edit = 'Edit';
                $validation = 'Validation';
            } else {
                $edit = '';
                $validation = 'NoValidation';
            }
            // Checks if a validation is forced
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['addvalidationifadmin']) {
                $validation = 'Validation';
            }

            if ($matches['separator'][$matchKey]) {

                $prefix = $matches['prefix'][$matchKey];
                if ($prefix) {
                    $validation = 'Validation';
                    $replacementString = '<div class="column1">$$$label' . $required . '[' . $matches['label'][$matchKey] . ']$$$</div>' . '<div class="column2"></div>' . '<div class="column3">###render' . ucfirst($prefix) . '[' . $matches['fieldName'][$matchKey] . ']###</div>';
                    if ($prefix == 'new') {
                        $class = ($querier->getFieldValueFromNewRow($fullFieldName) ? 'column4Different' : 'column4Same');
                        $replacementString .= '<div class="' . $class . '">###render' . $validation . '[' . $matches['fieldName'][$matchKey] . ']###</div>';
                    }
                } else {
                    $class = ($querier->getFieldValueFromCurrentRow($fullFieldName) == $querier->getFieldValueFromSavedRow($fullFieldName) ? 'column4Same' : 'column4Different');
                    $replacementString = '<div class="column1">$$$label[' . $matches['label'][$matchKey] . ']$$$</div>' . '<div class="column2">###renderSaved[' . $matches['fieldName'][$matchKey] . ']###</div>' . '<div class="column3">###render' . $edit . '[' . $matches['fieldName'][$matchKey] . ']###</div>' . '<div class="' . $class . '">###render' . $validation . '[' . $matches['fieldName'][$matchKey] . ']###</div>';
                }
            } else {
                $replacementString = '###render' . $edit . '[' . $matches['fieldName'][$matchKey] . ']###' . '###render' . $validation . '[' . $matches['fieldName'][$matchKey] . ']###';
            }
            $template = str_replace($matches[0][$matchKey], $replacementString, $template);
        }
        return $template;
    }
}
?>
