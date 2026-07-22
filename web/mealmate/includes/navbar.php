<?php
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetchColumn();
} else {
    $unread_count = 0;
}
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="/mealmate/index.php" class="nav-logo">MealMate</a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
        <div class="nav-links" id="navLinks">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/mealmate/pages/dashboard.php">Dashboard</a>
                <a href="/mealmate/pages/inventory.php">Inventory</a>
                <a href="/mealmate/pages/browse.php">Browse</a>
                <a href="/mealmate/pages/meal-planner.php">Meal Planner</a>
                <a href="/mealmate/pages/analytics.php">Analytics</a>
                <a href="/mealmate/pages/notifications.php" class="nav-notif">
                    Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="/mealmate/pages/settings.php">Settings</a>
                <a href="/mealmate/auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="/mealmate/auth/login.php">Login</a>
                <a href="/mealmate/auth/register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container">
