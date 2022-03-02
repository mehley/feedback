<?php


/**
 * APP_ROOT_PATH
 * Absoluter Pfad zu dem Root von TWM
 */
    define('APP_ROOT_PATH', __DIR__ . '/..');


    define('TEMP_PATH', APP_ROOT_PATH . '/tmp');


    define('LOG_PATH', TEMP_PATH . '/logs');
    
    //Ordner für Dateien die durch Tests generiert wurden, z.B. pdfs
    define('TEST_PATH', TEMP_PATH . '/tests');

/**
 * DEBUG_MODE:
 * Was macht es?
 * Wenn true, werden die Funktionen dd() etwas ausgeben (wie print).
 * Das gilt auch für debug(), hier wird in der Konsole nur dann was ausgegeben wenn DEBUG_MODE true ist
 */
    define('DEBUG_MODE', false);
