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
require_once (dirname(__FILE__) . '/phpunit/class.tx_phpunit_frontend.php');

require_once (dirname(__FILE__) . '/../class.tx_savlibrary.php');
require_once (PATH_tslib . 'class.tslib_fe.php');
require_once (PATH_tslib . 'class.tslib_content.php');

class tx_savlibrary_testcase extends tx_phpunit_frontend
{

    public $fixture;

    public function setUp()
    {
        
        // Init database
        $this->initDatabase();
        
        // Import data in the database
        // The extension sav_library_example1 must be installed for this test case
        $this->importExtensions(array(
            'sav_library_example1'
        ));
        $this->importDataSet(dirname(__FILE__) . '/tx_savlibrary_testcase_dataset.xml');
        
        $this->initFE();
        
        // Create the sav_library object
        $this->fixture = new tx_savlibrary();
        $this->fixture->extObj = $this->loadExt('sav_library_example1');
        $this->fixture->xmlToSavlibrayConfig($this->fixture->extObj->cObj->fileResource('EXT:sav_library/res/sav_library.xml'));
        $this->fixture->initVars($this->fixture->extObj);
        $this->fixture->initClasses();
    }

    public function tearDown()
    {
        
        // insures that test database always is dropped
        // even when testcases fails
        $this->dropDatabase();
    }
    
    // Get the Data from the local table
    public function getDataFromTable($from, $select = '*', $where = '')
    {
        // set the tableLocal
        $this->fixture->tableLocal = $from;
        // Get the content from the table
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where);
        while ($row = $this->fixture->queriers->sql_fetch_assoc_with_tablename($res)) {
            $row['uid'] = $row[$this->fixture->tableLocal . '.uid'];
            $data[] = $row;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        return $data;
    }

    /**
     * ************************************************************
     */
    /* Form methods */
    /**
     * ************************************************************
     */
    public function test_getFunc()
    {
        
        // Check String Input
        $config = array(
            'type' => 'input'
        );
        $this->assertEquals('viewStringInput', $this->fixture->getFunc($config));
        
        // Check String Input in edit mode
        $config = array(
            'type' => 'input',
            'edit' => 1
        );
        $this->assertEquals('viewStringInputEditMode', $this->fixture->getFunc($config));
        
        // Check password
        $config = array(
            'type' => 'input',
            'eval' => 'password'
        );
        $this->assertEquals('viewStringPassword', $this->fixture->getFunc($config));
        
        // check file
        $config = array(
            'type' => 'group',
            'internal_type' => 'file'
        );
        $this->assertEquals('viewFile', $this->fixture->getFunc($config));
        
        // check global selector
        $config = array(
            'type' => 'select',
            'foreign_table' => 'test'
        );
        $this->assertEquals('viewDbRelationSelectorGlobal', $this->fixture->getFunc($config));
    }

    public function test_checkCut()
    {
        
        // Get the content of the tx_savlibraryexample1_members table as data test
        $data = $this->getDataFromTable('tx_savlibraryexample1_members');
        
        // Assert False if no configuration is provided
        $config = array();
        $this->assertFalse($this->fixture->checkCut($config, $data[0]));
        
        // Check when cutIfNull is used
        // Assert True if config['value'] is empty
        $config = array(
            'cutifnull' => 1,
            'value' => ''
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        $config = array(
            'cutifnull' => 1,
            'value' => 0
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        // Assert True if config['_value'] is empty
        $config = array(
            'cutifnull' => 1,
            'value' => ''
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        
        // Check when cutIf is used
        // Assert True when the condition is satisfied. Local table is assumed.
        $config = array(
            'cutif' => 'lastname = DOE'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        // Assert True when the condition is satisfied. Full name is assumed.
        $config = array(
            'cutif' => 'tx_savlibraryexample1_members.lastname = DOE'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        // Assert True when the condition is not satisfied. Local table is assumed.
        $config = array(
            'cutif' => 'lastname != DONE'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        // Test the special value EMPTY
        $config = array(
            'cutif' => 'image = EMPTY'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        
        // Check several conditions
        // 2 true conditions with a AND (&)
        $config = array(
            'cutif' => 'lastname != DONE & lastname != DO'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        
        // 3 conditions are true with a AND (&) and one OR (|)
        $config = array(
            'cutif' => 'lastname != DONE & lastname != DO | lastname = DOE'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        // 3 conditions: First is false (thus Anded conditions are false), the last is false
        $config = array(
            'cutif' => 'lastname != DOE & lastname != DO | lastname = DONE'
        );
        $this->assertFalse($this->fixture->checkCut($config, $data[0]));
        
        // Check ###user### and ###cruser### tags
        // set a valid user
        $this->userAuth('validUser', 'test');
        $config = array(
            'cutif' => 'cruser_id = ###user###'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        $config = array(
            'cutif' => 'cruser_id = ###cruser###'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        
        // Check ###usergroup### tag
        $config = array(
            'cutif' => '###usergroup=validGroup###'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        $config = array(
            'cutif' => '###usergroup!=unvalidGroup###'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        
        // Check ###group### tag
        // Get the content of the fe_users table as data test
        $data = $this->getDataFromTable('fe_users');
        $config = array(
            'cutif' => '###group=validGroup###'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
        $config = array(
            'cutif' => '###group!=unvalidGroup###'
        );
        $this->assertTrue($this->fixture->checkCut($config, $data[0]));
    }

    public function test_getValue()
    {
        
        // Get the content of the fe_users table as data test
        $data = $this->getDataFromTable('fe_users', '*, substring_index(name, \' \', 1) as firstName');
        
        // Get the name of the first user (Valid User) with only the field name
        $this->assertEquals('Valid User', $this->fixture->getValue('fe_users', 'name', $data[0]));
        
        // Get the name of the first user (Valid User) with the full field name
        $this->assertEquals('Valid User', $this->fixture->getValue('fe_users', 'fe_users.name', $data[0]));
        
        // Get the first name (case of an alias)
        $this->assertEquals('Valid', $this->fixture->getValue('', 'firstName', $data[0]));
        
        // Return void string if data is not an array
        $data = '';
        $this->assertEquals('', $this->fixture->getValue('', 'firstName', $data));
    }

    public function test_processTitle()
    {
        
        // Get the content of the tx_savlibraryexample1_members table as data test
        $data = $this->getDataFromTable('tx_savlibraryexample1_members');
        
        // If title is not an array return &nbsp;
        $this->assertEquals('&nbsp;', $this->fixture->processTitle('', $data[0]));
        
        // If the tag is a full field name and the viewName is not showAll, then it is replaced by its value
        $title['config']['field'] = 'Test with a full field name tag : ###tx_savlibraryexample1_members.lastname###';
        $this->assertEquals('Test with a full field name tag : DOE', $this->fixture->processTitle($title, $data[0]));
        
        // If the tag is a full field name and the viewName is showAll, then it is replaced by its label
        $this->fixture->viewName = 'showAll';
        $title['config']['field'] = 'Test with a full field name tag : ###tx_savlibraryexample1_members.lastname###';
        $this->assertEquals('Test with a full field name tag : Last Name', $this->fixture->processTitle($title, $data[0]));
        
        // If the tag is a short field name and the viewName is showAll, then it is replaced by its label
        // Local table is assumed to be the table in which the field is
        $this->fixture->viewName = 'showAll';
        $title['config']['field'] = 'Test with a short field name tag : ###firstname###';
        $this->assertEquals('Test with a short field name tag : First Name', $this->fixture->processTitle($title, $data[0]));
    }

    /**
     * ************************************************************
     */
    /* Admin methods */
    /**
     * ************************************************************
     */
    public function test_userIsAdmin()
    {
        
        // Get the content of the tx_savlibraryexample1_members table as data test
        $data = $this->getDataFromTable('tx_savlibraryexample1_members');
        
        // True if there is not an inputAdminField configuration
        $this->fixture->conf['inputAdminField'] = '';
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        
        // True if there is an inputAdminField configuration and the user is
        // a super admin (* in the TS config)
        $this->fixture->conf['inputAdminField'] = '';
        $this->fixture->conf['inputApplyLimit'] = 0;
        // set a valid user
        $this->userAuth('validSuperUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        
        // True if there is an inputAdminField configuration and the user has a
        // correct TS config
        $this->fixture->conf['inputAdminField'] = 'lastname';
        // set a valid user
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        // Same with an admin plus user
        $this->userAuth('validAdminPlusUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        // False is the user is not admin plus and admin plus is checked
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->userIsAdmin($data[0], 1));
        // True is user is admin plus and admin plus is checked
        $this->userAuth('validAdminPlusUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0], 1));
        
        // True if there is an inputAdminField configuration and the user has a
        // correct TS config and dates are correct
        $this->fixture->conf['inputAdminField'] = 'lastname';
        $this->fixture->conf['inputApplyLimit'] = 1;
        $this->fixture->conf['inputStartDate'] = time() - 1000;
        $this->fixture->conf['inputEndDate'] = time() + 1000;
        // set a valid user
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        
        // False if there is an inputAdminField configuration and the user has a
        // correct TS config and the start date is incorrect
        $this->fixture->conf['inputAdminField'] = 'lastname';
        $this->fixture->conf['inputApplyLimit'] = 1;
        $this->fixture->conf['inputStartDate'] = time() + 500;
        $this->fixture->conf['inputEndDate'] = time() + 1000;
        // set a valid user
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->userIsAdmin($data[0]));
        // Also false the user is an admin plus and the limit is applied to him/her
        $this->fixture->conf['inputApplyLimit'] = 2;
        $this->userAuth('validAdminPlusUser', 'test');
        $this->assertFalse($this->fixture->userIsAdmin($data[0], 1));
        // but true if it is a super admin user
        $this->fixture->conf['inputApplyLimit'] = 2;
        $this->userAuth('validSuperUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        $this->fixture->conf['inputApplyLimit'] = 3;
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        
        // False if there is an inputAdminField configuration and the user has a
        // correct TS config and the stop date is incorrect
        $this->fixture->conf['inputAdminField'] = 'lastname';
        $this->fixture->conf['inputApplyLimit'] = 1;
        $this->fixture->conf['inputStartDate'] = time() - 1000;
        $this->fixture->conf['inputEndDate'] = time() - 500;
        // set a valid user
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->userIsAdmin($data[0]));
        $this->fixture->conf['inputApplyLimit'] = 2;
        $this->userAuth('validAdminPlusUser', 'test');
        $this->assertFalse($this->fixture->userIsAdmin($data[0], 1));
        // but true if it is a super admin user
        $this->fixture->conf['inputApplyLimit'] = 2;
        $this->userAuth('validSuperUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        $this->fixture->conf['inputApplyLimit'] = 3;
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        
        // True if there is an inputAdminField configuration set to cruser_id
        // and the user has a correct TS config and the user created the record.
        $this->fixture->conf['inputAdminField'] = 'cruser_id';
        $this->fixture->conf['inputApplyLimit'] = 0;
        // set a valid user
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->userIsAdmin($data[0]));
        
        // False if there is an inputAdminField configuration set to cruser_id
        // and the user has a correct TS config and the user has not created the record.
        $this->fixture->conf['inputAdminField'] = 'cruser_id';
        $this->fixture->conf['inputApplyLimit'] = 0;
        // set a valid user
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->userIsAdmin($data[1]));
    }

    public function test_userIsSuperAdmin()
    {
        
        // Get the content of the tx_savlibraryexample1_members table as data test
        $data = $this->getDataFromTable('tx_savlibraryexample1_members');
        
        $this->userAuth('validSuperUser', 'test');
        $this->assertTrue($this->fixture->userIsSuperAdmin());
        
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->userIsSuperAdmin());
    }

    public function test_userIsAllowedToExportData()
    {
        // Assert true if the user is allowed to export data for an extension
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->userIsAllowedToExportData());
        
        // Assert false if the user has no TSconfig
        // set an unvalid user
        $this->userAuth('unvalidUser', 'test');
        $this->assertFalse($this->fixture->userIsAllowedToExportData());
    }

    public function test_userIsAllowedToInputData()
    {
        
        // Get the content of the tx_savlibraryexample1_members table as data test
        $data = $this->getDataFromTable('tx_savlibraryexample1_members');
        
        // set a valid user
        $this->userAuth('validUser', 'test');
        
        // True if there is no group, no date set
        $this->fixture->conf['inputOnForm'] = true;
        $this->assertTrue($this->fixture->userIsAllowedToInputData());
        
        // False if there is no group, no date set butinputOnForm false
        $this->fixture->conf['inputOnForm'] = false;
        $this->assertFalse($this->fixture->userIsAllowedToInputData());
        
        // True if there is no group and the dates are correct
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->conf['inputApplyLimit'] = 1;
        $this->fixture->conf['inputStartDate'] = time() - 1000;
        $this->fixture->conf['inputEndDate'] = time() + 1000;
        $this->assertTrue($this->fixture->userIsAllowedToInputData());
        
        // False if there is no group and the start date is incorrect
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->conf['inputApplyLimit'] = 1;
        $this->fixture->conf['inputStartDate'] = time() + 500;
        $this->fixture->conf['inputEndDate'] = time() + 1000;
        $this->assertFalse($this->fixture->userIsAllowedToInputData());
        
        // False if there is no group and the end date is incorrect
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->conf['inputApplyLimit'] = 1;
        $this->fixture->conf['inputStartDate'] = time() - 1000;
        $this->fixture->conf['inputEndDate'] = time() - 500;
        $this->assertFalse($this->fixture->userIsAllowedToInputData());
        
        // True if there is a valid group
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->conf['inputApplyLimit'] = 0;
        $this->fixture->conf['allowedGroups'] = 1;
        $this->assertTrue($this->fixture->userIsAllowedToInputData());
        
        // False if there is no valid group
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->conf['inputApplyLimit'] = 0;
        $this->fixture->conf['allowedGroups'] = 2;
        $this->assertFalse($this->fixture->userIsAllowedToInputData());
    }

    public function test_inputIsAllowedInForm()
    {
        // Assert true if there is allowed groups and the user belongs to it and inputMode is true
        $this->fixture->inputMode = true;
        $this->fixture->conf['allowedGroups'] = 1;
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->inputIsAllowedInForm());
        
        // Assert false if there is allowed groups and the user belongs to it and inputMode is false
        $this->fixture->inputMode = false;
        $this->fixture->conf['allowedGroups'] = 1;
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->inputIsAllowedInForm());
        
        // Assert false if there is allowed groups and the user does not belongs to it
        $this->fixture->inputMode = true;
        $this->fixture->conf['allowedGroups'] = 2;
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->inputIsAllowedInForm());
        
        // Assert true if there is no allowed groups and inputOnForm is true and inputMode is true
        $this->fixture->conf['allowedGroups'] = 0;
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->inputMode = true;
        $this->assertTrue($this->fixture->inputIsAllowedInForm());
        
        // Assert false if there is no allowed groups and inputOnForm is false and inputMode is true
        $this->fixture->conf['allowedGroups'] = 0;
        $this->fixture->conf['inputOnForm'] = false;
        $this->fixture->inputMode = true;
        $this->assertFalse($this->fixture->inputIsAllowedInForm());
        
        // Assert false if there is no allowed groups and inputOnForm is true and inputMode is false
        $this->fixture->conf['allowedGroups'] = 0;
        $this->fixture->conf['inputOnForm'] = true;
        $this->fixture->inputMode = false;
        $this->assertFalse($this->fixture->inputIsAllowedInForm());
    }

    public function test_userBelongsToAllowedGroup()
    {
        // Assert true if the user belongs to a valid group
        $this->fixture->conf['allowedGroups'] = 1;
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->userBelongsToAllowedGroup());
        
        // Assert false if the user does not belong to a valid group
        $this->fixture->conf['allowedGroups'] = 2;
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->userBelongsToAllowedGroup());
        
        // Assert false if there is no valid group
        $this->fixture->conf['allowedGroups'] = 0;
        $this->assertFalse($this->fixture->userBelongsToAllowedGroup());
    }

    /**
     * ************************************************************
     */
    /* Language methods */
    /**
     * ************************************************************
     */
    public function test_getLibraryLL()
    {
        $this->assertEquals('Message for tests: .', $this->fixture->getLibraryLL('message.forTests'));
        $this->assertEquals('Message for tests: added message.', $this->fixture->getLibraryLL('message.forTests', 'added message'));
        $this->assertEquals('', $this->fixture->getLibraryLL('unknown'));
    }

    public function test_getExtLL()
    {
        // Labelb Back is defined in locallang.xml of sav_library_example1
        $this->assertEquals('Back', $this->fixture->getExtLL('back'));
        
        // For an unknown label, getExtLL returns a void string
        // and errors is set to the message associated with error.missingLabel.
        $this->assertEquals('', $this->fixture->getExtLL('unknown'));
        $this->assertEquals($this->fixture->getLibraryLL('error.missingLabel', 'unknown'), $this->fixture->getError(0));
        
        // For an unknown label, getExtLL returns the label if the second argument is 0
        $this->assertEquals('unknown', $this->fixture->getExtLL('unknown', 0));
    }

    public function test_processLocalizationTags()
    {
        $this->loadExt('sav_library_example1');
        $this->fixture->tableLocal = 'tx_savlibraryexample1_members';
        
        // The string is not modified if there is no tag
        $this->assertEquals('Test without a tag', $this->fixture->processLocalizationTags('Test without a tag'));
        
        // The tag is replaced by its definition
        $this->assertEquals('Test without a tag : Back', $this->fixture->processLocalizationTags('Test without a tag : $$$back$$$'));
        
        // The tag is replaced by its definition. Several tags can be used
        $this->assertEquals('Back : Test without a tag : Back', $this->fixture->processLocalizationTags('$$$back$$$ : Test without a tag : $$$back$$$'));
        
        // A full field tag name is used
        $this->assertEquals('First Name', $this->fixture->processLocalizationTags('$$$label[tx_savlibraryexample1_members.firstname]$$$'));
        
        // A short field tag name is used. The local table is assumed.
        $this->assertEquals('First Name', $this->fixture->processLocalizationTags('$$$label[firstname]$$$'));
    }

    public function test_processMarkerTags()
    {
        
        // Set the data
        $data = array(
            'tableName.fieldName' => '1',
            'tableLocal.fieldName' => '2',
            'alias' => '3'
        );
        
        // set the local table
        $this->fixture->tableLocal = 'tableLocal';
        
        // The marker is a full name
        $this->assertEquals('1', $this->fixture->processMarkerTags('###tableName.fieldName###', $data));
        
        // The marker is a short name assuming that the local table is used
        $this->assertEquals('2', $this->fixture->processMarkerTags('###fieldName###', $data));
        
        // The marker is an alias
        $this->assertEquals('3', $this->fixture->processMarkerTags('###alias###', $data));
        
        // Two markers are used
        $this->assertEquals('1 2', $this->fixture->processMarkerTags('###tableName.fieldName### ###fieldName###', $data));
        
        // The marker does not exist
        $this->assertEquals('###tableName.unnown###', $this->fixture->processMarkerTags('###tableName.unnown###', $data));
        $this->assertEquals('error.unknownMarker', $this->fixture->getErrorLabel(0));
    }

    /**
     * ************************************************************
     */
    /* Other methods */
    /**
     * ************************************************************
     */
    public function test_compressParams()
    {
        $formParams = array(
            'formAction' => 'showSingle',
            'uid' => 1
        );
        $this->assertEquals('00121011', $this->fixture->compressParams($formParams));
        
        // Test with an unknown form parameter
        $formParams = array(
            'test' => 'test'
        );
        $this->fixture->compressParams($formParams);
        $this->assertEquals('error.unknownFormParam', $this->fixture->getErrorLabel(0));
        
        // Test with an unknown form action
        $formParams = array(
            'formAction' => 'test'
        );
        $this->fixture->compressParams($formParams);
        $this->assertEquals('error.unknownFormAction', $this->fixture->getErrorLabel(1));
    }

    public function test_uncompressParams()
    {
        $formParams = array(
            'formAction' => 'showSingle',
            'uid' => 1
        );
        
        $this->assertEquals($formParams, $this->fixture->uncompressParams('00121011'));
        
        // Test with an unknown form param
        $this->fixture->uncompressParams('a012');
        $this->assertEquals('error.unknownFormParam', $this->fixture->getErrorLabel(0));
        
        // Test with an unknown form action
        $this->fixture->uncompressParams('00230');
        $this->assertEquals('error.unknownFormAction', $this->fixture->getErrorLabel(1));
    }

    public function test_date2timestamp()
    {
        
        // Use default format '%d/%m/%Y' (date) '%d/%m/%Y %H:%M' (datetime)
        $config = array(
            'eval' => 'date'
        );
        $this->assertEquals(mktime(0, 0, 0, 2, 1, 2009), $this->fixture->date2timestamp('01/02/2009', $config, $errors));
        $config = array(
            'eval' => 'datetime'
        );
        $this->assertEquals(mktime(19, 30, 0, 2, 1, 2009), $this->fixture->date2timestamp('01/02/2009 19:30', $config, $errors));
        
        // use another format
        $config = array(
            'eval' => 'datetime',
            'format' => '%d/%m/%y %H:%M'
        );
        $this->assertEquals(mktime(7, 30, 0, 2, 1, 2009), $this->fixture->date2timestamp('01/02/09 07:30', $config, $errors));
    }

    /**
     * ************************************************************
     */
    /* Condition methods */
    /**
     * ************************************************************
     */
    public function test_isInString()
    {
        $this->assertTrue($this->fixture->isInString('This is a test.', 'test'));
        $this->assertFalse($this->fixture->isInString('This is a not a bird.', 'test'));
    }

    public function test_isNotInString()
    {
        $this->assertFalse($this->fixture->isNotInString('This is a test.', 'test'));
        $this->assertTrue($this->fixture->isNotInString('This is a not a bird.', 'test'));
    }

    public function test_isGroupMember()
    {
        // Assert true with an user with a valid group
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->isGroupMember('validGroup'));
        
        // Assert false with an user with an unvalid group
        $this->userAuth('unvalidUser', 'test');
        $this->assertFalse($this->fixture->isGroupMember('validGroup'));
        
        // Assert false if no group is provided
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->isGroupMember(''));
        
        // Assert false if an unknown group is provided
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->isGroupMember('unkownGroup'));
        
        // Assert false if no user is provided
        $this->userAuth('', '');
        $this->assertFalse($this->fixture->isGroupMember('validGroup'));
        
        // Assert false if no user and no group are provided
        $this->userAuth('', '');
        $this->assertFalse($this->fixture->isGroupMember(''));
    }

    public function test_isNotGroupMember()
    {
        // Assert false with an user with a valid group
        $this->userAuth('validUser', 'test');
        $this->assertFalse($this->fixture->isNotGroupMember('validGroup'));
        
        // Assert true with an user with an unvalid group
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->isNotGroupMember('unvalidGroup'));
        
        // Assert true if no group is provided
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->isNotGroupMember(''));
        
        // Assert true if an unknown group is provided
        $this->userAuth('validUser', 'test');
        $this->assertTrue($this->fixture->isNotGroupMember('unkownGroup'));
        
        // Assert true if no user is provided
        $this->userAuth('', '');
        $this->assertTrue($this->fixture->isNotGroupMember('validGroup'));
        
        // Assert true if no user and no group are provided
        $this->userAuth('', '');
        $this->assertTrue($this->fixture->isNotGroupMember(''));
    }
}

?>
