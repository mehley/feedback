<?php
session_start();
require_once('./inc/includes.php');

if (!empty($_POST['id_email_dispatch'])){
    $sql = 'SELECT * FROM email_dispatches WHERE id=:id';
    $sql_params = [
        ':id' => $_POST['id_email_dispatch']
    ];
    $email_dispatch = DB::fromDatabase($sql, '@line', $sql_params);

    $mail_img_path = Config::get('img_path').$email_dispatch['name'].Config::get('path_ending_mail_header');

    //Get all Persons from recieving groups:
    $sql = 'SELECT id_groups FROM mail_dispatches2groups WHERE id_mail_dispatches=:id_mail_dispatches';
    $sql_params = [
        ':id_mail_dispatches' => $_POST['id_email_dispatch']
    ];
    $id_groups = DB::fromDatabase($sql, '@array', $sql_params);

    $string_groups = implode(',',$id_groups);

    $sql = 'SELECT * FROM persons WHERE id_groups IN (:id_groups)';
    $params = [
        ':id_groups' => $string_groups
    ];
    $dataRecievers = DB::fromDatabase($sql, '@raw' , $params);

    $counter = 0;
    if (!empty($dataRecievers)){
        echo 'Folgende Benutzer haben eine E-Mail erhalten: ';
        echo '<table class="mt-3 w-100">';
        echo '<thead>';
        echo '<tr><th>Nachname</th>
                <th>Vorname</th>
                <th>E-Mail</th>
                <th>Sprache</th>
                <th>E-Mail von</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($dataRecievers as $key => $value){
            $sql = 'SELECT id FROM persons WHERE email=:email AND mail_sent=1';
            $sql_params = [
                ':email' => $value['email']
            ];
            $user_check = DB::fromDatabase($sql,'@array', $sql_params);

            if (empty($user_check) && $value['mail_sent'] == 0){
                $sql = 'SELECT id_mail_templates FROM email_dispatches WHERE id=:id';
                $sql_params = [
                    ':id' => $_POST['id_email_dispatch']
                ];
                $id_mail_template = DB::fromDatabase($sql, '@simple', $sql_params);

                $sql = 'SELECT * FROM mail_content WHERE id_languages=:id_languages AND id_mail_templates=:id_mail_templates';
                $params = [
                    ':id_languages' => $value['id_languages'],
                    ':id_mail_templates' => $id_mail_template
                ];
                $dataMail = DB::fromDatabase($sql,'@raw', $params);

                $message = file_get_contents('./emailContent.html');
                $message = preg_replace('/@mail-img@/',$mail_img_path,$message);
                $message = preg_replace('/@mail-content@/',$dataMail['mail_content'],$message);
                $message = preg_replace('/@mail-firstname@/',$value['firstname'],$message);
                $message = preg_replace('/@mail-lastname@/',$value['lastname'],$message);
                $message = preg_replace('/@mail-link_addition@/','?uid='.$value['uid'].'&formid='.$email_dispatch['id_forms'],$message);

                //SET MAIL PARAMS
                $ms_service_url = 'https://sendmail.planet-itservices.com';
                $ms_app = Config::get('ms_app');
                $ms_password = Config::get('ms_password');
                $sql = 'SELECT mail FROM mails_from WHERE id = (SELECT id_mails_from FROM groups WHERE id=:id)';
                $sql_params = [
                    ':id' => $value['id_groups']
                ];
                $ms_from = DB::fromDatabase($sql,'@simple',$sql_params);

                if (sendMailOverService($ms_service_url, $ms_app, $ms_password, $value['email'], $ms_from, $dataMail['mail_subject'], $message)){
                    $sql = 'UPDATE persons SET mail_sent = 1 WHERE id=:id';
                    $sql_params = [
                        ':id' => $value['id']
                    ];
                    DB::toDatabase($sql, $sql_params);

                    $sql_lan = 'SELECT `language` FROM languages WHERE id=:id';
                    $params_lan = [
                        ':id' => $value['id_languages']
                    ];
                    $language_person = DB::fromDatabase($sql_lan,'@simple',$params_lan);

                    echo '<tr><td>'.$value['lastname'].'</td>
                    <td>'.$value['firstname'].'</td>
                    <td>'.$value['email'].'</td>
                    <td>'.$language_person['language'].'</td>
                    <td>'.$ms_from.'</td></tr>';
                }

                $counter++;
            }
        }
        echo '</tbody></table><br/>';
        if ($counter == 1){
            echo 'Es wurde eine E-Mail versendet.';
        }else{
            echo 'Es wurden ' . $counter . ' E-Mails versendet.';
        }
    }else{
        echo 'Es wurden keine E-Mails versendet. Diese Gruppe enthält keine Teilnehmer, welche die Bedingungen für den Empfang der E-Mail erfüllen';
    }
}else{
    http_response_code(404);
}