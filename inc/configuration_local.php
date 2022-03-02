<?php


/**
 * Lokale Konfiguration (Beispiel für configuration_local.php)
 */

class configuration
{
    // global configuration file

    // DATABASE ==================
    public $debugMode = false;

    // USE VM_DEV LATEST DB
    public $host = '172.16.100.20';

    public $database = 'feedback';

    public $user = 'root';

    public $password = 'hopsing';

    //E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED;
    public $errorReportingLevel = 0;

    public $img_path = 'http://localhost/feedback/img/';

    public $ms_app = 'mailhog';

    public $ms_password = 'mailhog';

    public $ms_from = 'info@unternehmer-pbe-2021.de';

    public $path_ending_mail_header = '_mail_header.jpg';

    public $link_feedback = 'http://localhost/feedback/public/feedback.php';
}


$Configuration = new Configuration();

error_reporting($Configuration->errorReportingLevel);
@ini_set("display_errors", 0);

