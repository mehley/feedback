<?php

    require_once __DIR__ . '/../constants.php';

    /**
     * Class Config.
     */
    class Config
    {
        //Aktuelle Config (merge configuration.php und configuration_local.php)
        public static $config = null;

        //Liveconfig wird hier zusätzlich geladen, falls man zb in Tests auf die Liveconfig zugreifen möchte:
        public static $liveConfig = null;

        //Lokale Config
        public static $config_local = null;

        //wurde die live config oder local config geladen? local oder live
        private static $environment = null;

        public static function setConfig($config)
        {
            self::$config = $config;
        }

        /**
         * Get Config entry from current loaded configuration file.
         *
         * @param      $key
         * @param null $defaultValue
         *
         * @throws Exception
         *
         * @return mixed
         */
        public static function get($key = null, $defaultValue = null)
        {
            if (self::$config === null) {
                throw new Exception('No Configuration file was loaded');
            }

            if($key === null) {
                //alles
                return self::$config;
            }


            //...env File hat Vorrang:
            if(isset($_ENV[$key])){
                return self::castValue($_ENV[$key]);
            }

            if (property_exists(self::$config, $key)) {
                return self::$config->$key;
            }
            //Property not found in config, return default value:
            return $defaultValue;
        }

        /**
         * String values casten
         * Wird verwendet um String Werte (aus ...env Datei nach Typ umzuwandeln)
         * @param $value
         * @return bool|null
         */
        private static function castValue($value)
        {
            $value = strtolower($value);
            if ($value === 'null') {
                return null;
            }

            if ($value === 'true') {
                return true;
            }

            if ($value === 'false') {
                return false;
            }

            return $value;
        }

        /**
         * Config-Wert vorübergehend ändern.
         *
         * @param      $key
         * @param null $value
         */
        public static function set($key, $value = null)
        {
            self::$config->$key = $value;
        }

        /**
         * Aktuell geladene Config.
         */
        public static function getConfig()
        {
            return self::$config;
        }

        /**
         * Lade App Config
         * Lädt IMMER die configuration.php - existiert die _local Version, wird diese auch geladen und die Werte
         * aus der Live Config überschrieben.
         */
        public static function loadConfig()
        {
            $config_file_live = __DIR__ . '/../configuration.php';
            $config_file_local = __DIR__ . '/../configuration_local.php';

            if (file_exists($config_file_local)) {
                self::setEnvironment('local');
                include $config_file_local;
            } else {
                self::setEnvironment('live');
                include $config_file_live;
            }

            //Zusätzlich die Live Config immer laden:
            //include $config_file_live;


            self::loadEnv();
            self::setConfig($Configuration);
        }

        /**
         * env Datei laden
         * Sobald die Datei geladen ist, kann man auf die Werte mit getenv('keyname') zugreifen
         * @return bool
         */
        public static function loadEnv(){

            $envFile = '...env';

            if(!file_exists(APP_ROOT_PATH . '/' . $envFile)){
                return false;
            }

            $dotenv = \Dotenv\Dotenv::createImmutable(APP_ROOT_PATH, $envFile);
            $dotenv->load();
        }

        public static function init()
        {
            self::loadConfig();
        }

        public static function getEnvironment()
        {
            return self::$environment;
        }

        /**
         * @param null $environment
         */
        private static function setEnvironment($environment): void
        {
            self::$environment = $environment;
        }
    }

Config::init();
