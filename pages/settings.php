<?php
$page_title = 'Settings';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_2fa'])) {
        $two_fa = isset($_POST['two_fa_enabled']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET two_fa_enabled = ? WHERE id = ?");
        $stmt->execute([$two_fa, $user_id]);
        $success = 'Two-factor authentication setting updated.';
    }

    if (isset($_POST['update_visibility'])) {
        $visibility = $_POST['listing_visibility'];
        $stmt = $pdo->prepare("UPDATE users SET listing_visibility = ? WHERE id = ?");
        $stmt->execute([$visibility, $user_id]);
        $success = 'Listing visibility updated.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../includes/navbar.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-gear"></i> Settings</h1>
        <p class="page-subtitle">Manage your account, security, and privacy preferences.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-toast"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error alert-toast"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><i class="fa-solid fa-id-card"></i> Profile</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p>
        <strong>Account status:</strong>
        <?php if ($user['account_status'] === 'active'): ?>
            <span class="status-pill status-pill-active"><i class="fa-solid fa-circle-check"></i> Verified</span>
        <?php else: ?>
            <span class="status-pill status-pill-pending"><i class="fa-solid fa-triangle-exclamation"></i> Pending verification</span>
        <?php endif; ?>
    </p>
</div>

<div class="card">
    <h2><i class="fa-solid fa-shield-halved"></i> Two-Factor Authentication</h2>
    <p class="card-hint">Adds a verification step at login for extra account security.</p>
    <form method="POST" action="">
        <div class="setting-row">
            <span>Enable 2FA</span>
            <label class="toggle">
                <input type="hidden" name="two_fa_enabled" value="0">
                <input type="checkbox" name="two_fa_enabled" value="1" <?= $user['two_fa_enabled'] ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>
        <button type="submit" name="toggle_2fa" class="btn btn-primary">Save 2FA setting</button>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-eye"></i> Listing Visibility</h2>
    <p class="card-hint">Controls who can see the food items you list for donation.</p>
    <form method="POST" action="">
        <div class="form-group">
            <label for="listing_visibility">Who can see your donation listings?</label>
            <select id="listing_visibility" name="listing_visibility">
                <option value="public" <?= $user['listing_visibility'] === 'public' ? 'selected' : '' ?>>Public — visible to everyone browsing donations</option>
                <option value="private" <?= $user['listing_visibility'] === 'private' ? 'selected' : '' ?>>Private — hidden from Browse Food Items</option>
            </select>
        </div>
        <button type="submit" name="update_visibility" class="btn btn-primary">Save visibility</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
