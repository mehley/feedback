<?php
session_start();
require_once('./../inc/includes.php');


switch ($_POST['action']){
    case 'addMail':
        echo '<br><input class="form-control-check mt-2 form-control" type="text" placeholder="E-Mail" name="'.SslCrypt::encrypt('additionalMailFrom').'[]"/>';

        break;
    case 'removeMail':
        $sql = 'DELETE FROM mails_from WHERE id=:id LIMIT 1';
        $sql_params = [
            ':id' => $_POST['id']
        ];
        DB::toDatabase($sql, $sql_params);

        getMailsFrom();
        break;
    default:

        break;
}