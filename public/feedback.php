
<?php
require_once ('./../inc/first.php');

?>
<body>
<div class="verlauf p-3">
    <img class="header_img rounded mx-auto d-block mb-3 mt-3" src="./../img/keyvisual.jpg">
</div>

<?php

if (!empty($_GET['uid']) && !empty($_GET['formid'])){
    $uid = $_GET['uid'];
    $formid = $_GET['formid'];
    $allowedForm = false;
    $expiredForm = false;

    $sql = 'SELECT *,
       (SELECT language_code FROM languages WHERE languages.id=persons.id_languages) AS language_code FROM persons
       WHERE uid = :uid';
    $params = [
        ':uid' => $uid
    ];

    $user = DB::fromDatabase($sql,'@line',$params);

    //check if user is allowed to see form
    $sql = 'SELECT DISTINCT id_groups FROM mail_dispatches2groups WHERE id_mail_dispatches IN (SELECT id FROM email_dispatches WHERE id_forms=:id_forms)';
    $sql_params = [
        ':id_forms' => $formid
    ];
    $allowedGroups = DB::fromDatabase($sql, '@array', $sql_params);
    if (in_array($user['id_groups'],$allowedGroups)){
        $allowedForm = true;

        $sql = 'SELECT MAX(expiration_time) FROM email_dispatches WHERE id IN (SELECT DISTINCT id_mail_dispatches FROM mail_dispatches2groups WHERE id_groups=:id_groups) AND id_forms=:id_forms';
        $sql_params = [
            ':id_groups' => $user['id_groups'],
            ':id_forms' => $formid
        ];
        $expiration = DB::fromDatabase($sql, '@simple', $sql_params);

        $date = date('Y-m-d H:i:s', time());
        if (!empty($expiration) && ($date > $expiration)){
            $expiredForm = true;
        }
    }

    if (!empty($_POST)){

        $valid = true;
        foreach ($_POST as $key => $value){
            $id_form_element = str_replace('form_field_','',$key);
            $sql = 'SELECT validation FROM form_element_types WHERE id=(SELECT id_form_element_types FROM form_elements WHERE id=:id)';
            $params = [
                ':id' => $id_form_element
            ];
            $validation = DB::fromDatabase($sql,'@simple', $params);
            if (!empty($validation['validation'])){
                switch ($validation['validation']){
                    case 'text':
                        if (empty($value) || !is_string($value)){
                            $valid = false;
                        }
                        break;
                    case 'number':
                        if (empty($value) || !is_numeric($value)){
                            $valid = false;
                        }
                        break;
                    default:
                        if (empty($value)){
                            $valid = false;
                        }
                        break;
                }
            }
        }

        if ($valid){
            //Save answers to database
            $sql = 'INSERT INTO answers SET id_form_elements=:id_form_elements, id_persons=:id_persons, answer=:answer';

            foreach ($_POST as $key => $value){
                $id_element = str_replace('form_field_','',$key);
                $params = [
                    ':id_form_elements' => $id_element,
                    ':id_persons' => $user['id'],
                    ':answer' => $value
                ];
                DB::toDatabase($sql,$params);
            }

            //set person completed
            DB::toDatabase('UPDATE persons SET completed=1 WHERE id=:id',[':id' => $user['id']]);

            $msg_after_submit = array();
            $msg_after_submit['de'] = 'Vielen Dank für Ihre Teilnahme an der Umfrage!';
            $msg_after_submit['en'] = 'Thank you for taking part in the survey!';
            $msg_after_submit['it'] = '';
            $msg_after_submit['fr'] = '';
            $msg_after_submit['es'] = '';
            $msg_after_submit['jp'] = '';

            $sql = 'SELECT language_code FROM languages WHERE id=:id';
            $sql_params = [
                ':id' => $user['id_languages']
            ];
            $language_code = DB::fromDatabase($sql, '@simple', $sql_params);

            echo '<div class="container">
                    <div class="row">
                    <div class="col-xl-1"></div>
                    <div class="col-xl-10">
                    <p class="alert">'.$msg_after_submit[$language_code].'</p></div>
                    <div class="col-xl-1"></div>
                    </div></div>';
        }else{

            if ($user['id_languages']==1) {
                $text = 'Fehler: Ihre Antworten konnten nicht abgespeichert werden, bitte versuchen Sie es erneut.';
            }else{
                $text = 'Error: Your answers could not be saved, please try again.';
            }
            echo '<div class="container">
                    <div class="row">
                    <div class="col-xl-1"></div>
                    <div class="col-xl-10">
                    <p class="alert">'.$text.'</p></div>
                    <div class="col-xl-1"></div>
                    </div></div>';
            header("Refresh:4");
        }
    }
}

if (!empty($user) && empty($_POST) && $user['completed'] == 0 && $allowedForm && !$expiredForm){
    $language_code = $user['language_code'];
    $number = 1;
    ?>
    <div class="container">
        <div class="row">
            <div class="col-xl-1"></div>
            <div class="col-xl-10">
                <form method="post" class="feedbackForm">
                    <?php
                        $sql = 'SELECT * FROM form_elements WHERE id_forms=:id_forms ORDER BY `order` ASC';
                        $sql_params = [
                            ':id_forms' => $formid
                        ];
                        $form_elements = DB::fromDatabase($sql,'@raw',$sql_params);

                        $rating_count = 1;
                        foreach ($form_elements as $key => $value){
                            $sql = 'SELECT `type` FROM form_element_types WHERE id = :id';
                            $sql_params = [
                                ':id' => $value['id_form_element_types']
                            ];
                            $form_element_type = DB::fromDatabase($sql, '@simple', $sql_params);

                            switch ($form_element_type){
                                case 'text':
                                    $number = createText($number,$language_code,$value['id']);
                                    break;
                                case 'inputText':
                                    $number = createInputText($number,$language_code,$value['id']);
                                    break;
                                case 'inputText_multiple':
                                    $number = createInputText_multiple($number,$language_code,$value['id']);
                                    break;
                                case 'rating':
                                    $number = createRating($number,$language_code,$value['id'],$rating_count);
                                    $rating_count++;
                                    break;
                                case 'rating10':
                                    $number = createRating10($number,$language_code,$value['id'],$rating_count);
                                    $rating_count++;
                                    break;
                                default:
                                    break;
                            }

                        }

                    ?>
                    <br>
                    <div class="container mb-2">
                        <div class="col-md-12 text-center">
                            <?php
                            $absenden = array();
                            $absenden['de']='Absenden';
                            $absenden['en']='Submit';
                            $absenden['it']='Inviare';
                            $absenden['fr']='enviar';
                            $absenden['es']='Soumettre';
                            $absenden['jp']='回答を送信する';
                            ?>
                            <button type="submit" class="btn btn-primary col-md-3 m-auto full_width_sm feedbackFormSubmit"><?php echo $absenden[$user['language_code']]; ?></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-1"></div>
        </div>
    </div>
<?php
}

if ((!empty($user) && !$expiredForm) && empty($_POST)){
    echo '<div class="container">
                    <div class="row">
                    <div class="col-xl-1"></div>
                    <div class="col-xl-10">
                    <p class="alert">Your link has expired!</p></div>
                    <div class="col-xl-1"></div>
                    </div></div>';
}

if ((empty($user)||!$allowedForm) && empty($_POST)){
    echo '<div class="container">
                    <div class="row">
                    <div class="col-xl-1"></div>
                    <div class="col-xl-10">
                    <p class="alert">Something went wrong. Please check whether the link was entered correctly.</p></div>
                    <div class="col-xl-1"></div>
                    </div></div>';
}

if (!empty($user) && $user['completed'] == 1 && empty($_POST)){
    echo '<div class="container">
                    <div class="row">
                    <div class="col-xl-1"></div>
                    <div class="col-xl-10">
                    <p class="alert">'.translate('form_already_completed').'</p></div>
                    <div class="col-xl-1"></div>
                    </div></div>';
}
require_once('./../inc/footer.php')
?>
