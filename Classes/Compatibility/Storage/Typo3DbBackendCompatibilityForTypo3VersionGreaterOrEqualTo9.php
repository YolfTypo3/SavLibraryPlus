<?php
namespace YolfTypo3\SavLibraryPlus\Compatibility\Storage;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Typo3DbBackendCompatibilityForTypo3VersionGreaterOrEqualTo9
{

    /**
     * Function adapted from \TYPO3\CMS\Extbase\Persistence\Storage\Typo3DbBackend
     *
     * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
     * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
     *
     * @param
     *            @param string $tableName The tablename
     * @param array $rows
     * @return array
     */
    public static function doLanguageAndWorkspaceOverlay(string $tableName, array $rows)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $context = $objectManager->get(Context::class);
        $workspaceUid = $context->getPropertyFromAspect('workspace', 'id');

        $pageRepository = $objectManager->get(PageRepository::class, $context);

        // Fetches the move-placeholder in case it is supported
        // by the table and if there's only one row in the result set
        // (applying this to all rows does not work, since the sorting
        // order would be destroyed and possible limits not met anymore)
        if (! empty($workspaceUid) && BackendUtility::isTableWorkspaceEnabled($tableName) && count($rows) === 1) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $movePlaceholder = $queryBuilder->select($tableName . '.*')
                ->from($tableName)
                ->where($queryBuilder->expr()
                ->eq('t3ver_state', $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)), $queryBuilder->expr()
                ->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceUid, \PDO::PARAM_INT)), $queryBuilder->expr()
                ->eq('t3ver_move_id', $queryBuilder->createNamedParameter($rows[0]['uid'], \PDO::PARAM_INT)))
                ->setMaxResults(1)
                ->execute()
                ->fetch();
            if (! empty($movePlaceholder)) {
                $rows = [
                    $movePlaceholder
                ];
            }
        }

        $overlaidRows = [];
        foreach ($rows as $row) {
            // If current row is a translation select its parent
            if (isset($tableName) && isset($GLOBALS['TCA'][$tableName]) && isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField']) && isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])) {
                if (isset($row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']]) && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0) {
                    $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
                    $queryBuilder->getRestrictions()->removeAll();
                    $row = $queryBuilder->select($tableName . '.*')
                        ->from($tableName)
                        ->where($queryBuilder->expr()
                        ->eq($tableName . '.uid', $queryBuilder->createNamedParameter($row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']], \PDO::PARAM_INT)), $queryBuilder->expr()
                        ->eq($tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'], $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();
                }
            }

            $pageRepository->versionOL($tableName, $row, true);
            if ($tableName === 'pages') {
                $row = $pageRepository->getPageOverlay($row, self::getLanguageUid());
            } elseif (isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField']) && $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== '') {
                if (in_array($row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']], [
                    - 1,
                    0
                ])) {
                    $overlayMode = self::getLanguageMode() === 'strict' ? 'hideNonTranslated' : '';
                    $row = $pageRepository->getRecordOverlay($tableName, $row, self::getLanguageUid(), $overlayMode);
                }
            }
            if ($row !== null && is_array($row)) {
                $overlaidRows[] = $row;
            }
        }
        return $overlaidRows;
    }

    /**
     * Gets the language Uid
     *
     * @return integer
     */
    protected static function getLanguageUid()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $context = $objectManager->get(Context::class);
        $aspect = $context->getAspect('language');

        return $aspect->getId();
    }

    /**
     * Gets the language mode
     *
     * @return integer
     */
    protected static function getLanguageMode()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $context = $objectManager->get(Context::class);
        $aspect = $context->getAspect('language');

        return $aspect->getLegacyLanguageMode();
    }
}