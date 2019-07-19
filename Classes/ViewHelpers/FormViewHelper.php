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
 * Use <f:form> to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this, use method="get" as an argument.
 * <code title="Example">
 * <f:form action="...">...</f:form>
 * </code>
 *
 * = A complex form with a specified encoding type =
 *
 * <code title="Form with enctype set">
 * <f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 * </code>
 *
 * = A Form which should render a domain object =
 *
 * <code title="Binding a domain object to a form">
 * <f:form action="..." name="customer" object="{customer}">
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
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        if (version_compare(TYPO3_version, '9.0', '>=')) {
            parent::initializeArguments();
        } elseif (version_compare(TYPO3_version, '8.0', '>=')) {
            $this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
            $this->registerTagAttribute('method', 'string', 'Transfer type (GET or POST)');
            $this->registerTagAttribute('name', 'string', 'Name of form');
            $this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
            $this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');
            $this->registerTagAttribute('target', 'string', 'Target attribute of the form');
            $this->registerUniversalTagAttributes();
       } else {
            parent::initializeArguments();
       }
    }

    /**
     * Render the form.
     *
     * @param string $action Target action
     * @param array $arguments Arguments
     * @param string $controller Target controller
     * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
     * @param string $pluginName Target plugin. If empty, the current plugin name is used
     * @param int $pageUid Target page uid
     * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
     * @param int $pageType Target page type
     * @param bool $noCache set this to disable caching for the target page. You should not need this.
     * @param bool $noCacheHash set this to suppress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section The anchor to be added to the action URI (only active if $actionUri is not set)
     * @param string $format The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)
     * @param array $additionalParams additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)
     * @param bool $absolute If set, an absolute action URI is rendered (only active if $actionUri is not set)
     * @param bool $addQueryString If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set
     * @param string $fieldNamePrefix Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin
     * @param string $actionUri can be used to overwrite the "action" attribute of the form tag
     * @param string $objectName name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
     * @param string $hiddenFieldClassName
     * @param string $addQueryStringMethod Method to use when keeping query parameters (GET or POST, only active if $actionUri is not set)
     *
     * @return string rendered form
     */
    public function render($action = null, array $arguments = [], $controller = null, $extensionName = null, $pluginName = null, $pageUid = null, $object = null, $pageType = 0, $noCache = false, $noCacheHash = false, $section = '', $format = '', array $additionalParams = [], $absolute = false, $addQueryString = false, array $argumentsToBeExcludedFromQueryString = [], $fieldNamePrefix = null, $actionUri = null, $objectName = null, $hiddenFieldClassName = null, $addQueryStringMethod = '')
    {
        // Sets the new action
        $compressedParameters = UriManager::getCompressedParameters();
        $libraryParam = AbstractController::changeCompressedParameters($compressedParameters, 'formAction', $this->arguments['action']);

        // Changes the other parameters if any
        foreach ($this->arguments['arguments'] as $argumentKey => $argument) {
            $libraryParam = AbstractController::changeCompressedParameters($libraryParam, $argumentKey, $argument);
        }

        // sets the additionalParams
        $additionalParams = array_merge($this->arguments['additionalParams'], [
                AbstractController::LIBRARY_NAME => $libraryParam
            ]
        );

        // Sets the noCacheHash based on the extension type
        $noCacheHash = ! ExtensionConfigurationManager::isUserPlugin();

        if ($this->hasArgument('actionUri')) {
            $formActionUri = $this->arguments['actionUri'];
        } else {
            $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
            $formActionUri = $uriBuilder->reset()
                ->setTargetPageUid($this->arguments['pageUid'])
                ->setTargetPageType($this->arguments['pageType'])
                ->setNoCache(false)
                ->setUseCacheHash(! $noCacheHash)
                ->setArguments($additionalParams)
                ->build();
            $this->formActionUriArguments = $uriBuilder->getArguments();
        }

        $this->tag->addAttribute('action', $formActionUri);
        if (strtolower($this->arguments['method']) === 'get') {
            $this->tag->addAttribute('method', 'get');
        } else {
            $this->tag->addAttribute('method', 'post');
        }

        $this->addFormObjectNameToViewHelperVariableContainer();
        $this->addFormObjectToViewHelperVariableContainer();
        $this->addFieldNamePrefixToViewHelperVariableContainer();
        $this->addFormFieldNamesToViewHelperVariableContainer();

        $content = $this->renderChildren();

        $this->tag->setContent($content);

        $this->removeFieldNamePrefixFromViewHelperVariableContainer();
        $this->removeFormObjectFromViewHelperVariableContainer();
        $this->removeFormObjectNameFromViewHelperVariableContainer();
        $this->removeFormFieldNamesFromViewHelperVariableContainer();
        $this->removeCheckboxFieldNamesFromViewHelperVariableContainer();

        return $this->tag->render();
    }
}
?>
