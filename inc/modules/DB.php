<?php

/** @noinspection ThrowRawExceptionInspection */

/**
 * Class Database.
 */
class DB extends Base
{
    private static $db_toTimes = []; // global cache array

    private static $db_to = []; // global cache array

    private static $pdo = null;

    private static $connected_db = '';

    private static $connected_db_name = '';

    private static $table_names = [];

    private static $column_names = [];

    private static $_columntExistsCache = [];

    public static $affected_rows = 0;

    public static $realLastInsertId = null;

    public static function init()
    {
        global $Configuration;
        self::connectDB();
    }

    public static function connectDB(): void
    {
        if (self::$connected_db === 'twm') {
            return;
        }

        $db_port = Config::get('db_port', 3306);
        $db_type = Config::get('db_type', 'mysql');
        $db_host = Config::get('host');
        $db_name = Config::get('database');
        $db_charset = Config::get('db_charset', 'utf8');
        $db_username = Config::get('user');
        $db_password = Config::get('password');
        self::connect($db_host, $db_name, $db_username, $db_password, $db_port, $db_type, $db_charset);
        self::$connected_db = 'twm';
        self::$connected_db_name = $db_name;
    }

    public static function getErmTunnel($tunnel_url = null)
    {
        $tunnel_url = $tunnel_url ??  'https://sql.erm.app/' ;

        $ermConfig = Config::get('ERM');

        $config = [
            'host' => $ermConfig['host'],
            'dbname' => $ermConfig['database'],
            'username' => $ermConfig['user'],
            'password' => $ermConfig['password'],
        ];

        try {
            $db = new \SoapClient(null, [
                'location' => $tunnel_url,
                'uri' => $tunnel_url,
                'trace' => 1,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            ]);

            $db->setConfig($config);
        } catch (\SoapFault $fault) {
            $errorMsg = "SOAP-Error: (Error No: {$fault->faultcode}, "
                . "Error: {$fault->faultstring})";

            throw new \Exception($errorMsg);
        } catch (\Exception $fault) {
            die($fault->getMessage());
            //Cli::write($e->getMessage());
        }

        return $db;
    }


    public static function connectERM(): void
    {
        if (self::$connected_db === 'erm') {
            return;
        }

        $erm = Config::get('ERM');
        if ($erm === null) {
            throw new RuntimeException('ERM configuration missing');
        }

        $db_port = $erm['port'] ?? 3306;
        $db_type = $erm['db_type'] ?? 'mysql';
        $db_host = $erm['host'];
        $db_name = $erm['database'];
        $db_charset = $erm['db_charset'] ?? 'utf8';
        $db_username = $erm['user'];
        $db_password = $erm['password'];
        self::connect($db_host, $db_name, $db_username, $db_password, $db_port, $db_type, $db_charset);
        self::$connected_db = 'erm';
        self::$connected_db_name = $db_name;
    }

    public static function connect($host, $database, $user, $password, $port = 3306, $type = 'mysql', $charset = 'utf8'): void
    {
        self::disconnect();

        $db_dsn = "{$type}:host={$host};dbname={$database};charset={$charset};port={$port}";
        
        self::$pdo = new \PDO($db_dsn, $user, $password, array(
            //Erlaube Import lokaler Dateien (muss hier gesetzt werden, nicht unten)
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        ));

        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::MYSQL_ATTR_COMPRESS, true);
        //self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        self::$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        self::$pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
        self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        //self::$pdo->setAttribute(PDO::MYSQL_ATTR_LOCAL_INFILE, true);


        $db_init_command = Config::get('db_init_command', null);
        if ($db_init_command !== null) {
            //self::setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND , $db_init_command);
            self::toDatabase($db_init_command);
        }
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
        self::$connected_db = '';
    }

    /**
     * @param string $sql                  already properly escaped sql string
     * @param string $field                array key by field value
     * @param array  $__pdo__              array of query parameters
     * @param bool   $html                 if values should have encoded html special chars
     * @param bool   $groupby              field name on which result is grouped by
     * @param bool   $withNumRows          saves numRows inside parameters if set
     * @param bool   $doNotCheckDataStatus if data_status must be contained within where clause
     * @param bool   $rewriteForViews      if sql should be rewritten to fetch data from personal view
     *
     * @throws Exception
     *
     * @return array|bool|string
     */
    public static function fromDatabase(
        string $sql,
        string $field,
        array $__pdo__ = [],
        bool $html = false,
        $groupby = false,
        bool $withNumRows = false,
        bool $doNotCheckDataStatus = false,
        bool $rewriteForViews = true
    ) {

       $time = microtime(true);

        if(Config::get('debugMode', false)){
            write_tmp_log("# fromDatabase\n" . $sql . "\n\n;;;\n\n" . print_r($__pdo__, true));
        }


        if (is_bool($__pdo__)) {
            $html = $__pdo__;
            $__pdo__ = [];

            TinyError::throwError('__pdo__ is bool, $html paramater replaced with params bool, params is set to array');
        }

        if ($__pdo__ === null || is_string($__pdo__)) {
            $__pdo__ = [];
        }

        if (!$sql) {
            TinyError::throwError('SQL STATEMENT MISSING!');
        }

        if (!$field) {
            self::warning('$field param is missing or empty, will assume `id` as field');
            $field = 'id';
        }

        $extras = [
            'sql' => $sql,
            'field' => $field,
            '__pdo__' => $__pdo__,
        ];

        TinyError::addAdditionalInfoForError('SQL', $extras);

        $resultArray = [];

        $result = self::cachedQuery($sql);

            $exe = $result->execute($__pdo__);

        if ($exe === false) {
            // 0 SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
            // 1 Driver-specific error code.
            // 2 Driver-specific error message.
            $errorMsg = $result->errorInfo();

            TinyError::throwError('SQL ERROR on: ' . $sql . ' <br/> PDO ERRORINFO: ' . $errorMsg[2]);
        }

        $rowCount = (int) $result->rowCount();

        if ($withNumRows) {
            Parameter::set('numRows', $rowCount);
        }

        // format result data array from param value $field
        if ($field == '@flat') {
            if ($rowCount) {
                $tempAll = $result->fetchAll();
                $result->closeCursor();

                foreach ($tempAll as $temp) {
                    if ($html) {
                        $resultArray[htmlspecialchars($temp[0], ENT_QUOTES)] = htmlspecialchars($temp[1], ENT_QUOTES);
                    } else {
                        $resultArray[$temp[0]] = $temp[1];
                    }
                }
                $result = $resultArray;
            }

        } elseif ($field == '@array') {
            if ($rowCount) {
                $tempAll = $result->fetchAll();
                $result->closeCursor();

                foreach ($tempAll as $temp) {
                    $resultArray[] = $temp[0];
                }
                
                $result = $resultArray;
            }

            //return false;
        } elseif ($field == '@simple') {
            if ($rowCount) {
                $temp = $result->fetchAll(PDO::FETCH_ASSOC);
                $temp = $temp[0];
                $result->closeCursor();

                $resultArray = array_values($temp);

                if ($html) {
                    $resultArray[0] = htmlspecialchars($resultArray[0], ENT_QUOTES);
                }

                $result = $resultArray[0];
            }

        } elseif ($field == '@line') {
            if ($rowCount) {
                $resultArray = [];
                $temp = $result->fetchAll(PDO::FETCH_ASSOC);
                $result->closeCursor();
                $temp = $temp[0];

                if ($html) {
                    foreach ($temp as $key => $value) {
                        $resultArray[$key] = htmlspecialchars($value, ENT_QUOTES);
                    }
                } else {
                    $resultArray = $temp;
                }

                $result = $resultArray;
            }

        } elseif ($field == '@raw') {
            $i = 0;
            $resultArray = [];

            if ($rowCount) {
                $resultArray = $result->fetchAll(PDO::FETCH_ASSOC);
                $result->closeCursor();
                $result = $resultArray;
            }

        } elseif ($field == '@groupby') {
            $i = 0;
            $last_groupby = '';
            $resultArray = [];

            if ($rowCount) {
                $tempData = $result->fetchAll(PDO::FETCH_ASSOC);
                $result->closeCursor();
                //while ($temp = $result->fetchAll(PDO::FETCH_ASSOC)) {
                foreach($tempData as $temp){

                    if ($groupby == 'Name') {
                        if ($temp[$groupby][0] != $last_groupby) {
                            $i = 0;
                        }

                        $resultArray[$temp[$groupby][0]][$i] = $temp;
                        $last_groupby = $temp[$groupby][0];
                    } else {
                        if ($temp[$groupby] != $last_groupby) {
                            $i = 0;
                        }

                        $resultArray[$temp[$groupby]][$i] = $temp;
                        $last_groupby = $temp[$groupby];
                    }

                    ++$i;
                }

                $result = $resultArray;
            }

        }else{

            //take given fieldname for array index key
            if ($rowCount) {
                $tempData = $result->fetchAll(PDO::FETCH_ASSOC);
                $result->closeCursor();
                foreach($tempData as $temp){
                //while () {
                    if ($html) {
                        foreach ($temp as &$value) {
                            $value = htmlspecialchars($value, ENT_QUOTES);
                        }

                        $resultArray[$temp[$field]] = $temp;
                    } else {
                        $resultArray[$temp[$field]] = $temp;
                    }
                }
                
                $result = $resultArray;
            }

        }

        Clockwork::addDatabaseQuery($sql, $__pdo__, microtime(true) - $time);

        if($rowCount){

            return $result;
        }else{
            return false;
        }
    }

    /**
     * printr-artiger Debugwrapper für fromDatabase.
     *
     * @param        $sql
     * @param        $field
     * @param array  $__pdo__
     * @param bool   $html
     * @param bool   $groupby
     * @param bool   $withNumRows
     * @param bool   $doNotCheckDataStatus
     * @param bool   $rewriteForViews
     * @param bool   $die
     * @param string $fn_raw
     * @param string $channel
     * @param bool   $get_result
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public static function fromDatabaseR(
        string $sql,
        string $field,
        array $__pdo__ = [],
        bool $html = true,
        bool $groupby = false,
        bool $withNumRows = false,
        bool $doNotCheckDataStatus = false,
        bool $rewriteForViews = true,
        $die = true, $fn_raw = 'print_r', $channel = 'stderr', $get_result = false
    ) {
        $fn_stderr = static function ($data) use ($fn_raw) {
            if ($fn_raw === 'var_dump') {
                $out = var_export($data, true);
            } else {
                $out = print_r($data, true);
            }

            fwrite(STDERR, $out);
        };

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $is_cli = PHP_SAPI === 'cli';
        $fn = $fn_raw;
        if ($is_cli && ($channel === 'stderr')) {
            $fn = $fn_stderr;
        }

        $fn_output = static function ($cat, $data) use ($fn, $is_cli) {
            if ($is_cli) {
                echo '\n** ' . $cat . ' **\n';
            } else {
                echo '<br><b>' . $cat . '</b><br>';
            }

            $fn($data);
            if ($is_cli) {
                echo '\n';
            } else {
                echo '<br>';
            }
        };

        if (!$is_cli) {
            //Display in browser
            echo "\n<pre>";
            echo "<b>{$caller['file']} <span style='color:red'>(Line: {$caller['line']})</span></b><br/>";
        } else {
            //display in console:
            echo "\n******** {$caller['file']}:{$caller['line']}\n";
        }

        $fn_output('sql', $sql);
        $fn_output('field', $field);
        $fn_output('__pdo__', $__pdo__);
        $fn_output('html', $html);
        $fn_output('groupby', $groupby);
        $fn_output('withNumRows', $withNumRows);
        $fn_output('doNotCheckDataStatus', $doNotCheckDataStatus);
        $fn_output('rewriteForViews', $rewriteForViews);

        $result = false;
        if ($get_result || !$die) {
            $result = self::fromDatabase($sql,
                $field,
                $__pdo__,
                $html,
                $groupby,
                $withNumRows,
                $doNotCheckDataStatus,
                $rewriteForViews);
            if ($get_result) {
                $fn_output('result', $result);
            }
        }

        if (!$is_cli) {
            echo "</pre>\n\n";
        }

        if ($die) {
            die();
        }

        return $result;
    }

    /**
     * Debugwrapper für fromDatabase, spuckt Resultat mit aus (shortcut für fromDatabaseR mit $get_result = true).
     *
     * @param        $sql
     * @param        $field
     * @param array  $__pdo__
     * @param bool   $html
     * @param bool   $groupby
     * @param bool   $withNumRows
     * @param bool   $doNotCheckDataStatus
     * @param bool   $rewriteForViews
     * @param bool   $die
     * @param string $fn_raw
     * @param string $channel
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public static function fromDatabaseRR(
        string $sql,
        string $field,
        array $__pdo__ = [],
        bool $html = true,
        bool $groupby = false,
        bool $withNumRows = false,
        bool $doNotCheckDataStatus = false,
        bool $rewriteForViews = true,
        $die = true, $fn_raw = 'print_r', $channel = 'stderr'
    ) {
        return self::fromDatabaseR($sql,
            $field,
            $__pdo__,
            $html,
            $groupby,
            $withNumRows,
            $doNotCheckDataStatus,
            $rewriteForViews, $die, $fn_raw, $channel, true);
    }

    public static function parseId(string $sql, array $pdo)
    {
        
        try {
            preg_match_all("/[\s`]id`?\s*=\s*(?:(?P<numeric>\d+)|(?:'(?P<string>.+?)')|(?::(?P<pdo>\w+)))/", $sql, $all_matches, PREG_UNMATCHED_AS_NULL + PREG_SET_ORDER);
            $matches = end($all_matches);

            $ret = null;
            if ($matches['numeric']) {
                $ret = $matches['numeric'];
            } elseif ($matches['string']) {
                $ret = $matches['string'];
            } elseif ($matches['pdo']) {
                $ret = $pdo[$matches['pdo']];
            }

            return (int) $ret;

        } catch (\Exception $e) {

            //Oberes Regex ist unzuverlässig, Fehler vorgekommen: "A non-numeric value encountered"
            return 0;
        }



    }


    /**
     * @param       $sql
     * @param array $__pdo__              array of query parameters
     * @param       $allowForbiddenAction [bool]: allows updates without where and deletes without limit
     * @param bool|array      $withLog    [bool]: should this be logged? can only be used if module "logs" is loaded
     *                                    [mixed]: [ 1234 or true ] forces an id to be used for updates/deletes in case automatic detection fails (which fails most of the time)
     *                                    Note: automatic detection finds the last id=... statement in the query and uses that for logging
     * @param       $logTitle
     *
     * @throws Exception
     *
     * @return string value of mysql_query
     */
    public static function toDatabase(
        string $sql,
        array $__pdo__ = [],
        $withLog = false,
        bool $allowForbiddenAction = false,
        string $logTitle = ''
    ) {
        global $Helper, $Logs;
        $time = microtime(true);

        if(Config::get('debugMode', false)){
            write_tmp_log("# toDatabase\n" . $sql . "\n\n;;;\n\n" . print_r($__pdo__, true));
        }

        $sql = trim($sql);
        self::$affected_rows = 0;

        $extras = [
            'sql' => $sql,
            'params' => $__pdo__,
            'allowForbiddenAction' => $allowForbiddenAction ? 'true' : 'false',
            'withLog' => $withLog ? 'true' : 'false',
            'logTitle' => $logTitle,
        ];


        //LogFile::write($sql, $__pdo__);
        TinyError::addAdditionalInfoForError('SQL', $extras);

        if (is_bool($__pdo__)) {
            $withLog = $__pdo__;
            $__pdo__ = [];

            self::warning('__pdo__ is bool, $withLog paramater replace with params bool, params is set to array');
        }

        // clean input
        //@ToDo deaktiviert, Data output ist malformed, GE 15.10.2020
        //$__pdo__ = Auth::frontendArray($__pdo__);

        if (!$sql) {
            TinyError::throwError('SQL STATEMENT MISSING!');
        }

        self::toConsole($sql);

        $debugArray = debug_backtrace();

        if (Parameter::get('debugModeState')) {
            self::debugMessage('sql', $sql);
        }

        if ((preg_match('/^UPDATE/i', $sql)) && (!preg_match('/\s+WHERE\s+/i', $sql)) && (!$allowForbiddenAction)) {
            TinyError::throwError('Use of UPDATE statement without "WHERE" clause is forbidden (use allowForbiddenAction?)!');
        }

        if (
            (preg_match('/^DELETE/i', $sql))
            && (!preg_match('/LIMIT 1 |LIMIT 1$/i', $sql))
            && (!$allowForbiddenAction)) {
            TinyError::throwError('Use of DELETE statement without "LIMIT 1" is forbidden (use allowForbiddenAction?)!');
        }

        //$params = self::escapeArray($__pdo__);
        $params = $__pdo__;

        $result = self::cachedQuery($sql);

        if (!$result) {
            TinyError::throwError('BASIC SQL Error: ' . $sql);
            //throw new Exception("BASIC SQL Error");
        }

        $exe = false;
        if ($withLog) {
            // check for module "logs"
            if (!class_exists('Logs')) {
                self::warning('Module "LOGS" NOT loaded while withLog set!');

                return false;
            }

            // INSERT
            if (preg_match('/^INSERT/i', $sql)) {
                // get table name
                preg_match('/INSERT\s+INTO\s+`(.+?)`/i', $sql, $matches);
                $tableName = $matches[1];

                $exe = $result->execute($__pdo__);

                // Save last inserted id
                $logId = is_numeric($withLog) ? $withLog : self::$pdo->lastInsertId();
                self::$realLastInsertId = $logId;

                $Logs->logInsert($tableName, $logId, $logTitle);

            }

            // UPDATE
            if (preg_match('/^UPDATE/i', $sql)) {
                // extract id
                $id = is_numeric($withLog) ? $withLog :  self::parseId($sql, $__pdo__);

                // get field list
                $sqlForRegexp = substr($sql, strpos($sql, 'SET'));
                preg_match_all('/`(.+?)`/', $sqlForRegexp, $matches);

                $fields = $matches[0];

                // get table name
                preg_match('/UPDATE\s+`(.+?)`/i', $sql, $matches);
                $tableName = $matches[1];

                // fetch all old values
                $sqlOld = 'SELECT ' . $Helper->arrayToString(',', $fields) . ' FROM `' . $tableName . '` WHERE id = ?';

                $oldValues = self::fromDatabase($sqlOld, '@line', [$id]);

                $exe = $result->execute($__pdo__);

                $newValues = self::fromDatabase($sqlOld, '@line', [$id]);
                if ( is_numeric($id)  && $oldValues !== false ) {
                    $Logs->logUpdate($tableName, $id, $oldValues, $newValues, $logTitle);
                }

            }

            //DELETE
            if (preg_match('/^DELETE/i', $sql)) {
                // extract id
                $id = is_numeric($withLog) ? $withLog :  self::parseId($sql, $__pdo__);

                $exe = $result->execute($__pdo__);

                $Logs->logSystemAction($id, 'delete statement', $sql);

            }
            
            Clockwork::addDatabaseQuery($sql, $__pdo__, microtime(true) - $time);

            if ($exe === false) {
                // 0 SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
                // 1 Driver-specific error code.
                // 2 Driver-specific error message.
                $errorMsg = $result->errorInfo();
                throw new \Exception("SQL ERROR");
            } else {
                /**
                 * @var PDOStatement $exe
                 */
                self::$affected_rows = $result->rowCount();
                return true;
            }
        } else {
                //query ohne logs

                $exe = $result->execute($__pdo__);
                $result->closeCursor();
                Clockwork::addDatabaseQuery($sql, $__pdo__, microtime(true) - $time);

            if ($exe === false) {
                // 0 SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
                // 1 Driver-specific error code.
                // 2 Driver-specific error message.
                
                $errorMsg = $result->errorInfo();
                throw new Exception('SQL ERROR on: ' . $sql . ' <br/> PDO ERRORINFO: ' . $errorMsg[2]);
            } else {
                /**
                 * @var PDOStatement $exe
                 */
                self::$affected_rows = $result->rowCount();
                return true;
            }
        }

        return false;
    }

    public static function lastInsertId()
    {
        if(self::$realLastInsertId === null ){
            
            return self::$pdo->lastInsertId();
            
        }else{
            $realId = self::$realLastInsertId;
            
            self::$realLastInsertId = null;
            
            return $realId;
        }
    }

    /**
     * @return int
     */
    public static function getAffectedRows()
    {
        return self::$affected_rows;
    }

    /**
     * @param $sql [string]: sql statement to check
     *
     * @throws Exception
     *
     * @return string $link: link to prepared statement
     */
    private static function cachedQuery($sql)
    {
        $md5 = md5($sql);

        if (self::$pdo === null) {
            //keine PDO Instanz, abort.
            throw new Exception('Database Connection Failed');
        }

        if (isset(self::$db_to[$md5])) {
            // already in library
            // update time
            $microtime = microtime(true);
            self::$db_to[$md5]['time'] = $microtime;
            unset(self::$db_toTimes[array_search($md5, self::$db_toTimes)]);
            self::$db_toTimes[$microtime] = $md5;
        } else {
            // prepare statement and get into local $link variable
            $link = self::$pdo->prepare($sql /*, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]*/);

            // check how many statements already cached and if limit reached (assume 10)
            if (count(self::$db_to) > 50) {
                // overwrite oldest entry
                $oldest = min(array_keys(self::$db_toTimes));
                unset(self::$db_to[self::$db_toTimes[$oldest]], self::$db_toTimes[$oldest]);

                $microtime = microtime(true);
                self::$db_toTimes[$microtime] = $md5;
                self::$db_to[$md5]['time'] = $microtime;
                self::$db_to[$md5]['link'] = $link;
            } else {
                $microtime = microtime(true);
                self::$db_toTimes[$microtime] = $md5;
                self::$db_to[$md5]['time'] = $microtime;
                self::$db_to[$md5]['link'] = $link;
            }
        }

        return self::$db_to[$md5]['link'];
    }

    /**
     * Escape an array with html entities.
     *
     * @param      $__pdo__
     * @param bool $allowHtml
     *
     * @return array
     */
    public static function escapeArray($__pdo__)
    {
        $cleanArray = [];

        if (!is_array($__pdo__)) {
            return $cleanArray;
        }

        foreach ($__pdo__ as $key => $val) {
            $val = Web::purifyHtml($val);

            $cleanArray[$key] = $val;
        }

        return $cleanArray;
    }

    /**
     * Delete a record softly (no real delete but no more visible in gridviews).
     *
     * @param string $table
     * @param int    $id
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function deleteSoftly(string $table, int $id): bool
    {
        return self::deleteRecord($id, $table, 'delete');
    }

    public static function getConnectedDBName(): string
    {
        return self::$connected_db_name;
    }
    /**
     * Prüft ob eine Tabelle existiert.
     *
     * @param string $table
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isValidTable(string &$table): bool
    {
        if (!$table) {
            return false;
        }

        if (!isset(self::$table_names[self::$connected_db])) {
            self::$table_names[self::$connected_db] = self::fromDatabase('SHOW TABLES', '@array');
        }

        if (!in_array($table, self::$table_names[self::$connected_db], true)) {
            return false;
        }

        if (function_exists('untaint')) {
            untaint($table);
        }

        return true;
    }

    /**
     * Prüft ob eine Spalte existiert.
     *
     * @param string $table
     * @param string $column
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isValidColumn(?string &$table, ?string &$column): bool
    {
        if (!$column) {
            return false;
        }

        if (!self::isValidTable($table)) {
            return false;
        }

        if (!isset(self::$column_names[self::$connected_db][$table])) {
            $sql = '
                SELECT 
                    COLUMN_NAME 
                FROM 
                    information_schema.`COLUMNS` 
                WHERE 
                    TABLE_NAME = :table AND 
                    table_schema = :database            
            ';

            self::$column_names[self::$connected_db][$table] = self::fromDatabase($sql, '@array', ['table' => $table, 'database' => self::$connected_db_name]);
        }

        if (!in_array($column, self::$column_names[self::$connected_db][$table], true)) {
            return false;
        }

        if (function_exists('untaint')) {
            untaint($column);
        }

        return true;
    }

    /**
     * Prüft ob eine Spalte in irgendeiner Tabelle der Datenbank existiert.
     *
     * @param string $column
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isAnyTableValidColumn(?string &$column): bool
    {
        if (!$column) {
            return false;
        }

        if (!isset(self::$column_names[self::$connected_db][true])) {
            $sql = '
                SELECT 
                    COLUMN_NAME 
                FROM 
                    information_schema.`COLUMNS` 
                WHERE 
                    table_schema = :database            
            ';

            self::$column_names[self::$connected_db][true] = self::fromDatabase($sql, '@array', ['database' => self::$connected_db_name]);
        }

        if (!in_array($column, self::$column_names[self::$connected_db][true], true)) {
            return false;
        }

        if (function_exists('untaint')) {
            untaint($column);
        }

        return true;
    }

    /**
     * @param $id
     * @param $table  string Kann in twm aus dem Userspace kommen...
     * @param $action string 'delete' (softdelete) or 'delete-real'
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function deleteRecord($id, $table, $action = 'delete')
    {
        global $Logs, $User, $App;

        if ($id < 1) {
            throw new \Exception('id has unexpected value (' . (int) $id . ')');
        }

        if (!self::isValidTable($table)) {
            throw new \Exception('bad table!');
        }

        // check if this record does exist and can be set inactive
        /** @noinspection SqlResolve */
        $sql = "
            SELECT 
                id
            FROM 
                `{$table}`
            WHERE 
                data_status != 2 AND 
                id = ?
        ";

        $doesExist = self::fromDatabase($sql, '@simple', [$id]);

        if ($doesExist) {
            if ($action === 'delete') {
                $pdo = [
                    'id' => $id,
                ];

                $theChangerUser = '';
                $uid = '';
                if ($table !== 'twm_pdf_mail_contents') {
                    $pdo['id_user'] = $User->id;
                    $theChangerUser = ', `id_users__changedBy`= :id_user';
                    if ($table === 'twm_codes' && ((int) $App->id_client === 4)) {
                        $pdo['id_b'] = $id;
                        $uid = ', `uid`= uid + "-" +md5(:id_b)';
                    }
                }
                /** @noinspection SqlResolve */
                $sql = "
                    UPDATE 
                        `{$table}` 
                    SET 
                        `data_status`=2
                        {$theChangerUser}
                        {$uid} 
                    WHERE 
                        `id`=:id
                ";

                self::toDatabase($sql, $pdo);

                $Logs->logSystemAction($id, 'delete', 'user deleted record ' . $id . ' in table ' . $table);

                return true;
            }

            if ($action === 'delete-real') {
                /** @noinspection SqlResolve */
                $sql = "DELETE FROM `{$table}` WHERE `id`=? LIMIT 1";
                self::toDatabase($sql, [$id], $id);

                $Logs->logSystemAction($id, 'delete',
                    'user ' . $User->id . ' deleted record ' . $id . ' in table ' . $table);

                return true;
            }
        }
        //throw new \Exception('Record (id: ' . $id . ', table: ' . $table . ') cannot be deleted!');

        return false;
    }

    /**
     * get a single line from database.
     *
     * @param       $sql
     * @param array $__pdo__
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public static function getLine($sql, $__pdo__ = [])
    {
        //  vardump($__pdo__,0);
        return self::fromDatabase($sql, '@line', $__pdo__);
    }

    /**
     * Get data from database in raw mode.
     *
     * @param       $sql
     * @param array $__pdo__
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public static function get($sql, $__pdo__ = [])
    {
        return self::fromDatabase($sql, '@raw', $__pdo__);
    }

    /**
     * Get a simple value, (only one column).
     *
     * @param       $sql
     * @param array $__pdo__
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public static function getSimple($sql, $__pdo__ = [])
    {
        return self::fromDatabase($sql, '@simple', $__pdo__);
    }

    public static function getForSelect($sql, $__pdo__ = [])
    {
        return self::fromDatabase($sql, '@flat', $__pdo__);
    }

    /**
     * Update a database entry.
     *
     * @param $table      - name of the table
     * @param $data       - array - data to update ([column => value])
     * @param $conditions - array  - where conditions [
     *
     * @throws Exception
     *
     * @return string
     */
    public static function update(String $table, array $data, array $conditions)
    {
        if (empty($conditions)) {
            throw new \Exception("Conditions can't be empty for update");
        }

        if (empty($data)) {
            throw new \Exception('Nothing to update. Data empty!');
        }

        $sql = [];
        $sql[] = "UPDATE `$table` SET";

        $sql_params = [];
        foreach ($data as $key => $val) {
            $sql_params[] = "`{$key}` = :{$key}";
        }

        $sql[] = implode(",\n", $sql_params);

        $where_params = [];
        foreach ($conditions as $key => $val) {
            $data['_where_' . $key] = $val;
            $where_params[] = "`{$key}` = :_where_{$key}";
        }

        $sql[] = 'WHERE';
        $sql[] = implode(' AND ', $where_params);

        $sqlQuery = implode(' ', $sql);

        return self::toDatabase($sqlQuery, $data);
    }

    /**
     * Wrapper für Forms saveToDatabase aus class.Forms.php.
     *
     * @param string     $table
     * @param array      $data - wenn null, $_POST wird genommen
     * @param array|bool $conditions (used for where ?)
     *
     * @return int id des Eintrags (new id on inserts)
     */
    public static function save(String $table, $data = null, $conditions = null): int
    {
        global $Forms;

        return $Forms->saveToDatabase($table, $data, $conditions);
    }

    /**
     * Get all columns for a table.
     *
     * @param $table
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getColumns($table): array
    {
        $sql = <<<SQL
            SELECT column_name
              FROM information_schema.columns 
             WHERE table_schema = :database_name 
               AND table_name = :table_name
SQL;

        $colsRaw = self::get($sql, [
            'database_name' => Config::get('database'),
            'table_name' => $table,
        ]);

        $re = [];
        foreach ($colsRaw as $col) {
            $re[] = $col['column_name'];
        }

        return $re;
    }

    /**
     * Get last id after INSERT.
     *
     * @return mixed
     */
    public static function getLastInsertId()
    {
        
        if(self::$realLastInsertId === null ){
            return self::$pdo->lastInsertId();
        }else{
            $realId = self::$realLastInsertId;
            self::$realLastInsertId = null;
            return $realId;
        }

        //return self::$pdo->lastInsertId();
    }

    /**
     * Erstellt ein Where String anhand eines key/val arrays.
     *
     * @param array $conditions - zb:  [x=>10, y=>20]
     *
     * @return string  `x` = :x AND `y` = :y
     */
    public static function buildWhere(array &$conditions): string
    {
        $out = [];
        $additionalConditions = [];     //additional conditions will be filled here (for use by "IN")
        $additionalPlaceholders = [];     //additional placeholders for use by IN

        $nr = 0;

        foreach ($conditions as $k => $v) {
            ++$nr;

            if (is_array($v)) {
                //Das wird ein IN, Platzhalter funktioniert nicht mit IN, deshalb direkt in der Query

                foreach ($v as $vv) {
                    ++$nr;
                    $placeholder = $k . '_' . $nr;
                    $additionalPlaceholders[] = ':' . $placeholder;
                    $additionalConditions[$placeholder] = $vv;
                }

                $out[] = "`{$k}` IN (" . implode(', ', $additionalPlaceholders) . ')';

                //entferne from conditions, damit es nicht für als placeholder param verwendet wird
                unset($conditions[$k]);
            } else {
                $out[] = "`{$k}` = :{$k}";
            }
        }

        if (!empty($additionalConditions)) {
            $conditions = array_merge($conditions, $additionalConditions);
        }

        return implode(' AND ', $out);
    }

    /*
     * Erstellt einen SET String anhand eines key/val arrays
     * @param array $pdo
     * @return string
     */

    public static function buildSet(array $pdo): string
    {
        $out = [];
        foreach ($pdo as $key => $value) {
            $out[] = "{$key} = :{$key}";
        }

        return implode(', ', $out);
    }

    /**
     * Erstellt einen INTO und einen VALUES String um mehrere Sätze in die Datenbank zu schreiben.
     *
     * @param array $pdo PDO Array der alle parameter erhält
     * @param array $keys Schlüssel im $parameters Array die geschrieben werden sollen
     * @param array $parameters Array über Arrays die die zu schreibenden Werte enthalten
     * @param array $static Statische Werte die für jeden Satz geschrieben werden sollen als Key/Value Paare
     * @param array $key_mapping Mappt zu schreibende Keys auf Keys wie sie in $parameters vorhanden sind
     *
     * @return array
     */
    public static function buildValues(array &$pdo, array $keys, array $parameters, array $static = [], array $key_mapping = []): array
    {
        $into = array_merge($keys, array_keys($static));
        $values = [];

        $i = 0;
        foreach ($parameters as $dummy_key => $parameter_values) {
            $val = [];
            foreach ($keys as $key) {
                $the_key = $key . '_' . $i;
                $val[] = ':' . $the_key;
                $pdo[$the_key] = isset($key_mapping[$key]) ? $parameter_values[$key_mapping[$key]] : $parameter_values[$key];
            }
            foreach ($static as $key => $static_value) {
                $the_key = $key . '_' . $i;
                $val[] = ':' . $the_key;
                $pdo[$the_key] = $static_value;
            }
            $values[] = '(' . implode(',', $val) . ')';

            ++$i;
        }

        $ret['into'] = '(' . implode(',', $into) . ')';
        $ret['values'] = implode(',', $values);

        return $ret;
    }

    /**
     * @param $var
     *
     * @param string $wildcards
     * @return string "CONCAT('%', :{$var}, '%')"
     */
    public static function wrapForLike($var, $wildcards='b'): string
    {
        if($wildcards=='b'){
            return "CONCAT('%', :{$var}, '%')";
        }

        if($wildcards=='l'){
            return "CONCAT('%', :{$var})";
        }

        if($wildcards=='r'){
            return "CONCAT(:{$var}, '%')";
        }
    }

    public static function likeGET($get_variable_name, array &$pdo): string
    {
        $pdo[$get_variable_name] = $_GET[$get_variable_name];

        return self::wrapForLike($get_variable_name);
    }

    public static function likePOST($post_variable_name, array &$pdo): string
    {
        $pdo[$post_variable_name] = $_GET[$post_variable_name];

        return self::wrapForLike($post_variable_name);
    }

    /**
     * Macht eine neue automatisch durchgezählte PDO Variable (z.B. wenn eine SQL-Query in einem Loop zusammengestückelt wird).
     *
     * @param        $value
     * @param        $pdo
     * @param bool   $like
     * @param string $name
     *
     * @return string ":{generierter Name}" bzw "CONCAT('%', :{generierter Name}, '%')" wenn $like=true
     */
    public static function nextPDOValue($value, &$pdo, $like = false, $name = 'value'): string
    {
        static $ref = [];
        $c = @++$ref[$name];
        $key = "{$name}{$c}";
        $pdo[$key] = $value;


        if(($like=='b') || ($like===true)){
          return self::wrapForLike($key, 'b');
        }

        if($like=='l'){
            return self::wrapForLike($key, 'l');
        }

        if($like=='r'){
            return self::wrapForLike($key, 'r');
        }

        if($like===false){
          return (':' . $key);
        }

    }

    /**
     * Daten modifiziert kopieren
     * Es nimmt sich die Daten vor, manipuliert diese und fügt sie in die selbe Tabelle ein.
     *
     * @param string $table
     * @param array  $conditions  - key/value Paaren für die Suche der Quelldaten
     * @param array  $replaceData - gefundene Daten sollen mit diesen Daten geändert werden
     *
     * @throws Exception
     *
     * @return array - Liste mit neuen IDs
     */
    public static function copyData(String $table, array $conditions, array $replaceData): array
    {

        //Erst mal Originaldaten holen:
        $where = self::buildWhere($conditions);
        $sql = "SELECT * FROM `$table` WHERE {$where} AND data_status = 1";

        $sourceItems = self::get($sql, $conditions);

        /*
        printr($sql,0);
        printr($conditions,0);
        printr($sourceItems,0);
        */

        //Neue Einträge bekommen eine neue ID, diese werden hier gespeichert und zum Schluß returnt:
        $ids = [];

        if ($sourceItems === false) {
            //Quelldaten nicht gefunden.. es gibt nichts zu kopieren
            return [];
        }

        /*
         * 1. Jetzt gehen wir jeden einzelnen Quelle-Datensatz durch,
         * 2. modifizieren sie entsprechend $replaceData
         * 3. fügen die modifizierten Daten in die Tabelle ein
         */
        foreach ($sourceItems as $item) {

            //Beim Kopieren muss die ID "new" sein, setze auf new, es sei denn der User hat was anderes übergeben..
            $replaceData['id'] = $replaceData['id'] ?? 'new';
            $newEventPayload = array_merge($item, $replaceData);

            //Daten speichern und hole die neue ID:
            //im key steht die alte ID, als value die neue ID
            $ids[$item['id']] = self::save($table, $newEventPayload);
        }

        return $ids;
    }

    /**
     * @param $array
     * @param $table
     * @param $id Wenn das nicht null, dann wird das UPDATE, sonder INSERT
     */
    public static function arrayToDatabase($array, $table, $id = [])
    {
        if (!is_array($array)) {
            throw new \Exception('$array is not Array()');
        }
        if (empty($table)) {
            throw new \Exception('$table parameter is empty!');
        }
        if (gettype($table) != 'string') {
            throw new \Exception('$table parameter type is not string!');
        }

        if (count($id) == 0) {
            $query = 'INSERT INTO `' . $table . '` (';
            $x = 0;
            foreach ($array as $key => $value) {
                if ($x > 0) {
                    $query .= ',';
                }
                $query .= '`' . $key . '`';
                ++$x;
            }
            $query .= ') VALUES (';
            $x = 0;
            foreach ($array as $key => $value) {
                if ($x > 0) {
                    $query .= ',';
                }
                $query .= ':' . $key . '';
                ++$x;
            }
            $query .= ')';
        } else {
            $query = 'UPDATE `' . $table . '` SET ';
            $x = 0;
            foreach ($array as $key => $value) {
                if ($x > 0) {
                    $query .= ',';
                }
                $query .= '`' . $key . '`=:' . $key;
                ++$x;
            }
            $k = array_keys($id);
            $query .= ' WHERE ' . $k[0] . '=:' . $k[0];
            $array[$k[0]] = $id[$k[0]];
        }

        return self::toDatabaseSimple($query, $array);
    }

    public static function toDatabaseSimple($sql, $params = [])
    {
        $result = self::$pdo->prepare($sql/*, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]*/);
        $result->execute($params);
    }

    public static function columnExists($column, $table)
    {
        if (isset(self::$_columntExistsCache[$table][$column])) {
            return self::$_columntExistsCache[$table][$column];
        }
        $sql = 'SHOW COLUMNS FROM `' . $table . "` LIKE '" . $column . "'";

        if (self::fromDatabase($sql, '@raw')) {
            self::$_columntExistsCache[$table][$column] = true;

            return true;
        }
        self::$_columntExistsCache[$table][$column] = false;

        return false;
    }

    /**
     * Funktion konvertiert einen (z.B. id) Array für die Verwendung über PDO für eine IN-clause
     * Beispiel:
     *     $bindString = DB::bindParamArray("id", $_GET['ids'], $bindArray); // ergibt ":id1,:id2,:id3"
     *     $userConditions .= " AND users.id IN({$bindString})";
     * Quelle: https://stackoverflow.com/questions/920353/can-i-bind-an-array-to-an-in-condition.
     *
     * @param string $prefix    Prefix für die generierten pdo parameter
     * @param array  $values    Value array
     * @param array  $bindArray Array in den die pdo Parameter gespeichert werden
     * @param string $default   Rückgabewert wenn $values leer ist
     *
     * @return string Query string der zwischen die Klammern der IN-cluse gehört
     */
    public static function bindParamArray(string $prefix, array $values, array &$bindArray, string $default = 'NULL'): string
    {
        if (count($values) === 0) {
            return $default;
        }
        $str = '';
        foreach ($values as $index => $value) {
            $str .= ':' . $prefix . $index . ',';
            $bindArray[$prefix . $index] = $value;
        }

        return rtrim($str, ',');
    }

    /**
     * Wandelt die Werte eines Arrays in ints. $array kann auch eine Komma-separierte liste sein.
     *
     * @param array|string $array
     *
     * @return array|string
     */
    public static function intifyArray(&$array)
    {
        $is_array = is_array($array);

        if (!$is_array) {
            $array = explode(',', $array);
        }

        $array = array_map(static function ($value) {
            return (int) $value;
        }, $array);

        if (!$is_array) {
            $array = implode(',', $array);
        }

        return $array;
    }

    /**
     * Eine oder mehrere Tabellen leeren.
     *
     * @param [mixed] $table - Name der Tabelle oder ein Array mit mehreren Namen
     *
     * @return bool
     */
    public static function truncate($table): bool
    {
        if (is_string($table)) {
            self::toDatabase("TRUNCATE TABLE `{$table}`");

            return true;
        }

        if (is_array($table)) {
            foreach ($table as $tbl) {
                self::toDatabase("TRUNCATE TABLE `{$tbl}`");
            }

            return true;
        }

        //Unbekannter Datentyp
        return false;
    }

    //Daten inserten (Child Funktion von 'insert', siehe unten)
    private static function _insert($table, $data)
    {
        //Query generieren:
        $sql = [];
        $sql[] = "INSERT INTO `{$table}` SET";

        $vals = [];
        foreach ($data as $column => $value) {
            $vals[] = "`{$column}` = :{$column}";
        }

        $sql[] = implode(",\n", $vals);
        $sql_query = implode("\n", $sql);

        self::toDatabase($sql_query, $data);
    }

    /**
     * 1 oder mehrere Einträge in die Datenbank einfügen.
     *
     * @param string $table - Name der Tabelle
     * @param array $data - Associatives Array (wenn nur 1 Eintrag) oder sequenzielles (wenn mherere Einträge)
     */
    public static function insert(string $table, array $data)
    {

        //ist das ein Array von Daten oder nur ein Param Array?
        if (isset($data[0])) {
            //Mehrere Einträge:
            foreach ($data as $row) {
                self::_insert($table, $row);
            }
        } else {
            //nur ein Eintrag:
            self::_insert($table, $data);
        }
    }

    /**
     * Sauberen Namen für PDO Platzhalter holen.
     *
     * @param  string $string - Platzhalter Name, z.B hello-world
     *
     * @return string         - helloworld
     */
    public static function getPlaceholderFromString($string)
    {
        $re = '/(-| |\'|\")/m';
        $newString = preg_replace($re, '', $string);

        if (strpos($newString, '-')) {
            printr($newString);
        }

        return $newString;
    }

    /**
     * ID einer autoinkrement Spalte holen
     * @param  string $table - Name der Tabelle
     * @return int        $id
     */
    public static function getIncrementId($table){
        $sql = "SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_name = :table_name AND table_schema = DATABASE()";
        $id = self::fromDatabase($sql, "@simple", ['table_name' => $table]);
        return (int) $id;
    }


    /**
     * Get all tables that contain a specific column name.
     *
     * @param $column_name
     * @param string $table_filter - regex match (eg. ^twm_testcars = all tables beginning with twm_testcars)
     *
     * @return array
     */
    public static function getTablesWithColumn($column_name, $table_filter = '^twm_.')
    {

        $sql = <<<sql
        SELECT c.`TABLE_NAME` from information_schema.columns c WHERE c.`TABLE_SCHEMA` = :database
                        AND c.`TABLE_NAME` REGEXP :table_filter AND c.`COLUMN_NAME` = :column_name
sql;

        $sql_params = [
            'column_name' => $column_name,
            'database' => Config::get('database'),
            'table_filter' => $table_filter,
        ];
        $tables = self::fromDatabase($sql, '@raw', $sql_params);

        $re = [];
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $re[] = $table['TABLE_NAME'];
            }
        }

        return $re;
    }

}

DB::init();
