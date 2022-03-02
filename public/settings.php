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
$showMenu = false;

if (!empty($_POST) && array_key_exists('login',$_POST)){
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);

    $sql = 'SELECT * FROM users WHERE username=:username';
    $params = [
        ':username' => $username
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);

    if (!empty($userData) && $password === $userData['password']) {
        $_SESSION['user_id'] = $userData['id'];
        $showLoginForm = false;
        $showMenu = true;
    } else {
        $error = true;
    }
}

if ($showLoginForm){
    if (!empty($_SESSION['user_id']) && $_SESSION['user_id']>0){
        //show menu
        $showMenu = true;
    }else{
        //show login form
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
                        <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-4" name="login">Login</button>
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

if ($showMenu){
    ?>
    <div class="container">
        <div class="row mt-2 mb-4">
            <div class="col-lg-3"></div>
            <div class="col-lg-6 text-center custom_border">
                <div class="col-lg-12">
                    <a href="./backend.php"><span class="float-end"><i class="fas fa-backward"></i> zurück</span></a></br>
                    <form method="post" enctype="multipart/form-data">

                        <?php
                        $sql = "SELECT * FROM languages";
                        $languages = DB::fromDatabase($sql,'@raw');
                        if (!empty($languages)){
                            ?>
                            <label class="form-label fw-bold" for="file_key_visual">Verfügbare Sprachen bei Formularen</label>
                            <br>
                            <select class="js-example-basic-multiple form-select" name="<?=SslCrypt::encrypt('languages')?>[]" multiple="multiple">
                                <?php
                                foreach ($languages as $key => $value){
                                    echo '<option value="'.$value['id'].'" '.($value['active'] == 1 ? 'selected' : '').'>'.$value['language'].'</option>';
                                }
                                ?>
                            </select>
                            <br>
                            <?php
                        }
                        ?>
                        <label class="form-label fw-bold mt-3" for="button_color_picker">Farbe für Knöpfe</label>
                        <br>
                        <input type="color" id="button_color_picker" name="button_color" value="<?= $button_color?>">
                        <br>
                        <label class="form-label fw-bold mt-3" for="file_key_visual">Key Visual</label>
                        <input class="mt-1 form-control" type="file" id="file_key_visual" name="file_key_visual"/>
                        <br>
                        <label class="form-label fw-bold mt-4" for="file_header_image">Hintergrundgrafik</label>
                        <input class="mt-1 form-control" type="file" id="file_header_image" name="file_header_image"/>
                        <br>
                        <label class="form-label fw-bold mt-4" for="file_header_image">Mailadressen für den Versand</label>
                        <div class="mailsFromContainer">

                        <?php
                        getMailsFrom();
                        ?>
                        </div>
                        <br>
                        <button type="button" class="addElementBtn settingsAddMailfromBtn" title="Mailadresse hinzufügen"><i class="fas fa-plus"></i></button>

                        <input type="submit" style="background-color: <?=$button_color?>" class="btn btn-primary w-100 mt-4" value="Änderungen übernehmen" name="saveSettings">
                    </form>
                </div>
            </div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <?php
}

if (!empty($_POST) && array_key_exists('saveSettings',$_POST)){

    if (!empty($_POST['button_color'])){
        $sql = 'UPDATE globals SET value = :value WHERE identifier = :identifier';
        $sql_params = [
            ':value' => $_POST['button_color'],
            ':identifier' => 'color'
        ];
        DB::toDatabase($sql,$sql_params);
    }
    if (!empty($_POST['languages'])){
        $ids_languages = implode(',',$_POST['languages']);
        $sql = 'UPDATE languages SET active = 0 WHERE id NOT IN ('.$ids_languages.')';

        DB::toDatabase($sql);
        $sql = 'UPDATE languages SET active = 1 WHERE id IN ('.$ids_languages.')';

        DB::toDatabase($sql);
    }
    if (!empty($_POST['additionalMailFrom'])){
        foreach ($_POST['additionalMailFrom'] as $key => $value){
            $sql = "INSERT INTO mails_from SET mail=:mail";
            $sql_params = [
                ':mail' => $value
            ];
            DB::toDatabase($sql, $sql_params);
        }
    }

    $refresh = true;
}

if (!empty($_FILES)){
    $keyvisual = false;
    $headerimage = false;

    if (!empty($_FILES['file_key_visual']) && $_FILES['file_key_visual']['size'] > 0) {
        $uploaddir = './../img/';
        $uploadfile = $uploaddir . 'keyvisual.jpg';

        if (move_uploaded_file($_FILES['file_key_visual']['tmp_name'], $uploadfile)) {
            $keyvisual = true;
            $refresh = true;
        } else {
            echo '<p class="alert m-auto text-center">Upload des Key Visuals fehlgeschlagen!</p>';
        }
    }

    if (!empty($_FILES['file_header_image']) && $_FILES['file_header_image']['size'] > 0) {
        $uploaddir = './../img/';
        $uploadfile = $uploaddir . 'header_image.jpg';

        if (move_uploaded_file($_FILES['file_header_image']['tmp_name'], $uploadfile)) {
            $headerimage = true;
            $refresh = true;
        } else {
            echo '<p class="alert m-auto text-center">Upload der Hintergrundgrafik fehlgeschlagen!</p>';
        }
    }
}
if ($refresh){
    header('Refresh:0');
}
require_once('./../inc/footer.php')
?>

