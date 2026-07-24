<?php
$page_title = 'Log in';
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/verification.php';

$error = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Keep the login flow simple and predictable for classroom demonstrations.
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $old_email = $email;

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, password, account_status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['account_status'] !== 'active') {
            // Registered but never finished email verification.
            $result = issue_verification_code($pdo, (int) $user['id'], $email, $user['full_name']);
            $_SESSION['pending_user_id'] = (int) $user['id'];
            $_SESSION['pending_user_email'] = $email;
            $_SESSION['pending_user_name'] = $user['full_name'];
            $_SESSION['last_code_sent'] = time();
            if (!$result['delivered']) {
                $_SESSION['demo_code'] = $result['code'];
            }
            header('Location: ' . BASE_URL . '/auth/verify.php');
            exit;
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-split">
        <?php require '../includes/auth-panel.php'; ?>

        <div class="auth-form-panel">
            <div class="auth-form-card">
                <h2>Welcome back</h2>
                <p class="auth-subtitle">Log in to pick up where you left off.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="form auth-form" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old_email) ?>" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-action">
                            <input type="password" id="password" name="password" autocomplete="current-password" required>
                            <button type="button" class="toggle-visibility" data-target="password" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Log in</button>
                </form>

                <p class="form-footer">Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Create one</a></p>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
