<?php
require_once('./../inc/first.php');
?>

<body>

<div class="verlauf p-3">
    <img class="header_img rounded mx-auto d-block mb-3 mt-3" src="./../img/keyvisual.jpg">
</div>
<?php
// Language handling
$error = false;
$showLoginForm = true;
$showMenu = false;

if (!empty($_POST)){
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
                        <input class="login form-control-lg" type="text" name="username" placeholder="<?= translate('username')?>"/>
                        <br />
                        <input class="login form-control-lg" type="password" name="password" placeholder="<?= translate('password')?>"/>
                        <br />
                        <button style="background-color: <?= $button_color ?>" type="submit" class="btn btn-primary w-100 mt-4">Login</button>
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
    $sql = 'SELECT * FROM users WHERE id=:id';
    $params = [
        ':id' => $_SESSION['user_id']
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);

    ?>
    <div class="container">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4 text-center">
                <?php
                if ($userData['backend_mail'] == 1){
                    echo '<a style="background-color: '.$button_color.'" href="./backend_mail.php" class="btn btn-primary w-100 mt-4">'.translate('mail_dispatches').'</a>';
                }

                ?>
                <a style="background-color: <?= $button_color ?>" href="./groups.php" class="btn btn-primary w-100 mt-4"><?= translate('groups')?></a>
                <a style="background-color: <?= $button_color ?>" href="./form_generator.php" class="btn btn-primary w-100 mt-4"><?= translate('forms')?></a>
                <a style="background-color: <?= $button_color ?>" href="./reports.php" class="btn btn-primary w-100 mt-4"><?= translate('report')?></a>
                <a style="background-color: <?= $button_color ?>" href="./mail_template.php" class="btn btn-primary w-100 mt-4"><?= translate('mail_content')?></a>
                <a style="background-color: <?= $button_color ?>" href="./settings.php" class="btn btn-primary w-100 mt-4"><?= translate('settings')?></a>
                <a style="background-color: <?= $button_color ?>" href="./logout.php" class="btn btn-primary w-100 mt-4"><?= translate('logout')?></a>
            </div>
            <div class="col-lg-4"></div>
        </div>
    </div>
    <?php
}

require_once('./../inc/footer.php');
?>