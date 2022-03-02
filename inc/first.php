<?php

session_start();
error_reporting(E_ALL);
ini_set("display_errors","On");

require_once('./../inc/includes.php');

$sql = "SELECT value FROM globals WHERE identifier = :identifier";
$sql_params = [
    ':identifier' => 'color'
];
$button_color = DB::fromDatabase($sql, '@simple', $sql_params);
if (empty($_SESSION['language'])){
    $_SESSION['language'] = 'de';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="../css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/jquery-ui.min.css">

</head>
<header>
    <div class="language_selector"><button class="lan_de language_btn language_de">DE</button>   <button class="lan_en language_btn language_en">EN</button></div>
</header>