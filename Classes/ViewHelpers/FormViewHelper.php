<?php
namespace SAV\SavLibraryPlus\ViewHelpers;

/*
 * This script belongs to the FLOW3 package "Fluid". *
 * *
 * It is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License as published by the Free *
 * Software Foundation, either version 3 of the License, or (at your *
 * option) any later version. *
 * *
 * This script is distributed in the hope that it will be useful, but *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN- *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General *
 * Public License for more details. *
 * *
 * You should have received a copy of the GNU General Public License *
 * along with the script. *
 * If not, see http://www.gnu.org/licenses/gpl.html *
 * *
 * The TYPO3 project - inspiring people to share! *
 */

use SAV\SavLibraryPlus\Controller\AbstractController;
use SAV\SavLibraryPlus\Managers\UriManager;
use SAV\SavLibraryPlus\Managers\ExtensionConfigurationManager;

/**
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
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
     * Render the form.
     *
     * @param string $action
     *            Target action
     * @param array $arguments
     *            Arguments
     * @param string $controller
     *            Target controller
     * @param string $extensionName
     *            Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
     * @param string $pluginName
     *            Target plugin. If empty, the current plugin name is used
     * @param integer $pageUid
     *            Target page uid
     * @param mixed $object
     *            Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
     * @param integer $pageType
     *            Target page type
     * @param boolean $noCache
     *            set this to disable caching for the target page. You should not need this.
     * @param boolean $noCacheHash
     *            set this to supress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section
     *            The anchor to be added to the action URI (only active if $actionUri is not set)
     * @param string $format
     *            The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)
     * @param array $additionalParams
     *            additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)
     * @param boolean $absolute
     *            If set, an absolute action URI is rendered (only active if $actionUri is not set)
     * @param boolean $addQueryString
     *            If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)
     * @param array $argumentsToBeExcludedFromQueryString
     *            arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set
     * @param string $fieldNamePrefix
     *            Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin
     * @param string $actionUri
     *            can be used to overwrite the "action" attribute of the form tag
     * @param string $objectName
     *            name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
     * @param string $hiddenFieldClassName
     * @return string rendered form
     */
    public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $pageUid = NULL, $object = NULL, $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $format = '', array $additionalParams = array(), $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $fieldNamePrefix = NULL, $actionUri = NULL, $objectName = NULL, $hiddenFieldClassName = NULL)
    {
        // Sets the new action
        $compressedParameters = UriManager::getCompressedParameters();
        $libraryParam = AbstractController::changeCompressedParameters($compressedParameters, 'formAction', $action);

        // Changes the other parameters if any
        foreach ($arguments as $argumentKey => $argument) {
            $libraryParam = AbstractController::changeCompressedParameters($libraryParam, $argumentKey, $argument);
        }

        // sets the additionalParams
        $additionalParams = array_merge($additionalParams, array(
            AbstractController::LIBRARY_NAME => $libraryParam
        ));

        // Sets the noCacheHash based on the extension type
        $noCacheHash = ! ExtensionConfigurationManager::isUserPlugin();

        if ($this->hasArgumentCompatibleMethod('actionUri')) {
            $formActionUri = $this->arguments['actionUri'];
        } else {
            $uriBuilder = $this->controllerContext->getUriBuilder();
            $formActionUri = $uriBuilder->reset()
                ->setTargetPageUid($this->arguments['pageUid'])
                ->setTargetPageType($this->arguments['pageType'])
                ->setNoCache(FALSE)
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

    /**
     * Gets the hasArgument method for compatiblity
     *
     * @param string $argument
     *            argument
     * @return string
     */
    protected function hasArgumentCompatibleMethod($argument)
    {
        if (method_exists($this, 'hasArgument')) {
            // For 4.6 and higher
            return $this->hasArgument($argument);
        } else {
            // For 4.5
            return $this->arguments->hasArgument($argument);
        }
    }
}

?>
