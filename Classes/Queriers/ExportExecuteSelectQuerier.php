<?php
namespace YolfTypo3\SavLibraryPlus\Queriers;

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
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;
use YolfTypo3\SavLibraryPlus\Compatibility\EnvironmentCompatibility;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\TcaConfigurationManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\Controller;

/**
 * Default Export Execute Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportExecuteSelectQuerier extends ExportSelectQuerier
{

    /**
     * The xml reference array
     *
     * @var array
     */
    protected $xmlReferenceArray = [];

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
    protected $previousMarkers = [];

    /**
     * Executes the query
     *
     * @return void
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
        $this->exportConfiguration = [];
        $query = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('query');
        if (! empty($query)) {
            // Checks if the user is allowed to use queries
            if ($this->getController()
                ->getUserManager()
                ->userIsAllowedToExportDataWithQuery() === false) {
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
            $this->resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($query);

            // Sets the fields in not already done
            if (count($this->getController()
                ->getUriManager()
                ->getPostVariablesItem('fields')) == 0) {
                $this->rows[0] = $this->getRowWithFullFieldNames();
                // Replaces the field values by the checkbox value
                $this->exportConfiguration = [];
                foreach ($this->rows[0] as $rowKey => $row) {
                    if ($this->isFieldToExclude($rowKey) === false) {
                        $this->exportConfiguration['fields'][$rowKey]['selected'] = 0;
                        $this->exportConfiguration['fields'][$rowKey]['render'] = 0;
                    }
                }
                // Re-executes the query
                $this->resource = DatabaseCompatibility::getDatabaseConnection()->sql_query($query);
            }
        } else {
            // Executes the select query to get the field names
            $this->resource = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTquery(
				/* SELECT   */	$this->buildSelectClause(),
				/* FROM     */	$this->buildFromClause(),
	 			/* WHERE    */	$this->buildWhereClause(),
				/* GROUP BY */	$this->buildGroupByClause(),
				/* ORDER BY */  $this->buildOrderByClause(),
				/* LIMIT    */  $this->buildLimitClause());
        }

        // Checks if the query returns rows
        if (DatabaseCompatibility::getDatabaseConnection()->sql_num_rows($this->resource) == 0) {
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
            $typoScriptConfiguration = [
                'parameter' => $this->getTemporaryFilesPath(true) . $exportStatus,
                'extTarget' => '_blank'
            ];
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
        // Initializes the WHERE clause
        $whereClause = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('whereClause');
        if (empty($whereClause)) {
            $whereClause = '1';
        }

        // Adds the enable fields conditions for the main table
        $mainTable = $this->queryConfigurationManager->getMainTable();
        $whereClause .= $this->getPageRepository()->enableFields($mainTable);

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
     * @return void
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

        if (! empty($xmlFile)) {
            if ($this->processXmlFile($xmlFile) === false) {
                return false;
            }
        }

        // Sets the output file
        $outputFileName = AbstractController::getFormName() . date('_Y_m_d_H_i') . '.csv';
        GeneralUtility::unlink_tempfile($outputFileName);

        // Opens the output file
        $this->outputFileHandle = fopen($filePath . $outputFileName, 'ab');
        if ($this->outputFileHandle === false) {
            return FlashMessages::addError('error.fileOpenError', [
                $outputFileName
            ]);
        }

        // Exports the field names if requested and there is no XML file
        $exportFieldNames = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('exportFieldNames');
        if (empty($exportFieldNames) === false && empty($xmlFile)) {
            $values = [];
            $orderedFieldList = explode(';', preg_replace('/[\n\r]/', '', $this->getController()
                ->getUriManager()
                ->getPostVariablesItem('orderedFieldList')));
            $fields = $this->getController()
                ->getUriManager()
                ->getPostVariablesItem('fields');
            $fieldNames = array_merge($orderedFieldList, array_diff(array_keys($fields), $orderedFieldList));
            foreach ($fieldNames as $fieldName) {
                if ($fields[$fieldName]['selected'] || $fields[$fieldName]['render']) {
                    $values[] = $fieldName;
                }
            }
            fwrite($this->outputFileHandle, $this->csvValues($values, ';') . chr(10));
        }

        // Processes the rows
        $counter = 0;
        $this->rows[0] = $this->getRowWithFullFieldNames($counter ++, false);
        $markers = $this->processRow();

        while ($this->rows[0]) {

            // The current row is kept for post processing
            $previousRow = $this->rows[0];

            // Gets the next row
            $this->rows[0] = $this->getRowWithFullFieldNames($counter ++, false);
            if ($this->rows[0]) {
                $this->nextMarkers = $this->processRow();

                // Checks if a XML file is set
                if (empty($xmlFile)) {
                    // Writes the content to the output file
                    fwrite($this->outputFileHandle, $this->csvValues($markers, ';') . chr(10));
                } else {
                    if ($this->processXmlReferenceArray($previousRow, $markers) === false) {
                        return false;
                    }
                }

                // Sets the current markers
                $markers = $this->nextMarkers;
            }
        }

        // Post-processes the XML file if any
        if (empty($xmlFile) === false) {
            if ($this->processXmlReferenceArray($previousRow, $markers) === false) {
                return false;
            }
            // Processes last markers
            if ($this->postprocessXmlReferenceArray($previousRow, $markers) === false) {
                return false;
            }
        } else {
            // Writes the content to the output file
            fwrite($this->outputFileHandle, $this->csvValues($markers, ';') . chr(10));
        }

        // Checks if a XLST file is set
        $xsltFile = $this->getController()
            ->getUriManager()
            ->getPostVariablesItem('xsltFile');
        if (empty($xsltFile) === false) {
            if ($this->processXsltFile($outputFileName) === false) {
                return false;
            }
        } elseif (empty($xmlFile) === false) {
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
                $xmlfilePath = EnvironmentCompatibility::getSitePath();
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
            if (! empty($exec)) {
                // Processes special controls
                $match = [];
                if (preg_match('/^(RENAME|COPY)\s+(###FILE###)\s+(.*)$/', $exec, $match)) {
                    switch ($match[1]) {
                        case 'RENAME':
                            rename($filePath . $outputFileName, str_replace('###SITEPATH###', dirname(EnvironmentCompatibility::getThisScriptPath()), $match[3]));
                            break;
                        case 'COPY':
                            rename($filePath . $outputFileName, str_replace('###SITEPATH###', dirname(EnvironmentCompatibility::getThisScriptPath()), $match[3]));
                            break;
                    }
                    return true;
                }
                // Replaces some tags
                $cmd = str_replace('###FILE###', $filePath . $outputFileName, $exec);
                $cmd = str_replace('###SITEPATH###', dirname(EnvironmentCompatibility::getThisScriptPath()), $cmd);

                // Processes the command if not in safe mode
                if (! ini_get('safe_mode')) {
                    $cmd = escapeshellcmd($cmd);
                }

                // Special processing for white spaces in windows directories
                $cmd = preg_replace('/\/(\w+(?:\s+\w+)+)/', '/"$1"', $cmd);

                // Executes the command
                exec($cmd);
                return true;
            }
        }

        return $outputFileName;
    }

    /**
     * Processes the xslt file
     *
     * @param string $fileName
     *
     * @return boolean Returns false if an error occured, true otherwise
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
            libxml_use_internal_errors(true);
            $typoScriptConfiguration = [];
            if (@$xml->load($filePath . $xmlfileName) === false) {
                $extensionConfigurationManager = $this->getController()->getExtensionConfigurationManager();
                $typoScriptConfiguration['parameter'] = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/typo3temp/' . $extensionConfigurationManager->getExtensionKey() . '/' . $xmlfileName;
                $typoScriptConfiguration['target'] = '_blank';
                FlashMessages::addError('error.incorrectXmlProducedFile', [
                    $extensionConfigurationManager->getExtensionContentObject()->typoLink(FlashMessages::translate('error.xmlErrorFile'), $typoScriptConfiguration)
                ]);

                // Gets the errors
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    FlashMessages::addError('error.xmlError', [
                        $error->message,
                        $error->line
                    ]);
                }
                libxml_clear_errors();
                return false;
            }

            // Loads the xslt file
            $xsl = new \DOMDocument();
            if (@$xsl->load($xsltFile) === false) {
                FlashMessages::addError('error.incorrectXsltFile', [
                    $xsltFile
                ]);
                return false;
            }

            // Configures the transformer
            $proc = new \XSLTProcessor();
            $proc->importStyleSheet($xsl); // attach the xsl rules

            // Writes the result directly
            fclose($this->outputFileHandle);
            $bytes = @$proc->transformToURI($xml, 'file://' . $filePath . $fileName);
            if ($bytes === false) {
                FlashMessages::addError('error.incorrectXsltResult');
                return false;
            }

            // Deletes the xml file
            unlink($filePath . $xmlfileName);
            return true;
        } else {
            FlashMessages::addError('error.fileDoesNotExist', [
                $xsltFile
            ]);
            return false;
        }
    }

    /**
     * Gets the path of temporary files
     *
     * @param boolean $relativePath
     *            Optional, if true returns the relative path
     *
     * @return string The path
     */
    protected function getTemporaryFilesPath($relativePath = false)
    {
        // Sets the path site
        $pathSite = ($relativePath === false ? EnvironmentCompatibility::getSitePath() : '');

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
        $markers = [];

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

        $additionalFieldsConfiguration = [];
        foreach ($fieldsConfiguration as $fieldConfiguration) {
            if (empty($fieldConfiguration) === false) {
                $matches = [];
                preg_match('/(\w+\.\w+)\.([^=]+)\s*=\s*(.*)/', $fieldConfiguration, $matches);
                $additionalFieldsConfiguration[$matches[1]][trim(strtolower($matches[2]))] = $matches[3];
            }
        }

        foreach ($fieldNames as $fieldName) {
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

                        // Adds the uid to the field configuration in case of:
                        // - a MM relation
                        // - a file rendering
                        if ($fieldConfiguration['MM'] || $fieldConfiguration['renderType'] == 'Files') {
                            $fieldConfiguration['uid'] = $this->getFieldValueFromCurrentRow($fieldConfiguration['tableName'] . '.uid');
                        }

                        // Calls the item viewer
                        $className = 'YolfTypo3\\SavLibraryPlus\\ItemViewers\\General\\' . $fieldConfiguration['fieldType'] . 'ItemViewer';
                        $itemViewer = GeneralUtility::makeInstance($className);
                        $itemViewer->injectController($this->getController());
                        $itemViewer->injectItemConfiguration($fieldConfiguration);
                        $markers['###raw[' . $fieldName . ']###'] = $fieldConfiguration['value'];
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
     * @return boolean true if OK
     */
    protected function processXmlReferenceArray($row, $markers)
    {
        // Gets the template service
        $markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

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
                            $this->recursiveChangeField($key, 'changed', true);
                        }
                        $this->xmlReferenceArray[$key]['fieldValue'] = $row[$value['id']];
                        // Resets the flag replaceIfMatch
                        $this->recursiveChangeField($key, 'replaceIfMatch', false);
                    }

                    // Checks if the parent will change at next row.
                    if ($row[$value['id']] != $this->rows[0][$value['id']]) {
                        $this->xmlReferenceArray[$key]['willChangeNext'] = true;
                    } else {
                        $this->xmlReferenceArray[$key]['willChangeNext'] = false;
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
                    // @extensionScannerIgnoreLine
                    $currentBuffer = $markerBasedTemplateService->substituteMarkerArrayCached($template, $markers, [], []);

                    // Processes the template with the next marker
                    // @extensionScannerIgnoreLine
                    $nextBuffer = $markerBasedTemplateService->substituteMarkerArrayCached($template, $this->nextMarkers, [], []);

                    // Processes the template with the previous marker
                    // @extensionScannerIgnoreLine
                    $previousBuffer = $markerBasedTemplateService->substituteMarkerArrayCached($template, $this->previousMarkers, [], []);

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
                        return false;
                    }

                    break;
                case 'replacedistinct':
                    if ($value['changed']) {
                        // Parses the template with the previous known markers
                        $buffer = ($this->isInUtf8() ? $value['template'] : utf8_decode($value['template']));
                        // @extensionScannerIgnoreLine
                        $buffer = $markerBasedTemplateService->substituteMarkerArrayCached($buffer, $this->previousMarkers, [], []);

                        $fileName = $key . '.xml';
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return false;
                        }

                        $this->recursiveChangeField($key, 'changed', false);
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
                        $value['changed'] = true;
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
                        // @extensionScannerIgnoreLine
                        $buffer = $markerBasedTemplateService->substituteMarkerArrayCached($buffer, $currentMarkers, [], []);

                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return false;
                        }

                        if (! $isChildOfReplaceAlways) {
                            $this->recursiveChangeField($key, 'changed', false);
                        }
                    } else {
                        // The field is cut
                        $buffer = '';
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return false;
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
                    // @extensionScannerIgnoreLine
                    $buffer = $markerBasedTemplateService->substituteMarkerArrayCached($buffer, $markers, [], []);

                    if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer, 'a')) {
                        return false;
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
                        // @extensionScannerIgnoreLine
                        $buffer = $markerBasedTemplateService->substituteMarkerArrayCached($buffer, $markers, [], []);
                    } else {
                        // Keeps the first and last tags
                        $buffer = preg_replace('/^(?s)(<[^>]+>)(.*?)(<\/[^>]+>)$/', '$1$3', $buffer);
                    }

                    if ($row[$value['id']] == $value['value']) {
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return false;
                        }
                        // Sets the flag replaceIfMatch to true
                        $this->xmlReferenceArray[$key]['replaceIfMatch'] = true;
                    } elseif (! $this->xmlReferenceArray[$key]['replaceIfMatch']) {
                        // Replaces only if not yet done
                        if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer)) {
                            return false;
                        }
                    }
                    break;
            }
        }

        // Keeps the marker array
        $this->previousMarkers = $markers;

        return true;
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
            $this->xmlReferenceArray[$key]['sameAsPrevious'] = true;
        } else {
            $buffer = $fieldValue;
            $this->xmlReferenceArray[$key]['sameAsPrevious'] = false;
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
     * @return boolean true if OK
     */
    protected function postprocessXmlReferenceArray($row, $markers)
    {
        // Gets the template service
        $markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        // Marks all references as changed
        $replaceDistinct = false;
        foreach ($this->xmlReferenceArray as $key => $value) {
            $this->xmlReferenceArray[$key]['changed'] = true;
            switch ($value['type']) {
                case 'replacedistinct':
                    $replaceDistinct = true;
                    $this->xmlReferenceArray[$key]['postprocessReplaceDistinct'] = true;
                    break;
            }
        }

        // Processes all the references one more time
        if ($replaceDistinct) {
            if (! $this->processXmlReferenceArray($row, $markers)) {
                return false;
            }
        }

        // Sets the file Path
        $filePath = $this->getTemporaryFilesPath();

        // Converts to utf8 only for replaceLast
        $utf8Encode = false;
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
                    // @extensionScannerIgnoreLine
                    $buffer = $markerBasedTemplateService->substituteMarkerArrayCached($buffer, $this->previousMarkers, [], []);

                    $fileName = $key . '.xml';

                    if (! $this->replaceReferenceMarkers($filePath, $fileName, $buffer, 'w', $utf8Encode, $altPattern)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
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
     * @return void
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
     * @return void
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
     * @return boolean true if OK
     */
    protected function isChildOfReplaceAlways($keySearch)
    {
        $parent = $this->xmlReferenceArray[$keySearch]['parent'];
        while ($parent != null) {
            if ($this->xmlReferenceArray[$parent]['type'] == 'replacealways') {
                return true;
            } else {
                $parent = $this->xmlReferenceArray[$parent]['parent'];
            }
        }
        return false;
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
        while ($parent != null) {
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
     * @return boolean true if OK
     */
    protected function replaceReferenceMarkers($filePath, $fileName, $template, $mode = 'w', $utf8Encode = false, $altPattern = '')
    {
        // Gets the querier
        $querier = $this->getController()->getQuerier();

        // Sets the pattern
        $pattern = '/(?s)(.*?)(<[^>]+>)###(REF_[^#]+)###(<\/[^>]+>)/';
        $pattern = ($altPattern ? $altPattern : $pattern);

        $matches = [];
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
                            while (($buffer = fread($fileHandleRef, 2048))) {
                                $buffer = ($utf8Encode ? utf8_encode($buffer) : $buffer);
                                fwrite($fileHandle, $buffer);
                            }
                            fclose($fileHandleRef);
                            unlink($filePath . $fileNameRef);
                        } else {
                            return FlashMessages::addError('error.fileOpenError', [
                                $fileName
                            ]);
                        }
                    } else {
                        // Error, the file does not exist
                        return FlashMessages::addError('error.fileDoesNotExist', [
                            $fileNameRef
                        ]);
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
                return FlashMessages::addError('error.fileOpenError', [
                    $fileName
                ]);
            }
        } else {
            if ($fileHandle = fopen($filePath . $fileName, $mode)) {
                $template = ($utf8Encode ? utf8_encode($template) : $template);
                $template = $querier->parseConstantTags($template);
                $template = $querier->parseLocalizationTags($template);

                fwrite($fileHandle, $template);
                fclose($fileHandle);
            } else {
                return FlashMessages::addError('error.fileOpenError', [
                    $fileName
                ]);
            }
        }
        return true;
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
        if (file_exists($fileName) === false) {
            return FlashMessages::addError('error.fileDoesNotExist', [
                $fileName
            ]);
        }

        // Loads and processes the xml file
        $xml = simplexml_load_file($fileName);
        if ($xml === false) {
            return FlashMessages::addError('error.incorrectXmlFile', [
                $fileName
            ]);
        }

        // Gets the namespaces
        $this->namespaces = [];
        $namespaces = $xml->getNamespaces(true);

        $this->namespaces[] = '';
        if (! empty($namespaces)) {
            foreach ($namespaces as $namespace)
                $this->namespaces[] = $namespace;
        }

        if (! $this->processXmlTree($xml)) {
            return false;
        }

        // Sets the parent field
        foreach ($this->xmlReferenceArray as $referenceKey => $reference) {
            $matches = [];
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

        return true;
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
        foreach ($this->namespaces as $namespace) {
            foreach ($element->children($namespace) as $child) {
                if (! $this->processXmlTree($child)) {
                    return false;
                }
            }
        }

        // Gets the attributes
        $attributes = [];
        foreach ($this->namespaces as $namespace) {
            foreach ($element->attributes($namespace) as $attribute) {
                $attributes[$attribute->getName()] = (string) $attribute;
            }
        }

        if ((string) $attributes['sav_type']) {
            $reference = 'REF_' . (int) $this->referenceCounter ++;

            $this->xmlReferenceArray[$reference]['type'] = strtolower((string) $attributes['sav_type']);
            $this->xmlReferenceArray[$reference]['id'] = (string) $attributes['sav_id'];
            $this->xmlReferenceArray[$reference]['value'] = (string) $attributes['sav_value'];
            $this->xmlReferenceArray[$reference]['changed'] = false;
            $this->xmlReferenceArray[$reference]['fieldValue'] = null;
            $this->xmlReferenceArray[$reference]['previousFieldValue'] = null;
            $this->xmlReferenceArray[$reference]['parent'] = null;

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
                        return FlashMessages::addError('error.xmlIdMissing', [
                            $this->xmlReferenceArray[$reference]['type']
                        ]);
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
            $match = [];
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
        return true;
    }

    /**
     * Takes a row and returns a CSV string of the values with $delim (default is ,) and $quote (default is ") as separator chars.
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
        $out = [];
        foreach ($row as $value) {
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
     * Returns true if the rendering is in utf-8.
     *
     * @return boolean
     */
    protected function isInUtf8()
    {
        return ($this->getTypoScriptFrontendController()->metaCharset == 'utf-8');
    }
}
?>