<?php
require_once('./../inc/first.php');
?>

<body>
<div class="verlauf p-3">
    <img class="header_img rounded mx-auto d-block mb-3 mt-3" src="./../img/keyvisual.jpg">
</div>

<?php
$error = false;
$showLoginForm = true;
$showNewGroupForm = false;
$showGroupForm = false;
$showSuccess = false;

if (!empty($_POST) && empty($_POST['group']) && empty($_POST['file_upload']) && empty($_POST['import_file_receivers'])) {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);

    $sql = 'SELECT * FROM users WHERE username=:username';
    $params = [
        ':username' => $username
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);

    if (!empty($userData) && $password === $userData['password'] && $userData['backend_mail'] == 1) {
        $showLoginForm = false;
        $showNewGroupForm = true;
        $showGroupForm = false;
        $showSuccess = false;
    } else {
        $error = true;
    }
}

if (!empty($_POST) && !empty($_POST['group'])) {
    $showLoginForm = false;
    $showNewGroupForm = true;
    $showGroupForm = true;
    $showSuccess = false;
}

if (!empty($_POST['edit_group'])) {
    $sql = 'UPDATE `groups` SET id_mails_from=:id_mails_from, name=:name WHERE id=:id';
    $params = [
        ':id_mails_from' => $_POST['edit_groupMailfrom'],
        ':name' => $_POST['edit_group_name'],
        ':id' => $_POST['group']
    ];
    DB::toDatabase($sql,$params);

    $showSuccess = true;
}

if (!empty($_SESSION['user_id']) && $_SESSION['user_id']>0) {
    $sql = 'SELECT * FROM users WHERE id=:id';
    $params = [
        ':id' => $_SESSION['user_id']
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);
}

if (!empty($_FILES)){
    if (!empty($_FILES['file_receivers'])) {
        $uploaddir = './../uploads/';
        $uploadfile = $uploaddir . 'participants.csv';
        $count_recipients = 0;
        $id_group = 0;

        if (move_uploaded_file($_FILES['file_receivers']['tmp_name'], $uploadfile)) {
            if (($handle = fopen($uploadfile, "r")) !== FALSE) {
                //check if row titles are as expectet
                $csvFirstrow = fgetcsv($handle, 0, ";");

                $headerCheck = true;

                if ((strtolower($csvFirstrow[0]) != 'firstname') || (strtolower($csvFirstrow[1]) != 'lastname') || (strtolower($csvFirstrow[2]) != 'email')  || (strtolower($csvFirstrow[3]) != 'language') || (strtolower($csvFirstrow[4]) != 'gender')){
                    //$headerCheck = false;
                    //todo springt hier immer rein warum auch immer
                }

                if($headerCheck){
                    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                        //Define group at first participant because all members of a list have the same group
                        if ($count_recipients === 0){

                            //Check if group exists, if not create group
                            $sql = 'SELECT id FROM `groups` WHERE name=:name';
                            $params = [
                                ':name' => $_POST['group_name']
                            ];
                            $group_exists = DB::fromDatabase($sql,'@simple',$params);
                            if (!empty($group_exists['id'])){
                                $id_group = $group_exists['id'];
                            }else{
                                $sql = 'INSERT INTO `groups` SET `name`=:name, id_mails_from=:id_mails_from';
                                $params = [
                                    ':name' => $_POST['group_name'],
                                    ':id_mails_from' => $_POST['groupMailfrom']
                                ];
                                DB::toDatabase($sql,$params);
                                $id_group = DB::lastInsertId();
                            }
                        }

                        //get language id
                        if (empty($data[3])){
                            $data[3] = 'en';
                        }
                        $sql = 'SELECT id FROM languages WHERE `language_code`=:language_code';
                        $params = [
                            ':language_code' => $data[3]
                        ];
                        $id_language = DB::fromDatabase($sql,'@simple', $params);

                        $gender = 0;

                        if (strtolower($data[4]) == 'female' || strtolower($data[4]) == 'f' || strtolower($data[4]) == 'weiblich' || strtolower($data[4]) == 'w'){
                            $gender = 1;
                        }

                        if (strtolower($data[4]) == 'male' || strtolower($data[4]) == 'm' || strtolower($data[4]) == 'männlich'){
                            $gender = 2;
                        }

                        //Insert receiver into DB
                        $sql = 'INSERT INTO persons SET id_groups=:id_groups, id_languages=:id_languages, firstname=:firstname, lastname=:lastname, email=:email, gender=:gender';
                        $params = [
                            ':id_groups' => $id_group,
                            ':id_languages' => $id_language['id'],
                            ':firstname' => $data[0],
                            ':lastname' => $data[1],
                            ':email' => $data[2],
                            ':gender' => $gender
                        ];
                        DB::toDatabase($sql, $params);
                        $count_recipients++;
                    }
                }else{
                    echo '<p class="alert m-auto text-center">'.translate('group_import_header_error').'</p>';
                }

                fclose($handle);
            }
            echo '<p class="alert m-auto text-center">CSV-Datei wurde erfolgreich eingepflegt! ' . $count_recipients . ' Teilnehmer wurden hinzugefügt.</p>';
        } else {
            echo '<p class="alert m-auto text-center">Import fehlgeschlagen!</p>';
        }
        unlink($uploadfile);

        $showLoginForm = false;
        $showNewGroupForm = true;
        $showGroupForm = false;
        $showSuccess = false;
    }
}

if ($showLoginForm){
    if (!empty($userData) && $userData['backend_mail'] == 1){

        //show menu
        $showLoginForm = false;
        $showNewGroupForm = true;
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
if ($showNewGroupForm){
    $sql = 'SELECT * FROM `groups` ORDER BY name ASC';
    $groups = DB::fromDatabase($sql,'@raw');

    $sql = 'SELECT * FROM `mails_from` ORDER BY mail ASC';
    $mails = DB::fromDatabase($sql,'@raw');
    ?>
    <div class="container">
        <div class="row mt-2 mb-4">
            <div class="col-lg-3"></div>
            <div class="col-lg-6 text-center custom_border">
                <div class="col-lg-12">
                    <a href="./backend.php"><span class="float-end"><i class="fas fa-backward"></i> <?= translate('back')?></span></a></br>
                    <form method="post" enctype="multipart/form-data" class="mt-3">

                        <label class="form-label fw-bold" for="file_receivers"><?= translate('groups_import_title')?></label>
                        <input class="mt-1 form-control" type="file" name="file_receivers"/>
                        <input class="" type="hidden" name="import_file_receivers" value="1"/>

                        <p class="mt-3"><?= translate('groups_csv_info')?></p>

                        <select class="form-select" name="groupMailfrom" required>
                            <option value=""><?= translate('select_standard')?></option>
                            <?php
                            foreach ($mails as $key => $value){
                                echo '<option value="'.$value['id'].'">'.$value['mail'].'</option>';
                            }
                            ?>
                        </select>

                        <input class="mt-4 form-control" type="text" name="group_name" placeholder="<?= translate('groupname')?>"/>

                        <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-2"><?= translate('group_import')?></button>
                    </form>
                </div>
                <br/>
                <hr/>
                <form method="post" class="mt-5">
                    <label class="form-label fw-bold" for="groupSelect"><?= translate('groups')?></label>
                    <select class="form-select" name="group" id="groupSelect" required>
                        <option value=""><?= translate('select_standard')?></option>
                        <?php
                        foreach ($groups as $key => $value){
                            echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                        }
                        ?>
                    </select>
                    <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-2"><?= translate('group_show')?></button>
                </form>

            </div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <?php
}

if ($showGroupForm && !empty($_POST['group']) && empty($_POST['group_participation']) && empty($_POST['group_formtype'])){
    $sql = 'SELECT * FROM `groups` WHERE id=:id';
    $params = [
        ':id' => $_POST['group']
    ];
    $groupData = DB::fromDatabase($sql,'@line',$params);

    $sql = 'SELECT * FROM `mails_from` WHERE id=:id';
    $params = [
        ':id' => $groupData['id_mails_from']
    ];
    $mailActive = DB::fromDatabase($sql,'@line', $params);

    $sql = 'SELECT * FROM `mails_from` ORDER BY mail ASC';
    $mails = DB::fromDatabase($sql,'@raw');
    ?>
    <div class="container mt-4 mb-4">
        <div class="row">
            <div class="col-lg-1"></div>

            <div class="col-lg-10 custom_border">
                <br />
                <form method="post">
                    <div class=" full_width_md">
                        <?php
                        if ($showSuccess){
                            echo '<p class="alert alert-success mb-4">'.translate('group_success').'</p>';
                        }
                        ?>

                        <input class="mt-2 form-control" type="text" name="edit_group_name" placeholder="Gruppenname" value="<?= $groupData['name'] ?>"/>
                        <br />
                        <select class="form-select" name="edit_groupMailfrom" required>
                            <?php
                            echo '<option value="'.$mailActive['id'].'">'.$mailActive['mail'].'</option>';

                            foreach ($mails as $key => $value){
                                if ($value['id'] != $mailActive['id']){
                                    echo '<option value="'.$value['id'].'">'.$value['mail'].'</option>';
                                }
                            }
                            ?>
                        </select>
                        <br />
                        <button style="background-color: <?= $button_color ?>" type="submit" name="edit_group" value="1" class="btn btn-primary w-100 mt-2 float-start mb-4"><?= translate('save_changes')?></button>

                        <input type="hidden" name="group" value="<?= $_POST['group'] ?>">
                    </div>
                </form>

            <?php
            $sql = 'SELECT * FROM persons WHERE id_groups=:id_groups ORDER BY lastname ASC';
            $params = [
                ':id_groups' => $_POST['group']
            ];
            $persons = DB::fromDatabase($sql,'@raw',$params);

            if (!empty($persons)){
                ?>
                <table class="col-lg-12">
                    <thead>
                    <tr>
                        <th><?= translate('lastname')?></th>
                        <th><?= translate('firstname')?></th>
                        <th><?= translate('email')?></th>
                        <th><?= translate('language')?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($persons as $key => $value){
                        echo '<tr>';
                        echo '<td>'.$value['lastname'].'</td>';

                        echo '<td>'.$value['firstname'].'</td>';

                        echo '<td>'.$value['email'].'</td>';

                        $sql = 'SELECT `language` FROM languages WHERE id=:id';
                        $params = [
                            ':id' => $value['id_languages']
                        ];
                        $language = DB::fromDatabase($sql,'@line',$params);
                        echo '<td>'.$language['language'].'</td>';

                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
                <?php
            }else{
                echo '<p class="mt-4">Diese Gruppe enthält keine Teilnehmer!</p>';
            }
            ?>
            <div class="col-lg-1"></div>

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
