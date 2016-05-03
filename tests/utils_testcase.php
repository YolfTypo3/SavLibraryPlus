<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Yolf (Laurent Foulloy) <yolf.typo3@orange.fr>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
require_once (dirname(__FILE__) . '/../class.utils.php');

class utils_testcase extends tx_phpunit_testcase
{

    public $fixture;

    public function setUp()
    {
        $this->fixture = new utils();
    }

    public function test_addAttribute()
    {
        $this->assertNotEquals('', $this->fixture->htmlAddAttribute('', ''));
        $this->assertNotEquals('', $this->fixture->htmlAddAttribute('class', ''));
        $this->assertEquals('class="test"', $this->fixture->htmlAddAttribute('class', 'test'));
    }

    public function test_addAttributeIfNotNull()
    {
        $this->assertEquals('', $this->fixture->htmlAddAttributeIfNotNull('', ''));
        $this->assertEquals('', $this->fixture->htmlAddAttributeIfNotNull('class', ''));
        $this->assertEquals('class="test"', $this->fixture->htmlAddAttributeIfNotNull('class', 'test'));
    }

    public function test_cleanAttributesArray()
    {
        $this->assertEquals(array(), array_values($this->fixture->htmlCleanAttributesArray(array(
            '',
            '',
            '',
            ''
        ))));
        $this->assertEquals(array(
            'a',
            'b',
            'c'
        ), array_values($this->fixture->htmlCleanAttributesArray(array(
            'a',
            '',
            'b',
            '',
            '',
            'c'
        ))));
    }

    public function test_htmlInputTextElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('value', '0')
        );
        $this->assertEquals('<input type="text" name="test" value="0" />', $this->fixture->htmlInputTextElement($attributes));
    }

    public function test_htmlInputPasswordElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('value', '')
        );
        $this->assertEquals('<input type="password" name="test" value="" />', $this->fixture->htmlInputPasswordElement($attributes));
    }

    public function test_htmlInputHiddenElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('value', '0')
        );
        $this->assertEquals('<input type="hidden" name="test" value="0" />', $this->fixture->htmlInputHiddenElement($attributes));
    }

    public function test_htmlInputFileElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('value', '0')
        );
        $this->assertEquals('<input type="file" name="test" value="0" />', $this->fixture->htmlInputFileElement($attributes));
    }

    public function test_htmlInputCheckboxElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('value', '0')
        );
        $this->assertEquals('<input type="checkbox" name="test" value="0" />', $this->fixture->htmlInputCheckboxElement($attributes));
        $attributes = array_merge($attributes, array(
            $this->fixture->htmlAddAttribute('checked', 'checked')
        ));
        $this->assertEquals('<input type="checkbox" name="test" value="0" checked="checked" />', $this->fixture->htmlInputCheckboxElement($attributes));
    }

    public function test_htmlInputRadioElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('value', '0')
        );
        $this->assertEquals('<input type="radio" name="test" value="0" />', $this->fixture->htmlInputRadioElement($attributes));
        $attributes = array_merge($attributes, array(
            $this->fixture->htmlAddAttribute('checked', 'checked')
        ));
        $this->assertEquals('<input type="radio" name="test" value="0" checked="checked" />', $this->fixture->htmlInputRadioElement($attributes));
    }

    public function test_htmlInputImageElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('name', 'test'),
            $this->fixture->htmlAddAttribute('src', 'test.jpg')
        );
        $this->assertEquals('<input type="image" name="test" src="test.jpg" />', $this->fixture->htmlInputImageElement($attributes));
    }

    public function test_htmlInputSubmitElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('class', 'test'),
            $this->fixture->htmlAddAttribute('value', 'test')
        );
        $this->assertEquals('<input type="submit" class="test" value="test" />', $this->fixture->htmlInputSubmitElement($attributes));
    }

    public function test_htmlBrElement()
    {
        $attributes = array();
        $this->assertEquals('<br />', $this->fixture->htmlBrElement($attributes));
        $attributes = array_merge($attributes, array(
            $this->fixture->htmlAddAttribute('class', 'test')
        ));
        $this->assertEquals('<br class="test" />', $this->fixture->htmlBrElement($attributes));
    }

    public function test_htmlSpanElement()
    {
        $attributes = array();
        $this->assertEquals('<span>Test content</span>', $this->fixture->htmlSpanElement($attributes, 'Test content'));
        $attributes = array_merge($attributes, array(
            $this->fixture->htmlAddAttribute('class', 'test')
        ));
        $this->assertEquals('<span class="test">Test content</span>', $this->fixture->htmlSpanElement($attributes, 'Test content'));
    }

    public function test_htmlDivElement()
    {
        $attributes = array();
        $this->assertEquals('<div>Test content</div>', $this->fixture->htmlDivElement($attributes, 'Test content'));
        $attributes = array_merge($attributes, array(
            $this->fixture->htmlAddAttribute('class', 'test')
        ));
        $this->assertEquals('<div class="test">Test content</div>', $this->fixture->htmlDivElement($attributes, 'Test content'));
    }

    public function test_htmlOptionElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('value', '1')
        );
        $this->assertEquals('<option value="1">Test content</option>', $this->fixture->htmlOptionElement($attributes, 'Test content'));
        $attributes = array_merge($attributes, array(
            $this->fixture->htmlAddAttribute('selected', 'selected'),
            $this->fixture->htmlAddAttribute('class', 'test')
        ));
        $this->assertEquals('<option value="1" selected="selected" class="test">Test content</option>', $this->fixture->htmlOptionElement($attributes, 'Test content'));
    }

    public function test_htmlSelectElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('class', 'test')
        );
        $this->assertEquals('<select class="test">Test content</select>', $this->fixture->htmlSelectElement($attributes, 'Test content'));
    }

    public function test_htmlIframeElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('src', 'test.php'),
            $this->fixture->htmlAddAttribute('width', '50'),
            $this->fixture->htmlAddAttribute('height', '100')
        );
        $this->assertEquals('<iframe src="test.php" width="50" height="100">Test content</iframe>', $this->fixture->htmlIframeElement($attributes, 'Test content'));
    }

    public function test_htmlImgElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('src', 'test.jpg'),
            $this->fixture->htmlAddAttribute('width', '50'),
            $this->fixture->htmlAddAttribute('height', '100')
        );
        $this->assertEquals('<img src="test.jpg" width="50" height="100" />', $this->fixture->htmlImgElement($attributes));
    }

    public function test_htmlTextareaElement()
    {
        $attributes = array(
            $this->fixture->htmlAddAttribute('width', '50'),
            $this->fixture->htmlAddAttribute('height', '100')
        );
        $this->assertEquals('<textarea width="50" height="100">Test content</textarea>', $this->fixture->htmlTextareaElement($attributes, 'Test content'));
    }
}

?>
