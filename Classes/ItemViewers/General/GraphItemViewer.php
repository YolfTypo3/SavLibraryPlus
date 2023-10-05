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

namespace YolfTypo3\SavLibraryPlus\ItemViewers\General;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use YolfTypo3\SavCharts\Controller\DefaultController;
use YolfTypo3\SavCharts\XmlParser\XmlParser;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;
use YolfTypo3\SavLibraryPlus\Managers\AdditionalHeaderManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;
use YolfTypo3\SavLibraryPlus\Utility\HtmlElements;


/**
 * General Graph item Viewer.
 *
 * @package SavLibraryPlus
 */
class GraphItemViewer extends AbstractItemViewer
{

    /**
     * The xml parser
     *
     * @var XmlParser
     */
    protected $xmlParser;

    /**
     * If true the template is not processed
     *
     * @var bool
     */
    protected $doNotProcessTemplate = false;

    /**
     * Renders the item.
     *
     * @return string
     */
    protected function renderItem()
    {
        // Checks that sav_charts is loaded
        if (ExtensionManagementUtility::isLoaded('sav_charts')) {

            // Creates the configuration manager
            $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
            if (version_compare($typo3Version->getVersion(), '11.0', '<')) {
                $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
                $configurationManager = $objectManager->get(ConfigurationManager::class);
            } else {
                $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            }

            // Gets the content object
            $contentObject = $this->getController()
            ->getExtensionConfigurationManager()
            ->getExtensionContentObject();

            $configurationManager->setContentObject($contentObject);
            $configurationManager->setConfiguration([
                'extensionName' => 'SavCharts',
                'pluginName' => 'Default',
                'vendorName' => 'YolfTypo3',
                'settings' => [
                    'flexform' => [
                        'allowQueries' => ($this->getItemConfiguration('allowqueries') ? 1 : 0)
                    ]
                ]
            ]);

            // Creates an instance of the controller
            if (version_compare($typo3Version->getVersion(), '11.0', '<')) {
                /** @var DefaultController $controller */
                $controller = $objectManager->get(DefaultController::class);
                $controller->injectObjectManager($objectManager);
                $controller->injectConfigurationManager($configurationManager);
                $controller->setControllerContext();
            } else {
                $controller = GeneralUtility::makeInstance(DefaultController::class);
                $controller->injectConfigurationManager($configurationManager);
            }

            // Creates the xml parser
            $this->xmlParser = GeneralUtility::makeInstance(XmlParser::class);
            $this->xmlParser->injectController($controller);
            $this->xmlParser->clearXmlTagResults();

            // Processes the tags
            $this->processTags();

            // Defines the file name for the resulting image
            if ($this->doNotProcessTemplate === false) {
                $content = $this->processTemplate();
            }

            // Tranfers the message to the default queue
            if (version_compare($typo3Version->getVersion(), '11.0', '<')) {
                $messages = $controller->getControllerContext()
                    ->getFlashMessageQueue()
                    ->getAllMessagesAndFlush();
            } else {
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier('extbase.flashmessages.tx_savcharts_default');
                $messages = $flashMessageQueue->getAllMessagesAndFlush();
            }
            foreach ($messages as $message) {
                FlashMessages::addMessageToQueue($message);
            }
        } else {
            FlashMessages::addError('error.graphExtensionNotLoaded');
            $content = '';
        }

        return $content;
    }

    /**
     * Processes the tags.
     *
     * @return void
     */
    protected function processTags()
    {
        $tags = $this->getItemConfiguration('tags');
        if (empty($tags)) {
            // For compatibility with the old item configuration
            $tags = $this->getItemConfiguration('markers');
        }

        // Sets the markers if any
        if (! empty($tags)) {
            $tags = explode(',', $tags);

            // Processes the tags
            foreach ($tags as $tag) {
                $match = [];
                if (preg_match('/^([0-9A-Za-z_]+)#([0-9A-Za-z_]+)\s*=\s*(.*)$/', trim($tag), $match)) {

                    $name = $match[1];
                    $id = $match[2];
                    $value = $match[3];

                    // Processes the value
                    $value = $this->getController()
                        ->getQuerier()
                        ->parseLocalizationTags($value);
                    $value = $this->getController()
                        ->getQuerier()
                        ->parseFieldTags($value, false);

                    // Checks if the not empty condition is satisfied
                    if (strtolower($value) == 'notempty[]') {
                        FlashMessages::addError('error.graphFieldIsEmpty', [
                            $match[3]
                        ]);
                        $this->doNotProcessTemplate = true;
                        continue;
                    } else {
                        $value = preg_replace('/(?i)notempty\[([^\]]+)\]/', '$1', $value);
                    }

                    // Processes the tag if it has been replaced.
                    if (preg_match('/^###[0-9A-Za-z_]+###$/', $value) == 0) {
                        $xml = '<' . $name . ' id ="' . $id . '">' . $value . '</' . $name . '>';
                        $this->xmlParser->loadXmlString($xml);
                        $this->xmlParser->parseXml();
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
        $content = '';

        // Processes the template
        $graphTemplate = $this->getItemConfiguration('graphtemplate');

        if (empty($graphTemplate)) {
            FlashMessages::addError('error.graphTemplateNotSet');
        } else {
            if (file_exists(Environment::getPublicPath() . '/' . $graphTemplate)) {
                $this->xmlParser->loadXmlFile($graphTemplate);
                $this->xmlParser->parseXml();
                // Post-processing to get the javascript
                $result = $this->xmlParser->postProcessing();

                // Adds the latest javascript file
                $javaScriptRootDirectory = ExtensionManagementUtility::extPath('sav_charts') . 'Resources/Public/JavaScript';
                $extensionWebPath = AbstractController::getExtensionWebPath('sav_charts');
                $javaScriptFooterFile = $extensionWebPath . 'Resources/Public/JavaScript/' . $this->getLatestVersionInDirectory($javaScriptRootDirectory);
                AdditionalHeaderManager::addJavaScriptFooterFile($javaScriptFooterFile);

                // Prepares the content
                $canvases = $result['canvases'];
                if (! empty($canvases)) {
                    foreach ($canvases as $canvas) {
                        $chartId = str_replace('###contentObjectUid###', ExtensionConfigurationManager::getExtensionContentObject()->data['uid'], $canvas['chartId']);
                        $javaScriptFooterInlineCode = str_replace('###contentObjectUid###', ExtensionConfigurationManager::getExtensionContentObject()->data['uid'], $result['javaScriptFooterInlineCode']);

                        $content .= HtmlElements::htmlDivElement([
                            HtmlElements::htmlAddAttribute('class', 'charts chart' . $chartId)
                        ], '<canvas id="canvas' . $chartId . '" width="' . $canvas['width'] . '" height="' . $canvas['height'] . '"></canvas>');

                        // Adds the javacript
                        AdditionalHeaderManager::addJavaScriptFooterInlineCode($chartId, $javaScriptFooterInlineCode);
                    }
                }
            } else {
                FlashMessages::addError('error.graphTemplateUnknown', [
                    $graphTemplate
                ]);
            }
        }

        return $content;
    }

    /**
     * Gets latest version in a directroy containing version of a file
     *
     * @param string $directory
     *            The directory
     *
     * @return string The file
     */
    protected function getLatestVersionInDirectory(string $directory): string
    {
        $files = scandir($directory);
        sort($files); // For unknown reason scandir does not sort the array on my computer
        $count = count($files);
        return $files[$count - 1];
    }
}
