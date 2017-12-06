<?php
namespace YolfTypo3\SavLibraryPlus\Queriers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\TcaConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\Controller;

/**
 * Default Export Execute Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExportExecuteSelectQuerier extends ExportSelectQuerier
{

    /**
     * The xml reference array
     *
     * @var array
     */
    protected $xmlReferenceArray = array();

    /**
     * The reference counter
     *
     * @var integer
     */
    protected $referenceCounter = 0;

    /**
     * The output file handle
     *
     * @var integer
     */
    protected $outputFileHandle;

    /**
     * The previous marker array
     *
     * @var array
     */
    protected $previousMarkers = array();

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {
        // Injects the additional tables
        $this->queryConfigurationManager->setQueryConfigurationParameter('foreignTables', $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('additionalTables'));

        // Injects the additional fields
        $aliases = $this->queryConfigurationManager->getAliases();
        $additionalFields = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('additionalFields');
        if (! empty($additionalFields)) {
            $aliases .= (empty($aliases) ? $additionalFields : ', ' . $additionalFields);
            $this->queryConfigurationManager->setQueryConfigurationParameter('aliases', $aliases);
        }

        // Processes the query
        $this->exportConfiguration = array();
        $query = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('query');
        if (! empty($query)) {
            // Checks if the user is allowed to use queries
            if ($this->getController()
                ->getUserManager()
                ->userIsAllowedToExportDataWithQuery() === FALSE) {
                FlashMessages::addError('fatal.notAllowedToUseQueryInExport');

                // Sets the export configuration
                $this->exportConfiguration = $this->getController()
                    ->getUriManager()
                    ->getPostVariables();

                // Adds the query mode to redisplay the query
                $this->exportConfiguration['queryMode'] = 1;

                return;
            }
            // Executes the query
            $this->resource = $GLOBALS['TYPO3_DB']->sql_query($query);

            // Sets the fields in not already done
            if (count($this->getController()
                ->getUriManager()
                ->getPostVariablesItem('fields')) == 0) {
                $this->rows[0] = $this->getRowWithFullFieldNames();
                // Replaces the field values by the checkbox value
                $this->exportConfiguration = array();
                foreach ($this->rows[0] as $rowKey => $row) {
                    if ($this->isFieldToExclude($rowKey) === FALSE) {
                        $this->exportConfiguration['fields'][$rowKey]['selected'] = 0;
                        $this->exportConfiguration['fields'][$rowKey]['render'] = 0;
                    }
                }
                // Re-executes the query
                $this->resource = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
        } else {
            // Executes the select query to get the field names
            $this->resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				/* SELECT   */	$this->buildSelectClause(),
				/* FROM     */	$this->buildFromClause(),
	 			/* WHERE    */	$this->buildWhereClause(),
				/* GROUP BY */	$this->buildGroupByClause(),
				/* ORDER BY */  $this->buildOrderByClause(),
				/* LIMIT    */  $this->buildLimitClause());
        }

        // Checks if the query returns rows
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($this->resource) == 0) {
            FlashMessages::addError('warning.noRecord');
        }

        // Exports the data in CSV
        if (count($this->getController()
            ->getUriManager()
            ->getPostVariablesItem('fields')) > 0) {
            $exportStatus = $this->exportDataInCsv();
        }

        // Gets the post variables
        $postVariables = $this->getController()
            ->getUriManager()
            ->getPostVariables();

        // Sets the export configuration
        $this->exportConfiguration = array_merge($this->exportConfiguration, $postVariables);

        // Creates the file link, if needed
        if (is_string($exportStatus)) {
            // Builds a link to the file
            $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
            $typoScriptConfiguration = array(
                'parameter' => $this->getTemporaryFilesPath(TRUE) . $exportStatus,
                'extTarget' => '_blank'
            );
            $message = FlashMessages::translate('general.clickHere');
            $this->exportConfiguration['fileLink'] = $extensionConfigurationManager->getExtensionContentObject()->typoLink($message, $typoScriptConfiguration);
        }
    }

    /**
     * Builds the WHERE BY Clause.
     *
     * @return string
     */
    protected function buildWhereClause()
    {

        // Gets the extension configuration manager
        $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();

        // Initializes the WHERE clause
        $whereClause = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('whereClause');
        if (empty($whereClause)) {
            $whereClause = '1';
        }

        // Adds the enable fields conditions for the main table
        $mainTable = $this->queryConfigurationManager->getMainTable();
        $whereClause .= $extensionConfigurationManager->getExtensionContentObject()->enableFields($mainTable);

        // Adds the allowed pages condition
        $whereClause .= $this->getAllowedPages($mainTable);

        return $whereClause;
    }

    /**
     * Builds the ORDER BY Clause.
     *
     * @return string
     */
    protected function buildOrderByClause()
    {
        $orderByClause = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('orderByClause');
        if (empty($orderByClause)) {
            $orderByClause = parent::buildOrderByClause();
        }

        return $orderByClause;
    }

    /**
     * Builds the GROUP BY Clause.
     *
     * @return string
     */
    protected function buildGroupByClause()
    {
        $groupByClause = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('groupByClause');
        $exportMM = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('exportMM');
        if (empty($groupByClause) && empty($exportMM)) {
            $groupByClause = parent::buildGroupByClause();
        }

        return $groupByClause;
    }

    /**
     * Builds the LIMIT BY Clause.
     *
     * @return string
     */
    protected function buildLimitClause()
    {
        return '';
    }

    /**
     * Processes the query
     *
     * @return none
     */
    protected function exportDataInCsv()
    {
        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Creates the directory in typo3temp if it does not exist
        if (! is_dir('typo3temp/' . $extensionKey)) {
            mkdir('typo3temp/' . $extensionKey);
        }

        // Gets the path for the files
        $filePath = $this->getTemporaryFilesPath();

        // Checks if a XML file is set
        $xmlFile = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('xmlFile');
        if (empty($xmlFile) === FALSE) {
            if ($this->processXmlFile($xmlFile) === FALSE) {
                return FALSE;
            }
        }

        // Sets the output file
        $outputFileName = \YolfTypo3\SavLibraryPlus\Controller\AbstractController::getFormName() . date('_Y_m_d_H_i') . '.csv';
        GeneralUtility::unlink_tempfile($outputFileName);

        // Opens the output file
        $this->outputFileHandle = fopen($filePath . $outputFileName, 'ab');
        if ($this->outputFileHandle === FALSE) {
            return FlashMessages::addError('error.fileOpenError', array(
                $outputFileName
            ));
        }

        // Exports the field names if requested and there is no XML file
        $exportFieldNames = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('exportFieldNames');
        if (empty($exportFieldNames) === FALSE && empty($xmlFile)) {
            $values = array();
            $orderedFieldList = explode(';', preg_replace('/[\n\r]/', '', $this->getController()
                ->getUriManager()
                ->getPostVariablesItem('orderedFieldList')));
            $fields = $this->getController()
                ->getUriManager()
                ->getPostVariablesItem('fields');
            $fieldNames = array_merge($orderedFieldList, array_diff(array_keys($fields), $orderedFieldList));
            foreach ($fieldNames as $fieldNameKey => $fieldName) {
                if ($fields[$fieldName]['selected'] || $fields[$fieldName]['render']) {
                    $values[] = $fieldName;
                }
            }
            fwrite($this->outputFileHandle, $this->csvValues($values, ';') . chr(10));
        }

        // Processes the rows
        $counter = 0;
        $this->rows[0] = $this->getRowWithFullFieldNames($counter ++, FALSE);
        $markers = $this->processRow();

        while ($this->rows[0]) {

            // The current row is kept for post processing
            $previousRow = $this->rows[0];

            // Gets the next row
            $this->rows[0] = $this->getRowWithFullFieldNames($counter ++, FALSE);
            if ($this->rows[0]) {
                $this->nextMarkers = $this->processRow();

                // Checks if a XML file is set
                if (empty($xmlFile)) {
                    // Writes the content to the output file
                    fwrite($this->outputFileHandle, $this->csvValues($markers, ';') . chr(10));
                } else {
                    if ($this->processXmlReferenceArray($previousRow, $markers) === FALSE) {
                        return FALSE;
                    }
                }

                // Sets the current markers
                $markers = $this->nextMarkers;
            }
        }

        // Post-processes the XML file if any
        if (empty($xmlFile) === FALSE) {
            if ($this->processXmlReferenceArray($previousRow, $markers) === FALSE) {
                return FALSE;
            }
            // Processes last markers
            if ($this->postprocessXmlReferenceArray($previousRow, $markers) === FALSE) {
                return FALSE;
            }
        } else {
            // Writes the content to the output file
            fwrite($this->outputFileHandle, $this->csvValues($markers, ';') . chr(10));
        }

        // Checks if a XLST file is set
        $xsltFile = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('xsltFile');
        if (empty($xsltFile) === FALSE) {
            if ($this->processXsltFile($outputFileName) === FALSE) {
                return FALSE;
            }
        } elseif (empty($xmlFile) === FALSE) {
            // Gets the xml file name from the last item in the reference array
            end($this->xmlReferenceArray);
            if (key($this->xmlReferenceArray)) {
                fclose($this->outputFileHandle);
                $xmlfileName = key($this->xmlReferenceArray) . '.xml';

                // Copies and deletes the temp file
                copy($filePath . $xmlfileName, $filePath . $outputFileName);
                unlink($filePath . $xmlfileName);
            } else {
                fclose($this->outputFileHandle);

                $xmlfileName = $xmlFile;
                $xmlfilePath = PATH_site;
                // Copies the file
                $errors['copy'] = copy($xmlfilePath . $xmlfileName, $filePath . $outputFileName);
            }
        } else {
            fclose($this->outputFileHandle);
        }

        clearstatcache();
        GeneralUtility::fixPermissions($filePath . $outputFileName);

        // Checks if an Exec command exists, if allowed
        if ($this->getController()
            ->getExtensionConfigurationManager()
            ->getAllowExec()) {
            $exec = $this->getController()
                ->getUriManager()
                ->getPostVariablesItem('exec');
            if (empty($exec) === FALSE) {
                // Processes special controls
                if (preg_match('/^(RENAME|COPY)\s+(###FILE###)\s+(.*)$/', $exec, $match)) {
                    switch ($match[1]) {
                        case 'RENAME':
                            rename($filePath . $outputFileName, str_replace('###SITEPATH###', dirname(PATH_thisScript), $match[3]));
                            break;
                        case 'COPY':
                            rename($filePath . $outputFileName, str_replace('###SITEPATH###', dirname(PATH_thisScript), $match[3]));
                            break;
                    }
                    return TRUE;
                }
                // Replaces some tags
                $cmd = str_replace('###FILE###', $filePath . $outputFileName, $exec);
                $cmd = str_replace('###SITEPATH###', dirname(PATH_thisScript), $cmd);

                // Processes the command if not in safe mode
                if (! ini_get('safe_mode')) {
                    $cmd = escapeshellcmd($cmd);
                }

                // Special processing for white spaces in windows directories
                $cmd = preg_replace('/\/(\w+(?:\s+\w+)+)/', '/"$1"', $cmd);

                // Executes the command
                exec($cmd);
                return TRUE;
            }
        }

        return $outputFileName;
    }

    /**
     * Processes the xslt file
     *
     * @param string $fileName
     *
     * @return boolean Returns FALSE if an error occured, TRUE otherwise
     */
    protected function processXsltFile($fileName)
    {

        // Gets the xslt file
        $xsltFile = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('xsltFile');

        if (file_exists($xsltFile)) {

            // Gets the xml file name from the last item in the reference array
            end($this->xmlReferenceArray);
            $xmlfileName = key($this->xmlReferenceArray) . '.xml';

            // Gets the path for the files
            $filePath = $this->getTemporaryFilesPath();

            // Loads the XML source
            $xml = new \DOMDocument();
            libxml_use_internal_errors(TRUE);
            if (@$xml->load($filePath . $xmlfileName) === FALSE) {
                $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
                $typoScriptConfiguration['parameter'] = 'typo3temp/' . $extensionConfigurationManager->getExtensionKey() . '/' . $xmlfileName;
                $typoScriptConfiguration['target'] = '_blank';
                FlashMessages::addError('error.incorrectXmlProducedFile', array(
                    $extensionConfigurationManager->getExtensionContentObject()->typoLink(FlashMessages::translate('error.xmlErrorFile'), $typoScriptConfiguration)
                ));

                // Gets the errors
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    FlashMessages::addError('error.xmlError', array(
                        $error->message,
                        $error->line
                    ));
                }
                libxml_clear_errors();
                return FALSE;
            }

            // Loads the xslt file
            $xsl = new \DOMDocument();
            if (@$xsl->load($xsltFile) === FALSE) {
                FlashMessages::addError('error.incorrectXsltFile', array(
                    $xsltFile
                ));
                return FALSE;
            }

            // Configures the transformer
            $proc = new \XSLTProcessor();
            $proc->importStyleSheet($xsl); // attach the xsl rules

            // Writes the result directly
            fclose($this->outputFileHandle);
            $bytes = @$proc->transformToURI($xml, 'file://' . $filePath . $fileName);
            if ($bytes === FALSE) {
                FlashMessages::addError('error.incorrectXsltResult');
                return FALSE;
            }

            // Deletes the xml file
            unlink($filePath . $xmlfileName);
            return TRUE;
        } else {
            FlashMessages::addError('error.fileDoesNotExist', array(
                $xsltFile
            ));
            return FALSE;
        }
    }

    /**
     * Gets the path of temporary files
     *
     * @param boolean $relativePath
     *            Optional, if TRUE returns the relative path
     *
     * @return string The path
     */
    protected function getTemporaryFilesPath($relativePath = FALSE)
    {
        // Sets the path site
        $pathSite = ($relativePath === FALSE ? PATH_site : '');

        // Gets the extension key
        $extensionKey = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionKey();

        // Sets the path for the files
        $path = $pathSite . 'typo3temp/' . $extensionKey . '/';

        return $path;
    }

    /**
     * Processes a row
     *
     * @return array The markers
     */
    protected function processRow()
    {
        // Initializes the markers array
        $markers = array();

        // Gets the field names
        $orderedFieldList = explode(';', preg_replace('/[\n\r]/', '', $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('orderedFieldList')));
        $fields = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('fields');
        $fieldNames = array_merge($orderedFieldList, array_diff(array_keys($fields), $orderedFieldList));

        // Gets the fields configuration
        $fieldsConfiguration = explode(';', preg_replace('/[\n\r]/', '', $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('fieldsConfiguration')));

        $additionalFieldsConfiguration = array();
        foreach ($fieldsConfiguration as $fieldConfiguration) {
            if (empty($fieldConfiguration) === FALSE) {
                preg_match('/(\w+\.\w+)\.([^=]+)\s*=\s*(.*)/', $fieldConfiguration, $matches);
                $additionalFieldsConfiguration[$matches[1]][trim(strtolower($matches[2]))] = $matches[3];
            }
        }

        foreach ($fieldNames as $fieldNameKey => $fieldName) {
            // Checks if the field is selected
            if ($fields[$fieldName]['selected']) {
                // Sets the marker according to the rendering mode
                if (empty($fields[$fieldName]['render'])) {
                    // Raw rendering : the value is taken from the row
                    $markers['###' . $fieldName . '###'] = $this->getFieldValueFromCurrentRow($fieldName);
                } else {
                    // Renders the field based on the TCA configuration as it would be rendered in a single view
                    $basicFieldConfiguration = $this->getController()
                        ->getLibraryConfigurationManager()
                        ->searchBasicFieldConfiguration(Controller::cryptTag($fieldName));

                    // Adds the basic configuration, if found, to the TCA
                    if (is_array($basicFieldConfiguration)) {
                        $fieldConfiguration = array_merge(TcaConfigurationManager::getTcaConfigFieldFromFullFieldName($fieldName), $basicFieldConfiguration);
                    } else {
                        // Builds the basic configuration from the TCA
                        $fieldConfiguration = TcaConfigurationManager::buildBasicConfigurationFromTCA($fieldName);
                    }
                    // Adds the additional field configuration
                    if (is_array($additionalFieldsConfiguration[$fieldName])) {
                        $fieldConfiguration = array_merge($fieldConfiguration, $additionalFieldsConfiguration[$fieldName]);
                    }

                    // Checks if the fieldType is set
                    if (isset($fieldConfiguration['fieldType'])) {
                        // Adds the value to the field configuration
                        $fieldConfiguration['value'] = $this->getFieldValueFromCurrentRow($fieldName);

                        // Adds the uid to the field configuration in case of MM relation
                        if ($fieldConfiguration['MM']) {
                            $fieldConfiguration['uid'] = $this->getFieldValueFromCurrentRow($fieldConfiguration['tableName'] . '.uid');
                        }

                        // Calls the item viewer
                        $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\General\\' . $fieldConfiguration['fieldType'] . 'ItemViewer';
                        $itemViewer = GeneralUtility::makeInstance($className);
                        $itemViewer->injectController($this->getController());
                        $itemViewer->injectItemConfiguration($fieldConfiguration);
                        $markers['###' . $fieldName . '###'] = $itemViewer->render();
                    } else {
                        // Raw rendering
                        $markers['###' . $fieldName . '###'] = $this->getFieldValueFromCurrentRow($fieldName);
                    }
                }
            }
        }

        return $markers;
    }

    /**
     * Processes the XML file
     *
     * @param array $row
     *            Row of data
     * @param array $markers
     *            Array of marker
     *
     * @return boolean TRUE if OK
     */
    protected function processXmlReferenceArray($row, $markers)
    {
        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        // Special processing
        foreach ($markers as $key => $value) {
            // Replaces &nbsp; by a space
            $markers[$key] = str_replace('&nbsp;', ' ', $markers[$key]);

            // Replaces & by &amp;
            $markers[$key] = str_replace('& ', '&amp; ', $markers[$key]);

            // Suppresses empty tags
            $markers[$key] = preg_replace('/<[^\/>][^>]*><\/[^>]+>/', '', $markers[$key]);
        }

        // Sets the file Path
        $filePath = $this->getTemporaryFilesPath();

        // Checks if a replaceDistinct id has changed
        foreach ($this->xmlReferenceArray as $key => $value) {
            switch ($value['type']) {
                case 'replacedistinct':
                    if ($row[$value['id']] != $value['fieldValue']) {
                        if (! is_null($value['fieldValue'])) {
                            // Sets all the previous replaceDistinct ids to "changed"
                            $this->recursiveChangeField($key, 'changed', TRUE);
                        }
                        $this->xmlReferenceArray[$key]['fieldValue'] = $row[$value['id']];
                        // Resets the flag replaceIfMatch
                        $this->recursiveChangeField($key, 'replaceIfMatch', FALSE);
                    }

                    // Checks if the parent will change at next row.
                    if ($row[$value['id']] != $this->rows[0][$value['id']]) {
                        $this->xmlReferenceArray[$key]['willChangeNext'] = TRUE;
                    } else {
                        $this->xmlReferenceArray[$key]['willChangeNext'] = FALSE;
                    }
                    break;
            }
        }

        // Processes the replaceDistinct and cutter parts
        foreach ($this->xmlReferenceArray as $key => $value) {

            switch ($value['type']) {
                case 'emptyifsameasprevious':
                    // Parses the template with the known markers
                    $template = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));
                    $currentBuffer = $contentObject->substituteMarkerArrayCached($template, $markers, array(), array());

                    // Processes the template with the next marker
                    $nextBuffer = $contentObject->substituteMarkerArrayCached($template, $this->nextMarkers, array(), array());

                    // Processes the template with the previous marker
                    $previousBuffer = $contentObject->substituteMarkerArrayCached($template, $this->previousMarkers, array(), array());

                    // Processes the buffer
                    if ($this->isChildOfReplaceAlways($key)) {
                        // EmptyIfSameAsPrevious is a child of replaceAlways

                        // Keeps the values in the XML reference array
                        $this->xmlReferenceArray[$key]['fieldValue'] = $currentBuffer;
                        $this->xmlReferenceArray[$key]['nextFieldValue'] = $nextBuffer;

                        // Processes the template
                        if (empty($value['rowsep']) || strtolower($value['rowsep']) == 'zeroifsameasprevious') {
                            $processFunction = 'processEmptyifsameaspreviousTemplate';
                        } else {
                            $processFunction = 'process' . ucfirst(strtolower($value['rowsep'])) . 'Template';
                        }
                        $buffer = $this->$processFunction($key, $template);
                    } else {
                        // EmptyIfSameAsPrevious is a child of replaceDistinct

                        // Gets the parent
                        $parent = $this->getParent($key, 'replacedistinct');
                        if (! empty($parent)) {
                            // Propagates the postprocessReplaceDistinct flag
                            $this->xmlReferenceArray[$key]['postprocessReplaceDistinct'] = $this->xmlReferenceArray[$parent]['postprocessReplaceDistinct'];

                            // Processes the template if the parent changed
                            if ($this->xmlReferenceArray[$parent]['changed']) {
                                if (empty($value['rowsep']) || strtolower($value['rowsep']) == 'zeroifsameasprevious') {
                                    $processFunction = 'processEmptyifsameaspreviousTemplate';
                                } else {
                                    $processFunction = 'process' . ucfirst(strtolower($value['rowsep'])) . 'Template';
                                }
                                $buffer = $this->$processFunction($key, $template);
                            }

                            // Keeps the values in the XML reference array if the parent will change at new row
                            if ($this->xmlReferenceArray[$parent]['willChangeNext']) {
                                $this->xmlReferenceArray[$key]['fieldValue'] = $currentBuffer;
                                $this->xmlReferenceArray[$key]['nextFieldValue'] = $nextBuffer;
                            }
                        }
                    }

                    $fileName = $key . '.xml';
                    if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                        return FALSE;
                    }

                    break;
                case 'replacedistinct':
                    if ($value['changed']) {
                        // Parses the template with the previous known markers
                        $buffer = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));
                        $buffer = $contentObject->substituteMarkerArrayCached($buffer, $this->previousMarkers, array(), array());

                        $fileName = $key . '.xml';
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return FALSE;
                        }

                        $this->recursiveChangeField($key, 'changed', FALSE);
                        $this->unlinkReplaceAlways($filePath, $key);
                    }
                    break;
                case 'cutifnull':
                case 'cutifempty':
                case 'cutifnotnull':
                case 'cutifnotempty':
                case 'cutifequal':
                case 'cutifnotequal':
                case 'cutifgreater':
                case 'cutifless':
                case 'cutifgreaterequal':
                case 'cutiflessequal':
                case 'cutifbitset':
                case 'cutifbitnotset':

                    // Sets the file name
                    $fileName = $key . '.xml';

                    // Sets the field value
                    $value['fieldValue'] = $row[$value['id']];

                    // The processing of the cutters depends on their place with respect to the replaceAlways attribute
                    $isChildOfReplaceAlways = $this->isChildOfReplaceAlways($key);
                    if ($isChildOfReplaceAlways) {
                        $value['changed'] = TRUE;
                        $fieldValue = $value['fieldValue'];
                        $currentMarkers = $markers;
                    } else {
                        $fieldValue = $value['previousFieldValue'];
                        $currentMarkers = $this->previousMarkers;
                    }

                    // Sets the condition
                    switch ($value['type']) {
                        case 'cutifnull':
                        case 'cutifempty':
                            $condition = empty($fieldValue);
                            break;
                        case 'cutifnotnull':
                        case 'cutifnotempty':
                            $condition = ! empty($fieldValue);
                            break;
                        case 'cutifequal':
                            $condition = ($fieldValue == $value['value']);
                            break;
                        case 'cutifnotequal':
                            $condition = ($fieldValue != $value['value']);
                            break;
                        case 'cutifgreater':
                            $condition = ($fieldValue > $value['value']);
                            break;
                        case 'cutifless':
                            $condition = ($fieldValue > $value['value']);
                            break;
                        case 'cutifgreaterequal':
                            $condition = ($fieldValue >= $value['value']);
                            break;
                        case 'cutiflessequal':
                            $condition = ($fieldValue <= $value['value']);
                            break;
                        case 'cutifbitset':
                            $condition = ($fieldValue & (1 << $value['value'])) > 0;
                            break;
                        case 'cutifbitnotset':
                            $condition = ($fieldValue & (1 << $value['value'])) == 0;
                            break;
                    }

                    // Checks if the field must be replaced
                    if ($value['changed'] && ! $condition) {

                        // Replaces markers in the template
                        $buffer = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));
                        $buffer = $contentObject->substituteMarkerArrayCached($buffer, $currentMarkers, array(), array());

                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return FALSE;
                        }

                        if (! $isChildOfReplaceAlways) {
                            $this->recursiveChangeField($key, 'changed', FALSE);
                        }
                    } else {
                        // The field is cut
                        $buffer = '';
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return FALSE;
                        }
                    }

                    // Updates the previous fied value
                    $this->xmlReferenceArray[$key]['previousFieldValue'] = $value['fieldValue'];

                    break;
            }
        }

        // Processes the replaceAlways part
        foreach ($this->xmlReferenceArray as $key => $value) {
            switch ($value['type']) {
                case 'replacealways':
                    $fileName = $key . '.xml';

                    // Replaces markers in the template
                    $buffer = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));
                    $buffer = str_replace('<none>', '', $buffer);
                    $buffer = str_replace('</none>', '', $buffer);
                    $buffer = $contentObject->substituteMarkerArrayCached($buffer, $markers, array(), array());

                    if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer, 'a')) {
                        return FALSE;
                    }
                    break;
                case 'replaceifmatch':

                    $fileName = $key . '.xml';

                    if (! file_exists($filePath . $fileName)) {
                        touch($filePath . $fileName);
                    }

                    // Replaces markers in the template
                    $buffer = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));

                    if ($row[$value['id']] == $value['value']) {
                        $buffer = $contentObject->substituteMarkerArrayCached($buffer, $markers, array(), array());
                    } else {
                        // Keeps the first and last tags
                        $buffer = preg_replace('/^(?s)(<[^>]+>)(.*?)(<\/[^>]+>)$/', '$1$3', $buffer);
                    }

                    if ($row[$value['id']] == $value['value']) {
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return FALSE;
                        }
                        // Sets the flag replaceIfMatch to TRUE
                        $this->xmlReferenceArray[$key]['replaceIfMatch'] = TRUE;
                    } elseif (! $this->xmlReferenceArray[$key]['replaceIfMatch']) {
                        // Replaces only if not yet done
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return FALSE;
                        }
                    }
                    break;
            }
        }

        // Keeps the marker array
        $this->previousMarkers = $markers;

        return TRUE;
    }

    /**
     * Processes the template for Emptyifsameasprevious attribute
     *
     * @param string $key
     *            The key
     * @param string $template
     *            The template
     *
     * @return string The processed template
     */
    protected function processEmptyifsameaspreviousTemplate($key, $template)
    {
        // Initialization
        $previousFieldValue = $this->xmlReferenceArray[$key]['previousFieldValue'];
        $fieldValue = $this->xmlReferenceArray[$key]['fieldValue'];
        $nextFieldValue = $this->xmlReferenceArray[$key]['nextFieldValue'];

        // Cuts the value if the previous field is the same as the current one
        if ($previousFieldValue == $fieldValue) {
            $buffer = preg_replace('/^(<[^>]+>)([^<]*)(<\/[^>]+>)$/', '$1$3', $template);
            $this->xmlReferenceArray[$key]['sameAsPrevious'] = TRUE;
        } else {
            $buffer = $fieldValue;
            $this->xmlReferenceArray[$key]['sameAsPrevious'] = FALSE;
        }

        // Processes the rowsep if any (rowsep is set if the next field is different from the current one
        if ($nextFieldValue != $fieldValue) {
            $buffer = str_replace('rowsep="zeroIfSameAsPrevious"', 'rowsep="1"', $buffer);
            $this->xmlReferenceArray[$key]['rowsepValue'] = 1;
        } else {
            $buffer = str_replace('rowsep="zeroIfSameAsPrevious"', 'rowsep="0"', $buffer);
            $this->xmlReferenceArray[$key]['rowsepValue'] = 0;
        }
        // Keeps the value in the XML reference array
        $this->xmlReferenceArray[$key]['previousFieldValue'] = $fieldValue;

        return $buffer;
    }

    /**
     * Processes the template for Emptyafterfirst attribute
     *
     * @param string $key
     *            The key
     * @param string $template
     *            The template
     *
     * @return string The processed template
     */
    protected function processPreviousbrotherTemplate($key, $template)
    {
        // Gets the previous brother if any
        $previousBrother = $this->getPreviousBrother($key, $this->xmlReferenceArray[$key]['type']);

        // Checks if we are postprocessing the template
        if (! empty($previousBrother)) {

            // Checks if we are postprocessing the template
            if ($this->xmlReferenceArray[$previousBrother]['postprocessReplaceDistinct']) {
                $brotherFieldValue = $this->xmlReferenceArray[$previousBrother]['nextFieldValue'];
                $brotherNextFieldValue = '';
                $fieldValue = $this->xmlReferenceArray[$key]['nextFieldValue'];
            } else {
                $brotherFieldValue = $this->xmlReferenceArray[$previousBrother]['fieldValue'];
                $brotherNextFieldValue = $this->xmlReferenceArray[$previousBrother]['nextFieldValue'];
                $fieldValue = $this->xmlReferenceArray[$key]['fieldValue'];
            }

            // Cuts the value if the previous field is the same as the current one
            if ($this->xmlReferenceArray[$previousBrother]['sameAsPrevious']) {
                $buffer = preg_replace('/^(<[^>]+>)([^<]*)(<\/[^>]+>)$/', '$1$3', $template);
            } else {
                $buffer = $fieldValue;
            }

            // Processes the rowsep
            $buffer = str_replace('rowsep="previousBrother"', 'rowsep="' . $this->xmlReferenceArray[$previousBrother]['rowsepValue'] . '"', $buffer);

            // Keeps the value in the XML reference array
            $this->xmlReferenceArray[$key]['previousFieldValue'] = $fieldValue;
        }
        return $buffer;
    }

    /**
     * Processes the last markers in the XML file
     *
     * @param array $row
     *            row of data
     * @param array $markers
     *            array of markers
     *
     * @return boolean TRUE if OK
     */
    protected function postprocessXmlReferenceArray($row, $markers)
    {
        // Gets the content object
        $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

        // Marks all references as changed
        $replaceDistinct = FALSE;
        foreach ($this->xmlReferenceArray as $key => $value) {
            $this->xmlReferenceArray[$key]['changed'] = TRUE;
            switch ($value['type']) {
                case 'replacedistinct':
                    $replaceDistinct = TRUE;
                    $this->xmlReferenceArray[$key]['postprocessReplaceDistinct'] = TRUE;
                    break;
            }
        }

        // Processes all the references one more time
        if ($replaceDistinct) {
            if (! $this->processXmlReferenceArray($row, $markers)) {
                return FALSE;
            }
        }

        // Sets the file Path
        $filePath = $this->getTemporaryFilesPath();

        // Converts to utf8 only for replaceLast
        $utf8Encode = FALSE;
        $altPattern = '';

        // Post-processing
        foreach ($this->xmlReferenceArray as $key => $value) {
            switch ($value['type']) {
                case 'replacelast':
                    $utf8Encode = ! $this->isInUtf8();
                    $altPattern = '/(?s)(.*)(###)(REF_[^#]+)(###)(.*)/';
                case 'replacelastbutone':

                    // Parses the template with the previous known markers
                    $buffer = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));
                    $buffer = $contentObject->substituteMarkerArrayCached($buffer, $this->previousMarkers, array(), array());

                    $fileName = $key . '.xml';

                    if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer, 'w', $utf8Encode, $altPattern)) {
                        return FALSE;
                    }
                    break;
            }
        }

        return TRUE;
    }

    /**
     * Changes a given field value for all the child of a node
     *
     * @param string $keySearch
     *            key
     * @param string $setField
     *            field to change
     * @param mixed $setvalue
     *            value for the field
     *
     * @return none
     */
    protected function recursiveChangeField($keySearch, $setField, $setValue)
    {
        $this->xmlReferenceArray[$keySearch][$setField] = $setValue;
        foreach ($this->xmlReferenceArray as $key => $value) {
            if ($this->xmlReferenceArray[$key]['parent'] == $keySearch) {
                $this->recursiveChangeField($key, $setField, $setValue);
            }
        }
    }

    /**
     * Unlinks the file associated with a replaceAlways item
     *
     * @param string $filePath
     *            file path
     * @param string $keySearch
     *            key
     *
     * @return none
     */
    protected function unlinkReplaceAlways($filePath, $keySearch)
    {
        foreach ($this->xmlReferenceArray as $key => $value) {
            if ($this->xmlReferenceArray[$key]['parent'] == $keySearch) {
                if ($this->xmlReferenceArray[$key]['type'] != 'replacealways') {
                    $this->unlinkReplaceAlways($filePath, $key);
                } elseif (file_exists($filePath . $key . '.xml')) {
                    unlink($filePath . $key . '.xml');
                }
            }
        }
    }

    /**
     * Checks if the key is a child of a replaceAlways item
     *
     * @param string $keySearch
     *            key
     *
     * @return boolean TRUE if OK
     */
    protected function isChildOfReplaceAlways($keySearch)
    {
        $parent = $this->xmlReferenceArray[$keySearch]['parent'];
        while ($parent != NULL) {
            if ($this->xmlReferenceArray[$parent]['type'] == 'replacealways') {
                return TRUE;
            } else {
                $parent = $this->xmlReferenceArray[$parent]['parent'];
            }
        }
        return FALSE;
    }

    /**
     * Gets the parent for a given key and a given type
     *
     * @param string $keySearch
     *            The key
     * @param string $type
     *            The type
     *
     * @return string The parent key if found, empty string otherwise
     */
    protected function getParent($keySearch, $type)
    {
        $parent = $this->xmlReferenceArray[$keySearch]['parent'];
        while ($parent != NULL) {
            if ($this->xmlReferenceArray[$parent]['type'] == $type) {
                return $parent;
            } else {
                $parent = $this->xmlReferenceArray[$parent]['parent'];
            }
        }
        return '';
    }

    /**
     * Gets the previous brother for a given key and a given type
     *
     * @param string $keySearch
     *            The key
     * @param string $type
     *            The type
     *
     * @return string The parent key if found, empty string otherwise
     */
    protected function getPreviousBrother($keySearch, $type)
    {
        $parentKey = $this->xmlReferenceArray[$keySearch]['parent'];
        $brotherKey = '';
        foreach ($this->xmlReferenceArray as $referenceKey => $reference) {
            if ($reference['parent'] == $parentKey && $reference['type'] == $type) {
                if ($referenceKey == $keySearch) {
                    return $brotherKey;
                } else {
                    $brotherKey = $referenceKey;
                }
            }
        }
        return '';
    }

    /**
     * Replaces the reference markers
     *
     * @param string $filePath
     *            file path
     * @param string $fileName
     *            file name
     * @param string $template
     *            template containing the markers
     * @param string $mode
     *            mode for the file writing
     *
     * @return boolean TRUE if OK
     */
    protected function replaceReferenceMarkers($filePath, $fileName, $template, $mode = 'w', $utf8Encode = FALSE, $altPattern = '')
    {
        // Gets the querier
        $querier = $this->getController()->getQuerier();

        // Sets the pattern
        $pattern = '/(?s)(.*?)(<[^>]+>)###(REF_[^#]+)###(<\/[^>]+>)/';
        $pattern = ($altPattern ? $altPattern : $pattern);

        if (preg_match_all($pattern, $template, $matches)) {
            if ($fileHandle = fopen($filePath . $fileName, 'a')) {
                foreach ($matches[0] as $matchKey => $match) {

                    // Replaces markers in the template
                    $buffer = $matches[1][$matchKey];
                    $buffer = ($utf8Encode ? utf8_encode($buffer) : $buffer);
                    $buffer = $querier->parseConstantTags($buffer);
                    $buffer = $querier->parseLocalizationTags($buffer);
                    fwrite($fileHandle, $buffer);

                    $fileNameRef = $matches[3][$matchKey] . '.xml';
                    if (file_exists($filePath . $fileNameRef)) {
                        if ($fileHandleRef = fopen($filePath . $fileNameRef, 'r')) {
                            while ($buffer = fread($fileHandleRef, 2048)) {
                                $buffer = ($utf8Encode ? utf8_encode($buffer) : $buffer);
                                fwrite($fileHandle, $buffer);
                            }
                            fclose($fileHandleRef);
                            unlink($filePath . $fileNameRef);
                        } else {
                            return FlashMessages::addError('error.fileOpenError', array(
                                $fileName
                            ));
                        }
                    } else {
                        // Error, the file does not exist
                        return FlashMessages::addError('error.fileDoesNotExist', array(
                            $fileNameRef
                        ));
                    }

                    // Removes the matched string from the template
                    $template = str_replace($matches[0][$matchKey], '', $template);
                }

                // Writes the remaining template
                $template = ($utf8Encode ? utf8_encode($template) : $template);
                $template = $querier->parseConstantTags($template);
                $template = $querier->parseLocalizationTags($template);

                fwrite($fileHandle, $template);
                fclose($fileHandle);
            } else {
                // Error, the file cannot be opened
                return FlashMessages::addError('error.fileOpenError', array(
                    $fileName
                ));
            }
        } else {
            if ($fileHandle = fopen($filePath . $fileName, $mode)) {
                $template = ($utf8Encode ? utf8_encode($template) : $template);
                $template = $querier->parseConstantTags($template);
                $template = $querier->parseLocalizationTags($template);

                fwrite($fileHandle, $template);
                fclose($fileHandle);
            } else {
                return FlashMessages::addError('error.fileOpenError', array(
                    $fileName
                ));
            }
        }
        return TRUE;
    }

    /**
     * Processes a XML file
     *
     * @param string $fileName
     *
     * @return boolean
     */
    protected function processXmlFile($fileName)
    {
        // Checks if the file exists
        if (file_exists($fileName) === FALSE) {
            return FlashMessages::addError('error.fileDoesNotExist', array(
                $fileName
            ));
        }

        // Loads and processes the xml file
        $xml = simplexml_load_file($fileName);
        if ($xml === FALSE) {
            return FlashMessages::addError('error.incorrectXmlFile', array(
                $fileName
            ));
        }

        // Gets the namespaces
        $this->namespaces = array();
        $namespaces = $xml->getNamespaces(TRUE);

        $this->namespaces[] = '';
        if (! empty($namespaces)) {
            foreach ($namespaces as $namespace)
                $this->namespaces[] = $namespace;
        }

        if (! $this->processXmlTree($xml)) {
            return FALSE;
        }

        // Sets the parent field
        foreach ($this->xmlReferenceArray as $referenceKey => $reference) {
            if (preg_match_all('/###(REF_[^#]+)###/', $reference['template'], $matches)) {
                foreach ($matches[0] as $matchKey => $match) {
                    $this->xmlReferenceArray[$matches[1][$matchKey]]['parent'] = $referenceKey;
                }
            }
        }

        // Clears all reference files
        foreach ($this->xmlReferenceArray as $referenceKey => $reference) {
            $fileName = $this->getTemporaryFilesPath() . $referenceKey . '.xml';
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return TRUE;
    }

    /**
     * Processes the XML tree
     *
     * @param object $element
     *            XML element object
     *
     * @return array Merged arrays
     */
    protected function processXmlTree($element)
    {
        // Processes recursively all nodes
        foreach ($this->namespaces as $namespaceKey => $namespace) {
            foreach ($element->children($namespace) as $child) {
                if (! $this->processXmlTree($child)) {
                    return FALSE;
                }
            }
        }

        // Gets the attributes
        $attributes = array();
        foreach ($this->namespaces as $namespaceKey => $namespace) {
            foreach ($element->attributes($namespace) as $attribute) {
                $attributes[$attribute->getName()] = (string) $attribute;
            }
        }

        if ((string) $attributes['sav_type']) {
            $reference = 'REF_' . (int) $this->referenceCounter ++;

            $this->xmlReferenceArray[$reference]['type'] = strtolower((string) $attributes['sav_type']);
            $this->xmlReferenceArray[$reference]['id'] = (string) $attributes['sav_id'];
            $this->xmlReferenceArray[$reference]['value'] = (string) $attributes['sav_value'];
            $this->xmlReferenceArray[$reference]['changed'] = FALSE;
            $this->xmlReferenceArray[$reference]['fieldValue'] = NULL;
            $this->xmlReferenceArray[$reference]['previousFieldValue'] = NULL;
            $this->xmlReferenceArray[$reference]['parent'] = NULL;

            // Checks if a reference id has to be set
            switch ($this->xmlReferenceArray[$reference]['type']) {
                case 'emptyifsameasprevious':
                    $this->xmlReferenceArray[$reference]['rowsep'] = (string) $attributes['rowsep'];
                    break;
                case 'replacedistinct':
                case 'cutifnull':
                case 'cutifempty':
                case 'cutifnotnull':
                case 'cutifnotempty':
                case 'cutifequal':
                case 'cutifnotequal':
                case 'cutifgreater':
                case 'cutifless':
                case 'cutifgreaterequal':
                case 'cutiflessequal':
                    if (! $this->xmlReferenceArray[$reference]['id']) {
                        return FlashMessages::addError('error.xmlIdMissing', array(
                            $this->xmlReferenceArray[$reference]['type']
                        ));
                    }
                    break;
            }

            // Removes the repeat attributes
            unset($element[0]['sav_type']);
            unset($element[0]['sav_id']);
            unset($element[0]['sav_value']);

            // Sets the template
            $template = $element->asXML();

            // Checks if there is an xml header in the template
            if (preg_match('/^<\?xml[^>]+>/', $template, $match)) {

                // Removes the header
                $template = str_replace($match[0], '', $template);
                $this->xmlReferenceArray[$reference]['template'] = $template;
                if (! $this->xmlReferenceArray[$reference]['type']) {
                    $this->xmlReferenceArray[$reference]['type'] = 'replacelastbutone';
                }

                // Sets the template with relaceLast type
                $lastReference = 'REF_' . (int) $this->referenceCounter ++;
                $this->xmlReferenceArray[$lastReference]['template'] = $match[0] . '###' . $reference . '###';
                $this->xmlReferenceArray[$lastReference]['type'] = 'replacelast';
            } else {
                $this->xmlReferenceArray[$reference]['template'] = $template;
            }

            // Deletes all the children
            foreach ($this->namespaces as $namespaceKey => $namespace) {
                foreach ($element->children($namespace) as $child) {
                    unset($element->$child);
                }
            }

            // Replaces the node by the reference or a special reference
            switch ($this->xmlReferenceArray[$reference]['type']) {
                default:
                    $element[0] = '###' . $reference . '###';
                    break;
            }
        } else {

            $template = $element->asXML();
            // Checks if there is an xml header in the template
            if (preg_match('/^<\?xml[^>]+>/', $template, $match)) {
                $reference = 'REF_' . (int) $this->referenceCounter ++;

                // Removes the header
                $template = str_replace($match[0], '', $template);
                $this->xmlReferenceArray[$reference]['template'] = $template;
                if (! $this->xmlReferenceArray[$reference]['type']) {
                    $this->xmlReferenceArray[$reference]['type'] = 'replacelastbutone';
                }

                // Sets the template with replaceLast type
                $lastReference = 'REF_' . (int) $this->referenceCounter ++;
                $this->xmlReferenceArray[$lastReference]['template'] = $match[0] . '###' . $reference . '###';
                $this->xmlReferenceArray[$lastReference]['type'] = 'replacelast';
                // Deletes all the children
                foreach ($element->children() as $child) {
                    unset($element->$child);
                }
                // Replaces the node by the reference
                $element[0] = '###' . $reference . '###';
            }
        }
        return TRUE;
    }

    /**
     * Takes a row and returns a CSV string of the values with $delim (default is ,) and $quote (default is ") as separator chars.
     * Usage: 5
     *
     * @param array $row
     *            Input array of values
     * @param string $delim
     *            Delimited, default is comman
     * @param string $quote
     *            Quote-character to wrap around the values.
     * @return string A single line of CSV
     */
    protected function csvValues($row, $delim = ',', $quote = '"')
    {
        reset($row);
        $out = array();
        while (list (, $value) = each($row)) {
            // Modification to keep multiline information
            // list($valPart) = explode(chr(10),$value);
            // $valPart = trim($valPart);
            if (mb_detect_encoding($value) == 'UTF-8') {
                $value = utf8_decode($value);
            }
            $valPart = $value;
            $out[] = str_replace($quote, $quote . $quote, $valPart);
        }
        $str = $quote . implode($quote . $delim . $quote, $out) . $quote;

        return $str;
    }

    /**
     * Returns TRUE if the rendering is in utf-8.
     *
     * @return boolean
     */
    protected function isInUtf8()
    {
        return ($GLOBALS['TSFE']->renderCharset == 'utf-8');
    }
}
?>