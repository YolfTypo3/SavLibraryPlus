<?php
namespace YolfTypo3\SavLibraryPlus\ViewHelpers;

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
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;
use YolfTypo3\SavLibraryPlus\Managers\UriManager;
use YolfTypo3\SavLibraryPlus\Managers\ExtensionConfigurationManager;

/**
 *
 * @package Fluid
 * @subpackage ViewHelpers
 */

/**
 * Form view helper.
 * Generates a <form> Tag.
 *
 * = Basic usage =
 *
 * Use <sav:form> to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this, use method="get" as an argument.
 * <code title="Example">
 * <sav:form action="...">...</f:form>
 * </code>
 *
 * = A complex form with a specified encoding type =
 *
 * <code title="Form with enctype set">
 * <sav:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 * </code>
 *
 * = A Form which should render a domain object =
 *
 * <code title="Binding a domain object to a form">
 * <sav:form action="..." name="customer" object="{customer}">
 * <f:form.hidden property="id" />
 * <f:form.textbox property="name" />
 * </f:form>
 * </code>
 * This automatically inserts the value of {customer.name} inside the textbox and adjusts the name of the textbox accordingly.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *          @scope prototype
 */
class FormViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper
{

    /**
     * Render the form.
     *
     * @return string rendered form
     */
    public function render()
    {
        // Sets the extension name
        $this->arguments['extensionName'] = ExtensionConfigurationManager::getExtensionName();
        $this->arguments['pluginName'] = 'pi1';

        // Sets the new action
        $compressedParameters = UriManager::getCompressedParameters();
        $libraryParam = AbstractController::changeCompressedParameters($compressedParameters, 'formAction', $this->arguments['action']);
        unset($this->arguments['action']);

        // Changes the other parameters if any
        if (is_array($this->arguments['arguments'])) {
            foreach ($this->arguments['arguments'] as $argumentKey => $argument) {
                $libraryParam = AbstractController::changeCompressedParameters($libraryParam, $argumentKey, $argument);
                unset($this->arguments['arguments'][$argumentKey]);
            }
        }
        // sets the additionalParams
        $this->arguments['additionalParams'] = array_merge($this->arguments['additionalParams'], [
            AbstractController::LIBRARY_NAME => $libraryParam
        ]);

        return parent::render();
    }
}
?>
