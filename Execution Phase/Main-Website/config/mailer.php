<?php
require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('huddershub_base_url')) {
    function huddershub_base_url() {
        return defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'http://localhost/Main-Website';
    }
}

if (!function_exists('huddershub_send_html_mail')) {
    function huddershub_send_html_mail($toEmail, $toName, $subject, $htmlBody, $textBody = '') {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        $mail->send();
        return true;
    }
}

if (!function_exists('huddershub_send_verification_email')) {
    function huddershub_send_verification_email($toEmail, $toName, $verifyLink) {
        $subject = 'Verify your HuddersHub account';
        $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#0F260B;margin:0 0 12px;">Welcome to HuddersHub</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Please verify your email address to activate your account.</p>'
            . '<p><a href="' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#FF5E3A;color:#fff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:700;">Verify Email</a></p>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;">If the button does not work, copy and paste this link: <br>' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $toName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_reset_email')) {
    function huddershub_send_reset_email($toEmail, $toName, $resetLink) {
        $subject = 'Reset your HuddersHub password';
        $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#0F260B;margin:0 0 12px;">Password reset requested</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Use the link below to set a new password. This link expires in 1 hour.</p>'
            . '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#FF5E3A;color:#fff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:700;">Reset Password</a></p>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;">If the button does not work, copy and paste this link: <br>' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $toName, $subject, $html);
    }
}