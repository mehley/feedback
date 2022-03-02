<?php

/**
 *  * Class Error
 * For displaying and processing any kind of errors
 * by Michael Milawski - 25.04.2017.
 */
final class TinyError
{
    /**
     * @var null
     */
    public static $whoopsHandler = null;

    /**
     * @param string $error
     */
    public static function throwError($error = '')
    {
        throw new Exception($error);
    }

    public static function decryptRequest()
    {
        if (isset($_POST)) {
            $_POST = [];
        }
    }

    /**
     * Init Whoops Error Handler.
     */
    public static function initWhoops()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $whoops = new \Whoops\Run();
        $errorHandler = new \Whoops\Handler\PrettyPageHandler();

        // Default Fehleranzeige im Browser
        //$POST = $_POST;
        $errorHandler->addDataTable('POST Data (Plain)', $_POST);

        //$_POST wird geleert weil sonst zeigt der Error Handler den Post unverschlüsselt an
        // Dies können wir umgehen indem wir den $_POST oben in addDataTable reinhauen.
        //$_POST = [];
        self::$whoopsHandler = $errorHandler;
        $whoops->pushHandler($errorHandler);
        $whoops->pushHandler([Clockwork::class, 'whoopsHandler']);
        /*
         * Fehleranzeige im Ajax Request
         * Zeigt die Fehler als JSON an
         */
        if (\Whoops\Util\Misc::isAjaxRequest()) {
            $jsonHandler = new \Whoops\Handler\JsonResponseHandler();
            $jsonHandler->addTraceToOutput(true);
            //$jsonHandler->addTraceToOutput()
            $whoops->pushHandler($jsonHandler);
        }

        /*
         * Fehleranzeige in der Console
         * Falls das Script zB per cron aufgerufen wurde
         */
        if (\Whoops\Util\Misc::isAjaxRequest()) {
            $jsonHandler = new \Whoops\Handler\JsonResponseHandler();
            $jsonHandler->addTraceToOutput(true);
            //$jsonHandler->addTraceToOutput()
            $whoops->pushHandler($jsonHandler);
        }

        $whoops->register();
    }

    public static function addAdditionalInfoForError($groupName, $extras)
    {
        if (self::$whoopsHandler === null) {
            //Currently only whoops handler can use extra info
            return false;
        }

        self::$whoopsHandler->addDataTable($groupName, $extras);
    }
}


if (Config::get('useWhoops', false) ) {
    TinyError::initWhoops();
}
