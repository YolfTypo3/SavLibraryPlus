<?php
namespace SAV\SavLibraryPlus\Controller;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy <yolf.typo3@orange.fr>
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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use SAV\SavLibraryPlus\Controller\AbstractController;

/**
 * Flash messages.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class FlashMessages
{

    /**
     * Adds a message either to the BE_USER session (if the $message has the storeInSession flag set)
     * or it adds the message to self::$messages.
     *
     * @param object $message
     *            Message
     * @return void
     */
    protected static function addMessageToQueue($flashMessage)
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $identifier = 'core.template.flashMessages';
        $flashMessageService->getMessageQueueByIdentifier($identifier)->enqueue($flashMessage);
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @return array Array of objects
     */
    public static function getAllMessages()
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $identifier = 'core.template.flashMessages';
        return $flashMessageService->getMessageQueueByIdentifier($identifier)->getAllMessages();
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @return array Array of objects
     */
    public static function getAllMessagesAndFlush()
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $identifier = 'core.template.flashMessages';
        return $flashMessageService->getMessageQueueByIdentifier($identifier)->getAllMessagesAndFlush();
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @param string $key
     *            The message key
     * @param array $arguments
     *            Arguments associated with the translation of the message key
     * @param const $severity
     *            The message severity
     *
     * @return array object
     */
    protected static function createFlashMessage($key, $arguments, $severity)
    {
        return GeneralUtility::makeInstance(FlashMessage::class, self::translate($key, $arguments), $key, $severity);
    }

    /**
     * Returns the severity OK constant.
     *
     * @return integer
     */
    protected static function getSeverityOK()
    {
        return FlashMessage::OK;
    }

    /**
     * Returns the severity ERROR constant.
     *
     * @return integer
     */
    protected static function getSeverityERROR()
    {
        return FlashMessage::ERROR;
    }

    /**
     * Adds a message to the messages array
     *
     * @param string $key
     *            The message key
     * @param array $arguments
     *            The argument array
     *
     * @return none
     */
    public static function addMessage($key, $arguments = NULL)
    {
        $flashMessage = self::createFlashMessage($key, $arguments, self::getSeverityOK());
        self::addMessageToQueue($flashMessage);
    }

    /**
     * Translates a message
     *
     * @param string $key
     *            The message key
     * @param array $arguments
     *            The argument array
     *
     * @return none
     */
    public static function translate($key, $arguments = NULL)
    {
        return LocalizationUtility::translate($key, AbstractController::LIBRARY_NAME, $arguments);
    }

    /**
     * Adds a message to the messages array only once
     *
     * @param string $key
     *            The message key
     * @param array $arguments
     *            The argument array
     *
     * @return none
     */
    public static function addMessageOnce($key, $arguments = NULL)
    {
        $flashMessages = self::getAllMessages();
        // If the message already exists, just return
        foreach ($flashMessages as $flashMessage) {
            if ($flashMessage->getTitle() == $key) {
                return;
            }
        }
        // If we are here, the key was not found
        self::addMessage($key, $arguments);
    }

    /**
     * Adds an error to the errors array
     *
     * @param string $key
     *            The message key
     * @param array $arguments
     *            The argument array
     *
     * @return boolean Returns always FALSE so that it can be used in return statements
     */
    public static function addError($key, $arguments = NULL)
    {
        $flashMessage = self::createFlashMessage($key, $arguments, self::getSeverityERROR());
        self::addMessageToQueue($flashMessage);
        return FALSE;
    }

    /**
     * Adds an error to the errors array only once
     *
     * @param string $key
     *            The message key
     * @param array $arguments
     *            The argument array
     *
     * @return boolean Returns always FALSE so that it can be used in return statements
     */
    public static function addErrorOnce($key, $arguments = NULL)
    {
        $flashMessages = self::getAllMessages();
        // If the message already exists, just return
        foreach ($flashMessages as $flashMessage) {
            if ($flashMessage->getTitle() == $key) {
                return;
            }
        }
        // If we are here, the key was not found
        self::addError($key, $arguments);
        return FALSE;
    }
}

?>