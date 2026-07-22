<?php
require_once '../includes/header.php';

$_SESSION = array();
session_destroy();
header('Location: /mealmate/auth/login.php');
exit;
