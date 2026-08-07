<?php

declare(strict_types=1);

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

namespace YolfTypo3\SavLibraryPlus\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Flash messages.
 *
 * @package SavLibraryPlus
 */
final class FlashMessages
{

    /**
     * Adds a message either to the BE_USER session (if the $message has the storeInSession flag set)
     * or it adds the message to self::$messages.
     *
     * @param FlashMessage $message
     *            Message
     * @return void
     */
    public static function addMessageToQueue(FlashMessage $flashMessage): void
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @return array Array of objects
     */
    public static function getAllMessages(): array
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        return $flashMessageService->getMessageQueueByIdentifier()->getAllMessages();
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @return array Array of objects
     */
    public static function getAllMessagesAndFlush(): array
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        return $flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @param string $key
     *            The message key
     * @param array|null $arguments
     *            Arguments associated with the translation of the message key
     * @param ContextualFeedbackSeverity $severity
     *            The message severity
     *
     * @return FlashMessage
     */
    protected static function createFlashMessage(string $key, ?array $arguments, ContextualFeedbackSeverity $severity): FlashMessage
    {
        return GeneralUtility::makeInstance(FlashMessage::class, self::translate($key, $arguments), $key, $severity);
    }

    /**
     * Returns the severity OK constant.
     *
     * @return ContextualFeedbackSeverity
     */
    protected static function getSeverityOK(): ContextualFeedbackSeverity
    {
        return ContextualFeedbackSeverity::OK;
    }

    /**
     * Returns the severity ERROR constant.
     *
     * @return ContextualFeedbackSeverity
     */
    protected static function getSeverityERROR(): ContextualFeedbackSeverity
    {
        return ContextualFeedbackSeverity::ERROR;
    }

    /**
     * Adds a message to the messages array
     *
     * @param string $key
     *            The message key
     * @param array|null $arguments
     *            The argument array
     *
     * @return void
     */
    public static function addMessage(string $key, ?array $arguments = null): void
    {
        $flashMessage = self::createFlashMessage($key, $arguments, self::getSeverityOK());
        self::addMessageToQueue($flashMessage);
    }

    /**
     * Translates a message
     *
     * @param string $key
     *            The message key
     * @param array|null $arguments
     *            The argument array
     *
     * @return string|null
     */
    public static function translate(string $key, ?array $arguments = null): ?string
    {
        return LocalizationUtility::translate($key, AbstractController::LIBRARY_NAME, $arguments);
    }

    /**
     * Adds a message to the messages array only once
     *
     * @param string $key
     *            The message key
     * @param array|null $arguments
     *            The argument array
     *
     * @return void
     */
    public static function addMessageOnce(string $key, ?array $arguments = null): void
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
     * @param array|null $arguments
     *            The argument array
     *
     * @return boolean Returns always false so that it can be used in return statements
     */
    public static function addError(string $key, ?array $arguments = null): bool
    {
        $flashMessage = self::createFlashMessage($key, $arguments, self::getSeverityERROR());
        self::addMessageToQueue($flashMessage);
        return false;
    }

    /**
     * Adds an error to the errors array only once
     *
     * @param string $key
     *            The message key
     * @param array|null $arguments
     *            The argument array
     *
     * @return boolean Returns always false so that it can be used in return statements
     */
    public static function addErrorOnce(string $key, ?array $arguments = null): bool
    {
        $flashMessages = self::getAllMessages();
        // If the message already exists, just return
        foreach ($flashMessages as $flashMessage) {
            if ($flashMessage->getTitle() == $key) {
                return false;
            }
        }
        // If we are here, the key was not found
        self::addError($key, $arguments);
        return false;
    }
}
