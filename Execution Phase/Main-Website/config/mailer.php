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

        try {
            $result = $mail->send();
            return $result;
        } catch (Exception $e) {
            // Fall back to PHP mail() so emails are never silently lost
            return huddershub_send_fallback_mail($toEmail, $toName, $subject, $htmlBody, $textBody);
        }
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

// Last-resort mail() fallback when PHPMailer fails
if (!function_exists('huddershub_send_fallback_mail')) {
    function huddershub_send_fallback_mail($toEmail, $toName, $subject, $htmlBody, $textBody = '') {
        $headers  = "From: " . MAIL_FROM . "\r\n";
        $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return (bool)@mail($toEmail, $subject, $htmlBody, $headers);
    }
}

if (!function_exists('huddershub_send_order_confirmation')) {
    function huddershub_send_order_confirmation($toEmail, $toName, $orderId, $items, $total, $slotDate, $slotTime) {
        $subject = 'Order Confirmed - HuddersHub Order #' . $orderId;
        
        $itemsHtml = '';
        $subtotal = 0;
        foreach ($items as $item) {
            $itemTotal = $item['QUANTITY'] * $item['UNIT_PRICE'];
            $subtotal += $itemTotal;
            $itemsHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['NAME']) . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' . $item['QUANTITY'] . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">£' . number_format($itemTotal, 2) . '</td></tr>';
        }
        
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#0F260B;margin:0 0 12px;">Order Confirmed!</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Your order has been confirmed. Here are the details:</p>'
            . '<div style="background:#f9fafb;padding:16px;border-radius:8px;margin:16px 0;">'
            . '<p style="margin:4px 0;"><strong>Order ID:</strong> #HDR-' . $orderId . '</p>'
            . '<p style="margin:4px 0;"><strong>Collection:</strong> ' . htmlspecialchars($slotDate) . ' at ' . htmlspecialchars($slotTime) . '</p>'
            . '</div>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
            . '<thead><tr><th style="text-align:left;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Product</th><th style="padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Qty</th><th style="text-align:right;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Price</th></tr></thead>'
            . '<tbody>' . $itemsHtml . '</tbody>'
            . '<tfoot><tr><td colspan="2" style="padding:8px;text-align:right;"><strong>Subtotal</strong></td><td style="padding:8px;text-align:right;">£' . number_format($subtotal, 2) . '</td></tr>'
            . '<tr><td colspan="2" style="padding:8px;text-align:right;">Service Fee</td><td style="padding:8px;text-align:right;">£2.40</td></tr>'
            . '<tr><td colspan="2" style="padding:8px;text-align:right;font-weight:bold;">Total Paid</td><td style="padding:8px;text-align:right;font-weight:bold;">£' . number_format($total, 2) . '</td></tr></tfoot></table>'
            . '<p style="color:#6b7280;font-size:14px;margin-top:16px;">Pick up from: Hudders Butchers, 45 Market Street, Huddersfield</p>'
            . '<p style="color:#6b7280;font-size:12px;">Thank you for ordering with HuddersHub!</p>'
            . '</div>';
        
        return huddershub_send_html_mail($toEmail, $toName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_trader_order_notification')) {
    function huddershub_send_trader_order_notification($toEmail, $traderName, $orderId, $items, $slotDate, $slotTime) {
        $subject = 'New Order Received - HuddersHub Order #' . $orderId;
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['NAME']) . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' . $item['QUANTITY'] . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">£' . number_format($item['UNIT_PRICE'], 2) . '</td></tr>';
        }
        
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#0F260B;margin:0 0 12px;">New Order Received!</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">You have received a new order for your shop. Here are the items to prepare:</p>'
            . '<div style="background:#f9fafb;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #FF5E3A;">'
            . '<p style="margin:4px 0;"><strong>Order ID:</strong> #HDR-' . $orderId . '</p>'
            . '<p style="margin:4px 0;"><strong>Collection Slot:</strong> ' . htmlspecialchars($slotDate) . ' at ' . htmlspecialchars($slotTime) . '</p>'
            . '</div>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
            . '<thead><tr><th style="text-align:left;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Product</th><th style="padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Qty</th><th style="text-align:right;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Unit Price</th></tr></thead>'
            . '<tbody>' . $itemsHtml . '</tbody>'
            . '</table>'
            . '<p style="color:#374151;line-height:1.6;">Please ensure these items are ready for collection at the specified slot.</p>'
            . '<p style="color:#6b7280;font-size:12px;margin-top:24px;">Thank you for selling with HuddersHub!</p>'
            . '</div>';
        
        return huddershub_send_html_mail($toEmail, $traderName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_order_cancellation')) {
    function huddershub_send_order_cancellation($toEmail, $toName, $orderId, $items, $total, $slotDate, $slotTime) {
        $subject = 'Order Cancelled - HuddersHub Order #' . $orderId;
        
        $itemsHtml = '';
        $subtotal = 0;
        foreach ($items as $item) {
            $itemTotal = $item['QUANTITY'] * $item['UNIT_PRICE'];
            $subtotal += $itemTotal;
            $itemsHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['NAME']) . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' . $item['QUANTITY'] . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">£' . number_format($itemTotal, 2) . '</td></tr>';
        }
        
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#B91C1C;margin:0 0 12px;">Order Cancelled</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Your order has been cancelled successfully. A full refund has been issued.</p>'
            . '<div style="background:#FEF2F2;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #B91C1C;">'
            . '<p style="margin:4px 0;"><strong>Order ID:</strong> #HDR-' . $orderId . '</p>'
            . '<p style="margin:4px 0;"><strong>Status:</strong> Cancelled</p>'
            . '<p style="margin:4px 0;"><strong>Refund:</strong> Full refund issued</p>'
            . '<p style="margin:4px 0;"><strong>Refund processed:</strong> Within 3-5 business days</p>'
            . '</div>'
            . '<h3 style="color:#374151;margin:16px 0 8px;">Cancelled Items</h3>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
            . '<thead><tr><th style="text-align:left;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Product</th><th style="padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Qty</th><th style="text-align:right;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Price</th></tr></thead>'
            . '<tbody>' . $itemsHtml . '</tbody>'
            . '<tfoot><tr><td colspan="2" style="padding:8px;text-align:right;"><strong>Subtotal</strong></td><td style="padding:8px;text-align:right;">£' . number_format($subtotal, 2) . '</td></tr>'
            . '<tr><td colspan="2" style="padding:8px;text-align:right;">Service Fee</td><td style="padding:8px;text-align:right;">£2.40</td></tr>'
            . '<tr><td colspan="2" style="padding:8px;text-align:right;font-weight:bold;">Total Refunded</td><td style="padding:8px;text-align:right;font-weight:bold;color:#B91C1C;">£' . number_format($total, 2) . '</td></tr></tfoot></table>'
            . '<div style="background:#f9fafb;padding:12px;border-radius:8px;margin:16px 0;">'
            . '<p style="margin:4px 0;color:#374151;"><strong>Original Collection:</strong> ' . htmlspecialchars($slotDate) . ' at ' . htmlspecialchars($slotTime) . '</p>'
            . '</div>'
            . '<p style="color:#6b7280;font-size:14px;margin-top:16px;">If you did not request this cancellation, please contact us immediately.</p>'
            . '<p style="color:#6b7280;font-size:12px;">HuddersHub Customer Support - adminhuddershub@gmail.com</p>'
            . '</div>';
        
        return huddershub_send_html_mail($toEmail, $toName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_trader_welcome')) {
    function huddershub_send_trader_welcome($toEmail, $traderName, $shopName, $loginLink) {
        $subject = 'Welcome to HuddersHub, ' . htmlspecialchars($traderName) . '!';
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#0F260B;margin:0 0 12px;">Welcome to HuddersHub!</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Your shop <strong>' . htmlspecialchars($shopName) . '</strong> has been registered on HuddersHub. We are excited to have you on board!</p>'
            . '<div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #0F260B;">'
            . '<p style="margin:4px 0;"><strong>Next steps:</strong></p>'
            . '<ul style="color:#374151;line-height:1.8;margin:8px 0 0 20px;">'
            . '<li>Log in to your trader dashboard</li>'
            . '<li>Add your products and set your prices</li>'
            . '<li>Manage your stock levels and orders</li>'
            . '<li>View your sales reports and analytics</li>'
            . '</ul>'
            . '</div>'
            . '<p><a href="' . htmlspecialchars($loginLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#FF5E3A;color:#fff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:700;">Go to Dashboard</a></p>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;margin-top:16px;">If you have any questions, contact us at adminhuddershub@gmail.com</p>'
            . '<p style="color:#6b7280;font-size:12px;">HuddersHub Team - Connecting local traders with the community</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $traderName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_trader_apex_credentials')) {
    function huddershub_send_trader_apex_credentials($toEmail, $traderName, $shopName, $apexUrl, $username, $password) {
        $subject = 'Your Oracle APEX Login Credentials - HuddersHub';
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<h2 style="color:#0F260B;margin:0 0 12px;">Oracle APEX Login Credentials</h2>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Your trader account for <strong>' . htmlspecialchars($shopName) . '</strong> has been set up on Oracle APEX. Use the credentials below to access the system:</p>'
            . '<div style="background:#f0fdf4;padding:20px;border-radius:8px;margin:16px 0;border-left:4px solid #0F260B;">'
            . '<p style="margin:6px 0;"><strong>APEX URL:</strong> <a href="' . htmlspecialchars($apexUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#FF5E3A;">' . htmlspecialchars($apexUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p style="margin:6px 0;"><strong>Username:</strong> <code style="background:#fff;padding:2px 8px;border-radius:4px;">' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</code></p>'
            . '<p style="margin:6px 0;"><strong>Password:</strong> <code style="background:#fff;padding:2px 8px;border-radius:4px;">' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '</code></p>'
            . '</div>'
            . '<div style="background:#fef3c7;padding:12px;border-radius:8px;margin:16px 0;border-left:4px solid #f59e0b;">'
            . '<p style="margin:4px 0;color:#92400e;font-size:13px;"><strong>Important:</strong> Please keep these credentials secure. Do not share them with anyone. We recommend changing your password after your first login.</p>'
            . '</div>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;margin-top:16px;">If you did not register as a trader on HuddersHub, please contact us immediately at adminhuddershub@gmail.com</p>'
            . '<p style="color:#6b7280;font-size:12px;">HuddersHub Team</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $traderName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_trader_approved')) {
    function huddershub_send_trader_approved($toEmail, $traderName, $shopName, $dashboardLink) {
        $subject = 'Your Trader Account Has Been Approved - HuddersHub';
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<div style="background:#0F260B;color:#fff;padding:24px;text-align:center;margin:-24px -24px 24px -24px;">'
            . '<h2 style="margin:0 0 8px;">Account Approved!</h2>'
            . '<p style="margin:0;opacity:0.9;">Welcome to the HuddersHub trader community</p>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Great news! Your trader application for <strong>' . htmlspecialchars($shopName) . '</strong> has been reviewed and approved by our team.</p>'
            . '<div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #0F260B;">'
            . '<p style="margin:4px 0;color:#0F260B;font-weight:600;">Your account is now active!</p>'
            . '<p style="margin:4px 0;color:#374151;">You can now:</p>'
            . '<ul style="color:#374151;line-height:1.8;margin:8px 0 0 20px;">'
            . '<li>Access your trader dashboard</li>'
            . '<li>List and manage your products</li>'
            . '<li>Receive and process customer orders</li>'
            . '<li>View sales reports and analytics</li>'
            . '<li>Update your shop profile and settings</li>'
            . '</ul>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">You should have received a separate email with your Oracle APEX login credentials. Use those to access the full system.</p>'
            . '<p style="text-align:center;margin:24px 0;"><a href="' . htmlspecialchars($dashboardLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#FF5E3A;color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:16px;">Go to Trader Dashboard</a></p>'
            . '<div style="background:#f9fafb;padding:16px;border-radius:8px;margin:16px 0;">'
            . '<p style="margin:4px 0;color:#374151;font-size:14px;"><strong>Need help getting started?</strong></p>'
            . '<p style="margin:4px 0;color:#6b7280;font-size:13px;">Contact our trader support team at <a href="mailto:adminhuddershub@gmail.com" style="color:#FF5E3A;">adminhuddershub@gmail.com</a></p>'
            . '</div>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;margin-top:16px;">Thank you for joining HuddersHub. We look forward to helping your business grow!</p>'
            . '<p style="color:#6b7280;font-size:12px;">HuddersHub Team - Connecting local traders with the community</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $traderName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_trader_registration_received')) {
    function huddershub_send_trader_registration_received($toEmail, $traderName, $shopName, $shopType) {
        $subject = 'Trader Registration Received - HuddersHub';
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<div style="background:#0F260B;color:#fff;padding:24px;text-align:center;margin:-24px -24px 24px -24px;">'
            . '<h2 style="margin:0 0 8px;">Application Received!</h2>'
            . '<p style="margin:0;opacity:0.9;">Thank you for joining the HuddersHub community</p>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">We have successfully received your trader application for <strong>' . htmlspecialchars($shopName) . '</strong>. Thank you for your interest in becoming part of our local trader community!</p>'
            . '<div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #0F260B;">'
            . '<p style="margin:4px 0;color:#0F260B;font-weight:600;">Application Summary</p>'
            . '<p style="margin:4px 0;color:#374151;"><strong>Shop Name:</strong> ' . htmlspecialchars($shopName) . '</p>'
            . '<p style="margin:4px 0;color:#374151;"><strong>Shop Type:</strong> ' . htmlspecialchars($shopType) . '</p>'
            . '<p style="margin:4px 0;color:#374151;"><strong>Status:</strong> Pending Review</p>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">Our team will carefully review your application. This process usually takes <strong>1-2 business days</strong>. You will receive an email notification as soon as a decision has been made.</p>'
            . '<h3 style="color:#374151;margin:20px 0 8px;">What happens next?</h3>'
            . '<ul style="color:#374151;line-height:1.8;margin:8px 0 0 20px;">'
            . '<li>Our admin team reviews your application details</li>'
            . '<li>You will receive an email confirming <strong>Approval</strong> or <strong>Decline</strong></li>'
            . '<li>If approved, you will gain instant access to your trader dashboard</li>'
            . '<li>You can start listing your products and receiving orders immediately</li>'
            . '</ul>'
            . '<p style="color:#374151;line-height:1.6;">If you have any questions in the meantime, feel free to contact us at <a href="mailto:adminhuddershub@gmail.com" style="color:#FF5E3A;">adminhuddershub@gmail.com</a>.</p>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;margin-top:24px;">Thank you for choosing HuddersHub.<br>The HuddersHub Team</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $traderName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_trader_declined')) {
    function huddershub_send_trader_declined($toEmail, $traderName, $shopName, $reason = '') {
        $subject = 'Trader Application Update - HuddersHub';
        $reasonBlock = '';
        if ($reason) {
            $reasonBlock = '<div style="background:#fef2f2;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #dc2626;">'
                . '<p style="margin:4px 0;color:#991b1b;font-weight:600;">Reason for Decline</p>'
                . '<p style="margin:4px 0;color:#374151;">' . htmlspecialchars($reason) . '</p>'
                . '</div>';
        }
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<div style="background:#374151;color:#fff;padding:24px;text-align:center;margin:-24px -24px 24px -24px;">'
            . '<h2 style="margin:0 0 8px;">Application Update</h2>'
            . '<p style="margin:0;opacity:0.9;">Regarding your HuddersHub trader application</p>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Thank you for taking the time to apply to become a HuddersHub trader.</p>'
            . '<p style="color:#374151;line-height:1.6;">After careful review, we regret to inform you that your application for <strong>' . htmlspecialchars($shopName) . '</strong> has <strong>not been approved</strong> at this time.</p>'
            . $reasonBlock
            . '<p style="color:#374151;line-height:1.6;">We encourage you to address the feedback above and re-apply in the future. Our team always looks forward to welcoming new local traders into the HuddersHub community.</p>'
            . '<p style="color:#374151;line-height:1.6;">If you have any questions or would like more information, please don\'t hesitate to reach out: '
            . '<a href="mailto:adminhuddershub@gmail.com" style="color:#FF5E3A;">adminhuddershub@gmail.com</a>.</p>'
            . '<div style="background:#fef3c7;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #f59e0b;">'
            . '<p style="margin:4px 0;color:#92400e;"><strong>Note:</strong> You may submit a new application at any time from our <a href="' . htmlspecialchars(huddershub_base_url() . '/public/register-trader.html', ENT_QUOTES, 'UTF-8') . '" style="color:#FF5E3A;">trader registration page</a>.</p>'
            . '</div>'
            . '<p style="color:#6b7280;font-size:12px;line-height:1.6;margin-top:24px;">Thank you for your interest in HuddersHub.<br>The HuddersHub Team</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, $traderName, $subject, $html);
    }
}

if (!function_exists('huddershub_send_admin_new_trader_notification')) {
    function huddershub_send_admin_new_trader_notification($toEmail, $firstName, $lastName, $shopName, $shopType, $shopLocation, $traderEmail, $phone, $description) {
        $subject = 'New Trader Registration: ' . $shopName;
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<div style="background:#0F260B;color:#fff;padding:24px;text-align:center;margin:-24px -24px 24px -24px;">'
            . '<h2 style="margin:0 0 8px;">New Trader Application</h2>'
            . '<p style="margin:0;opacity:0.9;">Awaiting your review</p>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">A new trader has registered and is awaiting approval.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;border:1px solid #e5e7eb;">'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;width:40%;">Name</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($firstName . ' ' . $lastName) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Email</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;"><a href="mailto:' . htmlspecialchars($traderEmail) . '" style="color:#FF5E3A;">' . htmlspecialchars($traderEmail) . '</a></td></tr>'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Phone</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($phone ?: 'N/A') . '</td></tr>'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Shop</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($shopName) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Type</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($shopType) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Location</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($shopLocation) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:700;background:#f9fafb;">Description</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($description ?: 'N/A') . '</td></tr>'
            . '</table>'
            . '<p style="color:#374151;">Please review and approve or decline via the admin dashboard.</p>'
            . '</div>';
        return huddershub_send_html_mail($toEmail, '', $subject, $html);
    }
}

if (!function_exists('hudderhub_send_trader_purchase_receipt')) {
    function hudderhub_send_trader_purchase_receipt($traderEmail, $traderName, $shopName, $orderId, $items, $subtotal) {
        $subject = 'New Order Received — HuddersHub #' . $orderId;

        $rows  = '';
        $lineTotalSum = 0;
        foreach ($items as $it) {
            $line = $it['QUANTITY'] * $it['UNIT_PRICE'];
            $lineTotalSum += $line;
            $rows .= '<tr>'
                . '<td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($it['NAME']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' . (int)$it['QUANTITY'] . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">£' . number_format($it['UNIT_PRICE'], 2) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">£' . number_format($line, 2) . '</td>'
                . '</tr>';
        }

        $itemCount = count($items);
        $plural    = $itemCount === 1 ? 'item' : 'items';

        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;background:#fff">'
            . '<div style="background:#0F260B;color:#fff;padding:24px;text-align:center;margin:-24px -24px 24px -24px;">'
            . '<h2 style="margin:0 0 8px;font-size:22px;">🎉 New Order Received!</h2>'
            . '<p style="margin:0;opacity:0.9;">A customer has just purchased from your shop</p>'
            . '</div>'
            . '<p style="color:#374151;line-height:1.6;">Hi ' . htmlspecialchars($traderName ?: 'Trader', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p style="color:#374151;line-height:1.6;">Great news! A customer has placed an order for <strong>' . $itemCount . ' ' . $plural . '</strong> from <strong>' . htmlspecialchars($shopName) . '</strong>. Here are the details:</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
            . '<thead><tr>'
            . '<th style="text-align:left;padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;">Product</th>'
            . '<th style="padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;text-align:center;">Qty</th>'
            . '<th style="padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;text-align:right;">Unit Price</th>'
            . '<th style="padding:8px;background:#f9fafb;border-bottom:2px solid #ddd;text-align:right;">Line Total</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '<tfoot><tr>'
            . '<td colspan="3" style="padding:8px;text-align:right;"><strong>Subtotal</strong></td>'
            . '<td style="padding:8px;text-align:right;"><strong>£' . number_format($lineTotalSum, 2) . '</strong></td>'
            . '</tr></tfoot>'
            . '</table>'
            . '<div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #0F260B;">'
            . '<p style="margin:4px 0;color:#374151;"><strong>Order ID:</strong> #HDR-' . $orderId . '</p>'
            . '<p style="margin:4px 0;color:#374151;"><strong>Shop:</strong> ' . htmlspecialchars($shopName) . '</p>'
            . '<p style="margin:4px 0;color:#374151;"><strong>Status:</strong> Pending — awaiting payment confirmation</p>'
            . '</div>'
            . '<p style="color:#FF5E3A;font-size:13px;font-weight:600;margin-top:16px;">Log into your <a href="' . htmlspecialchars(hudderhub_base_url() . '/trader/dashboard.html', ENT_QUOTES, 'UTF-8') . '" style="color:#FF5E3A;">Trader Dashboard</a> to view and manage this order.</p>'
            . '<p style="color:#6b7280;font-size:12px;margin-top:16px;">Thank you for being part of the HuddersHub community.</p>'
            . '</div>';

        return huddershub_send_html_mail($traderEmail, $traderName ?: $traderEmail, $subject, $html);
    }
}