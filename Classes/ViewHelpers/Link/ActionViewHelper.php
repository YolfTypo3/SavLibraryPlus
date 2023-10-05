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

namespace YolfTypo3\SavLibraryPlus\ViewHelpers\Link;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;

/**
 * A view helper for creating links to extbase actions.
 *
 * = Examples =
 *
 * <code title="link to the show-action of the current controller">
 * <sav:link.action action="show">action link</f:link.action>
 * </code>
 * <output>
 * <a href="index.php?id=123&tx_myextension_plugin[action]=show&tx_myextension_plugin[controller]=Standard&cHash=xyz">action link</f:link.action>
 * (depending on the current page and your TS configuration)
 * </output>
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ActionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper
{

    /**
     * Renders the viewhelper
     *
     * @return string Rendered link
     */
    public function render()
    {
        // Sets the extension and plugin names
        $this->arguments['extensionName'] = ExtensionConfigurationManager::getExtensionName();
        $this->arguments['pluginName'] = 'pi1';

        // Sets the new action
        $compressedParameters = UriManager::getCompressedParameters();
        $libraryParam = AbstractController::changeCompressedParameters($compressedParameters, 'formAction', $this->arguments['action']);
//        unset($this->arguments['action']);
        $formName = AbstractController::getFormName();
        $libraryParam = AbstractController::changeCompressedParameters($libraryParam, 'formName', $formName);

        // Changes the other parameters if any
        if (is_array($this->arguments['arguments'])) {
            foreach ($this->arguments['arguments'] as $argumentKey => $argument) {
                $libraryParam = AbstractController::changeCompressedParameters($libraryParam, $argumentKey, $argument);
                unset($this->arguments['arguments'][$argumentKey]);
            }
        }

        // Sets the additionalParams
        $this->arguments['additionalParams'] = array_merge($this->arguments['additionalParams'], [
            AbstractController::LIBRARY_NAME => $libraryParam
        ]);

        // Removes the default controller parameter
        if (version_compare(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class)->getVersion(), '10.0', '<')) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->arguments['extensionName']]['plugins']['pi1']['controllers']['Standard']='';
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->arguments['extensionName']]['plugins']['pi1']['controllers'][]['alias']='Standard';
        }
        static::getConfigurationManager()->setConfiguration(['features' => ['skipDefaultArguments' => 1]]);

        // Gets the content and cleans $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']
        $content = parent::render();
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->arguments['extensionName']]);

        return $content;
    }

    /**
     * Gets the configuration manager
     *
     * @return ConfigurationManagerInterface
     */
    protected static function getConfigurationManager(): ConfigurationManagerInterface
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if (version_compare($typo3Version->getVersion(), '11.0', '<')) {
            $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)->get(ConfigurationManagerInterface::class);
        } else {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        }
        return $configurationManager;
    }
}
