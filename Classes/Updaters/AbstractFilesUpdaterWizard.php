<?php
namespace YolfTypo3\SavLibraryPlus\Updaters;

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
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use YolfTypo3\SavLibraryPlus\Compatibility\EnvironmentCompatibility;

class AbstractFilesUpdaterWizard implements UpgradeWizardInterface, \Psr\Log\LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * Extension
     *
     * @var string
     */
    protected $extension = '';

    /**
     * Destination directory
     *
     * @var string
     */
    protected $destinationDirectory = '';

    /**
     * Destination subdirectories
     *
     * @var array
     */
    protected $destinationSubdirectories = [
        '' // Do not remove
    ];

    /**
     * Table name
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * Field names
     *
     * @var array
     */
    protected $fieldNames = [];

    /**
     * Key to be used, for example, when several tables of the same extension must be updated.
     * In such a case the key differentiates the wizards. The key is used as it so
     * UpperCamel is recommended.
     *
     * @var string
     */
    protected $key = '';

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return lcfirst(generalUtility::underscoredToUpperCamelCase($this->extension)) . $this->key . 'UpdateWizard';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Updates the extension "' . $this->extension . '"' . ($this->key ? ' for the key "' . $this->key . '"' : '');
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This update wizard is needed to have files moved into the FAL:
        - it copies the files in uploads/' . $this->extension . ' to ' . $this->destinationDirectory . '
        - each file is inserted in sys_file and linked to the extension in sys_file_reference';
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        // Copies the folders in uploads to the destination directory
        $sourceDirectory = 'uploads/tx_' . str_replace('_', '', $this->extension);
        GeneralUtility::copyDirectory($sourceDirectory, 'fileadmin' . $this->destinationDirectory);

        // Creates a fake admin user. It is required to defined workspace state when working with DataHandler
        $fakeAdminUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $fakeAdminUser->user = [
            'uid' => 0,
            'username' => '_migration_',
            'admin' => 1
        ];
        $fakeAdminUser->workspace = 0;
        $GLOBALS['BE_USER'] = $fakeAdminUser;

        // Iterates the folder
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(EnvironmentCompatibility::getSitePath() . 'fileadmin' . $this->destinationDirectory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isFile()) {

                // Creates or updates the file in sys_file
                $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
                $fileObjectFound = false;
                foreach ($this->destinationSubdirectories as $destinationSubdirectory) {
                    try {
                        if (! empty($destinationSubdirectory)) {
                            $destinationSubdirectory = $destinationSubdirectory . '/';
                        }
                        $fileObject = $resourceFactory->getFileObjectFromCombinedIdentifier('1:' . $this->destinationDirectory . '/' . $destinationSubdirectory . $item->getFileName());
                        $fileObjectFound = true;
                        break;
                    } catch (\InvalidArgumentException | InvalidPathException $e) {
                        if ($e instanceof InvalidPathException) {
                            $this->logger->error('\InvalidPathException thrown - File not imported', [
                                $item->getFileName()
                            ]);
                        }
                        continue;
                    }
                }
                if (! $fileObjectFound) {
                    if ($e instanceof InvalidPathException) {
                        continue;
                    } else {
                        $this->logger->error('File not found in destination directories', [
                            $item->getFileName()
                        ]);
                    }
                }
                // Skips insertion in sys_file_reference if fieldNames is empty
                if (empty($this->fieldNames)) {
                    continue;
                }

                // Inserts in sys_file_reference
                $fileImported = false;
                $recordDeleted = false;
                $recordHidden = false;
                foreach ($this->fieldNames as $fieldName) {
                    // Creates the query builder
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
                    $queryBuilder->getRestrictions()->removeAll();
                    $queryBuilder->select('uid', 'pid', 'deleted', 'hidden')
                        ->from($this->tableName)
                        ->where($queryBuilder->expr()
                        ->like($fieldName . '_save', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($item->getFileName()) . '%')));

                    // Gets the records
                    $records = $queryBuilder->execute()->fetchAll();

                    if (empty($records)) {
                        continue;
                    }
                    $fileImported = true;

                    // Checks if the file reference was already created
                    foreach ($records as $record) {
                        if ($record['deleted'] == 1) {
                            $fileImported = false;
                            $recordDeleted = true;
                            break;
                        } elseif ($record['hidden'] == 1) {
                            $fileImported = false;
                            $recordHidden = true;
                            break;
                        }

                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                        $queryBuilder->select('uid')
                            ->from('sys_file_reference')
                            ->where($queryBuilder->expr()
                            ->eq('tablenames', $queryBuilder->createNamedParameter($this->tableName)), $queryBuilder->expr()
                            ->eq('fieldname', $queryBuilder->createNamedParameter($fieldName)), $queryBuilder->expr()
                            ->eq('uid_foreign', $queryBuilder->createNamedParameter((int) $record['uid'], \PDO::PARAM_INT)));
                        // Gets the rows
                        $rows = $queryBuilder->execute()->fetchAll();
                        if (empty($rows)) {
                            // Assembles DataHandler data
                            $newId = 'NEW1234';
                            $data = [];
                            $data['sys_file_reference'][$newId] = [
                                'table_local' => 'sys_file',
                                'uid_local' => $fileObject->getUid(),
                                'tablenames' => $this->tableName,
                                'uid_foreign' => $record['uid'],
                                'fieldname' => $fieldName,
                                'pid' => $record['pid']
                            ];
                            $data[$this->tableName][$record['uid']] = [
                                $fieldName => $newId,
                                'pid' => $record['pid']
                            ];

                            // Get an instance of the DataHandler and process the data
                            /** @var DataHandler $dataHandler */
                            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                            $dataHandler->start($data, []);
                            $dataHandler->process_datamap();

                            // Error or success reporting
                            if (count($dataHandler->errorLog) != 0) {
                                return false;
                            }
                        }
                    }
                }
                if (! $fileImported) {
                    $message = 'File not imported';
                    if ($recordDeleted) {
                        $message .= ' (record deleted)';
                    } elseif ($recordHidden) {
                        $message .= ' (record hidden)';
                    }
                    $this->logger->warning($message, [
                        $item->getFileName()
                    ]);
                }
            }
        }
        return true;
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $sourceDirectory = 'uploads/tx_' . str_replace('_', '', $this->extension);
        if (! file_exists(EnvironmentCompatibility::getSitePath() . $sourceDirectory)) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of class names of prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [];
    }
}