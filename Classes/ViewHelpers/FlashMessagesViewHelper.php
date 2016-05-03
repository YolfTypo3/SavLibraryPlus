<?php
namespace SAV\SavLibraryPlus\ViewHelpers;

/*
 * This script belongs to the FLOW3 package "Fluid". *
 * *
 * It is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version. *
 * *
 * This script is distributed in the hope that it will be useful, but *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN- *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser *
 * General Public License for more details. *
 * *
 * You should have received a copy of the GNU Lesser General Public *
 * License along with the script. *
 * If not, see http://www.gnu.org/licenses/lgpl.html *
 * *
 * The TYPO3 project - inspiring people to share! *
 */

use TYPO3\CMS\Core\Messaging\FlashMessage;
use SAV\SavLibraryPlus\Controller\FlashMessages;

/**
 * View helper which renders the flash messages (if there are any) as an unsorted list.
 *
 * In case you need custom Flash Message HTML output, please write your own ViewHelper for the moment.
 *
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:flashMessages />
 * </code>
 * <output>
 * An ul-list of flash messages.
 * </output>
 *
 * <code title="Output with custom css class">
 * <f:flashMessages class="specialClass" />
 * </code>
 * <output>
 * <ul class="specialClass">
 * ...
 * </ul>
 * </output>
 *
 * <code title="TYPO3 core style">
 * <f:flashMessages renderMode="div" />
 * </code>
 * <output>
 * <div class="typo3-messages">
 * <div class="typo3-message message-ok">
 * <div class="message-header">Some Message Header</div>
 * <div class="message-body">Some message body</div>
 * </div>
 * <div class="typo3-message message-notice">
 * <div class="message-body">Some notice message without header</div>
 * </div>
 * </div>
 * </output>
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *          @api
 */
class FlashMessagesViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper
{

    const RENDER_MODE_UL = 'ul';
    const RENDER_MODE_DIV = 'div';

    /**
     * Render method.
     *
     * @param string $renderMode
     *            one of the RENDER_MODE_* constants
     * @param string $as
     *            The name of the current flashMessage variable for rendering inside
     * @return string rendered Flash Messages, if there are any.
     * @author Sebastian Kurfürst <sebastian@typo3.org>
     *         @api
     */
    public function render($renderMode = self::RENDER_MODE_UL, $as = null)
    {
        $flashMessages = FlashMessages::getAllMessagesAndFlush();

        if ($flashMessages === NULL || count($flashMessages) === 0) {
            return '';
        }
        switch ($renderMode) {
            case self::RENDER_MODE_UL:
                return $this->renderUl($flashMessages);
            case self::RENDER_MODE_DIV:
                return $this->renderDiv($flashMessages);
            default:
                throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Invalid render mode "' . $renderMode . '" passed to FlashMessageViewhelper', 1290697924);
        }
    }

    /**
     * Renders the flash messages as unordered list
     *
     * @param array $flashMessages
     * @return string
     */
    protected function renderUl(array $flashMessages)
    {
        $this->tag->setTagName('ul');
        if ($this->hasArgumentCompatibleMethod('class')) {
            $this->tag->addAttribute('class', $this->arguments['class']);
        }
        $tagContent = '';

        $classes = array(
            FlashMessage::NOTICE => 'notice',
            FlashMessage::INFO => 'information',
            FlashMessage::OK => 'message',
            FlashMessage::WARNING => 'warning',
            FlashMessage::ERROR => 'error'
        );

        foreach ($flashMessages as $singleFlashMessage) {
            $class = $classes[$singleFlashMessage->getSeverity()];
            $class = ($class ? ' class="' . $class . '"' : $class);
            $tagContent .= '<li' . $class . '>' . htmlspecialchars($singleFlashMessage->getMessage()) . '</li>';
        }
        $this->tag->setContent($tagContent);
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
