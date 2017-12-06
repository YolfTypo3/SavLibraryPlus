<?php
namespace YolfTypo3\SavLibraryPlus\Utility;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy <yolf.typo3@orange.fr>
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

/**
 * Html elements
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class HtmlElements
{

    /**
     * Adds a HTML attribute
     *
     * @param string $attributeName
     * @param string $attributeValue
     *
     * @return string
     */
    public static function htmlAddAttribute($attributeName, $attributeValue)
    {
        return $attributeName . '="' . $attributeValue . '"';
    }

    /**
     * Adds a HTML attribute if not null
     *
     * @param string $attributeName
     * @param string $attributeValue
     *
     * @return string
     */
    public static function htmlAddAttributeIfNotNull($attributeName, $attributeValue)
    {
        return ($attributeValue ? $attributeName . '="' . $attributeValue . '"' : '');
    }

    /**
     * Removes null items in the attributes array
     *
     * @param array $attributes
     *
     * @return array
     */
    public static function htmlCleanAttributesArray($attributes)
    {
        return array_diff($attributes, array(
            ''
        ));
    }

    /**
     * Returns a HTML INPUT Text Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputTextElement($attributes)
    {
        return '<input type="text" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT Password Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputPasswordElement($attributes)
    {
        return '<input type="password" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT Hidden Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputHiddenElement($attributes)
    {
        return '<input type="hidden" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT File Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputFileElement($attributes)
    {
        return '<input type="file" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT Checkbox Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputCheckboxElement($attributes)
    {
        return '<input type="checkbox" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT Radio Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputRadioElement($attributes)
    {
        return '<input type="radio" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT Image Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputImageElement($attributes)
    {
        return '<input type="image" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML INPUT Submit Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputSubmitElement($attributes)
    {
        return '<input type="submit" ' . implode(' ', self::htmlCleanAttributesArray($attributes)) . ' />';
    }

    /**
     * Returns a HTML BR Element
     *
     * @paramarray $attributes
     *
     * @return string
     */
    public static function htmlBrElement($attributes)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<br' . ($attributesString ? ' ' . $attributesString : '') . ' />';
    }

    /**
     * Returns a HTML SPAN Element
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public static function htmlSpanElement($attributes, $content)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<span' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</span>';
    }

    /**
     * Returns a HTML DIV Element
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public static function htmlDivElement($attributes, $content)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<div' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</div>';
    }

    /**
     * Returns a HTML OPTION Element
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public static function htmlOptionElement($attributes, $content)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<option' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</option>';
    }

    /**
     * Returns a HTML SELECT Element
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public static function htmlSelectElement($attributes, $content)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<select' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</select>';
    }

    /**
     * Returns a HTML IFRAME Element
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public static function htmlIframeElement($attributes, $content)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<iframe' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</iframe>';
    }

    /**
     * Returns a HTML IMG Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlImgElement($attributes)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<img' . ($attributesString ? ' ' . $attributesString : '') . ' />';
    }

    /**
     * Returns a HTML TEXTAREA Element
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public static function htmlTextareaElement($attributes, $content)
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<textarea' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</textarea>';
    }
}
?>
