<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\Database;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compatibility for the database connection
 *
 * @package SavLibraryPlus
 */
class DatabaseCompatibility
{

    /**
     * Database connection
     */
    protected static $databaseConnection = null;

    public static function getDatabaseConnection()
    {
        if (self::$databaseConnection === null) {

            // Initialize database connection in $GLOBALS and connect
            self::$databaseConnection = GeneralUtility::makeInstance(DatabaseConnection::class);
            self::$databaseConnection->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] ?? '');
            self::$databaseConnection->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] ?? '');
            self::$databaseConnection->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] ?? '');

            $databaseHost = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] ?? '';
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'])) {
                self::$databaseConnection->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port']);
            } elseif (strpos($databaseHost, ':') > 0) {
                // @TODO: Find a way to handle this case in the install tool and drop this
                list ($databaseHost, $databasePort) = explode(':', $databaseHost);
                self::$databaseConnection->setDatabasePort($databasePort);
            }
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'])) {
                self::$databaseConnection->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket']);
            }
            self::$databaseConnection->setDatabaseHost($databaseHost);

            self::$databaseConnection->debugOutput = false;

            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['persistentConnection']) && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['persistentConnection']) {
                self::$databaseConnection->setPersistentDatabaseConnection(true);
            }

            $isDatabaseHostLocalHost = in_array($databaseHost, [
                'localhost',
                '127.0.0.1',
                '::1'
            ], true);
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverOptions']) && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverOptions'] & MYSQLI_CLIENT_COMPRESS && ! $isDatabaseHostLocalHost) {
                self::$databaseConnection->setConnectionCompression(true);
            }

            if (! empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'])) {
                $commandsAfterConnect = GeneralUtility::trimExplode(LF, str_replace('\' . LF . \'', LF, $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands']), true);
                self::$databaseConnection->setInitializeCommandsAfterConnect($commandsAfterConnect);
            }

            self::$databaseConnection->initialize();
        }

        return self::$databaseConnection;
    }
}
