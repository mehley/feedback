<?php
require_once('./../inc/first.php');
error_reporting(E_ALL);
ini_set("display_errors","On");
?>

<body>
<div class="verlauf p-3">
    <img class="header_img rounded mx-auto d-block mb-3 mt-3" src="./../img/keyvisual.jpg">
</div>

<?php
$error = false;
$showLoginForm = true;
$showMailForm = false;
$showGroupForm = false;
$showSuccess = false;

if (!empty($_POST) && empty($_POST['groups']) && empty($_POST['edit_mail_dispatch']) && empty($_POST['maildispatch'])) {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);

    $sql = 'SELECT * FROM users WHERE username=:username';
    $params = [
        ':username' => $username
    ];

    $userData = DB::fromDatabase($sql,'@line', $params);

    if (!empty($userData) && $password === $userData['password'] && $userData['backend_mail'] == 1) {
        $showLoginForm = false;
        $showMailForm = true;
        $showGroupForm = false;
        $showSuccess = false;
    } else {
        $error = true;
    }
}
if (!empty($_POST) && !empty($_POST['groups']) && !empty($_POST['mail_dispatch_name']) && !empty($_POST['form']) && !empty($_POST['id_mail_templates'])) {
    if (!empty($_POST['group_expiration_date'])){
        $expiration = date('Y-m-d H:i:s', strtotime($_POST['group_expiration_date']. ' ' .$_POST['group_expiration_time']));
    }else{
        $expiration = null;
    }

    $sql = 'INSERT INTO email_dispatches SET `name`=:name, id_forms=:id_forms, sending_time=:sending_time, id_mail_templates=:id_mail_templates, expiration_time=:expiration_time';
    $sql_params = [
        ':name' => $_POST['mail_dispatch_name'],
        ':id_forms' => $_POST['form'],
        ':sending_time' => date('Y-m-d H:i:s', strtotime($_POST['group_date']. ' ' .$_POST['group_time'])),
        ':id_mail_templates' => $_POST['id_mail_templates'],
        ':expiration_time' => $expiration
    ];
    DB::toDatabase($sql,$sql_params);

    $id_mail_dispatch = DB::lastInsertId();

    foreach($_POST['groups'] as $key => $value){
        $sql = 'INSERT INTO mail_dispatches2groups SET id_groups=:id_groups, id_mail_dispatches=:id_mail_dispatches';
        $sql_params = [
            ':id_groups' => $value,
            ':id_mail_dispatches' => $id_mail_dispatch
        ];
        DB::toDatabase($sql, $sql_params);
    }

    $showLoginForm = false;
    $showMailForm = true;
    $showGroupForm = true;
    $showSuccess = false;
}

if (!empty($_SESSION['user_id']) && $_SESSION['user_id']>0) {
    $sql = 'SELECT * FROM users WHERE id=:id';
    $params = [
        ':id' => $_SESSION['user_id']
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);
}

if (!empty($_FILES) && !empty($_POST['mail_dispatch_name'])) {
    if (!empty($_FILES['file_mail_header']) && $_FILES['file_mail_header']['size'] > 0) {
        $uploaddir = './../img/';
        $uploadfile = $uploaddir . $_POST['mail_dispatch_name'] . Config::get('path_ending_mail_header');

        move_uploaded_file($_FILES['file_mail_header']['tmp_name'], $uploadfile);
    }

    if (!empty($_FILES['edit_file_mail_header']) && $_FILES['edit_file_mail_header']['size'] > 0) {
        $uploaddir = './../img/';
        $uploadfile = $uploaddir . $_POST['mail_dispatch_name'] . Config::get('path_ending_mail_header');

        if(file_exists($uploadfile)){
            unlink($uploadfile);
        }

        move_uploaded_file($_FILES['edit_file_mail_header']['tmp_name'], $uploadfile);
    }
}

if ($showLoginForm){
if (!empty($userData) && $userData['backend_mail'] == 1){
    //show menu
    $showLoginForm = false;
    $showMailForm = true;
    $showGroupForm = false;
    $showSuccess = false;
}else{
?>
<div class="container">
    <div class="row">
        <div class="col-lg-4"></div>
        <div class="col-lg-4 text-center">
            <form method="post">
                <input class="login form-control-lg" type="text" name="username" placeholder="Benutzername"/>
                <br />
                <input class="login form-control-lg" type="password" name="password" placeholder="Passwort"/>
                <br />
                <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-4">Login</button>
            </form>
            <?php
            if ($error){
                echo '<p class="alert alert-danger mt-3">Login fehlgeschlagen. Der Benutzername oder das Passwort ist fehlerhaft.</p>';
            }
            ?>
        </div>
        <div class="col-lg-4"></div>
    </div>
</div>
<?php
}
}
?>

<?php
if ($showMailForm){
    $sql = 'SELECT * FROM `email_dispatches` ORDER BY sending_time DESC';
    $mail_dispatches = DB::fromDatabase($sql,'@raw');
    ?>
    <div class="container">
        <div class="row mt-2 mb-4">
            <div class="col-lg-3"></div>
            <div class="col-lg-6 text-center custom_border">
                <div class="col-lg-12">
                    <a href="./backend.php"><span class="float-end"><i class="fas fa-backward"></i> zurück</span></a></br>
                    <form method="post" enctype="multipart/form-data" class="mt-3">
                        <input type="text" name="<?=SslCrypt::encrypt('mail_dispatch_name')?>" class="form-control mb-3" placeholder="Name" required>
                        <?php
                            $sql = 'SELECT * FROM `forms` ORDER BY name ASC';
                            $forms = DB::fromDatabase($sql,'@raw');
                            if(!empty($forms)){
                                ?>

                            <select class="form-select mb-3" name="<?=SslCrypt::encrypt('form')?>" required>
                                <option value="" selected>Formular auswählen</option>
                                <?php
                                foreach ($forms as $key => $value){
                                    echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                                }
                                ?>
                            </select>
                            <?php
                            }
                        $sql = 'SELECT * FROM `groups` ORDER BY name ASC';
                        $groups = DB::fromDatabase($sql,'@raw');
                        if(!empty($groups)){
                        ?>
                        <label for="groupSelect" class="fw-bold mb-2">Empfänger Gruppen:</label>
                        <select class="js-example-basic-multiple form-select" id="groupSelect" name="<?=SslCrypt::encrypt('groups')?>[]" multiple="multiple" required>
                            <?php
                            foreach ($groups as $key => $value){
                                echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                            }
                            ?>
                        </select>
                            <label class="form-label fw-bold mt-3" for="group_date">Zeitpunkt für den Versand:</label><br />
                            <input type="date" class="mt-1 form-control-lg w-66 full_width_md" id="group_date" name="<?=SslCrypt::encrypt('group_date')?>">
                            <input type="time" class="mt-1 form-control-lg w-33 full_width_md" name="<?=SslCrypt::encrypt('group_time')?>">

                            <label class="form-label fw-bold mt-3" for="group_expiration_date">Link gültig bis:</label><br />
                            <input type="date" class="mt-1 form-control-lg w-66 full_width_md" id="group_expiration_date" name="<?=SslCrypt::encrypt('group_expiration_date')?>">
                            <input type="time" class="mt-1 form-control-lg w-33 full_width_md" name="<?=SslCrypt::encrypt('group_expiration_time')?>">

                            <label class="form-label fw-bold mt-3" for="file_mail_header">Mail Header Image</label>
                            <input class="mt-1 form-control mb-3" type="file" id="file_mail_header" name="file_mail_header"/>

                            <?php
                            $sql = 'SELECT * FROM mail_templates ORDER BY name ASC';
                            $mail_templates = DB::fromDatabase($sql,'@raw');
                            if(!empty($mail_templates)){
                                ?>

                            <select class="form-select mb-3" name="<?=SslCrypt::encrypt('id_mail_templates')?>" required>
                                <option value="" selected>E-Mail Vorlage</option>
                                <?php
                                foreach ($mail_templates as $key => $value){
                                    echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                                }
                                ?>
                            </select>
                            <?php
                            }
                            ?>
                            <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-2">Versand anlegen</button>
                        <?php }else{
                            echo '<p>Bevor Sie einen Versand anlegen können, müssen Sie mindestens eine Gruppe erstellen</p>';
                        }?>
                    </form>
                </div>
                <br/>
                <hr/>
                <form method="post" class="mt-5">
                    <label class="form-label fw-bold" for="dispatchSelect">Versände</label>
                    <select class="form-select form-select-lg" name="<?=SslCrypt::encrypt('maildispatch')?>" id="dispatchSelect" required>
                        <option value=""><?= translate('select_standard')?></option>
                        <?php
                        foreach ($mail_dispatches as $key => $value){
                            $date = new DateTime($value['sending_time']);
                            $dateFormatted = $date->format('d.m.Y | H:i');

                            $formname = '';
                            if (!empty($value['name'])){
                                $formname = ' '.$value['name'];
                            }
                            echo '<option value="'.$value['id'].'">'.$dateFormatted.$formname.'</option>';
                        }
                        ?>
                    </select>
                    <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-2">Versand anzeigen</button>
                </form>

                <?php
                if ($showSuccess){
                    echo '<p class="alert alert-success mt-3">Der Versand wurde erfolgreich bearbeitet!</p>';
                }
                ?>
            </div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <?php
}

if (!empty($_POST['maildispatch'])){
    $id_mail_dispatch = $_POST['maildispatch'];
    $showGroupForm = true;
}

if (!empty($_POST['edit_mail_dispatch'])){
    if (!empty($_POST['edit_group_expiration_date'])){
        $expiration = date('Y-m-d H:i:s', strtotime($_POST['edit_group_expiration_date']. ' ' .$_POST['edit_group_expiration_time']));
    }else{
        $expiration = null;
    }

    $sql = 'UPDATE email_dispatches SET `name`=:name, id_forms=:id_forms, sending_time=:sending_time, id_mail_templates=:id_mail_templates, expiration_time=:expiration_time WHERE id=:id';
    $sql_params = [
        ':name' => $_POST['edit_mail_dispatch_name'],
        ':id_forms' => $_POST['edit_id_form'],
        ':sending_time' => date('Y-m-d H:i:s', strtotime($_POST['group_date']. ' ' .$_POST['group_time'])),
        ':id' => $_POST['edit_mail_dispatch'],
        ':id_mail_templates' => $_POST['edit_id_mail_templates'],
        ':expiration_time' => $expiration
    ];
    DB::toDatabase($sql, $sql_params);

    $sql = 'DELETE FROM mail_dispatches2groups WHERE id_mail_dispatches=:id_mail_dispatches';
    $sql_params = [
        ':id_mail_dispatches' => $_POST['edit_mail_dispatch']
    ];
    DB::toDatabase($sql,$sql_params,false,true);

    foreach ($_POST['groups'] as $key => $value){
        $sql = 'INSERT INTO mail_dispatches2groups SET id_groups=:id_groups, id_mail_dispatches=:id_mail_dispatches';
        $sql_params = [
            ':id_groups' => $value,
            ':id_mail_dispatches' => $_POST['edit_mail_dispatch']
        ];
        DB::toDatabase($sql,$sql_params);
    }
}

if ($showGroupForm && !empty($id_mail_dispatch)){
$sql = 'SELECT * FROM `email_dispatches` WHERE id=:id';
$params = [
    ':id' => $id_mail_dispatch
];
$mailData = DB::fromDatabase($sql,'@line',$params);

$date = new DateTime($mailData['sending_time']);
$groupDateReadable = $date->format('d.m.Y | H:i');

$dispatchDate = $date->format('Y-m-d');
$dispatchTime = $date->format('H:i');

$date = new DateTime($mailData['expiration_time']);
if (!empty($mailData['expiration_time'])){
    $groupExpirationDateReadable = $date->format('d.m.Y | H:i');

    $dispatchExpirationDate = $date->format('Y-m-d');
    $dispatchExpirationTime = $date->format('H:i');
}else{
    $dispatchExpirationDate = null;
    $dispatchExpirationTime = null;
}
?>
<div class="container mt-4 mb-4 custom_border">
    <div class="row">
        <div class="col-lg-7">
            <p class="fw-bold"><?= $groupDateReadable?></p>

            <form method="post">
                <div class="w-md-75 full_width_md">

                    <input class="mt-2 form-control" type="text" name="<?=SslCrypt::encrypt('edit_mail_dispatch_name')?>" placeholder="Versandname" value="<?= $mailData['name'] ?>"/>

                    <label class="form-label fw-bold mt-3" for="edit_group_date">Zeitpunkt für den Versand:</label><br />
                    <input type="date" class="mt-1 form-control-lg w-66 full_width_md mb-3" id="edit_group_date" value="<?= $dispatchDate ?>" name="<?=SslCrypt::encrypt('group_date')?>">
                    <input type="time" class="mt-1 form-control-lg w-33 full_width_md mb-3" value="<?= $dispatchTime ?>" name="<?=SslCrypt::encrypt('group_time')?>">
                    <?php
                    $sql = 'SELECT DISTINCT id_groups FROM mail_dispatches2groups WHERE id_mail_dispatches=:id_mail_dispatches';
                    $sql_params = [
                        ':id_mail_dispatches' => $id_mail_dispatch
                    ];
                    $selected_groups = DB::fromDatabase($sql, '@array',$sql_params);

                    $sql = 'SELECT * FROM `groups`';
                    $groups = DB::fromDatabase($sql,'@raw');
                    if(!empty($groups)){
                    ?>
                    <select class="js-example-basic-multiple form-select" name="<?=SslCrypt::encrypt('groups')?>[]" multiple="multiple">
                        <?php
                        foreach ($groups as $key => $value){
                            if (in_array($value['id'],$selected_groups)){
                                echo '<option value="'.$value['id'].'" selected>'.$value['name'].'</option>';

                            }else{
                                echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php
                    }
                    $sql = 'SELECT * FROM `forms`';
                    $forms = DB::fromDatabase($sql,'@raw');
                    if(!empty($forms)){
                        ?>

                        <select class="form-select mt-3" name="<?=SslCrypt::encrypt('edit_id_form')?>">
                            <?php
                            foreach ($forms as $key => $value){
                                if ($value['id'] == $mailData['id_forms']){
                                    echo '<option value="'.$value['id'].'" selected>'.$value['name'].'</option>';
                                }else{
                                    echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                                }
                            }
                            ?>
                        </select>
                        <?php
                    }
                    ?>
                    <br />
                    <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-3 float-start">Änderungen speichern</button>

                    <input type="hidden" name="<?=SslCrypt::encrypt('edit_mail_dispatch')?>" value="<?= $id_mail_dispatch ?>">
                </div>
        </div>
        <div class="col-lg-5">
            <label class="form-label fw-bold mt-3" for="edit_group_expiration_date">Link gültig bis:</label><br />
            <input type="date" class="mt-1 form-control-lg w-66 full_width_md" id="edit_group_expiration_date" name="<?=SslCrypt::encrypt('edit_group_expiration_date')?>" value="<?= $dispatchExpirationDate; ?>">
            <input type="time" class="mt-1 form-control-lg w-33 full_width_md" name="<?=SslCrypt::encrypt('edit_group_expiration_time')?>" value="<?= $dispatchExpirationTime; ?>">

            <label class="form-label fw-bold mt-3" for="edit_file_mail_header">Mail Header Image</label>
            <input class="mt-1 form-control mb-3" type="file" id="edit_file_mail_header" name="edit_file_mail_header"/>

            <?php
            $sql = 'SELECT * FROM mail_templates ORDER BY name ASC';
            $mail_templates = DB::fromDatabase($sql,'@raw');

            if(!empty($mail_templates)){
                ?>

                <select class="form-select mb-3" name="<?=SslCrypt::encrypt('edit_id_mail_templates')?>">
                    <?php
                    foreach ($mail_templates as $key => $value){
                        if ($value['id'] == $mailData['id_mail_templates']){
                            echo '<option value="'.$value['id'].'" selected>'.$value['name'].'</option>';
                        }else{
                            echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                        }
                    }
                    ?>
                </select>
                <?php
            }
            ?>
            </form>
            <button style="background-color: <?= $button_color ?>" type="button" class="btn btn-primary mt-2 w-100" data-grp="<?= $_POST['group'] ?>">Vorschau ansehen</button>

            <button style="background-color: <?= $button_color ?>" type="button" class="btn btn-primary mt-3 w-100 sendMails" data-id_email_dispatch="<?= $_POST['maildispatch'] ?>">E-Mails Versenden</button>
        </div>

        <?php
        $sql = 'SELECT *,(SELECT mail FROM mails_from WHERE id=(SELECT id_mails_from FROM groups WHERE id=persons.id_groups)) AS mail_from FROM persons WHERE id_groups IN (SELECT DISTINCT id_groups FROM mail_dispatches2groups WHERE id_mail_dispatches=:id_mail_dispatches) ORDER BY lastname ASC';
        $params = [
            ':id_mail_dispatches' => $id_mail_dispatch
        ];
        $persons = DB::fromDatabase($sql,'@raw',$params);

        if (!empty($persons)){
        ?>
        <table class="mt-4 p-4">
            <thead>
            <tr>
                <th>Nachname</th>
                <th>Vorname</th>
                <th>E-Mail</th>
                <th>Sprache</th>
                <th>E-Mail von</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($persons as $key => $value){
                $style = 'line_through';
                if($value['mail_sent']==1){
                    $style  = 'blue';
                }
                echo '<tr class="'. $style  .'">';
                echo '<td>'.$value['lastname'].'</td>';

                echo '<td>'.$value['firstname'].'</td>';

                echo '<td>'.$value['email'].'</td>';

                $sql = 'SELECT `language` FROM languages WHERE id=:id';
                $params = [
                    ':id' => $value['id_languages']
                ];
                $language = DB::fromDatabase($sql,'@simple',$params);
                echo '<td>'.$language.'</td>';

                echo '<td>'.$value['mail_from'].'</td>';

                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
        <?php
        }else{
            echo '<p class="mt-4">Diese Gruppe enthält keine Teilnehmer!</p>';
        }
        ?>
    </div>
</div>

<div class="container mt-4 mb-4 custom_border hidden recipient_list">
    <div class="row">
        <div class="mail_response col-lg-12">

        </div>
    </div>
</div>

<?php
}

require_once('./../inc/footer.php')
?>
