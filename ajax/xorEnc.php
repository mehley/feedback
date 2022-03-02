<?php
session_start();
require_once('./../inc/includes.php');
$encrypted = SslCrypt::encrypt($_POST['toCrypt']);

echo $encrypted;