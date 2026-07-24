<?php
$page_title = 'Verify Your Email';
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

if (!isset($_SESSION['pending_user_id'])) {
    // Nobody mid-registration — nothing to verify.
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/verification.php';

$user_id = (int) $_SESSION['pending_user_id'];
$email = $_SESSION['pending_user_email'];
$full_name = $_SESSION['pending_user_name'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    // Light cooldown so the resend button can't be hammered.
    $last_sent = $_SESSION['last_code_sent'] ?? 0;
    if (time() - $last_sent < 20) {
        $error = 'Please wait a few seconds before requesting another code.';
    } else {
        $result = issue_verification_code($pdo, $user_id, $email, $full_name);
        $_SESSION['last_code_sent'] = time();
        if (!$result['delivered']) {
            $_SESSION['demo_code'] = $result['code'];
        } else {
            unset($_SESSION['demo_code']);
        }
        $success = 'A new verification code has been sent to ' . htmlspecialchars($email) . '.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $entered = trim($_POST['code'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM email_verifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        // Line 6 (expired/invalid): no active code — force a resend.
        $error = 'Your verification code has expired. Request a new one below.';
    } elseif ($record['attempts'] >= VERIFICATION_MAX_ATTEMPTS) {
        $error = 'Too many incorrect attempts. Request a new code below.';
    } elseif (strtotime($record['expires_at']) < time()) {
        $error = 'That code has expired. Request a new one below.';
    } elseif (!ctype_digit($entered) || strlen($entered) !== 6) {
        $error = 'Enter the 6-digit code exactly as sent.';
    } elseif (!hash_equals($record['code'], $entered)) {
        $pdo->prepare("UPDATE email_verifications SET attempts = attempts + 1 WHERE id = ?")->execute([$record['id']]);
        $remaining = VERIFICATION_MAX_ATTEMPTS - ($record['attempts'] + 1);
        $error = $remaining > 0
            ? "Incorrect code. {$remaining} attempt(s) remaining."
            : 'Incorrect code. Request a new one below.';
    } else {
        // Success — activate the account and log the user in.
        $pdo->prepare("UPDATE users SET account_status = 'active' WHERE id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user_id]);

        $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'account', 'Welcome to MealMate! Your account is now active.')")
            ->execute([$user_id]);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $full_name;
        unset($_SESSION['pending_user_id'], $_SESSION['pending_user_email'], $_SESSION['pending_user_name'], $_SESSION['demo_code'], $_SESSION['last_code_sent']);

        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

$masked_email = preg_replace('/^(.).*(@.*)$/', '$1***$2', $email);
?>

<div class="auth-page">
    <div class="auth-split">
        <?php require '../includes/auth-panel.php'; ?>

        <div class="auth-form-panel">
            <div class="auth-form-card">
                <h2>Check your inbox</h2>
                <p class="auth-subtitle">We sent a 6-digit code to <strong><?= htmlspecialchars($masked_email) ?></strong>. It expires in <?= VERIFICATION_CODE_TTL_MINUTES ?> minutes.</p>

                <?php if (!empty($_SESSION['demo_code'])): ?>
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        No mail server is configured in this environment, so here's your code for demo purposes:
                        <strong class="demo-code"><?= htmlspecialchars($_SESSION['demo_code']) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="form auth-form" id="verifyForm" novalidate>
                    <div class="form-group">
                        <label for="code">Verification code</label>
                        <input type="text" id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required>
                    </div>
                    <button type="submit" name="verify_code" class="btn btn-primary btn-block">Verify and continue</button>
                </form>

                <form method="POST" action="" class="resend-form">
                    <button type="submit" name="resend" class="btn-link">Didn't get a code? Resend it</button>
                </form>

                <p class="form-footer"><a href="<?= BASE_URL ?>/auth/logout.php">Use a different email</a></p>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
