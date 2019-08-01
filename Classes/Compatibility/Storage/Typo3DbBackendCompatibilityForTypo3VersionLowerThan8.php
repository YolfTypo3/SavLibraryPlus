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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use YolfTypo3\SavLibraryPlus\Compatibility\Database\DatabaseCompatibility;

class Typo3DbBackendCompatibilityForTypo3VersionLowerThan8
{
 
    /**
     * Function adapted from \TYPO3\CMS\Extbase\Persistence\Storage\Typo3DbBackend
     *
     * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
     * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
     *
     * @param  @param string $tableName The tablename
     * @param array $rows
     * @return array
     */
    public static function doLanguageAndWorkspaceOverlay(string $tableName, array $rows)
    {
        $pageRepository = self::getPageRepository();

        // Fetches the move-placeholder in case it is supported
        // by the table and if there's only one row in the result set
        // (applying this to all rows does not work, since the sorting
        // order would be destroyed and possible limits not met anymore)
        if (!empty($pageRepository->versioningWorkspaceId)
            && BackendUtility::isTableWorkspaceEnabled($tableName)
            && count($rows) === 1
            ) {
                $movePlaceholder = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTgetSingleRow(
                    $tableName . '.*',
                    $tableName,
                    't3ver_state=3 AND t3ver_wsid=' . $pageRepository->versioningWorkspaceId
                    . ' AND t3ver_move_id=' . $rows[0]['uid']
                    );
                if (!empty($movePlaceholder)) {
                    $rows = [$movePlaceholder];
                }
            }
            
            $overlaidRows = [];
            foreach ($rows as $row) {
                // If current row is a translation select its parent
                if (isset($tableName) && isset($GLOBALS['TCA'][$tableName])
                    && isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
                    && isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
                    && !isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerTable'])
                    ) {
                        if (isset($row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']])
                            && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
                            ) {
                                $row = DatabaseCompatibility::getDatabaseConnection()->exec_SELECTgetSingleRow(
                                    $tableName . '.*',
                                    $tableName,
                                    $tableName . '.uid=' . (int)$row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] .
                                    ' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=0'
                                    );
                            }
                    }
                    $pageRepository->versionOL($tableName, $row, true);
                    if ($tableName == 'pages') {
                        $row = $pageRepository->getPageOverlay($row, self::getLanguageUid());
                    } elseif (isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
                        && $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== ''
                        && !isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerTable'])
                        ) {
                            if (in_array($row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']], [-1, 0])) {
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
     * Gets the Page Repository
     *
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected static function getPageRepository(): PageRepository
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        return $pageRepository;
    }
    
    /**
     * Gets the language Uid
     *
     * @return integer
     */
    protected static function getLanguageUid()
    {
        // @extensionScannerIgnoreLine
        return $GLOBALS['TSFE']->sys_language_uid;
    }
    
    /**
     * Gets the language mode
     *
     * @return integer
     */
    protected static function getLanguageMode()
    {
        // @extensionScannerIgnoreLine
        return $GLOBALS['TSFE']->sys_language_mode;
    }
    
}