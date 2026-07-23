<?php
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetchColumn();
} else {
    $unread_count = 0;
}

$current_page = basename($_SERVER['PHP_SELF']);
function nav_active($page) {
    global $current_page;
    return $current_page === $page ? ' class="active"' : '';
}
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= BASE_URL ?>/index.php" class="nav-logo"><i class="fa-solid fa-leaf"></i> MealMate</a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
        <div class="nav-links" id="navLinks">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>/pages/dashboard.php"<?= nav_active('dashboard.php') ?>><i class="fa-solid fa-gauge"></i> Dashboard</a>
                <a href="<?= BASE_URL ?>/pages/inventory.php"<?= nav_active('inventory.php') ?>><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
                <a href="<?= BASE_URL ?>/pages/browse.php"<?= nav_active('browse.php') ?>><i class="fa-solid fa-magnifying-glass"></i> Browse</a>
                <a href="<?= BASE_URL ?>/pages/meal-planner.php"<?= nav_active('meal-planner.php') ?>><i class="fa-solid fa-utensils"></i> Meal Planner</a>
                <a href="<?= BASE_URL ?>/pages/analytics.php"<?= nav_active('analytics.php') ?>><i class="fa-solid fa-chart-simple"></i> Analytics</a>
                <a href="<?= BASE_URL ?>/pages/notifications.php" class="nav-notif"<?= nav_active('notifications.php') ?>>
                    <i class="fa-solid fa-bell"></i> Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= BASE_URL ?>/pages/settings.php"<?= nav_active('settings.php') ?>><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="<?= BASE_URL ?>/auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="nav-cta">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container">
