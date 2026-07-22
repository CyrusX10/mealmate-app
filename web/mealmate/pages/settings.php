<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /mealmate/auth/login.php');
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
        $success = '2FA setting updated.';
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

<h1>Settings</h1>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Profile</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
</div>

<div class="card">
    <h2>Two-Factor Authentication</h2>
    <form method="POST" action="">
        <div class="setting-row">
            <span>Enable 2FA</span>
            <label class="toggle">
                <input type="hidden" name="two_fa_enabled" value="0">
                <input type="checkbox" name="two_fa_enabled" value="1" <?= $user['two_fa_enabled'] ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>
        <button type="submit" name="toggle_2fa" class="btn btn-primary">Save 2FA Setting</button>
    </form>
</div>

<div class="card">
    <h2>Listing Visibility</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="listing_visibility">Who can see your donation listings?</label>
            <select id="listing_visibility" name="listing_visibility">
                <option value="public" <?= $user['listing_visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $user['listing_visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
            </select>
        </div>
        <button type="submit" name="update_visibility" class="btn btn-primary">Save Visibility</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
