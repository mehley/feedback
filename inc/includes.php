<?php
if (preg_match('/sendmail\.php/', $_SERVER['PHP_SELF'])) {
    $dirBack = '';
}else{
    $dirBack = '../';
}
require_once('./'.$dirBack.'inc/modules/System.php');
require_once('./'.$dirBack.'inc/functions.php');
require_once('./'.$dirBack.'inc/modules/Config.php');
require_once('./'.$dirBack.'inc/modules/Base.php');
require_once('./'.$dirBack.'inc/modules/TinyError.php');
require_once('./'.$dirBack.'inc/modules/Clockwork.php');
require_once('./'.$dirBack.'inc/modules/Parameter.php');
require_once('./'.$dirBack.'inc/modules/DB.php');
require_once('./'.$dirBack.'inc/modules/SslCrypt.php');
require_once('./'.$dirBack.'inc/MailerService.php');

// Verschlüsselung
// decrypt $_GET/$_POST
if (((is_array($_POST))&&(!empty($_POST)))) {
    System::decryptPOST();
}

if (((is_array($_GET))&&(!empty($_GET)))) {
    if (isset($_GET[0]) && $_GET[0] == 'useFixedKey'){
        System::decryptGET(true);
    }else{
        System::decryptGET();
    }
}

