<?php
/**
 * Email verification helpers for Use Case 1 (Register User & Privacy Settings).
 *
 * Local dev environments (XAMPP) rarely have an SMTP server configured, so
 * mail() will usually fail silently or throw a warning. We attempt a real
 * send first; if it's not available, we fall back to displaying the code on
 * screen so the flow can still be demonstrated end-to-end. This mirrors how
 * many staging environments handle email during development.
 */

const VERIFICATION_CODE_TTL_MINUTES = 10;
const VERIFICATION_MAX_ATTEMPTS = 5;

/**
 * Creates a fresh 6-digit code for a user, invalidating any previous code,
 * and attempts to email it. Returns an array describing what happened so
 * the calling page can decide what to show the user.
 */
function issue_verification_code(PDO $pdo, int $user_id, string $email, string $full_name): array {
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', time() + VERIFICATION_CODE_TTL_MINUTES * 60);

    // Only one active code per user at a time.
    $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user_id]);

    $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $code, $expires_at]);

    $delivered = attempt_send_verification_email($email, $full_name, $code);

    return [
        'delivered' => $delivered,
        'code'      => $code, // only surfaced to the UI when $delivered is false
        'ttl'       => VERIFICATION_CODE_TTL_MINUTES,
    ];
}

function attempt_send_verification_email(string $email, string $full_name, string $code): bool {
    $subject = 'Your MealMate verification code';
    $body =
        "Hi {$full_name},\r\n\r\n" .
        "Your MealMate verification code is: {$code}\r\n" .
        "This code expires in " . VERIFICATION_CODE_TTL_MINUTES . " minutes.\r\n\r\n" .
        "If you didn't request this, you can ignore this email.\r\n";
    $headers = "From: MealMate <no-reply@mealmate.local>\r\n" .
               "Content-Type: text/plain; charset=UTF-8";

    // Suppress the native warning XAMPP throws when no SMTP relay is set up;
    // we handle the failure explicitly via the return value instead.
    return @mail($email, $subject, $body, $headers);
}
