<?php
$page_title = 'Register';
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/verification.php';

$error = '';
$old = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration keeps its validation feedback clear and user-friendly.
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $old = ['full_name' => $full_name, 'email' => $email];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password must include at least one letter and one number.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Alternative Course 3a from the UC1 spec: email already registered.
            $error = 'An account with this email already exists. Try logging in instead.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, account_status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$full_name, $email, $hashed]);
            $new_user_id = (int) $pdo->lastInsertId();

            $result = issue_verification_code($pdo, $new_user_id, $email, $full_name);

            // Not logged in yet — account is pending until the code is verified.
            $_SESSION['pending_user_id'] = $new_user_id;
            $_SESSION['pending_user_email'] = $email;
            $_SESSION['pending_user_name'] = $full_name;
            if (!$result['delivered']) {
                // No mail server available in this environment — show the
                // code directly so the flow can still be demonstrated.
                $_SESSION['demo_code'] = $result['code'];
            }

            header('Location: ' . BASE_URL . '/auth/verify.php');
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
                <h2>Create your account</h2>
                <p class="auth-subtitle">Takes less than a minute — no credit card, just a fridge worth saving.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="form auth-form" id="registerForm" novalidate>
                    <div class="form-group">
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>" autocomplete="name" placeholder="Jane Doe" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" autocomplete="email" placeholder="you@example.com" required>
                        <small class="field-hint" id="emailHint"></small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-action">
                            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters" required>
                            <button type="button" class="toggle-visibility" data-target="password" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength" aria-hidden="true">
                            <div class="password-strength-bar"><span></span></div>
                            <small class="password-strength-label">At least 8 characters, with a letter and a number</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <div class="input-with-action">
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" placeholder="Re-enter your password" required>
                            <button type="button" class="toggle-visibility" data-target="confirm_password" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="field-hint" id="matchHint"></small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Create account</button>
                </form>

                <p class="form-footer">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a></p>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
