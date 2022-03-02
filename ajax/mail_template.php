<?php
session_start();
require_once('./../inc/includes.php');

switch ($_POST['action']){
    case 'loadTemplate':
        $sql = 'SELECT `name` FROM mail_templates WHERE id=:id';
        $sql_params = [
            ':id' => $_POST['id']
        ];
        $name = DB::fromDatabase($sql,'@simple',$sql_params);

        $sql = 'SELECT id_languages, mail_subject FROM mail_content WHERE id_mail_templates=:id_mail_templates';
        $sql_params = [
            ':id_mail_templates' => $_POST['id']
        ];
        $mail_subject = DB::fromDatabase($sql, '@flat', $sql_params);

        $sql = 'SELECT id_languages, mail_content FROM mail_content WHERE id_mail_templates=:id_mail_templates';
        $sql_params = [
            ':id_mail_templates' => $_POST['id']
        ];
        $mail_content = DB::fromDatabase($sql, '@flat', $sql_params);

        echo '<input type="text" name="'.SslCrypt::encrypt('template_name').'" class="form-control" placeholder="'.translate('name_mail_content').'" value="'.$name.'" />';
        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        echo '<table class="mt-3 w-100">';
        foreach ($languages as $key => $value){
            echo '<tr>';
            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
            echo '<td><input name="'.SslCrypt::encrypt('mail_subject').'['.$value['language_code'].']" type="text" class="form-control-check mt-3 form-control" placeholder="'.translate('mailSubject').'" value="'.$mail_subject[$value['id']].'"></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="w_48px"></td>';
            echo '<td><textarea name="'.SslCrypt::encrypt('mail_content').'['.$value['language_code'].']" placeholder="'.translate('mail_content_textarea').'" class="form-control mt-1">'.$mail_content[$value['id']].'</textarea></td>';
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'removeTemplateConfirm':
        if ($_POST['id'] > 0){
            $translations = [
                'titleConfirm' => translate('swal_deleteTemplate_confirm_title'),
                'textConfirm' => translate('swal_deleteTemplate_confirm_text')
            ];

            echo json_encode($translations);
        }
        break;
    case 'removeTemplate':
        if ($_POST['id'] > 0){
            $sql = 'DELETE FROM mail_templates WHERE id=:id LIMIT 1';
            $sql_params = [
                ':id' => $_POST['id']
            ];
            DB::toDatabase($sql, $sql_params);

            $translations = [
                'title' => translate('swal_deleteTemplate_title'),
                'text' => translate('swal_deleteForm_text'),
            ];

            echo json_encode($translations);
        }
        break;
    default:

        break;
}