<?php
namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;

/**
 * General Graph item Viewer.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class GraphItemViewer extends AbstractItemViewer
{

    /**
     * The xml graph
     *
     * @var xmlGraph
     */
    protected $xmlGraph;

    /**
     * If TRUE the template is not processed
     *
     * @var boolean
     */
    protected $doNotProcessTemplate = FALSE;

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {

        // Checks that sav_jpgraph is loaded
        if (ExtensionManagementUtility::isLoaded('sav_jpgraph')) {

            // Creates the xlmgraph
            $this->createXmlGraph();

            // Allows the queries
            $this->xmlGraph->injectConfiguration(array(
                'allowQueries' => 1
            ));

            // Processes the markes
            $this->processMarkers();

            // Defines the file name for the resulting image
            if ($this->doNotProcessTemplate === FALSE) {
                $content = $this->processTemplate();
            }
        } else {
            FlashMessages::addError('error.savJpGraphNotLoaded');
            $content = '';
        }

        return $content;
    }

    /**
     * Creates the xml graph.
     *
     * @return none
     */
    protected function createXmlGraph()
    {
        // Defines the constant LOCALE for the use in the template
        define(LOCALE, $GLOBALS['TSFE']->config['config']['locale_all']);

        // Defines the constant CURRENT_PID for the use in the template
        define(CURRENT_PID, $GLOBALS['TSFE']->page['uid']);

        // Defines the constant STORAGE_PID for the use in the template
        $storageSiterootPids = $GLOBALS['TSFE']->getStorageSiterootPids();
        define(STORAGE_PID, $storageSiterootPids['_STORAGE_PID']);

        $recordStoragePages = ExtensionConfigurationManager::getExtensionContentObject()->data['pages'];
        define(RECORD_STORAGE, $recordStoragePages);

        // Redefines the constant for TTF directory if necessary
        $unserializedConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sav_jpgraph']);
        if ($unserializedConfiguration['plugin.']['sav_jpgraph.']['ttfDir']) {
            define('TTF_DIR', $unserializedConfiguration['plugin.']['sav_jpgraph.']['ttfDir']);
        }

        // Defines the main directory
        define('JP_maindir', ExtensionManagementUtility::extPath('sav_jpgraph') . 'Classes/JpGraph/');

        // Defines the cache dir
        define('CACHE_DIR', 'typo3temp/sav_jpgraph/');

        // Requires the xml class
        require_once (ExtensionManagementUtility::extPath('sav_jpgraph') . 'Classes/XmlParser/XmlTypo3Tag.php');
        require_once (ExtensionManagementUtility::extPath('sav_jpgraph') . 'Classes/XmlParser/XmlGraph.php');

        // Creates the xlmgraph
        $this->xmlGraph = GeneralUtility::makeInstance('YolfTypo3\\SavJpgraph\\XmlParser\\xmlGraph');
    }

    /**
     * Creates the image.
     *
     * @return string The image element
     */
    protected function createImage()
    {
        // Defines the file name for the resulting image
        if (is_dir('typo3temp/sav_jpgraph') === FALSE) {
            mkdir('typo3temp/sav_jpgraph');
        }
        $jpGraphCounter = $this->getController()
            ->getViewer()
            ->getJpGraphCounter();
        $formName = AbstractController::getFormName();
        $imageFileName = 'typo3temp/sav_jpgraph/img_' . $formName . '_' . $jpGraphCounter ++ . '.png';
        $this->getController()
            ->getViewer()
            ->setJpGraphCounter($jpGraphCounter);

        // Sets the file reference
        $this->xmlGraph->setReferenceArray('file', 1, $imageFileName);

        // Deletes the file if it exists
        if (file_exists(PATH_site . $imageFileName)) {
            unlink(PATH_site . $imageFileName);
        }

        // Creates the image element
        $content = HtmlElements::htmlImgElement(array(
            HtmlElements::htmlAddAttribute('class', 'jpgraph'),
            HtmlElements::htmlAddAttribute('src', $imageFileName),
            HtmlElements::htmlAddAttribute('title', ''),
            HtmlElements::htmlAddAttribute('alt', '')
        ));

        return $content;
    }

    /**
     * Processes the markers.
     *
     * @return none
     */
    protected function processMarkers()
    {
        // Sets the markers if any
        if ($this->getItemConfiguration('markers')) {
            $markers = explode(',', $this->getItemConfiguration('markers'));

            // Processes the markes
            foreach ($markers as $marker) {
                if (preg_match('/^([0-9A-Za-z_]+)#([0-9A-Za-z_]+)\s*=\s*(.*)$/', trim($marker), $match)) {

                    $name = $match[1];
                    $id = $match[2];
                    $value = $match[3];

                    // Processes the value
                    $value = $this->getController()
                        ->getQuerier()
                        ->parseLocalizationTags($value);
                    $value = $this->getController()
                        ->getQuerier()
                        ->parseFieldTags($value, FALSE);

                    // Checks if the not empty condition is satisfied
                    if (strtolower($value) == 'notempty[]') {
                        FlashMessages::addError('error.savJpGraphFieldIsEmpty', array(
                            $match[3]
                        ));
                        $this->doNotProcessTemplate = TRUE;
                        continue;
                    } else {
                        $value = preg_replace('/(?i)notempty\[([^\]]+)\]/', '$1', $value);
                    }

                    // Adds the marker to the reference array if it has been replaced.
                    if (preg_match('/^###[0-9A-Za-z_]+###$/', $value) == 0) {

                        // Post-processes it
                        switch ($name) {
                            case 'data':
                                $value = explode(',', $value);
                                break;
                        }

                        // Adds it
                        $this->xmlGraph->setReferenceArray($name, $id, $value);
                    }
                }
            }
        }
    }

    /**
     * Processes the template.
     *
     * @return string The image element or empty string
     */
    protected function processTemplate()
    {
        // Defines the file name for the resulting image
        $content = $this->createImage();

        // Processes the template
        $graphTemplate = $this->getItemConfiguration('graphtemplate');

        if (empty($graphTemplate)) {
            FlashMessages::addError('error.savJpGraphTemplateNotSet');
            $content = '';
        } else {
            if (file_exists(PATH_site . $graphTemplate)) {
                $this->xmlGraph->loadXmlFile($graphTemplate);
                $this->xmlGraph->processXmlGraph();
            } else {
                FlashMessages::addError('error.savJpGraphTemplateUnknown', array(
                    $graphTemplate
                ));
                $content = '';
            }
        }

        return $content;
    }
}
?>
