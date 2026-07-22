<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /mealmate/auth/login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

// Mark individual as read
if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['read'], $user_id]);
    header('Location: /mealmate/pages/notifications.php');
    exit;
}

// Mark all as read
if (isset($_GET['read_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header('Location: /mealmate/pages/notifications.php');
    exit;
}

// Delete notification
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    header('Location: /mealmate/pages/notifications.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

require_once '../includes/navbar.php';
?>

<h1>Notifications</h1>

<div class="action-bar">
    <span><?= $unread_count ?> unread notification<?= $unread_count !== 1 ? 's' : '' ?></span>
    <?php if ($unread_count > 0): ?>
        <a href="?read_all=1" class="btn btn-primary">Mark All as Read</a>
    <?php endif; ?>
</div>

<?php if (count($notifications) > 0): ?>
    <ul class="notif-list">
        <?php foreach ($notifications as $notif):
            $message = $notif['message'];
            // Strip item ID prefix from expiry notifications
            if ($notif['type'] === 'expiry' && strpos($message, ':') !== false) {
                $message = substr($message, strpos($message, ':') + 1);
            }
        ?>
            <li class="notif-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
                <div>
                    <span class="notif-type" style="text-transform:capitalize;font-weight:600;color:var(--primary);">[<?= htmlspecialchars($notif['type']) ?>]</span>
                    <?= htmlspecialchars($message) ?>
                    <br><span class="notif-time"><?= htmlspecialchars($notif['created_at']) ?></span>
                </div>
                <div class="btn-group">
                    <?php if (!$notif['is_read']): ?>
                        <a href="?read=<?= $notif['id'] ?>" class="btn btn-sm btn-accent">Read</a>
                    <?php endif; ?>
                    <a href="?delete=<?= $notif['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this notification?')">Delete</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="card">
        <p>No notifications yet.</p>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
