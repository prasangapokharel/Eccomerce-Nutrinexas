<?php

namespace App\Helpers;

/**
 * EmailHelper - PHPMailer Exclusive with Proper Error Handling
 * Ensures PHPMailer is loaded and works perfectly with HTML templates
 */
class EmailHelper
{
    private static $config = [
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'username' => 'support@nutrinexas.com',
        'password' => ':CG#Dn0?',
        'encryption' => 'ssl',
        'from_email' => 'support@nutrinexas.com',
        'from_name' => 'Nutri Nexus',
        'debug' => false
    ];

    private static $phpmailerLoaded = null;

    /**
     * Initialize PHPMailer - ensures it's properly loaded
     */
    private static function initPHPMailer()
    {
        if (self::$phpmailerLoaded !== null) {
            return self::$phpmailerLoaded;
        }

        try {
            // Check if Composer autoloader is loaded
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                // Try to load Composer autoloader
                $autoloadPaths = [
                    __DIR__ . '/../../vendor/autoload.php',
                    dirname(dirname(__DIR__)) . '/vendor/autoload.php',
                    dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
                ];

                foreach ($autoloadPaths as $autoloadPath) {
                    if (file_exists($autoloadPath)) {
                        require_once $autoloadPath;
                        error_log('Loaded Composer autoloader from: ' . $autoloadPath);
                        break;
                    }
                }
            }

            // Check if PHPMailer is now available
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                self::$phpmailerLoaded = true;
                error_log('PHPMailer successfully loaded');
                return true;
            } else {
                self::$phpmailerLoaded = false;
                error_log('PHPMailer not found after autoloader attempts');
                return false;
            }

        } catch (\Exception $e) {
            self::$phpmailerLoaded = false;
            error_log('Error loading PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using PHPMailer exclusively
     */
    public static function send($to, $subject, $body, $toName = '', $isHTML = true)
    {
        // Ensure PHPMailer is loaded
        if (!self::initPHPMailer()) {
            error_log('Cannot send email: PHPMailer not available');
            throw new \Exception('PHPMailer is required but not installed. Please run: composer install');
        }

        try {
            // Import PHPMailer classes
            $phpmailerClass = 'PHPMailer\PHPMailer\PHPMailer';
            $smtpClass = 'PHPMailer\PHPMailer\SMTP';
            $exceptionClass = 'PHPMailer\PHPMailer\Exception';

            // Suppress any output from PHPMailer
            ob_start();
            
            // Create PHPMailer instance
            $mail = new $phpmailerClass(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = self::getConfig('host');
            $mail->SMTPAuth = true;
            $mail->Username = self::getConfig('username');
            $mail->Password = self::getConfig('password');
            
            // Handle encryption
            $encryption = self::getConfig('encryption');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = $phpmailerClass::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = $phpmailerClass::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = self::getConfig('port');
            
            // Debug settings
            if (self::getConfig('debug')) {
                $mail->SMTPDebug = $smtpClass::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug Level $level: " . trim($str));
                };
            } else {
                $mail->SMTPDebug = $smtpClass::DEBUG_OFF;
            }

            // Recipients
            $mail->setFrom(self::getConfig('from_email'), self::getConfig('from_name'));
            $mail->addAddress($to, $toName);

            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Set charset and encoding
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Additional headers
            $mail->addCustomHeader('X-Mailer', 'NutriNexus Mailer v1.0');
            $mail->addCustomHeader('X-Priority', '3');

            // Send email
            $result = $mail->send();
            
            // Clean any output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            if ($result) {
                error_log("Email sent successfully to: $to via PHPMailer");
                return true;
            } else {
                error_log("Failed to send email to: $to - PHPMailer returned false");
                return false;
            }

        } catch (\Exception $e) {
            // Clean any output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            error_log("PHPMailer Exception for $to: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send template-based email
     * This is the missing method that AuthController is calling
     */
    public static function sendTemplate($to, $subject, $template, $templateData = [], $toName = '')
    {
        try {
            // Get template content
            $templatePath = self::getTemplatePath($template);
            $body = '';
            
            if ($templatePath && file_exists($templatePath)) {
                // Load template from file
                $body = file_get_contents($templatePath);
                
                if ($body !== false) {
                    // Replace placeholders with data
                    foreach ($templateData as $key => $value) {
                        $body = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $body);
                    }
                    error_log('Using email template from: ' . $templatePath);
                } else {
                    error_log('Failed to read template file: ' . $templatePath);
                    $body = self::createFallbackTemplate($template, $templateData);
                }
            } else {
                error_log('Template file not found: ' . $template . ', using fallback');
                $body = self::createFallbackTemplate($template, $templateData);
            }
            
            // Send the email
            return self::send($to, $subject, $body, $toName, true);
            
        } catch (\Exception $e) {
            error_log('Failed to send template email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email configuration status
     * This is the missing method that AuthController is calling
     */
    public static function getConfigStatus()
    {
        return [
            'phpmailer_available' => self::initPHPMailer(),
            'host' => self::getConfig('host'),
            'port' => self::getConfig('port'),
            'username' => self::getConfig('username'),
            'from_email' => self::getConfig('from_email'),
            'from_name' => self::getConfig('from_name'),
            'encryption' => self::getConfig('encryption'),
            'debug' => self::getConfig('debug')
        ];
    }

    /**
     * Send referral income notification email
     */
    public static function sendReferralIncomeNotification($to, $referrerName, $amount, $orderNumber, $referredUserName)
    {
        try {
            $templateData = [
                'referrer_name' => $referrerName,
                'amount' => number_format($amount, 2),
                'order_number' => $orderNumber,
                'referred_user_name' => $referredUserName,
                'site_name' => 'NutriNexus',
                'date' => date('F j, Y'),
                'time' => date('g:i A')
            ];

            $subject = '🎉 Referral Income Added - Rs. ' . number_format($amount, 2) . ' Earned!';
            
            return self::sendTemplate($to, $subject, 'referincomeadded', $templateData, $referrerName);
            
        } catch (\Exception $e) {
            error_log('Failed to send referral income notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send withdrawal completed notification email
     */
    public static function sendWithdrawalCompletedNotification($to, $userName, $amount, $withdrawalId, $paymentMethod)
    {
        try {
            $templateData = [
                'user_name' => $userName,
                'amount' => number_format($amount, 2),
                'withdrawal_id' => $withdrawalId,
                'payment_method' => $paymentMethod,
                'site_name' => 'NutriNexus',
                'completion_date' => date('F j, Y'),
                'completion_time' => date('g:i A'),
                'support_email' => 'support@nutrinexas.com',
                'support_phone' => '+977-1-4567890'
            ];

            $subject = '✅ Withdrawal Completed - Rs. ' . number_format($amount, 2) . ' Processed';
            
            return self::sendTemplate($to, $subject, 'withdrawcompleted', $templateData, $userName);
            
        } catch (\Exception $e) {
            error_log('Failed to send withdrawal completed notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create fallback email templates when template files are not found
     */
    private static function createFallbackTemplate($template, $data)
    {
        switch ($template) {
            case 'login':
                return self::createLoginTemplate($data);
            case 'register':
                return self::createWelcomeTemplate($data);
            case 'forgot-password':
                return self::createPasswordResetTemplate($data);
            case 'password-changed':
                return self::createPasswordChangedTemplate($data);
            case 'order':
                return self::createOrderTemplate($data);
            case 'referincomeadded':
                return self::createReferralIncomeTemplate($data);
            case 'withdrawcompleted':
                return self::createWithdrawalCompletedTemplate($data);
            default:
                return self::createGenericTemplate($template, $data);
        }
    }

    /**
     * Create referral income notification template
     */
    private static function createReferralIncomeTemplate($data)
    {
        $referrerName = self::getSafeValue($data, 'referrer_name', 'User');
        $amount = self::getSafeValue($data, 'amount', '0.00');
        $orderNumber = self::getSafeValue($data, 'order_number', 'N/A');
        $referredUserName = self::getSafeValue($data, 'referred_user_name', 'Someone');
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');
        $date = self::getSafeValue($data, 'date', date('F j, Y'));

        return self::getMinimalTemplate('🎉 Referral Income Added!', $siteName, "
            <div class='greeting'>Hello <strong>{$referrerName}</strong>,</div>
            <div class='success-box'>
                <h3>🎉 Great News! You've Earned Referral Income</h3>
                <p class='amount-highlight'>Rs. {$amount}</p>
                <p>has been added to your account balance!</p>
            </div>
            <div class='info-card'>
                <h3>📋 Transaction Details</h3>
                <p><strong>Referred User:</strong> {$referredUserName}</p>
                <p><strong>Order Number:</strong> #{$orderNumber}</p>
                <p><strong>Date:</strong> {$date}</p>
                <p><strong>Amount Earned:</strong> Rs. {$amount}</p>
            </div>
            <div class='message'>
                This referral income has been automatically added to your account balance. You can request a withdrawal anytime from your dashboard.
            </div>
            <div class='cta-section'>
                <a href='#' class='cta-button'>View My Balance</a>
                <a href='#' class='cta-button-secondary'>Request Withdrawal</a>
            </div>
        ");
    }

    /**
     * Create withdrawal completed notification template
     */
    private static function createWithdrawalCompletedTemplate($data)
    {
        $userName = self::getSafeValue($data, 'user_name', 'User');
        $amount = self::getSafeValue($data, 'amount', '0.00');
        $withdrawalId = self::getSafeValue($data, 'withdrawal_id', 'N/A');
        $paymentMethod = self::getSafeValue($data, 'payment_method', 'Bank Transfer');
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');
        $completionDate = self::getSafeValue($data, 'completion_date', date('F j, Y'));
        $completionTime = self::getSafeValue($data, 'completion_time', date('g:i A'));

        return self::getMinimalTemplate('✅ Withdrawal Completed', $siteName, "
            <div class='greeting'>Hello <strong>{$userName}</strong>,</div>
            <div class='success-box'>
                <h3>✅ Withdrawal Successfully Completed</h3>
                <p class='amount-highlight'>Rs. {$amount}</p>
                <p>has been processed and sent to your account!</p>
            </div>
            <div class='info-card'>
                <h3>📋 Withdrawal Details</h3>
                <p><strong>Withdrawal ID:</strong> #{$withdrawalId}</p>
                <p><strong>Amount:</strong> Rs. {$amount}</p>
                <p><strong>Payment Method:</strong> {$paymentMethod}</p>
                <p><strong>Completion Date:</strong> {$completionDate}</p>
                <p><strong>Completion Time:</strong> {$completionTime}</p>
            </div>
            <div class='message'>
                Your withdrawal has been successfully processed. The amount should reflect in your account within 1-3 business days depending on your bank.
            </div>
            <div class='security-notice'>
                <strong>📞 Need Help?</strong> If you don't receive the payment within the expected timeframe, please contact our support team.
            </div>
        ");
    }

    /**
     * Create login notification template
     */
    private static function createLoginTemplate($data)
    {
        $firstName = self::getSafeValue($data, 'first_name', 'User');
        $loginTime = self::getSafeValue($data, 'login_time', date('Y-m-d H:i:s'));
        $ipAddress = self::getSafeValue($data, 'ip_address', 'Unknown');
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');
        $userAgent = self::getSafeValue($data, 'user_agent', 'Unknown Device');

        return self::getMinimalTemplate('Login Notification', $siteName, "
            <div class='greeting'>Hello <strong>{$firstName}</strong>,</div>
            <div class='message'>We wanted to let you know that someone just logged into your {$siteName} account.</div>
            <div class='info-card'>
                <h3>Login Details</h3>
                <p><strong>Time:</strong> {$loginTime}</p>
                <p><strong>IP Address:</strong> {$ipAddress}</p>
                <p><strong>Device:</strong> {$userAgent}</p>
            </div>
            <div class='security-notice'>
                <strong>Security Notice:</strong> If this was you, you can safely ignore this email. If you didn't log in, please contact our support team immediately.
            </div>
        ");
    }

    /**
     * Create welcome email template
     */
    private static function createWelcomeTemplate($data)
    {
        $firstName = self::getSafeValue($data, 'first_name', 'User');
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');
        $siteUrl = self::getSafeValue($data, 'site_url', 'http://localhost');

        return self::getMinimalTemplate('🎉 Welcome to ' . $siteName, $siteName, "
            <div class='greeting'>Hello <strong>{$firstName}</strong>,</div>
            <div class='message'>Welcome to {$siteName}! We're thrilled to have you join our community of health-conscious individuals.</div>
            <div class='welcome-box'>
                <h3>🌟 What's Next?</h3>
                <p>Explore our premium nutrition supplements and start your wellness journey today!</p>
                <a href='{$siteUrl}' class='cta-button'>Start Shopping</a>
            </div>
            <div class='message'>If you have any questions, our support team is here to help. Feel free to reach out anytime!</div>
        ");
    }

    /**
     * Create password reset template
     */
    private static function createPasswordResetTemplate($data)
    {
        $firstName = self::getSafeValue($data, 'first_name', 'User');
        $resetUrl = self::getSafeValue($data, 'reset_url', '#');
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');

        return self::getMinimalTemplate('🔑 Reset Your Password', $siteName, "
            <div class='greeting'>Hello <strong>{$firstName}</strong>,</div>
            <div class='message'>We received a request to reset your password for your {$siteName} account. If you didn't make this request, you can safely ignore this email.</div>
            <div class='reset-box'>
                <h3>Reset Your Password</h3>
                <p>Click the button below to create a new password:</p>
                <a href='{$resetUrl}' class='reset-button'>Reset Password</a>
            </div>
            <div class='link-section'>
                <p>If the button doesn't work, copy and paste this link:</p>
                <div class='link-text'>{$resetUrl}</div>
            </div>
            <div class='security-info'>
                <p><strong>⏰ Important:</strong> This password reset link will expire in 1 hour for security reasons.</p>
            </div>
        ");
    }

    /**
     * Create password changed template
     */
    private static function createPasswordChangedTemplate($data)
    {
        $firstName = self::getSafeValue($data, 'first_name', 'User');
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');
        $changeDate = self::getSafeValue($data, 'change_date', date('Y-m-d H:i:s'));
        $ipAddress = self::getSafeValue($data, 'ip_address', 'Unknown');

        return self::getMinimalTemplate('✅ Password Changed', $siteName, "
            <div class='greeting'>Hello <strong>{$firstName}</strong>,</div>
            <div class='success-box'>
                <h3>Password Successfully Changed</h3>
                <p>Your password has been successfully updated on {$changeDate} from IP: {$ipAddress}</p>
            </div>
            <div class='security-notice'>
                <strong>⚠️ Important:</strong> If you did not make this change, please contact our support team immediately.
            </div>
        ");
    }

    /**
     * Create order confirmation template
     */
    private static function createOrderTemplate($data)
    {
        $customerName = self::getSafeValue($data, 'customer_name', 'Customer');
        $orderNumber = self::getSafeValue($data, 'order_number', 'N/A');
        $orderDate = self::getSafeValue($data, 'order_date', date('Y-m-d'));
        $totalAmount = self::getSafeValue($data, 'total_amount', '0.00');
        $orderItems = self::getSafeValue($data, 'order_items', '<tr><td colspan="4">No items</td></tr>');
        $companyName = self::getSafeValue($data, 'company_name', 'NutriNexus');

        return self::getMinimalTemplate('🎉 Order Confirmation', $companyName, "
            <div class='greeting'>Hello <strong>{$customerName}</strong>,</div>
            <div class='message'>Thank you for your order! We're processing it now.</div>
            <div class='order-summary'>
                <h3>Order #{$orderNumber}</h3>
                <p>Placed on {$orderDate}</p>
                <p class='total'>Total: ₹{$totalAmount}</p>
            </div>
            <table class='order-table'>
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>{$orderItems}</tbody>
            </table>
            <div class='message'>You'll receive tracking information once your order ships.</div>
        ");
    }

    /**
     * Create generic template for unknown template types
     */
    private static function createGenericTemplate($template, $data)
    {
        $siteName = self::getSafeValue($data, 'site_name', 'NutriNexus');
        
        return self::getMinimalTemplate('Notification', $siteName, "
            <div class='message'>You have received a notification from {$siteName}.</div>
            <div class='info-card'>
                <p>Template: {$template}</p>
            </div>
        ");
    }

    /**
     * Get minimal template structure
     */
    private static function getMinimalTemplate($title, $siteName, $content)
    {
        return "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title} - {$siteName}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: #0a3167; color: white; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
        .greeting { font-size: 18px; margin-bottom: 20px; color: #2c3e50; }
        .message { font-size: 16px; margin-bottom: 20px; color: #555; line-height: 1.7; }
        .info-card { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #0a3167; }
        .welcome-box, .reset-box, .success-box, .order-summary { background: #C5A572; color: white; padding: 25px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .cta-button, .reset-button { display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 5px; }
        .cta-button-secondary { display: inline-block; background: rgba(255,255,255,0.1); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 5px; border: 1px solid rgba(255,255,255,0.3); }
        .amount-highlight { font-size: 32px; font-weight: bold; color: #fff; margin: 15px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .cta-section { text-align: center; margin: 25px 0; }
        .link-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .link-text { background: #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; word-break: break-all; }
        .security-notice, .security-info { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .order-table th, .order-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .order-table th { background-color: #0a3167; color: white; }
        .total { font-weight: bold; font-size: 18px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$title}</h1>
        </div>
        {$content}
        <div class='footer'>
            <p><strong>{$siteName}</strong></p>
            <p>© 2025 {$siteName}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Send order confirmation email using PHPMailer
     */
    public static function sendOrderConfirmation($order, $orderItems, $user)
    {
        try {
            // Validate input data
            if (empty($order) || empty($user) || empty($user['email'])) {
                error_log('Invalid order confirmation email data');
                return false;
            }

            error_log('Preparing order confirmation email for: ' . $user['email']);

            // Prepare template data with safe defaults
            $templateData = [
                'customer_name' => self::getSafeValue($order, 'customer_name', 
                    self::getSafeValue($user, 'first_name', 'Customer') . ' ' . 
                    self::getSafeValue($user, 'last_name', '')),
                'order_number' => self::getSafeValue($order, 'invoice', 'N/A'),
                'order_date' => date('F j, Y', strtotime(self::getSafeValue($order, 'created_at', date('Y-m-d H:i:s')))),
                'payment_method' => self::getPaymentMethodName(self::getSafeValue($order, 'payment_method_id', 1)),
                'order_status' => ucfirst(self::getSafeValue($order, 'status', 'pending')),
                'subtotal' => number_format(self::getSafeValue($order, 'total_amount', 0) - self::getSafeValue($order, 'delivery_fee', 0), 2),
                'delivery_fee' => number_format(self::getSafeValue($order, 'delivery_fee', 0), 2),
                'total_amount' => number_format(self::getSafeValue($order, 'total_amount', 0), 2),
                'delivery_address' => self::getSafeValue($order, 'address', 'Address not provided'),
                'track_url' => self::getBaseUrl() . '/orders/track',
                'support_url' => self::getBaseUrl() . '/contact',
                'company_name' => 'NutriNexus',
                'company_email' => 'support@nutrinexus.com',
                'company_phone' => '+977-1-4567890'
            ];

            // Generate order items HTML safely
            $orderItemsHtml = self::generateOrderItemsHtml($orderItems);
            $templateData['order_items'] = $orderItemsHtml;

            // Send email using template
            $subject = 'Order Confirmation - #' . $templateData['order_number'];
            $result = self::sendTemplate($user['email'], $subject, 'order', $templateData, $templateData['customer_name']);

            if ($result) {
                error_log('Order confirmation email sent successfully via PHPMailer for order: ' . $templateData['order_number']);
            } else {
                error_log('Failed to send order confirmation email via PHPMailer for order: ' . $templateData['order_number']);
            }

            return $result;

        } catch (\Exception $e) {
            error_log('Failed to send order confirmation email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test PHPMailer connection and functionality
     */
    public static function testConnection()
    {
        try {
            // Ensure PHPMailer is loaded
            if (!self::initPHPMailer()) {
                error_log('PHPMailer connection test failed: PHPMailer not available');
                return false;
            }

            $phpmailerClass = 'PHPMailer\PHPMailer\PHPMailer';
            $smtpClass = 'PHPMailer\PHPMailer\SMTP';

            ob_start();
            
            $mail = new $phpmailerClass(true);
            $mail->isSMTP();
            $mail->Host = self::getConfig('host');
            $mail->SMTPAuth = true;
            $mail->Username = self::getConfig('username');
            $mail->Password = self::getConfig('password');
            $mail->SMTPSecure = self::getConfig('encryption') === 'ssl' ? 
                $phpmailerClass::ENCRYPTION_SMTPS : $phpmailerClass::ENCRYPTION_STARTTLS;
            $mail->Port = self::getConfig('port');
            $mail->SMTPDebug = $smtpClass::DEBUG_OFF;
            
            // Try to connect
            $result = $mail->smtpConnect();
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            if ($result) {
                $mail->smtpClose();
                error_log('PHPMailer SMTP connection test successful');
                return true;
            }
            
            error_log('PHPMailer SMTP connection test failed');
            return false;
            
        } catch (\Exception $e) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            error_log('PHPMailer connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send test email
     */
    public static function sendTestEmail($toEmail, $toName = 'Test User')
    {
        try {
            $templateData = [
                'first_name' => $toName,
                'site_name' => 'NutriNexus',
                'test_time' => date('Y-m-d H:i:s')
            ];

            $subject = 'Test Email from NutriNexus - PHPMailer';
            $result = self::sendTemplate($toEmail, $subject, 'test', $templateData, $toName);
            
            if ($result) {
                error_log('Test email sent successfully via PHPMailer to: ' . $toEmail);
                return true;
            } else {
                error_log('Test email failed via PHPMailer to: ' . $toEmail);
                return false;
            }
            
        } catch (\Exception $e) {
            error_log('Test email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate order items HTML safely
     */
    private static function generateOrderItemsHtml($orderItems)
    {
        if (empty($orderItems) || !is_array($orderItems)) {
            return '<tr><td colspan="4" style="text-align: center; color: #666;">No items found</td></tr>';
        }

        $html = '';
        foreach ($orderItems as $item) {
            $productName = self::getSafeValue($item, 'product_name', 'Product');
            $quantity = self::getSafeValue($item, 'quantity', 1);
            $price = self::getSafeValue($item, 'price', 0);
            $total = self::getSafeValue($item, 'total', 0);

            $html .= '<tr>';
            $html .= '<td class="product-name">' . htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . $quantity . '</td>';
            $html .= '<td class="price">₹' . number_format($price, 2) . '</td>';
            $html .= '<td class="price">₹' . number_format($total, 2) . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Helper methods
     */
    private static function getSafeValue($array, $key, $default = '')
    {
        return isset($array[$key]) && $array[$key] !== null ? $array[$key] : $default;
    }

    private static function getPaymentMethodName($paymentMethodId)
    {
        $methods = [
            1 => 'Cash on Delivery',
            2 => 'Bank Transfer',
            3 => 'Online Payment'
        ];
        return isset($methods[$paymentMethodId]) ? $methods[$paymentMethodId] : 'Unknown';
    }

    private static function getBaseUrl()
    {
        try {
            if (defined('URLROOT')) {
                return URLROOT;
            }
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            return $protocol . $host;
        } catch (\Exception $e) {
            error_log('Error getting base URL: ' . $e->getMessage());
            return 'http://192.168.1.74:8000';
        }
    }

    private static function getTemplatePath($template)
    {
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/assets/templates/' . $template . '.html',
            dirname(dirname(__DIR__)) . '/assets/templates/' . $template . '.html',
            dirname(dirname(dirname(__DIR__))) . '/assets/templates/' . $template . '.html',
            __DIR__ . '/../../assets/templates/' . $template . '.html',
            (defined('APPROOT') ? APPROOT . '/assets/templates/' . $template . '.html' : null)
        ];

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function getConfig($key)
    {
        try {
            switch ($key) {
                case 'host':
                    return defined('MAIL_HOST') ? MAIL_HOST : self::$config['host'];
                case 'port':
                    return defined('MAIL_PORT') ? MAIL_PORT : self::$config['port'];
                case 'username':
                    return defined('MAIL_USERNAME') ? MAIL_USERNAME : self::$config['username'];
                case 'password':
                    return defined('MAIL_PASSWORD') ? MAIL_PASSWORD : self::$config['password'];
                case 'encryption':
                    return defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : self::$config['encryption'];
                case 'from_email':
                    return defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : self::$config['from_email'];
                case 'from_name':
                    return defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : self::$config['from_name'];
                case 'debug':
                    return defined('MAIL_DEBUG') ? MAIL_DEBUG : self::$config['debug'];
                default:
                    return isset(self::$config[$key]) ? self::$config[$key] : '';
            }
        } catch (\Exception $e) {
            error_log('Error getting email config for key ' . $key . ': ' . $e->getMessage());
            return isset(self::$config[$key]) ? self::$config[$key] : '';
        }
    }

    public static function setConfig($config)
    {
        if (is_array($config)) {
            self::$config = array_merge(self::$config, $config);
        }
    }

    public static function enableDebug()
    {
        self::$config['debug'] = true;
    }

    public static function disableDebug()
    {
        self::$config['debug'] = false;
    }

    /**
     * Check if PHPMailer is properly installed
     */
    public static function isPHPMailerAvailable()
    {
        return self::initPHPMailer();
    }
}
