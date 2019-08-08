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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Managers\TemplateConfigurationManager;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;

/**
 * Default Form Viewer.
 *
 * @package SavLibraryPlus
 */
class FormViewer extends AbstractViewer
{
    /**
     * Item viewer directory
     *
     * @var string
     */
    protected $itemViewerDirectory = 'Edit';

    /**
     * Edit mode flag
     *
     * @var boolean
     */
    protected $inEditMode = false;

    /**
     * The template file
     *
     * @var string
     */
    protected $templateFile = 'Form.html';

    /**
     * The view type
     *
     * @var string
     */
    protected $viewType = 'FormView';

    /**
     * The query configuration manager
     *
     * @var \YolfTypo3\SavLibraryPlus\Managers\QueryConfigurationManager
     */
    protected $queryConfigurationManager;

    /**
     * The current processed row
     *
     * @var array
     */
    protected $row;

    /**
     * Checks if the view can be rendered
     *
     * @return boolean
     */
    public function viewCanBeRendered()
    {
        // Gets the library configuration manager
        $libraryConfigurationManager = $this->getController()->getLibraryConfigurationManager();

        // Gets the view configuration
        $viewIdentifier = $libraryConfigurationManager->getViewIdentifier('formView');

        $result = (empty($viewIdentifier) ? false : true);

        return $result;
    }

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render()
    {
        // Sets the library view configuration
        $this->setLibraryViewConfiguration();

        // Sets the active folder Key
        $this->setActiveFolderKey();

        // Creates the template configuration manager
        $templateConfigurationManager = GeneralUtility::makeInstance(TemplateConfigurationManager::class);
        $templateConfigurationManager->injectTemplateConfiguration($this->getLibraryConfigurationManager()
            ->getFormViewTemplateConfiguration());

        // Creates the field configuration manager
        $this->createFieldConfigurationManager();

        // Gets the item template
        $itemTemplate = $templateConfigurationManager->getItemTemplate();

        // Processes the rows
        $rows = $this->getController()
            ->getQuerier()
            ->getRows();

        $fields = [];
        foreach ($rows as $rowKey => $row) {

            $this->getController()
                ->getQuerier()
                ->setCurrentRowId($rowKey);

            // Gets the fields configuration for the folder
            $this->folderFieldsConfiguration = $this->getFieldConfigurationManager()->getFolderFieldsConfiguration($this->getActiveFolder());
            $listItemConfiguration = [
                'template' => $this->parseItemTemplate($itemTemplate),
                'uid' => $row['uid']
            ];

            $fields[] = $listItemConfiguration;
        }

        // Adds the fields configuration
        $this->addToViewConfiguration('fields', $fields);

        // Adds information to the view configuration
        $this->addToViewConfiguration(
            'general',
            [
                'extensionKey' => $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getExtensionKey(),
                'helpPage' => $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getHelpPageForListView(),
                'addPrintIcon' => $this->getActiveFolderField('addPrintIcon'),
                'formName' => AbstractController::getFormName(),
                'uid' => UriManager::getUid(),
                'title' => $this->processTitle($this->parseTitle($this->getActiveFolderTitle()))
            ]
        );

        return $this->renderView();
    }

    /**
     * Parses the item template
     *
     * @param string $itemTemplate
     *            The item template
     *
     * @return string The parsed item template
     */
    protected function parseItemTemplate($itemTemplate)
    {
        // Parses the field marker
        $itemTemplate = $this->parseFieldSpecialTags($itemTemplate);

        // Adds the required flag if needed
        $itemTemplate = $this->addRequiredFlag($itemTemplate);

        // Parses the buttons if any
        $itemTemplate = $this->parseButtonSpecialTags($itemTemplate);

        // Processes the rendering of the item
        $itemTemplate = $this->parseRenderTags($itemTemplate);

        // Parses localization tags
        $itemTemplate = $this->getController()
            ->getQuerier()
            ->parseLocalizationTags($itemTemplate, false);
        $itemTemplate = $this->getController()
            ->getQuerier()
            ->parseFieldTags($itemTemplate, false);

        return $itemTemplate;
    }

    /**
     * Parses the ###field[]### markers
     *
     * @param string $template
     *
     * @return string
     */
    protected function parseFieldSpecialTags($template)
    {
        // Checks if the value must be parsed
        if (strpos($template, '#') === false) {
            return $template;
        }

        // Processes the field marker
        preg_match_all('/###(?<prefix>new|show)?field\[(?<fieldName>[^\],]+)(?<separator>,?)(?<label>[^\]]*)\]###/', $template, $matches);

        foreach ($matches[0] as $matchKey => $match) {

            // Gets the crypted full field name
            $fullFieldName = $this->getController()
                ->getQuerier()
                ->buildFullFieldName($matches['fieldName'][$matchKey]);
            $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

            // Removes the field if not in admin mode
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['addeditifadmin'] && ! $this->getController()
                ->getUserManager()
                ->userIsAllowedToChangeData(UriManager::getUid(), '+')) {
                $template = str_replace($matches[0][$matchKey], '', $template);
                continue;
            }

            // Checks if the field can be edited
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['addedit']) {
                $edit = 'Edit';
            } else {
                $edit = '';
            }

            // Processes the errors
            if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['required'] && $this->folderFieldsConfiguration[$cryptedFullFieldName]['error']) {
                $error = ' error';
            } else {
                $error = '';
            }

            // Processes the field
            if ($matches['separator'][$matchKey]) {
                // Checks if required is needed
                if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['required']) {
                    $required = 'Required';
                } else {
                    $required = '';
                }

                $prefix = $matches['prefix'][$matchKey];
                if ($prefix) {
                    $replacementString = '<div class="column1' . $error . '">$$$label' . $required . '[' . $matches['label'][$matchKey] . ']$$$</div>' . '<div class="column2"></div>' . '<div class="column3">###render' . ucfirst($prefix) . '[' . $matches['fieldName'][$matchKey] . ']###</div>';
                } else {
                    $replacementString = '<div class="column1' . $error . '">$$$label' . $required . '[' . $matches['label'][$matchKey] . ']$$$</div>' . '<div class="column2">###renderSaved[' . $matches['fieldName'][$matchKey] . ']###</div>' . '<div class="column3">###render' . $edit . '[' . $matches['fieldName'][$matchKey] . ']###</div>';
                }
            } else {
                $replacementString = '###render' . $edit . '[' . $matches['fieldName'][$matchKey] . ']###';
            }
            $template = str_replace($matches[0][$matchKey], $replacementString, $template);
        }
        return $template;
    }

    /**
     * Parses ###button[]### markers
     *
     * @param string $template
     *
     * @return string
     */
    protected function parseButtonSpecialTags($template)
    {
        // Processes the buttons if needed
        preg_match_all('/###button\[([^\]]+)\]###/', $template, $matches);

        foreach ($matches[0] as $matchKey => $match) {
            $functionName = $matches[1][$matchKey] . 'Button';
            if (method_exists($this, $functionName)) {
                $template = str_replace($matches[0][$matchKey], $this->$functionName(), $template);
            }
        }
        return $template;
    }

    /**
     * Parses ###render[]###, ###renderEdit[]###, ###renderValidation[]### markers
     *
     * @param string $template
     *
     * @return string
     */
    protected function parseRenderTags($template)
    {
        // Processes the render marker
        preg_match_all('/###render(?<type>Edit|New|Show|Saved|Validation|NoValidation)?\[(?<fieldName>[^#]+)\]###/', $template, $matches);

        foreach ($matches[0] as $matchKey => $match) {

            // Builds the crypted full field name
            $fullFieldName = $this->getController()
                ->getQuerier()
                ->buildFullFieldName($matches['fieldName'][$matchKey]);
            $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

            if (empty($this->folderFieldsConfiguration[$cryptedFullFieldName])) {
                FlashMessages::addError(
                    'error.unknownFieldName',
                    [
                        $fullFieldName
                    ]
                );
                continue;
            }

            // Adds the item name
            if ($matches['type'][$matchKey] == 'New') {
                $uid = 0;
            } else {
                $uid = $this->getController()
                    ->getQuerier()
                    ->getFieldValueFromCurrentRow('uid');
            }
            $itemName = AbstractController::getFormName() . '[' . $cryptedFullFieldName . '][' . intval($uid) . ']';
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['itemName'] = $itemName;

            // Sets the default rendering
            $this->folderFieldsConfiguration[$cryptedFullFieldName]['edit'] = '0';

            switch ($matches['type'][$matchKey]) {
                case 'Edit':
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['edit'] = '1';
                    $replacementString = $this->renderItem($cryptedFullFieldName);
                    break;
                case 'New':
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['edit'] = '1';
                    $previousValue = $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'];
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = $this->getController()
                        ->getQuerier()
                        ->getFieldValueFromNewRow($fullFieldName);
                    $replacementString = $this->renderItem($cryptedFullFieldName);
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = $previousValue;
                    break;
                case 'Saved':
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['edit'] = '0';
                    $previousValue = $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'];
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = $this->getController()
                        ->getQuerier()
                        ->getFieldValueFromSavedRow($fullFieldName);
                    $replacementString = $this->renderItem($cryptedFullFieldName);
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['value'] = $previousValue;
                    break;
                case 'Show':
                case '':
                    $this->folderFieldsConfiguration[$cryptedFullFieldName]['edit'] = '0';
                    $replacementString = $this->renderItem($cryptedFullFieldName);
                    break;
                case 'Validation':
                    // If a validation is forced and addEdit is not set, adds a hidden field such that the configuration can be processed when saving
                    if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['addvalidationifadmin'] && (! $this->folderFieldsConfiguration[$cryptedFullFieldName]['addedit'] || ! $this->folderFieldsConfiguration[$cryptedFullFieldName]['addeditifadmin'])) {
                        $checkboxName = AbstractController::getFormName() . '[' . $cryptedFullFieldName . '][' . $uid . ']';
                        $hiddenElement = HtmlElements::htmlInputHiddenElement([
                                HtmlElements::htmlAddAttribute('name', $checkboxName),
                                HtmlElements::htmlAddAttribute('value', '0')
                            ]
                        );
                    } else {
                        $hiddenElement = '';
                    }

                    // Adds the hidden element for validation
                    $checkboxName = AbstractController::getFormName() . '[validation][' . $cryptedFullFieldName . ']';
                    $hiddenElement .= HtmlElements::htmlInputHiddenElement([
                            HtmlElements::htmlAddAttribute('name', $checkboxName),
                            HtmlElements::htmlAddAttribute('value', '0')
                        ]
                    );

                    // Sets the checked attribute
                    $fieldValidation = $this->getController()
                        ->getQuerier()
                        ->getFieldValidation($cryptedFullFieldName);
                    if ($fieldValidation !== null) {
                        $checked = $fieldValidation;
                    } else {
                        $checked = $this->folderFieldsConfiguration[$cryptedFullFieldName]['checkedinupdateformadmin'];
                    }

                    // Adds the checkbox element
                    $checkboxElement = HtmlElements::htmlInputCheckBoxElement([
                            HtmlElements::htmlAddAttribute('name', $checkboxName),
                            HtmlElements::htmlAddAttribute('value', '1'),
                            HtmlElements::htmlAddAttributeIfNotNull('checked', $checked)
                        ]
                    );
                    $replacementString = $hiddenElement . $checkboxElement;
                    break;
                case 'NoValidation':
                    $replacementString = '';
                    break;
            }

            // Renders the item
            $template = str_replace($matches[0][$matchKey], $replacementString, $template);
        }

        return $template;
    }

    /**
     * Adds the required flag
     *
     * @param string $template
     *
     * @return string
     */
    protected function addRequiredFlag($template)
    {
        preg_match_all('/\$\$\$label(Required)?\[([^\]]+)\]\$\$\$/', $template, $matches);

        foreach ($matches[0] as $matchKey => $match) {
            // Checks if labelRequired is set
            if ($matches[1][$matchKey]) {
                $template = str_replace(
                    $matches[0][$matchKey],
                    str_replace(
                        'labelRequired',
                        'label',
                        $matches[0][$matchKey]) . HtmlElements::htmlSpanElement([
                                HtmlElements::htmlAddAttribute('class', 'required')
                            ],
                            FlashMessages::translate('formView.required')
                         ),
                    $template
                );
            } else {
                // Builds the crypted full field name
                $fullFieldName = $this->getController()
                    ->getQuerier()
                    ->buildFullFieldName($matches[2][$matchKey]);
                $cryptedFullFieldName = AbstractController::cryptTag($fullFieldName);

                if ($this->folderFieldsConfiguration[$cryptedFullFieldName]['required']) {
                    $template = str_replace(
                        $matches[0][$matchKey],
                        $matches[0][$matchKey] . HtmlElements::htmlSpanElement([
                                HtmlElements::htmlAddAttribute('class', 'required')
                            ],
                            FlashMessages::translate('formView.required')
                        ),
                        $template
                    );
                }
            }
        }
        return $template;
    }

    /**
     * Parses the item template
     *
     * @param string $title
     *            The title
     *
     * @return string The parsed title
     */
    protected function parseTitle($title)
    {
        return $title;
    }

    /**
     * Generates a Submit Form button
     *
     * @return string (Submit Form button)
     */
    protected function submitButton()
    {
        $content = HtmlElements::htmlInputSubmitElement([
            HtmlElements::htmlAddAttribute('class', 'submitButton'),
                HtmlElements::htmlAddAttribute('value', FlashMessages::translate('button.submit')),
                HtmlElements::htmlAddAttribute('onclick', 'update(\'' . AbstractController::getFormName() . '\');')
            ]
        );

        return $content;
    }
}
?>
