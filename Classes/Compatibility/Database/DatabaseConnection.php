<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace YolfTypo3\SavLibraryPlus\Compatibility\Database;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SAV Library Plus is a quite old extension with many functionnalities which has evolved since TYPO3 4.x
 * The querier concepts used in this extension have not yet be translated to the doctrine-dbal API.
 *
 * This class is inspired from the typo3-db-legacy (\TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection) where the methods
 * needed for SAV Library Plus were kept. By doing so, SAV Library Plus remains a all-in-one extension without dependencies.
 */
class DatabaseConnection
{

    /**
     * The AND constraint in where clause
     *
     * @var string
     */
    const AND_Constraint = 'AND';

    /**
     * The OR constraint in where clause
     *
     * @var string
     */
    const OR_Constraint = 'OR';

    /**
     * Set "TRUE" or "1" if you want database errors outputted.
     * Set to "2" if you also want successful database actions outputted.
     *
     * @var bool|int
     */
    public $debugOutput = false;

    /**
     * Internally: Set to last built query (not necessarily executed...)
     *
     * @var string
     */
    public $debug_lastBuiltQuery = '';

    /**
     * Set "TRUE" if you want the last built query to be stored in $debug_lastBuiltQuery independent of $this->debugOutput
     *
     * @var bool
     */
    public $store_lastBuiltQuery = false;

    /**
     * Set this to 1 to get queries explained (devIPmask must match).
     * Set the value to 2 to the same but disregarding the devIPmask.
     * There is an alternative option to enable explain output in the admin panel under "TypoScript", which will produce much nicer output, but only works in FE.
     *
     * @var bool
     */
    public $explainOutput = 0;

    /**
     *
     * @var string Database host to connect to
     */
    protected $databaseHost = '';

    /**
     *
     * @var int Database port to connect to
     */
    protected $databasePort = 3306;

    /**
     *
     * @var string|NULL Database socket to connect to
     */
    protected $databaseSocket = null;

    /**
     *
     * @var string Database name to connect to
     */
    protected $databaseName = '';

    /**
     *
     * @var string Database user to connect with
     */
    protected $databaseUsername = '';

    /**
     *
     * @var string Database password to connect with
     */
    protected $databaseUserPassword = '';

    /**
     *
     * @var bool TRUE if database connection should be persistent
     * @see http://php.net/manual/de/mysqli.persistconns.php
     */
    protected $persistentDatabaseConnection = false;

    /**
     *
     * @var bool TRUE if connection between client and sql server is compressed
     */
    protected $connectionCompression = false;

    /**
     * The charset for the connection; will be passed on to
     * mysqli_set_charset during connection initialization.
     *
     * @var string
     */
    protected $connectionCharset = 'utf8';

    /**
     *
     * @var array List of commands executed after connection was established
     */
    protected $initializeCommandsAfterConnect = [];

    /**
     *
     * @var bool TRUE if database connection is established
     */
    protected $isConnected = false;

    /**
     *
     * @var \mysqli $link Default database link object
     */
    protected $link = null;

    /**
     * Default character set, applies unless character set or collation are explicitly set
     *
     * @var string
     */
    public $default_charset = 'utf8';

    /**
     *
     * @var array<PostProcessQueryHookInterface>
     */
    protected $preProcessHookObjects = [];

    /**
     *
     * @var array<PreProcessQueryHookInterface>
     */
    protected $postProcessHookObjects = [];

    /**
     * Internal property to mark if a deprecation log warning has been thrown in this request
     * in order to avoid a load of deprecation.
     *
     * @var bool
     */
    protected $deprecationWarningThrown = false;


    /**
     * Extension key
     * @var string
     */
    public $extensionKey;

    /**
     * Initialize the database connection
     */
    public function initialize()
    {
        // Intentionally blank as this will be overloaded by DBAL
    }

    /**
     * **********************************
     *
     * Query execution
     *
     * These functions are the RECOMMENDED DBAL functions for use in your applications
     * Using these functions will allow the DBAL to use alternative ways of accessing data (contrary to if a query is returned!)
     * They compile a query AND execute it immediately and then return the result
     * This principle heightens our ability to create various forms of DBAL of the functions.
     * Generally: We want to return a result pointer/object, never queries.
     * Also, having the table name together with the actual query execution allows us to direct the request to other databases.
     *
     * ************************************
     */

    /**
     * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
     * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
     *
     * @param string $table
     *            Table name
     * @param array $fields_values
     *            Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
     * @param bool|array|string $no_quote_fields
     *            See fullQuoteArray()
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function exec_INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        $res = $this->query($this->INSERTquery($table, $fields_values, $no_quote_fields));
        if ($this->debugOutput) {
            $this->debug('exec_INSERTquery');
        }

        return $res;
    }

    /**
     * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ.
     * 'uid=...') from the array with field/value pairs $fields_values.
     * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
     *
     * @param string $table
     *            Database tablename
     * @param string $where
     *            WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
     * @param array $fields_values
     *            Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
     * @param bool|array|string $no_quote_fields
     *            See fullQuoteArray()
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        $res = $this->query($this->UPDATEquery($table, $where, $fields_values, $no_quote_fields));
        if ($this->debugOutput) {
            $this->debug('exec_UPDATEquery');
        }

        return $res;
    }

    /**
     * Creates and executes a DELETE SQL-statement for $table where $where-clause
     *
     * @param string $table
     *            Database tablename
     * @param string $where
     *            WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function exec_DELETEquery($table, $where)
    {
        $res = $this->query($this->DELETEquery($table, $where));
        if ($this->debugOutput) {
            $this->debug('exec_DELETEquery');
        }

        return $res;
    }

    /**
     * Creates and executes a SELECT SQL-statement
     * Using this function specifically allow us to handle the LIMIT feature independently of DB.
     *
     * @param string $select_fields
     *            List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
     * @param string $from_table
     *            Table(s) from which to select. This is what comes right after "FROM ...". Required value.
     * @param string $where_clause
     *            Additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
     * @param string $groupBy
     *            Optional GROUP BY field(s), if none, supply blank string.
     * @param string $orderBy
     *            Optional ORDER BY field(s), if none, supply blank string.
     * @param string $limit
     *            Optional LIMIT value ([begin,]max), if none, supply blank string.
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '')
    {
        $query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
        $res = $this->query($query);
        if ($this->debugOutput) {
            $this->debug('exec_SELECTquery');
        }
        if ($this->explainOutput) {
            $this->explain($query, $from_table, $res->num_rows);
        }
        return $res;
    }

    /**
     * Creates and executes a SELECT SQL-statement AND traverse result set and returns array with records in.
     *
     * @param string $select_fields
     *            List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
     * @param string $from_table
     *            Table(s) from which to select. This is what comes right after "FROM ...". Required value.
     * @param string $where_clause
     *            Additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
     * @param string $groupBy
     *            Optional GROUP BY field(s), if none, supply blank string.
     * @param string $orderBy
     *            Optional ORDER BY field(s), if none, supply blank string.
     * @param string $limit
     *            Optional LIMIT value ([begin,]max), if none, supply blank string.
     * @param string $uidIndexField
     *            If set, the result array will carry this field names value as index. Requires that field to be selected of course!
     * @return array|NULL Array of rows, or NULL in case of SQL error
     * @see exec_SELECTquery()
     * @throws \InvalidArgumentException
     */
    public function exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '')
    {
        $res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
        if ($this->sql_error()) {
            $this->sql_free_result($res);
            return null;
        }
        $output = [];
        $firstRecord = true;
        while ($record = $this->sql_fetch_assoc($res)) {
            if ($uidIndexField) {
                if ($firstRecord) {
                    $firstRecord = false;
                    if (! array_key_exists($uidIndexField, $record)) {
                        $this->sql_free_result($res);
                        throw new \InvalidArgumentException('The given $uidIndexField "' . $uidIndexField . '" is not available in the result.', 1432933855);
                    }
                }
                $output[$record[$uidIndexField]] = $record;
            } else {
                $output[] = $record;
            }
        }
        $this->sql_free_result($res);
        return $output;
    }

    /**
     * Central query method.
     * Also checks if there is a database connection.
     * Use this to execute database queries instead of directly calling $this->link->query()
     *
     * @param string $query
     *            The query to send to the database
     * @return bool|\mysqli_result
     */
    protected function query($query)
    {
        if (! $this->isConnected) {
            $this->connectDB();
        }
        return $this->link->query($query);
    }

    /**
     * ************************************
     *
     * Query building
     *
     * ************************************
     */
    /**
     * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
     *
     * @param string $table
     *            See exec_INSERTquery()
     * @param array $fields_values
     *            See exec_INSERTquery()
     * @param bool|array|string $no_quote_fields
     *            See fullQuoteArray()
     * @return string|NULL Full SQL query for INSERT, NULL if $fields_values is empty
     */
    public function INSERTquery($table, $fields_values, $no_quote_fields = false)
    {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this
        // function (contrary to values in the arrays which may be insecure).
        if (! is_array($fields_values) || empty($fields_values)) {
            return null;
        }

        // Quote and escape values
        $fields_values = $this->fullQuoteArray($fields_values, $table, $no_quote_fields, true);
        // Build query
        $query = 'INSERT INTO ' . $table . ' (' . implode(',', array_keys($fields_values)) . ') VALUES ' . '(' . implode(',', $fields_values) . ')';
        // Return query
        if ($this->debugOutput || $this->store_lastBuiltQuery) {
            $this->debug_lastBuiltQuery = $query;
        }
        return $query;
    }

    /**
     * Creates an UPDATE SQL-statement for $table where $where-clause (typ.
     * 'uid=...') from the array with field/value pairs $fields_values.
     *
     *
     * @param string $table
     *            See exec_UPDATEquery()
     * @param string $where
     *            See exec_UPDATEquery()
     * @param array $fields_values
     *            See exec_UPDATEquery()
     * @param bool|array|string $no_quote_fields
     *            See fullQuoteArray()
     * @throws \InvalidArgumentException
     * @return string Full SQL query for UPDATE
     */
    public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = false)
    {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this
        // function (contrary to values in the arrays which may be insecure).
        if (is_string($where)) {
            $fields = [];
            if (is_array($fields_values) && ! empty($fields_values)) {
                // Quote and escape values
                $nArr = $this->fullQuoteArray($fields_values, $table, $no_quote_fields, true);
                foreach ($nArr as $k => $v) {
                    $fields[] = $k . '=' . $v;
                }
            }
            // Build query
            $query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) . ((string) $where !== '' ? ' WHERE ' . $where : '');
            if ($this->debugOutput || $this->store_lastBuiltQuery) {
                $this->debug_lastBuiltQuery = $query;
            }
            return $query;
        } else {
            throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !', 1270853880);
        }
    }

    /**
     * Creates a DELETE SQL-statement for $table where $where-clause
     *
     * @param string $table
     *            See exec_DELETEquery()
     * @param string $where
     *            See exec_DELETEquery()
     * @return string Full SQL query for DELETE
     * @throws \InvalidArgumentException
     */
    public function DELETEquery($table, $where)
    {
        if (is_string($where)) {
            // Table and fieldnames should be "SQL-injection-safe" when supplied to this function
            $query = 'DELETE FROM ' . $table . ((string) $where !== '' ? ' WHERE ' . $where : '');
            if ($this->debugOutput || $this->store_lastBuiltQuery) {
                $this->debug_lastBuiltQuery = $query;
            }
            return $query;
        } else {
            throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !', 1270853881);
        }
    }

    /**
     * Creates a SELECT SQL-statement
     *
     * @param string $select_fields
     *            See exec_SELECTquery()
     * @param string $from_table
     *            See exec_SELECTquery()
     * @param string $where_clause
     *            See exec_SELECTquery()
     * @param string $groupBy
     *            See exec_SELECTquery()
     * @param string $orderBy
     *            See exec_SELECTquery()
     * @param string $limit
     *            See exec_SELECTquery()
     * @return string Full SQL query for SELECT
     */
    public function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '')
    {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function
        // Build basic query
        $query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . ((string) $where_clause !== '' ? ' WHERE ' . $where_clause : '');
        // Group by
        $query .= (string) $groupBy !== '' ? ' GROUP BY ' . $groupBy : '';
        // Order by
        $query .= (string) $orderBy !== '' ? ' ORDER BY ' . $orderBy : '';
        // Group by
        $query .= (string) $limit !== '' ? ' LIMIT ' . $limit : '';
        // Return query
        if ($this->debugOutput || $this->store_lastBuiltQuery) {
            $this->debug_lastBuiltQuery = $query;
        }
        return $query;
    }

    /**
     * ************************************
     *
     * Various helper functions
     *
     * Functions recommended to be used for
     * - escaping values,
     * - cleaning lists of values,
     * - stripping of excess ORDER BY/GROUP BY keywords
     *
     * ************************************
     */
    /**
     * Escaping and quoting values for SQL statements.
     *
     * @param string $str
     *            Input string
     * @param string $table
     *            Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
     * @param bool $allowNull
     *            Whether to allow NULL values
     * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
     * @see quoteStr()
     */
    public function fullQuoteStr($str, $table, $allowNull = false)
    {
        if (! $this->isConnected) {
            $this->connectDB();
        }
        if ($allowNull && $str === null) {
            return 'NULL';
        }
        if (is_bool($str)) {
            $str = (int) $str;
        }

        return '\'' . $this->link->real_escape_string($str) . '\'';
    }

    /**
     * Will fullquote all values in the one-dimensional array so they are ready to "implode" for an sql query.
     *
     * @param array $arr
     *            Array with values (either associative or non-associative array)
     * @param string $table
     *            Table name for which to quote
     * @param bool|array|string $noQuote
     *            List/array of keys NOT to quote (eg. SQL functions) - ONLY for associative arrays
     * @param bool $allowNull
     *            Whether to allow NULL values
     * @return array The input array with the values quoted
     * @see cleanIntArray()
     */
    public function fullQuoteArray($arr, $table, $noQuote = false, $allowNull = false)
    {
        if (is_string($noQuote)) {
            $noQuote = explode(',', $noQuote);
        } elseif (! is_array($noQuote)) {
            $noQuote = (bool) $noQuote;
        }
        if ($noQuote === true) {
            return $arr;
        }
        foreach ($arr as $k => $v) {
            if ($noQuote === false || ! in_array($k, $noQuote)) {
                $arr[$k] = $this->fullQuoteStr($v, $table, $allowNull);
            }
        }
        return $arr;
    }

    /**
     * ************************************
     *
     * MySQL(i) wrapper functions
     * (For use in your applications)
     *
     * ************************************
     */
    /**
     * Executes query
     * MySQLi query() wrapper function
     * Beware: Use of this method should be avoided as it is experimentally supported by DBAL.
     * You should consider
     * using exec_SELECTquery() and similar methods instead.
     *
     * @param string $query
     *            Query to execute
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function sql_query($query)
    {
        $res = $this->query($query);
        if ($this->debugOutput) {
            $this->debug('sql_query', $query);
        }
        return $res;
    }

    /**
     * Returns the error status on the last query() execution
     *
     * @return string MySQLi error string.
     */
    public function sql_error()
    {
        return $this->link->error;
    }

    /**
     * Returns the error number on the last query() execution
     *
     * @return int MySQLi error number
     */
    public function sql_errno()
    {
        return $this->link->errno;
    }

    /**
     * Returns the number of selected rows.
     *
     * @param bool|\mysqli_result|object $res
     *            MySQLi result object / DBAL object
     * @return int Number of resulting rows
     */
    public function sql_num_rows($res)
    {
        if ($this->debug_check_recordset($res)) {
            return $res->num_rows;
        } else {
            return false;
        }
    }

    /**
     * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
     * MySQLi fetch_assoc() wrapper function
     *
     * @param bool|\mysqli_result|object $res
     *            MySQLi result object / DBAL object
     * @return array|bool Associative array of result row.
     */
    public function sql_fetch_assoc($res)
    {
        if ($this->debug_check_recordset($res)) {
            $result = $res->fetch_assoc();
            if ($result === null) {
                // Needed for compatibility
                $result = false;
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
     * The array contains the values in numerical indices.
     * MySQLi fetch_row() wrapper function
     *
     * @param bool|\mysqli_result|object $res
     *            MySQLi result object / DBAL object
     * @return array|bool Array with result rows.
     */
    public function sql_fetch_row($res)
    {
        if ($this->debug_check_recordset($res)) {
            $result = $res->fetch_row();
            if ($result === null) {
                // Needed for compatibility
                $result = false;
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Free result memory
     * free_result() wrapper function
     *
     * @param bool|\mysqli_result|object $res
     *            MySQLi result object / DBAL object
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function sql_free_result($res)
    {
        if ($this->debug_check_recordset($res) && is_object($res)) {
            $res->free();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the ID generated from the previous INSERT operation
     *
     * @return int The uid of the last inserted record.
     */
    public function sql_insert_id()
    {
        return $this->link->insert_id;
    }

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
     *
     * @return int Number of rows affected by last query
     */
    public function sql_affected_rows()
    {
        return $this->link->affected_rows;
    }

    /**
     * Move internal result pointer
     *
     * @param bool|\mysqli_result|object $res
     *            MySQLi result object / DBAL object
     * @param int $seek
     *            Seek result number.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function sql_data_seek($res, $seek)
    {
        $this->logDeprecation();
        if ($this->debug_check_recordset($res)) {
            return $res->data_seek($seek);
        } else {
            return false;
        }
    }

    /**
     * Get the type of the specified field in a result
     * mysql_field_type() wrapper function
     *
     * @param bool|\mysqli_result|object $res
     *            MySQLi result object / DBAL object
     * @param int $pointer
     *            Field index.
     * @return string Returns the name of the specified field index, or FALSE on error
     */
    public function sql_field_type($res, $pointer)
    {
        // mysql_field_type compatibility map
        // taken from: http://www.php.net/manual/en/mysqli-result.fetch-field-direct.php#89117
        // Constant numbers see http://php.net/manual/en/mysqli.constants.php
        $mysql_data_type_hash = [
            1 => 'tinyint',
            2 => 'smallint',
            3 => 'int',
            4 => 'float',
            5 => 'double',
            7 => 'timestamp',
            8 => 'bigint',
            9 => 'mediumint',
            10 => 'date',
            11 => 'time',
            12 => 'datetime',
            13 => 'year',
            16 => 'bit',
            // 252 is currently mapped to all text and blob types (MySQL 5.0.51a)
            253 => 'varchar',
            254 => 'char',
            246 => 'decimal'
        ];
        if ($this->debug_check_recordset($res)) {
            $metaInfo = $res->fetch_field_direct($pointer);
            if ($metaInfo === false) {
                return false;
            }
            return $mysql_data_type_hash[$metaInfo->type];
        } else {
            return false;
        }
    }

    /**
     * Open a (persistent) connection to a MySQL server
     *
     * @return bool|void
     * @throws \RuntimeException
     */
    public function sql_pconnect()
    {
        if ($this->isConnected) {
            return $this->link;
        }

        if (! extension_loaded('mysqli')) {
            throw new \RuntimeException('Database Error: PHP mysqli extension not loaded. This is a must have for TYPO3 CMS!', 1271492607);
        }

        $host = $this->persistentDatabaseConnection ? 'p:' . $this->databaseHost : $this->databaseHost;

        // We are not using the TYPO3 CMS shim here as the database parameters in this class
        // are settable externally. This requires building the connection parameter array
        // just in time when establishing the connection.
        $connection = \Doctrine\DBAL\DriverManager::getConnection([
            'driver' => 'mysqli',
            'wrapperClass' => Connection::class,
            'host' => $host,
            'port' => (int) $this->databasePort,
            'unix_socket' => $this->databaseSocket,
            'user' => $this->databaseUsername,
            'password' => $this->databaseUserPassword,
            'charset' => $this->connectionCharset
        ]);

        // Mimic the previous behavior of returning false on connection errors
        try {
            /** @var \Doctrine\DBAL\Driver\Mysqli\MysqliConnection $mysqliConnection */
            $mysqliConnection = $connection->getWrappedConnection();
            $this->link = $mysqliConnection->getWrappedResourceHandle();
        } catch (\Doctrine\DBAL\Exception\ConnectionException $exception) {
            return false;
        }

        if ($connection->isConnected()) {
            $this->isConnected = true;

            foreach ($this->initializeCommandsAfterConnect as $command) {
                if ($this->query($command) === false) {
                    self::getLogger()->log(LogLevel::EMERGENCY, 'Could not initialize DB connection with query "' . $command . '": ' . $this->sql_error(), [
                        'extension' => 'core'
                    ]);
                }
            }
            $this->checkConnectionCharset();
        } else {
            // @todo This should raise an exception. Would be useful especially to work during installation.
            $error_msg = $this->link->connect_error;
            $this->link = null;
            self::getLogger()->log(LogLevel::EMERGENCY, 'Could not connect to MySQL server ' . $host . ' with user ' . $this->databaseUsername . ': ' . $error_msg, [
                'extension' => 'core'
            ]);
        }

        return $this->link;
    }

    /**
     * Select a SQL database
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function sql_select_db()
    {
        if (! $this->isConnected) {
            $this->connectDB();
        }

        $ret = $this->link->select_db($this->databaseName);
        if (! $ret) {
            self::getLogger()->log(LogLevel::ERROR, 'Could not select MySQL database ' . $this->databaseName . ': ' . $this->sql_error(), [
                'extension' => 'core'
            ]);
        }
        return $ret;
    }

    /**
     * ************************************
     *
     * SQL admin functions
     * (For use in the Install Tool and Extension Manager)
     *
     * ************************************
     */

    /**
     * Returns information about each field in the $table (quering the DBMS)
     * In a DBAL this should look up the right handler for the table and return compatible information
     * This function is important not only for the Install Tool but probably for
     * DBALs as well since they might need to look up table specific information
     * in order to construct correct queries. In such cases this information should
     * probably be cached for quick delivery.
     *
     * @param string $tableName
     *            Table name
     * @return array Field information in an associative array with fieldname => field row
     */
    public function admin_get_fields($tableName)
    {
        $output = [];
        $columns_res = $this->query('SHOW FULL COLUMNS FROM `' . $tableName . '`');
        if ($columns_res !== false) {
            while ($fieldRow = $columns_res->fetch_assoc()) {
                $output[$fieldRow['Field']] = $fieldRow;
            }
            $columns_res->free();
        }
        return $output;
    }

    /**
     * ****************************
     *
     * Connect handling
     *
     * ****************************
     */

    /**
     * Set database host
     *
     * @param string $host
     */
    public function setDatabaseHost($host = 'localhost')
    {
        $this->disconnectIfConnected();
        $this->databaseHost = $host;
    }

    /**
     * Set database port
     *
     * @param int $port
     */
    public function setDatabasePort($port = 3306)
    {
        $this->disconnectIfConnected();
        $this->databasePort = (int) $port;
    }

    /**
     * Set database socket
     *
     * @param string|NULL $socket
     */
    public function setDatabaseSocket($socket = null)
    {
        $this->disconnectIfConnected();
        $this->databaseSocket = $socket;
    }

    /**
     * Set database name
     *
     * @param string $name
     */
    public function setDatabaseName($name)
    {
        $this->disconnectIfConnected();
        $this->databaseName = $name;
    }

    /**
     * Set database username
     *
     * @param string $username
     */
    public function setDatabaseUsername($username)
    {
        $this->disconnectIfConnected();
        $this->databaseUsername = $username;
    }

    /**
     * Set database password
     *
     * @param string $password
     */
    public function setDatabasePassword($password)
    {
        $this->disconnectIfConnected();
        $this->databaseUserPassword = $password;
    }

    /**
     * Set persistent database connection
     *
     * @param bool $persistentDatabaseConnection
     * @see http://php.net/manual/de/mysqli.persistconns.php
     */
    public function setPersistentDatabaseConnection($persistentDatabaseConnection)
    {
        $this->disconnectIfConnected();
        $this->persistentDatabaseConnection = (bool) $persistentDatabaseConnection;
    }

    /**
     * Set connection compression.
     * Might be an advantage, if SQL server is not on localhost
     *
     * @param bool $connectionCompression
     *            TRUE if connection should be compressed
     */
    public function setConnectionCompression($connectionCompression)
    {
        $this->disconnectIfConnected();
        $this->connectionCompression = (bool) $connectionCompression;
    }

    /**
     * Set commands to be fired after connection was established
     *
     * @param array $commands
     *            List of SQL commands to be executed after connect
     */
    public function setInitializeCommandsAfterConnect(array $commands)
    {
        $this->disconnectIfConnected();
        $this->initializeCommandsAfterConnect = $commands;
    }

    /**
     * Set the charset that should be used for the MySQL connection.
     * The given value will be passed on to mysqli_set_charset().
     *
     * The default value of this setting is utf8.
     *
     * @param string $connectionCharset
     *            The connection charset that will be passed on to mysqli_set_charset() when connecting the database. Default is utf8.
     */
    public function setConnectionCharset($connectionCharset = 'utf8')
    {
        $this->disconnectIfConnected();
        $this->connectionCharset = $connectionCharset;
    }

    /**
     * Connects to database for TYPO3 sites:
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    public function connectDB()
    {
        // Early return if connected already
        if ($this->isConnected) {
            return;
        }

        if (! $this->databaseName) {
            throw new \RuntimeException('TYPO3 Fatal Error: No database selected!', 1270853882);
        }

        if ($this->sql_pconnect()) {
            if (! $this->sql_select_db()) {
                throw new \RuntimeException('TYPO3 Fatal Error: Cannot connect to the current database, "' . $this->databaseName . '"!', 1270853883);
            }
        } else {
            throw new \RuntimeException('TYPO3 Fatal Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!', 1270853884);
        }
    }

    /**
     * Checks if database is connected
     *
     * @return bool
     */
    public function isConnected()
    {
        // We think we're still connected
        if ($this->isConnected) {
            // Check if this is really the case or if the database server has gone away for some reason
            // Using mysqlnd ping() does not reconnect (which we would not want anyway since charset etc would not be reinitialized that way)
            $this->isConnected = $this->link->ping();
        }
        return $this->isConnected;
    }

    /**
     * Checks if the current connection character set has the same value
     * as the connectionCharset variable.
     *
     * To determine the character set these MySQL session variables are
     * checked: character_set_client, character_set_results and
     * character_set_connection.
     *
     * If the character set does not match or if the session variables
     * can not be read a RuntimeException is thrown.
     *
     * @throws \RuntimeException
     */
    protected function checkConnectionCharset()
    {
        $sessionResult = $this->sql_query('SHOW SESSION VARIABLES LIKE \'character_set%\'');

        if ($sessionResult === false) {
            self::getLogger()->log(LogLevel::ERROR, 'Error while retrieving the current charset session variables from the database: ' . $this->sql_error(), [
                'extension' => 'core'
            ]);
            throw new \RuntimeException('TYPO3 Fatal Error: Could not determine the current charset of the database.', 1381847136);
        }

        $charsetVariables = [];
        while (($row = $this->sql_fetch_row($sessionResult)) !== false) {
            $variableName = $row[0];
            $variableValue = $row[1];
            $charsetVariables[$variableName] = $variableValue;
        }
        $this->sql_free_result($sessionResult);

        // These variables are set with the "Set names" command which was
        // used in the past. This is why we check them.
        $charsetRequiredVariables = [
            'character_set_client',
            'character_set_results',
            'character_set_connection'
        ];

        $hasValidCharset = true;
        foreach ($charsetRequiredVariables as $variableName) {
            if (empty($charsetVariables[$variableName])) {
                self::getLogger()->log(LogLevel::ERROR, 'A required session variable is missing in the current MySQL connection: ' . $variableName, [
                    'extension' => 'core'
                ]);
                throw new \RuntimeException('TYPO3 Fatal Error: Could not determine the value of the database session variable: ' . $variableName, 1381847779);
            }

            if ($charsetVariables[$variableName] !== $this->connectionCharset) {
                $hasValidCharset = false;
                break;
            }
        }

        if (! $hasValidCharset) {
            throw new \RuntimeException('It looks like the character set ' . $this->connectionCharset . ' is not used for this connection.', 1389697515);
        }
    }

    /**
     * Disconnect from database if connected
     */
    protected function disconnectIfConnected()
    {
        if ($this->isConnected) {
            $this->link->close();
            $this->isConnected = false;
        }
    }

    /**
     * ****************************
     *
     * Debugging
     *
     * ****************************
     */
    /**
     * Debug function: Outputs error if any
     *
     * @param string $func
     *            Function calling debug()
     * @param string $query
     *            Last query if not last built query
     */
    public function debug($func, $query = '')
    {
        $error = $this->sql_error();
        if ($error || (int) $this->debugOutput === 2) {
            $message = sprintf("\n--> Extension: %s\n--> Caller: %s\n--> Error: %s\n--> Last built query: %s\n----------",
                $this->extensionKey,
                DatabaseConnection::class . '::' . $func,
                $error,
                $query ? $query : $this->debug_lastBuiltQuery
            );
            if (isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['YolfTypo3']['SavLibraryPlus']['writerConfiguration'])) {
                self::getLogger()->log(LogLevel::DEBUG, $message);
            } else {
                debug($message);
            }
        }
    }

    /**
     * Checks if record set is valid and writes debugging information into devLog if not.
     *
     * @param
     *            bool|\mysqli_result|object MySQLi result object / DBAL object
     * @return bool TRUE if the record set is valid, FALSE otherwise
     */
    public function debug_check_recordset($res)
    {
        if ($res !== false && $res !== null) {
            return true;
        }
        $trace = debug_backtrace(0);
        array_shift($trace);
        $msg = 'Invalid database result detected: function TYPO3\\CMS\\Typo3DbLegacy\\Database\\DatabaseConnection->' . $trace[0]['function'] . ' called from file ' . substr($trace[0]['file'], (strlen(Environment::getPublicPath() . '/') + 2)) . ' in line ' . $trace[0]['line'] . '.';
        self::getLogger()->log(LogLevel::ERROR, $msg . ' Use a devLog extension to get more details.', [
            'extension' => 'core'
        ]);

        return false;
    }

    /**
     * Explain select queries
     * If $this->explainOutput is set, SELECT queries will be explained here.
     * Only queries with more than one possible result row will be displayed.
     * The output is either printed as raw HTML output or embedded into the TS admin panel (checkbox must be enabled!)
     *
     * @todo Feature is not DBAL-compliant
     *
     * @param string $query
     *            SQL query
     * @param string $from_table
     *            Table(s) from which to select. This is what comes right after "FROM ...". Required value.
     * @param int $row_count
     *            Number of resulting rows
     * @return bool TRUE if explain was run, FALSE otherwise
     */
    protected function explain($query, $from_table, $row_count)
    {
        $debugAllowedForIp = GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        if ((int) $this->explainOutput == 1 || ((int) $this->explainOutput == 2 && $debugAllowedForIp)) {
            // Raw HTML output
            $explainMode = 1;
        } elseif ((int) $this->explainOutput == 3) {
            // Embed the output into the TS admin panel
            $explainMode = 2;
        } else {
            return false;
        }
        $error = $this->sql_error();
        $trail = \TYPO3\CMS\Core\Utility\DebugUtility::debugTrail();
        $explain_tables = [];
        $explain_output = [];
        $res = $this->sql_query('EXPLAIN ' . $query, $this->link);
        if (is_a($res, '\\mysqli_result')) {
            while ($tempRow = $this->sql_fetch_assoc($res)) {
                $explain_output[] = $tempRow;
                $explain_tables[] = $tempRow['table'];
            }
            $this->sql_free_result($res);
        }
        $indices_output = [];
        // Notice: Rows are skipped if there is only one result, or if no conditions are set
        if ($explain_output[0]['rows'] > 1 || $explain_output[0]['type'] === 'ALL') {
            // Only enable output if it's really useful
            $debug = true;
            foreach ($explain_tables as $table) {
                $tableRes = $this->sql_query('SHOW TABLE STATUS LIKE \'' . $table . '\'');
                $isTable = $this->sql_num_rows($tableRes);
                if ($isTable) {
                    $res = $this->sql_query('SHOW INDEX FROM ' . $table, $this->link);
                    if (is_a($res, '\\mysqli_result')) {
                        while ($tempRow = $this->sql_fetch_assoc($res)) {
                            $indices_output[] = $tempRow;
                        }
                        $this->sql_free_result($res);
                    }
                }
                $this->sql_free_result($tableRes);
            }
        } else {
            $debug = false;
        }
        if ($debug) {
            if ($explainMode) {
                $data = [];
                $data['query'] = $query;
                $data['trail'] = $trail;
                $data['row_count'] = $row_count;
                if ($error) {
                    $data['error'] = $error;
                }
                if (! empty($explain_output)) {
                    $data['explain'] = $explain_output;
                }
                if (! empty($indices_output)) {
                    $data['indices'] = $indices_output;
                }
                if ($explainMode == 1) {
                    \TYPO3\CMS\Core\Utility\DebugUtility::debug($data, 'Tables: ' . $from_table, 'DB SQL EXPLAIN');
                } elseif ($explainMode == 2) {
                    /** @var TimeTracker $timeTracker */
                    $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
                    $timeTracker->setTSselectQuery($data);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Serialize destructs current connection
     *
     * @return array All protected properties that should be saved
     */
    public function __sleep()
    {
        $this->disconnectIfConnected();
        return [
            'debugOutput',
            'explainOutput',
            'databaseHost',
            'databasePort',
            'databaseSocket',
            'databaseName',
            'databaseUsername',
            'databaseUserPassword',
            'persistentDatabaseConnection',
            'connectionCompression',
            'initializeCommandsAfterConnect',
            'default_charset'
        ];
    }

    /**
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected static function getLogger()
    {
        /** @var \TYPO3\CMS\Core\Log\LogManager $logManager */
        $logManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);

        return $logManager->getLogger(get_class());
    }
}
