<?php
$page_title = 'Dashboard';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/navbar.php';

$user_id = $_SESSION['user_id'];

// Generate expiry notifications — one per item per day. Previously this
// tried to recover the item ID by parsing it back out of the message text
// with SUBSTRING_INDEX(message, ':', 1), which silently breaks for any
// message that doesn't start with a plain numeric ID. Using a real
// related_item_id column instead is both simpler and correct.
$stmt = $pdo->prepare("
    SELECT id, item_name FROM food_items
    WHERE user_id = ? AND status = 'available'
      AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
      AND id NOT IN (
          SELECT related_item_id FROM notifications
          WHERE user_id = ? AND type = 'expiry' AND related_item_id IS NOT NULL
            AND DATE(created_at) = CURDATE()
      )
");
$stmt->execute([$user_id, $user_id]);
$expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);
// A brief note for each expiring item keeps the dashboard feedback easy to follow.
foreach ($expiring as $item) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, related_item_id) VALUES (?, 'expiry', ?, ?)");
    $stmt->execute([$user_id, $item['item_name'] . ' is expiring soon!', $item['id']]);
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

// Only currently-available items — consumed/donated items have already
// "vanished" from view (they're still tracked historically via Analytics).
$stmt = $pdo->prepare("SELECT * FROM food_items WHERE user_id = ? AND status = 'available' ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="welcome">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
    <p>Here's your food waste reduction summary.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
        <div>
            <h3><?= $total_items ?></h3>
            <p>Items in Inventory</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-clock"></i></span>
        <div>
            <h3><?= $expiring_soon ?></h3>
            <p>Expiring Soon</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-leaf"></i></span>
        <div>
            <h3><?= $total_saved ?></h3>
            <p>Total Saved from Waste</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
        <div>
            <h3><?= $total_donated ?></h3>
            <p>Donations Made</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-utensils"></i></span>
        <div>
            <h3><?= $total_consumed ?></h3>
            <p>Items Consumed</p>
        </div>
    </div>
</div>

<div class="action-bar">
    <h2>Recent Items</h2>
    <a href="<?= BASE_URL ?>/pages/inventory.php" class="btn btn-accent"><i class="fa-solid fa-boxes-stacked"></i> Manage Inventory</a>
</div>

<?php if (count($recent_items) > 0): ?>
    <div class="card-grid">
        <?php foreach ($recent_items as $item):
            $days_left = (int) floor((strtotime($item['expiry_date']) - time()) / 86400);
            $badge = expiry_badge($days_left);
            $qty_display = rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.');
        ?>
            <div class="card <?= $badge['class'] ?>">
                <div class="item-card-header">
                    <span class="item-icon"><i class="fa-solid <?= category_icon($item['category']) ?>"></i></span>
                    <div>
                        <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                        <span class="item-category"><?= ucfirst($item['category']) ?></span>
                    </div>
                </div>
                <div class="item-pills">
                    <span class="pill"><i class="fa-solid fa-scale-balanced"></i> <?= htmlspecialchars($qty_display) ?> <?= htmlspecialchars($item['unit']) ?></span>
                    <span class="pill"><i class="fa-solid <?= storage_icon($item['storage_location']) ?>"></i> <?= ucfirst($item['storage_location']) ?></span>
                    <span class="pill pill-<?= $badge['class'] ?>"><i class="fa-solid <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card empty-state">
        <div class="empty-state-icon"><i class="fa-solid fa-basket-shopping"></i></div>
        <h3>No items yet</h3>
        <p>Add your first food item to start tracking what's in your kitchen.</p>
        <a href="<?= BASE_URL ?>/pages/inventory.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add your first item</a>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
