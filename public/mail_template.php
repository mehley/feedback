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

if (!empty($_POST['save_form'])){
    $errorDuplicate = false;
    if (!empty($_POST['selectExistingMailTemplate'])){
        $id = $_POST['selectExistingMailTemplate'];
    }else{
        $id = 0;
    }
    $sql = 'SELECT id FROM mail_templates WHERE `name` = :name AND id!=:id';
    $sql_params = [
        ':name' => $_POST['template_name'],
        ':id' => $id
    ];
    $existingName = DB::fromDatabase($sql, '@simple', $sql_params);

    if (empty($existingName)){

        if(!empty($_POST['selectExistingMailTemplate'])){
            $id_mail_template = $_POST['selectExistingMailTemplate'];

            $sql = 'UPDATE mail_templates SET `name`=:name WHERE id=:id';
            $sql_params = [
                ':name' => $_POST['template_name'],
                ':id' => $id_mail_template
            ];
            DB::toDatabase($sql, $sql_params);

            $sql = 'DELETE FROM mail_content WHERE id_mail_templates=:id_mail_templates';
            $sql_params = [
                ':id_mail_templates' => $id_mail_template
            ];
            DB::toDatabase($sql, $sql_params,false,true);
        }else{
            $sql = 'INSERT INTO mail_templates SET `name`=:name';
            $sql_params = [
                ':name' => $_POST['template_name']
            ];
            DB::toDatabase($sql, $sql_params);
            $id_mail_template = DB::lastInsertId();
        }

        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        foreach ($languages as $key => $value) {
            $sql = 'INSERT INTO mail_content SET `id_mail_templates`=:id_mail_templates, id_languages=:id_languages, mail_subject=:mail_subject, mail_content=:mail_content';
            $sql_params = [
                ':id_mail_templates' => $id_mail_template,
                ':id_languages' => $value['id'],
                ':mail_subject' => $_POST['mail_subject'][$value['language_code']],
                ':mail_content' => $_POST['mail_content'][$value['language_code']],
            ];
            DB::toDatabase($sql,$sql_params);
        }
    }else{
        $errorDuplicate = true;
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
                        <input class="login form-control-lg" type="text" name="<?= SslCrypt::encrypt('username') ?>" placeholder="<?= translate('username')?>"/>
                        <br />
                        <input class="login form-control-lg" type="password" name="<?= SslCrypt::encrypt('password') ?>" placeholder="<?= translate('password')?>"/>
                        <br />
                        <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-4" name="<?= SslCrypt::encrypt('login') ?>">Login</button>
                    </form>
                    <?php
                    if ($error){
                        echo '<p class="alert alert-danger mt-3">'. translate('login_error').'</p>';
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
            <div class="col-lg-1"></div>
            <div class="col-lg-10 text-center custom_border">
                <div class="col-lg-12">
                    <a href="./backend.php"><span class="float-end"><i class="fas fa-backward"></i> <?= translate('back') ?></span></a></br>
                    <h1><?= translate('mail_content') ?></h1>
                    <br>
                    <form method="post" id="form_mail_template">
                        <?php
                        if ($errorDuplicate){
                            echo '<p class="alert-danger">'.translate('errorDuplicateEntry').'</p>';
                        }
                        ?>
                        <div class="mail_content_select_form">
                            <select class="form-select" id="selectExistingMailTemplate" name="<?= SslCrypt::encrypt('selectExistingMailTemplate')?>">
                                <option value="" selected disabled><?= translate('selectExistingMailTemplate') ?></option>
                                <?php
                                $sql = 'SELECT * FROM mail_templates ORDER BY name ASC';
                                $templates = DB::fromDatabase($sql,'@raw');
                                foreach ($templates as $key => $value){
                                    echo '<option value="'.$value['id'].'">'. $value['name'] .'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <hr />
                        <div class="form_mail_template">
                        <input type="text" name="<?= SslCrypt::encrypt('template_name')?>" class="form-control" placeholder="<?= translate('name_mail_content')?>" />
                        <?php
                        $sql = 'SELECT * FROM languages WHERE active = 1';
                        $languages = DB::fromDatabase($sql,'@raw');
                        echo '<table class="mt-3 w-100">';
                        foreach ($languages as $key => $value){
                            echo '<tr>';
                            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
                            echo '<td><input name="'.SslCrypt::encrypt('mail_subject').'['.$value['language_code'].']" type="text" class="form-control-check mt-3 form-control" placeholder="'.translate('mailSubject').'"></td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo '<td class="w_48px"></td>';
                            echo '<td><textarea name="'.SslCrypt::encrypt('mail_content').'['.$value['language_code'].']" placeholder="'.translate('mail_content_textarea').'" class="form-control mt-1"></textarea></td>';
                            echo '</tr>';
                        }
                        echo '</table></div><hr />';
                        ?>

                            <div class="container">
                                <div class="row mt-2 mb-4">
                                    <div class="col-lg-9"><button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-4" name="<?= SslCrypt::encrypt('save_form')?>" value="1"><i class="far fa-save"></i> <?= translate('save_mail_template')?></button></div>
                                    <div class="col-lg-3"><button type="button" class="btn btn-danger w-100 mt-4 fg_delete_mail_content"><i class="far fa-trash-alt"></i> <?= translate('mail_template_delete')?></button></div>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-1"></div>
        </div>
    </div>
    <?php
}
require_once('./../inc/footer.php')
?>
