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

namespace YolfTypo3\SavLibraryPlus\Managers;


/**
 * Query configuration manager
 *
 * @package SavLibraryPlus
 */
final class QueryConfigurationManager extends AbstractManager
{

    /**
     * The query configuration
     *
     * @var array
     */
    protected array $queryConfiguration;

    /**
     * Sets the query configuration
     *
     * @param array $queryConfiguration
     *
     * @return void
     */
    public function setQueryConfiguration(array $queryConfiguration): void
    {
        $this->queryConfiguration = $queryConfiguration;
    }

    /**
     * Sets a query configuration parameter
     *
     * @param string $key
     *            The key
     * @param mixed $value
     *            The value
     *
     * @return void
     */
    public function setQueryConfigurationParameter(string $key, mixed $value): void
    {
        $this->queryConfiguration[$key] = $value;
    }

    /**
     * Gets the main table.
     *
     * @return string
     */
    public function getMainTable(): string
    {
        return $this->queryConfiguration['mainTable'] ?? '';
    }

    /**
     * Gets the foreign tables.
     *
     * @return string
     */
    public function getForeignTables(): string
    {
        return $this->queryConfiguration['foreignTables'] ?? '';
    }

    /**
     * Gets the SELECT clause.
     *
     * @return string
     */
    public function getSelectClause(): string
    {
        if (empty($this->queryConfiguration['selectClause'])) {
            return $this->getMainTable() . '.*';
        } else {
            return $this->queryConfiguration['selectClause'];
        }
    }

    /**
     * Gets the aliases.
     *
     * @return string
     */
    public function getAliases(): string
    {
        return $this->queryConfiguration['aliases'] ?? '';
    }

    /**
     * Gets the WHERE Clause.
     *
     * @return string
     */
    public function getWhereClause(): string
    {
        // If a WhereTag is used, its WHERE Clause overrides the configuration one
        $whereTagKey = $this->controller->getUriManager()->getWhereTagKey();

        if (empty($whereTagKey) === false) {
            $whereTag = $this->getWhereTag($whereTagKey);
            if (isset($whereTag['whereClause'])) {
                return $whereTag['whereClause'];
            }
        }

        // Returns the configuration WHERE clause
        if (empty($this->queryConfiguration['whereClause'])) {
            $whereClause = '1';
        } else {
            $whereClause = $this->queryConfiguration['whereClause'];
        }

        // Adds the system language WHERE part if needed
        $tcaConfigurationManager = $this->controller->getTcaConfigurationManager();
        $tableName = $this->getMainTable();
        $isOverridedTableForLocalization = $this->controller
            ->getLibraryConfigurationManager()
            ->isOverridedTableForLocalization($tableName);
            if ($tcaConfigurationManager->isLocalized($tableName) && ! $isOverridedTableForLocalization) {
                $languageField = $tcaConfigurationManager->getTcaCtrlLanguageField($tableName);
            $whereClause .= ' AND ' . $tableName . '.' . $languageField . ' IN (0,-1)';
        }

        return $whereClause;
    }

    /**
     * Gets the GROUP BY Clause.
     *
     * @return string
     */
    public function getGroupByClause(): string
    {
        return $this->queryConfiguration['groupByClause'] ?? '';

    }

    /**
     * Gets the ORDER BY Clause.
     *
     * @return string
     */
    public function getOrderByClause(): string
    {
        // If a WhereTag is used, its ORDER BY Clause overrides the configuration one
        $whereTagKey = $this->controller->getUriManager()->getWhereTagKey();

        if (empty($whereTagKey) === false) {
            $whereTag = $this->getWhereTag($whereTagKey);
            if (isset($whereTag['orderByClause'])) {
                return $whereTag['orderByClause'];
            }
        }

        // Returns the configuration ORDER BY clause if any otherwise the ORDER BY clause from the TCA
        if (empty($this->queryConfiguration['orderByClause'])) {
            return $this->controller->getTcaConfigurationManager()->getTcaOrderByClause($this->getMainTable());
        } else {
            return $this->queryConfiguration['orderByClause'];
        }
    }

    /**
     * Gets the LIMIT BY Clause.
     *
     * @return string
     */
    public function getLimitClause(): string
    {
        return $this->queryConfiguration['limitClause'] ?? '';
    }

    /**
     * Gets the WHERE Tag
     *
     * @param string $whereTagKey
     *            The WHERE Tag key
     *
     * @return array or null
     */
    public function getWhereTag(string $whereTagKey): ?array
    {
        return $this->queryConfiguration['whereTags'][$whereTagKey] ?? null;
    }

    /**
     * Gets the uid part to the WHERE clause
     *
     * @return string
     */
    public function getUidPartToWhereClause(): string
    {
        $uidForWhereClause = intval($this->controller->getUriManager()->getUid());
        $whereClausePart = ' AND ' . $this->getMainTable() . '.uid = ' . $uidForWhereClause;

        return $whereClausePart;
    }

    /**
     * Sets an additionalpart to the WHERE clause
     *
     * @param string $whereClausePart
     *            The part to add
     *
     * @return void
     */
    public function setAdditionalPartToWhereClause(string $whereClausePart): void
    {
        if (empty($this->queryConfiguration['additionalWhereClause'])) {
            $this->queryConfiguration['additionalWhereClause'] = $whereClausePart;
        } else {
            $this->queryConfiguration['additionalWhereClause'] .= $whereClausePart;
        }
    }

    /**
     * Gets the additional part to the WHERE clause
     *
     * @return string
     */
    public function getAdditionalPartToWhereClause(): string
    {
        return $this->queryConfiguration['additionalWhereClause'] ?? '';
    }
}
