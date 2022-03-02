<?php
// Increase time limit
set_time_limit(600);

// Increase memory limit
ini_set('memory_limit', '1024M');

$file_path = './../temp/auswertung.xlsx';

header('Content-type: application/vnd.ms-excel');
//open/save dialog box
header('Content-Disposition: attachment; filename=auswertung_'.$_GET['name'].'.xlsx');
//read from server and write to buffer
readfile($file_path);

unlink($file_path);