<?php
require_once 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /mealmate/pages/dashboard.php');
    exit;
} else {
    header('Location: /mealmate/auth/login.php');
    exit;
}
