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

namespace YolfTypo3\SavLibraryPlus\Utility;

/**
 * Html elements
 *
 * @package SavLibraryPlus
 */
final class HtmlElements
{
    /**
     * Adds a HTML attribute
     *
     * @param string $attributeName
     * @param mixed $attributeValue
     *
     * @return string
     */
    public static function htmlAddAttribute(string $attributeName, mixed $attributeValue): string
    {
        return $attributeName . '="' . $attributeValue . '"';
    }

    /**
     * Adds a HTML attribute if not null
     *
     * @param string $attributeName
     * @param mixed $attributeValue
     *
     * @return string
     */
    public static function htmlAddAttributeIfNotNull(string $attributeName, mixed $attributeValue): string
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
    public static function htmlCleanAttributesArray(array $attributes): array
    {
        return array_diff(
            $attributes,
            [
                ''
            ]
        );
    }

    /**
     * Returns a HTML INPUT Text Element
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function htmlInputTextElement(array $attributes): string
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
    public static function htmlInputPasswordElement(array $attributes): string
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
    public static function htmlInputHiddenElement(array $attributes): string
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
    public static function htmlInputFileElement(array $attributes): string
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
    public static function htmlInputCheckboxElement(array $attributes): string
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
    public static function htmlInputRadioElement(array $attributes): string
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
    public static function htmlInputImageElement(array $attributes): string
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
    public static function htmlInputSubmitElement(array $attributes): string
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
    public static function htmlBrElement(array $attributes): string
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
    public static function htmlSpanElement(array $attributes, string $content): string
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
    public static function htmlDivElement(array $attributes, string $content): string
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
    public static function htmlOptionElement(array $attributes, string $content): string
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
    public static function htmlSelectElement(array $attributes, string $content): string
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<select' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</select>';
    }

    /**
     * Returns a HTML IFRAME Element
     *
     * @param array $attributes
     * @param string|null $content
     *
     * @return string
     */
    public static function htmlIframeElement(array $attributes, ?string $content): string
    {
        $content = $content ?? '';
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
    public static function htmlImgElement(array $attributes): string
    {
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<img' . ($attributesString ? ' ' . $attributesString : '') . ' />';
    }

    /**
     * Returns a HTML TEXTAREA Element
     *
     * @param array $attributes
     * @param string|null $content
     *
     * @return string
     */
    public static function htmlTextareaElement(array $attributes, ?string $content): string
    {
        $content = $content ?? '';
        $attributesString = implode(' ', self::htmlCleanAttributesArray($attributes));
        return '<textarea' . ($attributesString ? ' ' . $attributesString : '') . '>' . $content . '</textarea>';
    }
}
