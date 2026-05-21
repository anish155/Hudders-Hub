<?php
// api/email/contact.php
// Handles the public contact-page form submission.
// Fields (POST): name, email, subject, message
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mailer.php';

header('Content-Type: application/json');

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Parse JSON payload from the fetch() call
$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name    = trim($input['name']    ?? '');
$email   = trim($input['email']   ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

// ── Basic validation ──────────────────────────────────────────────────────────
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Your name is required']);
    exit;
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'A valid email address is required']);
    exit;
}
if ($subject === '') {
    echo json_encode(['success' => false, 'error' => 'Please enter a subject']);
    exit;
}
if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Please enter a message']);
    exit;
}
if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'error' => 'Message must be at least 10 characters']);
    exit;
}

// ── Build & send email ────────────────────────────────────────────────────────
$to      = 'support@huddershub.test';
$toName  = 'HuddersHub Support';

$textBody = "New contact form submission from HuddersHub\n\n"
    . "From:     " . $name . " <" . $email . ">\n"
    . "Subject:  " . $subject . "\n\n"
    . "Message:\n" . $message . "\n";

$htmlBody = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
    . '<div style="background:#0F260B;color:#fff;padding:20px;text-align:center;margin:-24px -24px 24px -24px;">'
    . '<h2 style="margin:0;font-size:20px;">New Contact Form Submission</h2>'
    . '</div>'
    . '<p style="color:#374151;line-height:1.6;">A visitor has sent a message via the HuddersHub contact page.</p>'
    . '<table style="width:100%;border-collapse:collapse;margin:16px 0;border:1px solid #e5e7eb;">'
    . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;width:120px;">Name</td>'
    . '<td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Email</td>'
    . '<td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;"><a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '" style="color:#FF5E3A;">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a></td></tr>'
    . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Subject</td>'
    . '<td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '</table>'
    . '<h4 style="color:#374151;margin:16px 0 8px;">Message</h4>'
    . '<div style="background:#f9fafb;padding:16px;border-radius:8px;border-left:4px solid #0F260B;color:#374151;white-space:pre-wrap;line-height:1.6;">'
    . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
    . '</div>'
    . '</div>';

try {
    $sent = huddershub_send_html_mail($to, $toName, 'Contact Form: ' . $subject, $htmlBody, $textBody);

    // Also send an auto-reply to the visitor
    $autoReplyHtml = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
        . '<h2 style="color:#0F260B;margin:0 0 12px;">Message Received!</h2>'
        . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p style="color:#374151;line-height:1.6;">Thank you for contacting HuddersHub. We have received your message and will get back to you within 1 business day.</p>'
        . '<p style="color:#374151;line-height:1.6;"><strong>Original subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p style="color:#6b7280;font-size:12px;">HuddersHub Customer Support — support@huddershub.test</p>'
        . '</div>';
    @hudderhub_send_html_mail($email, $name, 'Message Received — HuddersHub', $autoReplyHtml);

    echo json_encode(['success' => true, 'message' => 'Thank you for contacting us! We\'ll reply within 1 business day.']);
} catch (Exception $e) {
    error_log('Contact form email failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to send message. Please try again later.']);
}
