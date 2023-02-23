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

namespace YolfTypo3\SavLibraryPlus\Managers;

/**
 * TCA configuration manager
 *
 * @package SavLibraryPlus

 */
class TcaConfigurationManager extends AbstractManager
{
    /**
     * Gets the TCA field label.
     *
     * @param string $tableName
     * @param string $fieldName
     *
     * @return string
     */
    public static function getTcaFieldLabel($tableName, $fieldName)
    {
        return (self::getTypoScriptFrontendController()->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']));
    }

    /**
     * Gets the TCA ctrl for a given field in a table.
     *
     * @param string $tableName
     * @param string $fieldName
     *
     * @return string
     */
    public static function getTcaCtrlField($tableName, $fieldName)
    {
        if (isset($GLOBALS['TCA'][$tableName]['ctrl'][$fieldName])) {
            return $GLOBALS['TCA'][$tableName]['ctrl'][$fieldName];
        } else {
            return null;
        }
    }

    /**
     * Gets the ctrl language field.
     *
     * @param string $tableName
     *
     * @return boolean
     */
    public static function getTcaCtrlLanguageField($tableName)
    {
        return self::getTcaCtrlField($tableName, 'languageField');
    }

    /**
     * Checks if the table is localized.
     *
     * @param string $tableName
     *
     * @return boolean
     */
    public static function isLocalized($tableName)
    {
        $languageField = self::getTcaCtrlLanguageField($tableName);
        return ! empty($languageField);
    }

    /**
     * Gets the TCA columns a given table.
     *
     * @param string $tableName
     *
     * @return string
     */
    public static function getTcaColumns($tableName)
    {
        return $GLOBALS['TCA'][$tableName]['columns'];
    }

    /**
     * Gets the TCA config for a given field in a table.
     *
     * @param string $tableName
     * @param string $fieldName
     *
     * @return array
     */
    public static function getTcaConfigField($tableName, $fieldName)
    {
        $config = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
        return (is_array($config) ? $config : []);
    }

    /**
     * Gets the TCA config for a full field name.
     *
     * @param string $fullFieldName
     *
     * @return array
     */
    public static function getTcaConfigFieldFromFullFieldName($fullFieldName)
    {
        $fieldNameParts = explode('.', $fullFieldName);
        return self::getTcaConfigField($fieldNameParts[0], $fieldNameParts[1]);
    }

    /**
     * Gets the TCA ORDER BY clause for the table.
     * It iseither the TCA default_sortby or sortby control field.
     *
     * @param string $tableName
     *
     * @return array
     */
    public static function getTcaOrderByClause($tableName)
    {
        $defaultSortBy = self::getTcaCtrlField($tableName, 'default_sortby');
        if (! empty($defaultSortBy)) {
            if (strpos($defaultSortBy, 'ORDER BY') !== false) {
                // for compatibility with previous version of SAV Library Kickstarter
                $defaultSortBy = str_replace('ORDER BY ', '', $defaultSortBy);
            } else {
                // Adds the table name
                $defaultSortBy = $tableName . '.' . $defaultSortBy;
            }
            return $defaultSortBy;
        } else {
            $sortBy = self::getTcaCtrlField($tableName, 'sortby');
            if (empty($sortBy) === false) {
                return $sortBy;
            } else {
                return '';
            }
        }
    }

    /**
     * Builds a basic configuration from the TCA.
     *
     * @param array $fullFieldName
     *            The full field name
     *
     *            return array The basic field configuration
     */
    public static function buildBasicConfigurationFromTCA($fullFieldName)
    {
        $fullFieldNameParts = explode('.', $fullFieldName);

        if (count($fullFieldNameParts) == 1) {
            return [];
        }

        // Gets the field configuration from the TCA
        $fieldConfiguration = self::getTcaConfigFieldFromFullFieldName($fullFieldName);

        // Builds the type
        switch ($fieldConfiguration['type']) {
            case 'input':
                $fieldType = 'String';
                break;
            case 'check':
                $fieldType = 'Checkbox';
                break;
            case 'check_4':
            case 'check_10':
                $fieldType = 'Checkboxes';
                break;
            case 'date':
                $fieldType = 'Date';
                break;
            case 'datetime':
                $fieldType = 'DateTime';
                break;
            case 'files':
                $fieldType = 'Files';
                break;
            case 'integer':
                $fieldType = 'Integer';
                break;
            case 'graph':
                $fieldType = 'Graph';
                break;
            case 'link':
                $fieldType = 'Link';
                break;
            case 'radio':
                $fieldType = 'RadioButtons';
                break;
            case 'rel':
                if ($fieldConfiguration['conf_rel_type'] == 'group') {
                    $fieldType = 'RelationManyToManyAsSubform';
                } elseif ($fieldConfiguration['conf_relations'] > 1) {
                    $fieldType = 'RelationManyToManyAsDoubleSelectorbox';
                } else {
                    $fieldType = 'RelationOneToManyAsSelectorbox';
                }
                break;
            case 'select':
                if (! empty($fieldConfiguration['foreign_table'])) {
                    $fieldType = 'RelationOneToManyAsSelectorbox';
                } else {
                    $fieldType = 'Selectorbox';
                }
                break;
            case 'ShowOnly':
                $fieldType = 'ShowOnly';
                break;
            case 'textarea':
                $fieldType = 'Text';
                break;
            case 'textarea_rte':
                $fieldType = 'RichTextEditor';
                break;
            default:
                $fieldType = 'Unknown';
                break;
        }

        $fieldConfiguration = array_merge(
            $fieldConfiguration,
            [
                'tableName' => $fullFieldNameParts[0],
                'fieldName' => $fullFieldNameParts[1],
                'fieldType' => $fieldType
            ]
        );

        return $fieldConfiguration;
    }
}
