<?php
require_once '../includes/header.php';

$_SESSION = array();
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
