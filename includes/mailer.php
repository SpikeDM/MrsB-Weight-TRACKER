<?php
// ============================================================
// MrsB Tracker — Mailer Helper (PHPMailer + SMTP)
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

/**
 * Create a configured PHPMailer instance
 */
function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}

/**
 * Send welcome email to a new client
 */
function sendWelcomeEmail(array $client, string $trackerUrl): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($client['email'], $client['name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your MrsB Fitness Programme Tracker is Ready!';
        $mail->Body    = buildEmailWrapper('
            <p style="font-size:1.1rem;color:#4c1044;font-weight:700;">Hi ' . htmlspecialchars($client['name']) . '! 👋</p>
            <p style="color:#555;line-height:1.7;">Your personal MrsB Fitness Programme Tracker is ready! Use the link below each week to record your progress, class passwords, and measurements throughout your 6-week programme.</p>
            <div style="text-align:center;margin:2rem 0;">
              <a href="' . $trackerUrl . '" style="display:inline-block;background:#d60058;color:#fff;padding:0.85rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;">Set Up My Tracker 💪</a>
            </div>
            <p style="color:#555;line-height:1.7;">On your first visit you\'ll be asked to set a personal password to keep your data secure.</p>
            <p style="color:#555;line-height:1.7;">Good luck with your programme — I\'m rooting for you! 🌟</p>
            <p style="color:#4c1044;font-weight:700;margin-top:2rem;">MrsB Fitness 💪</p>
        ', '#d60058');
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('MrsB Mailer — welcome email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a password reset email to a client (optionally includes tracker link)
 */
function sendPasswordResetEmail(array $client, string $resetUrl, string $trackerUrl = ''): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($client['email'], $client['name']);
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your MrsB Fitness Tracker Password';

        $trackerSection = $trackerUrl ? '
            <div style="background:#f5f0f4;border-radius:8px;padding:1rem;margin-top:1.5rem;">
                <p style="color:#4c1044;font-weight:700;font-size:0.9rem;margin:0 0 0.5rem;">📎 Your Tracker Link</p>
                <p style="color:#555;font-size:0.85rem;margin:0 0 0.75rem;">Bookmark this link so you always have it handy:</p>
                <a href="' . $trackerUrl . '" style="display:inline-block;background:#4c1044;color:#fff;padding:0.6rem 1.25rem;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.9rem;">Go to My Tracker</a>
                <p style="color:#aaa;font-size:0.75rem;word-break:break-all;margin-top:0.75rem;">' . $trackerUrl . '</p>
            </div>' : '';

        $mail->Body = buildEmailWrapper('
            <p style="font-size:1.1rem;color:#4c1044;font-weight:700;">Hi ' . htmlspecialchars($client['name']) . ',</p>
            <p style="color:#555;line-height:1.7;">We received a request to reset your tracker password. Click the button below to set a new one.</p>
            <p style="color:#888;font-size:0.85rem;">This link expires in <strong>24 hours</strong>. If you didn\'t request this, you can safely ignore this email.</p>
            <div style="text-align:center;margin:2rem 0;">
              <a href="' . $resetUrl . '" style="display:inline-block;background:#d60058;color:#fff;padding:0.85rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;">Reset My Password</a>
            </div>
            <p style="color:#aaa;font-size:0.8rem;word-break:break-all;">Or copy this link:<br>' . $resetUrl . '</p>
            ' . $trackerSection . '
        ', '#d60058');
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('MrsB Mailer — client reset email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send admin password reset email
 */
function sendAdminResetEmail(array $admin, string $resetUrl): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($admin['email']);
        $mail->isHTML(true);
        $mail->Subject = 'MrsB Tracker — Admin Password Reset';
        $mail->Body    = buildEmailWrapper('
            <p style="font-size:1.1rem;color:#4c1044;font-weight:700;">Admin Password Reset</p>
            <p style="color:#555;line-height:1.7;">A password reset was requested for the admin account. Click below to set a new password.</p>
            <p style="color:#888;font-size:0.85rem;">This link expires in <strong>24 hours</strong>. If you didn\'t request this, please ignore this email.</p>
            <div style="text-align:center;margin:2rem 0;">
              <a href="' . $resetUrl . '" style="display:inline-block;background:#4c1044;color:#fff;padding:0.85rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;">Reset Admin Password</a>
            </div>
            <p style="color:#aaa;font-size:0.8rem;word-break:break-all;">Or copy this link:<br>' . $resetUrl . '</p>
        ', '#4c1044');
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('MrsB Mailer — admin reset email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Shared email HTML wrapper
 */
function buildEmailWrapper(string $content, string $headerColour = '#d60058'): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f0f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0f4;padding:2rem 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">
        <tr><td style="background:' . $headerColour . ';padding:2rem;text-align:center;">
          <div style="font-size:2rem;font-weight:900;color:#fff;">MrsB Fitness</div>
          <div style="color:rgba(255,255,255,0.8);font-size:0.9rem;margin-top:0.25rem;">Programme Tracker</div>
        </td></tr>
        <tr><td style="padding:2rem;">' . $content . '</td></tr>
        <tr><td style="background:#f5f0f4;padding:1rem;text-align:center;">
          <p style="color:#9a7a96;font-size:0.8rem;margin:0;">MrsB Fitness — Programme Tracker</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';
}
