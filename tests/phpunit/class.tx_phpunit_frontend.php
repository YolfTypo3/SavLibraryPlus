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
require_once (PATH_tslib . 'class.tslib_fe.php');

require_once (PATH_tslib . 'class.tslib_pibase.php');
require_once (PATH_tslib . 'class.tslib_content.php');
require_once (PATH_t3lib . 'class.t3lib_timetrack.php');

class tx_phpunit_frontend extends tx_phpunit_database_testcase
{
    
    // Init the test database
    protected function initDatabase($importCore = true)
    {
        $this->createDatabase();
        $this->useTestDatabase();
        
        if ($importCore) {
            $this->importCore();
            $this->importDataSet(dirname(__FILE__) . '/core_dataset.xml');
            
            $importList[] = 'cms';
            if (t3lib_extMgm::isLoaded('templavoila')) {
                $importList[] = 'templavoila';
            }
            $this->importExtensions($importList);
        }
    }
    
    // Basic authentication
    protected function userAuth($name, $password)
    {
        t3lib_div::_GETset(array(
            'logintype' => 'login',
            'user' => $name,
            'pass' => $password
        ));
        
        // Reproduce initFEuser() which cannot be directly use because it
        // generates an error "Cannot modify header information" due to setcookie or
        // session_start. I have not been able to fix this problem.
        $GLOBALS['TSFE']->getMethodEnabled = true;
        $GLOBALS['TSFE']->fe_user = t3lib_div::makeInstance('tslib_feUserAuth');
        $GLOBALS['TSFE']->fe_user->lockIP = $this->TYPO3_CONF_VARS['FE']['lockIP'];
        $GLOBALS['TSFE']->fe_user->lockHashKeyWords = $this->TYPO3_CONF_VARS['FE']['lockHashKeyWords'];
        $GLOBALS['TSFE']->fe_user->checkPid = $this->TYPO3_CONF_VARS['FE']['checkFeUserPid'];
        $GLOBALS['TSFE']->fe_user->lifetime = intval($this->TYPO3_CONF_VARS['FE']['lifetime']);
        $GLOBALS['TSFE']->fe_user->checkPid_value = $GLOBALS['TYPO3_DB']->cleanIntList(t3lib_div::_GP('pid')); // List of pid's acceptable
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'fe_users', 'username=\'' . $name . '\' AND password=\'' . $password . '\'');
        $GLOBALS['TSFE']->fe_user->user = $result[0];
        $GLOBALS['TSFE']->fe_user->loginType = 'FE';
        $GLOBALS['TSFE']->initUserGroups();
    }
    
    // Basic FE environment
    protected function initFE()
    {
        chdir(PATH_site);
        $GLOBALS['TT'] = new t3lib_timeTrack();
        $temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
        $GLOBALS['TYPO3_DB']->debugOutput = true;
        $GLOBALS['TSFE'] = new $temp_TSFEclassName($GLOBALS['TYPO3_CONF_VARS'],
  		/* id       */ 1,
  		/* type     */ '',
  		/* no_cache */ 0,
  		/* cHash    */ '',
  		/*jumpurl   */ '',
  		/* MP       */ '',
  		/* RDCT     */ '');
        $GLOBALS['TSFE']->newCObj();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->config['config'] = array();
        $GLOBALS['TSFE']->initLLvars();
        
        $GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
        $GLOBALS['TSFE']->setSysPageWhereClause();
        $GLOBALS['TSFE']->getPageAndRootline();
    }

    /**
     * Import core tables.sql file
     *
     * @none
     *
     * @return void
     */
    private function importCore()
    {
        // read sql file content
        $sqlFilename = t3lib_div::getFileAbsFileName(PATH_t3lib . 'stddb/tables.sql');
        $fileContent = t3lib_div::getUrl($sqlFilename);
        
        // find definitions
        $install = new t3lib_install();
        $FDfile = $install->getFieldDefinitions_sqlContent($fileContent);
        
        if (count($FDfile)) {
            // find statements to query
            $FDdatabase = $install->getFieldDefinitions_sqlContent($this->getTestDatabaseSchema());
            $diff = $install->getDatabaseExtra($FDfile, $FDdatabase);
            $updateStatements = $install->getUpdateSuggestions($diff);
            
            $updateTypes = array(
                'add',
                'change',
                'create_table'
            );
            
            foreach ($updateTypes as $updateType) {
                if (array_key_exists($updateType, $updateStatements)) {
                    foreach ((array) $updateStatements[$updateType] as $string) {
                        $GLOBALS['TYPO3_DB']->admin_query($string);
                    }
                }
            }
        }
    }

    protected function loadExt($extKey)
    {
        $piObj = t3lib_div::makeInstance('tslib_pibase');
        $piObj->cObj = t3lib_div::makeInstance('tslib_cObj');
        $piObj->extKey = $extKey;
        $piObj->prefixId = 'tx' . str_replace('_', '', $extKey) . '_pi1';
        $piObj->scriptRelPath = 'pi1/class.' . $this->fixture->extObj->prefixId . '.php';
        $piObj->pi_loadLL();
        
        return $piObj;
    }

    /**
     * Returns test database schema dump
     * It overloads the private method in the class tx_phpunit_database_testcase
     * If this method is defined as protected, the following will be removed
     *
     * @return string
     */
    protected function getTestDatabaseSchema()
    {
        $db = $this->useTestDatabase();
        $tables = $this->getDatabaseTables();
        
        // find create statement for every table
        $linebreak = chr(10);
        $schema = '';
        $db->sql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
        foreach ($tables as $tableName) {
            $res = $db->sql_query('show create table ' . $tableName);
            $row = $db->sql_fetch_row($res);
            
            // modify statement to be accepted by TYPO3
            $createStatement = preg_replace('/ENGINE.*$/', '', $row[1]);
            $createStatement = preg_replace('/(CREATE TABLE.*\()/', $linebreak . '\\1' . $linebreak, $createStatement);
            $createStatement = preg_replace('/\) $/', $linebreak . ')', $createStatement);
            
            $schema .= $createStatement . ';';
        }
        
        return $schema;
    }
}

?>
