<?php
$page_title = 'Notifications';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

function notif_icon(string $type): string {
    $icons = [
        'expiry'   => 'fa-clock',
        'donation' => 'fa-hand-holding-heart',
        'meal'     => 'fa-utensils',
        'account'  => 'fa-user-check',
    ];
    return $icons[$type] ?? 'fa-bell';
}

// Mark individual as read
if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['read'], $user_id]);
    header('Location: ' . BASE_URL . '/pages/notifications.php');
    exit;
}

// Mark all as read
if (isset($_GET['read_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header('Location: ' . BASE_URL . '/pages/notifications.php?msg=read_all');
    exit;
}

// Delete notification
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    header('Location: ' . BASE_URL . '/pages/notifications.php?msg=deleted');
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

<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-bell"></i> Notifications</h1>
        <p class="page-subtitle"><?= $unread_count ?> unread notification<?= $unread_count !== 1 ? 's' : '' ?></p>
    </div>
    <?php if ($unread_count > 0): ?>
        <a href="?read_all=1" class="btn btn-primary"><i class="fa-solid fa-check-double"></i> Mark All as Read</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success alert-toast"><i class="fa-solid fa-trash"></i> Notification deleted.</div>
    <?php elseif ($_GET['msg'] === 'read_all'): ?>
        <div class="alert alert-success alert-toast"><i class="fa-solid fa-check-double"></i> All notifications marked as read.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if (count($notifications) > 0): ?>
    <ul class="notif-list">
        <?php foreach ($notifications as $notif): ?>
            <li class="notif-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
                <div class="notif-body">
                    <span class="notif-icon"><i class="fa-solid <?= notif_icon($notif['type']) ?>"></i></span>
                    <div>
                        <span class="notif-type-label"><?= htmlspecialchars($notif['type']) ?></span>
                        &middot; <?= htmlspecialchars($notif['message']) ?>
                        <br><span class="notif-time"><?= htmlspecialchars($notif['created_at']) ?></span>
                    </div>
                </div>
                <div class="btn-group">
                    <?php if (!$notif['is_read']): ?>
                        <a href="?read=<?= $notif['id'] ?>" class="icon-btn" title="Mark as read"><i class="fa-solid fa-check"></i></a>
                    <?php endif; ?>
                    <a href="?delete=<?= $notif['id'] ?>" class="icon-btn icon-btn-danger" title="Delete"
                       data-confirm-title="Delete this notification?"
                       data-confirm-message="This can't be undone."
                       data-confirm-icon="fa-trash" data-confirm-variant="danger" data-confirm-label="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="card empty-state">
        <div class="empty-state-icon"><i class="fa-solid fa-bell-slash"></i></div>
        <h3>You're all caught up</h3>
        <p>No notifications yet — we'll let you know about expiring items and donation activity here.</p>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
