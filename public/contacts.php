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

                </div>
            </div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <?php
}

if (!empty($_FILES)){
    $keyvisual = false;
    $headerimage = false;

    if (!empty($_FILES['file_key_visual']) && $_FILES['file_key_visual']['size'] > 0) {
        $uploaddir = './../img/';
        $uploadfile = $uploaddir . 'keyvisual.jpg';

        if (move_uploaded_file($_FILES['file_key_visual']['tmp_name'], $uploadfile)) {
            $keyvisual = true;
        } else {
            echo '<p class="alert m-auto text-center">Upload des Key Visuals fehlgeschlagen!</p>';
        }
    }

    if (!empty($_FILES['file_header_image']) && $_FILES['file_header_image']['size'] > 0) {
        $uploaddir = './../img/';
        $uploadfile = $uploaddir . 'header_image.jpg';

        if (move_uploaded_file($_FILES['file_header_image']['tmp_name'], $uploadfile)) {
            $headerimage = true;
        } else {
            echo '<p class="alert m-auto text-center">Upload der Hintergrundgrafik fehlgeschlagen!</p>';
        }
    }
}
require_once('./../inc/footer.php')
?>
