<?php
require_once('./../inc/first.php');
session_unset();
session_destroy();
header('Location: ./backend.php', true);