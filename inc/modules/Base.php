<?php

/**
 * Base Klasse
 * Diese Klasse wird immer geladen und soll für alle Children Klassen relevanten Funktionen enthalten.
 */
class Base
{
    /**
     * @param $toPrint[string]: which text to show
     *
     * @return void
     */
    public static function warning($toPrint)
    {
        $debugArray = debug_backtrace();

        if (!$toPrint) {
            echo 'nothing to show?';
        } else {
            echo '<div style="background-color:#faffa3; border:2px solid red; margin:1em 0; padding:0.5em;">';
            echo '<strong style="color:red;">@line: </strong>' . $debugArray[count($debugArray) - 1]['line'] . '<br/>';
            echo '<strong style="color:red;">in file: </strong>' . $debugArray[count($debugArray) - 1]['file'] . '<br/>';
            echo '<strong style="color:red;">' . $toPrint . '</strong></div>';
        }
    }

    /**
     * @param
     * $caption [!string]: how the debug output should be named
     * $data [string]: data that should be printed
     * @param mixed $caption
     * @param mixed $data
     *
     * @throws Exception
     *
     * @return void
     */
    public static function debugMessage($caption, $data)
    {
        if (!$caption) {
            throw new \Exception('no caption given');
        }

        if (Config::get('suppressDebug', false)) {
            return false;
        }

        $debugArray = debug_backtrace();

        echo "\n" . '<!-- ' . "\n";
        echo '@line: ' . $debugArray[count($debugArray) - 1]['line'] . ' in file: ' . $debugArray[count($debugArray) - 1]['file'] . "\n";
        echo $caption . ': ';

        print_r($data);

        echo "\n" . ' -->' . "\n";
    }

    /**
     * @param $what [string]: what should be saved inside console
     * @param $extra [array]: extra info (Optional)
     *
     * @throws Exception
     *
     * @return void
     */
    public static function toConsole($what, $extra = [])
    {
        if (!$what) {
            self::warning('nothing to write to console');
        }

        Clockwork::log($what, $extra);
        //LogFile::write($what, $extra, 'debug');
    }

    /**
     * @param $what[!string]: what should be saved inside console
     *
     * @return void
     *
     * @deprecated Logs in Sessions speichern = NO GO. Logs werden jetzt im Logs Ordner als Datei gespeichert.
     */
    public static function toConsoleSession($what)
    {
        if (!$what) {
            self::warning('nothing to write to console');
        }

        Clockwork::log($what);
    }

    /**
     * @param $toPrint string]: which text to show
     *
     * @return bool
     */
    public static function dieWithInfo($toPrint)
    {
        self::toConsole('--- Base::dieWithInfo(); ---');

        if (!$toPrint) {
            self::warning('nothing to show?');
        }

        if (Config::get('suppressDebug', false)) {
            return false;
        }

        Sentry::captureMessage($toPrint);

        return true;
    }
}
