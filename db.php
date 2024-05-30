<?php
$host_name = 'db5015881706.hosting-data.io';
$database = 'dbs12946570';
$user_name = 'dbu4621722';
$password = 'Barripper1998';

$mysqli = new mysqli($host_name, $user_name, $password, $database);

if ($mysqli->connect_error) {
    die('<p>Failed to connect to MySQL: ' . $mysqli->connect_error . '</p>');
}
?>
