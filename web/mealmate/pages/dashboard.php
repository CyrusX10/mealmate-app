<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /mealmate/auth/login.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/navbar.php';

$user_id = $_SESSION['user_id'];

// Generate expiry notifications
$stmt = $pdo->prepare("SELECT id, item_name FROM food_items WHERE user_id = ? AND status = 'available' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND id NOT IN (SELECT SUBSTRING_INDEX(message, ':', 1) FROM notifications WHERE user_id = ? AND type = 'expiry' AND DATE(created_at) = CURDATE())");
$stmt->execute([$user_id, $user_id]);
$expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($expiring as $item) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'expiry', ?)");
    $stmt->execute([$user_id, $item['id'] . ':' . $item['item_name'] . ' is expiring soon!']);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE user_id = ? AND status = 'available'");
$stmt->execute([$user_id]);
$total_items = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE user_id = ? AND status = 'available' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
$stmt->execute([$user_id]);
$expiring_soon = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_saved_log WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_saved = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_saved_log WHERE user_id = ? AND action = 'donated'");
$stmt->execute([$user_id]);
$total_donated = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_saved_log WHERE user_id = ? AND action = 'consumed'");
$stmt->execute([$user_id]);
$total_consumed = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM food_items WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="welcome">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
    <p>Here's your food waste reduction summary.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?= $total_items ?></h3>
        <p>Items in Inventory</p>
    </div>
    <div class="stat-card">
        <h3><?= $expiring_soon ?></h3>
        <p>Expiring Soon</p>
    </div>
    <div class="stat-card">
        <h3><?= $total_saved ?></h3>
        <p>Total Saved from Waste</p>
    </div>
    <div class="stat-card">
        <h3><?= $total_donated ?></h3>
        <p>Donations Made</p>
    </div>
    <div class="stat-card">
        <h3><?= $total_consumed ?></h3>
        <p>Items Consumed</p>
    </div>
</div>

<div class="action-bar">
    <h2>Recent Items</h2>
    <a href="/mealmate/pages/inventory.php" class="btn btn-accent">Manage Inventory</a>
</div>

<?php if (count($recent_items) > 0): ?>
    <div class="card-grid">
        <?php foreach ($recent_items as $item):
            $days_left = floor((strtotime($item['expiry_date']) - time()) / 86400);
            $expiry_class = 'expiry-safe';
            if ($days_left < 0) $expiry_class = 'expiry-danger';
            elseif ($days_left <= 3) $expiry_class = 'expiry-warning';
        ?>
            <div class="card <?= $expiry_class ?>">
                <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                <p>Category: <?= ucfirst($item['category']) ?></p>
                <p>Qty: <?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?></p>
                <p>Expires: <?= htmlspecialchars($item['expiry_date']) ?>
                    <?php if ($days_left < 0): ?>
                        <span style="color:var(--expiry-danger);font-weight:bold;">(Expired)</span>
                    <?php elseif ($days_left == 0): ?>
                        <span style="color:var(--expiry-warning);font-weight:bold;">(Today)</span>
                    <?php else: ?>
                        <span>(<?= $days_left ?> days left)</span>
                    <?php endif; ?>
                </p>
                <p>Storage: <?= ucfirst($item['storage_location']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <p>No items in your inventory yet. <a href="/mealmate/pages/inventory.php">Add your first item</a>.</p>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
