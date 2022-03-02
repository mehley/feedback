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
                    <h1><?= translate('form_generator') ?></h1>
                    <br>
                    <form method="post" class="fg_form">
                        <div class="fg_select_form">
                            <select class="form-select" id="selectExistingForm" name="<?= SslCrypt::encrypt('selectExistingForm')?>">
                                <option value="" selected disabled><?= translate('selectExistingForm') ?></option>
                                <?php
                                $sql = 'SELECT * FROM forms ORDER BY name ASC';
                                $forms = DB::fromDatabase($sql,'@raw');
                                foreach ($forms as $key => $value){
                                    echo '<option value="'.$value['id'].'">'. $value['name'] .'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="formGenerator">
                            <input class="mt-4 form-control" type="text" name="<?= SslCrypt::encrypt('form_name')?>" placeholder="<?= translate('form_name') ?>" required/>
                            <ul id="sortable" class="fg_list">
                            </ul>
                        </div>
                    <br>
                    <div>
                        <button type="button" class="addFormElement mt-2 addElementBtn" title="<?= translate('fg_add_element') ?>"><i class="fas fa-plus"></i></button>
                        <br>
                        <div class="container">
                            <div class="row mt-2 mb-4">
                                <div class="col-lg-9"><button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-4" name="<?= SslCrypt::encrypt('save_form')?>" value="1"><i class="far fa-save"></i> <?= translate('fg_save')?></button></div>
                                <div class="col-lg-3"><button type="button" class="btn btn-danger w-100 mt-4 fg_delete_form"><i class="far fa-trash-alt"></i> <?= translate('fg_delete')?></button></div>
                            </div>
                        </div>
                    </div>
                        <input type="hidden" name="<?= SslCrypt::encrypt('action')?>" value="submitForm">
                    </form>
                    <input type="hidden" id="elementCounter" value="1">
                </div>
            </div>
            <div class="col-lg-1"></div>
        </div>
    </div>
    <?php
}
require_once('./../inc/footer.php')
?>
