<?php

use Clockwork\Helpers\Serializer;
use Clockwork\Helpers\StackTrace;
use Clockwork\Request\UserData;
use Clockwork\Support\Vanilla\Clockwork as CW;


/**
 * Clockwork Debugger Modul
 * Siehe https://underground.works/ für mehr Infos.
 */
class Clockwork
{

    /**
     * @var \Clockwork\Support\Vanilla\Clockwork|\Clockwork\Clockwork null
     */
    private static $clockwork              = null;
    private static $globalSwitchSQLLogging = true;
    public static  $sqlLoggingEnabled      = false;

    public static function init()
    {

        if (!class_exists(CW::class)) {
            //Modul wurde nicht installiert?
            return false;
        }

        if (self::$clockwork !== null) {
            return true;
        }

        $clockwork       = CW::init([
            'api'              => '/ajax/clockwork_api.php?request=',
            'storage'          => 'sql',
            'storage_database' => self::getStorageDatabase(),
            'register_helpers' => true,
        ]);
        $clockwork->sendHeaders();
        self::$clockwork = $clockwork;

        # register_shutdown_function([$clockwork, 'requestProcessed']);
        register_shutdown_function(function () {
            @Clockwork::processor();
        });
    }

    private static function processor()
    {
        try {
            self::$clockwork->requestProcessed();
        } catch (Exception $e) {
        }
    }


    private static function getStorageDatabase()
    {
        $storage      = __DIR__ . '/../../tmp/clockwork';
        $databaseFile = $storage . '/clockwork.sqlite';

        if (!file_exists($storage)) {
            mkdir($storage);
        }

        if (!file_exists($databaseFile)) {
            touch($databaseFile);
        }

        return 'sqlite:' . $databaseFile;
    }

    public static function initApi()
    {
        self::$clockwork->returnMetadata();
    }

    private static function isEnabled()
    {
        if (Config::get('clockwork', false) == false) {
            return false;
        }

        self::init();
        return true;
    }

    private const VALID_LEVELS = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    /**
     * @param        $message
     * @param array  $extra
     * @param string $level = Clockwork::VALID_LEVELS[$item]
     * @return bool
     */
    public static function log($message, array $extra = [], string $level = 'info'): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        if (!in_array($level, self::VALID_LEVELS, true)) {
//            $extra += [
//                'invalid clockwork log level' => $level
//            ];
//            $level = 'error';
            self::$clockwork->log($level, $message, $extra);
            return true;
        }

        self::$clockwork->{$level}($message, $extra);
        return true;
    }

    public static function addDatabaseQuery($query, $bindings = [], $duration = null, $data = [])
    {
        if (!self::$sqlLoggingEnabled || !self::$globalSwitchSQLLogging || !self::isEnabled()) return false;

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);

        if ($trace) {
//            self::log($trace);
            $_trace = StackTrace::from($trace);

            $data += [
                'file' => $trace[0]['file'],
                'line' => $trace[0]['line'],
                'trace' => (new Serializer([ 'trace' => null ]))->trace($_trace),
            ];
        } else {
            self::log($query, [
                'bindings' => $bindings,
            ]);
        }

        self::$clockwork->addDatabaseQuery($query, $bindings, $duration, $data);
    }

    public static function addCacheQuery($type, $key, $value = null, $duration = null, $data = [])
    {
        if (!self::isEnabled()) return false;

        self::$clockwork->addCacheQuery($type, $key, $value, $duration, $data);
    }

    public static function startEvent($name, $description = '')
    {
        if (!self::isEnabled()) return false;
        self::$clockwork->startEvent($name, $description);
    }

    public static function endEvent($name)
    {
        if (!self::isEnabled()) return false;

        if ($name == 'first.php') {
            //SQL Logs starten sobald first.php beendet wurde
            self::$sqlLoggingEnabled = true;
        }

        self::$clockwork->endEvent($name);
    }

    /**
     * @param string $key
     * @return UserData
     * @noinspection PhpDocSignatureInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public static function userData(string $key)
    {
        if (!self::isEnabled()) /** @noinspection PhpIncompatibleReturnTypeInspection */ return new Ignorer();

        return self::$clockwork->userData($key);
    }

    public static function convertToUserTable(array $data, string $key_name = 'Key', string $value_name = 'Value', bool $nop_if_disabled = true): array
    {
        if ($nop_if_disabled && !self::isEnabled()) {
            return $data;
        }

        $clock_table = [];
        foreach ($data as $key => $value) {
            $clock_table[] = [$key_name => $key, $value_name => $value];
        }
        return $clock_table;
    }

    public static function whoopsHandler(): void
    {
        if (self::isEnabled()) {
            self::$clockwork->requestProcessed();
        }
    }
}

class Ignorer
{
    public function __call($function, $parameters)
    {
        if ($function === 'userData') {
            // For Clockwork
            return new self();
        }
    }

    public static function __callStatic($function, $parameters)
    {
        if ($function === 'userData') {
            // For Clockwork
            return new self();
        }
    }
}
