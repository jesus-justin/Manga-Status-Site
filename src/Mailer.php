<?php
/**
 * Mailer Class
 * 
 * Provides email sending utilities with template support.
 */

class Mailer {
    private $from;
    private $fromName;

    public function __construct() {
        $this->from = SMTP_FROM ?? 'noreply@mangalibrary.local';
        $this->fromName = APP_NAME;
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail($to, $token) {
        $subject = 'Verify Your ' . APP_NAME . ' Account';
        $verifyUrl = APP_URL . 'verify_email.php?token=' . urlencode($token);
        
        $html = $this->getVerificationTemplate($verifyUrl);
        return $this->send($to, $subject, $html);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($to, $token) {
        $subject = APP_NAME . ' - Password Reset Request';
        $resetUrl = APP_URL . 'reset_password.php?token=' . urlencode($token);
        
        $html = $this->getPasswordResetTemplate($resetUrl);
        return $this->send($to, $subject, $html);
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($to, $username) {
        $subject = 'Welcome to ' . APP_NAME . '!';
        $html = $this->getWelcomeTemplate($username);
        return $this->send($to, $subject, $html);
    }

    /**
     * Send notification email
     */
    public function sendNotification($to, $title, $message) {
        $subject = APP_NAME . ' - ' . $title;
        $html = $this->getNotificationTemplate($title, $message);
        return $this->send($to, $subject, $html);
    }

    /**
     * Send raw email
     */
    public function send($to, $subject, $html) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->fromName . " <" . $this->from . ">" . "\r\n";
        $headers .= "X-Mailer: " . APP_NAME . "" . "\r\n";
        
        return mail($to, $subject, $html, $headers);
    }

    /**
     * Get verification email template
     */
    private function getVerificationTemplate($verifyUrl) {
        return "<html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #d7263d;'>Verify Your Email</h2>
                    <p>Thank you for registering with " . APP_NAME . "!</p>
                    <p>Please click the button below to verify your email address:</p>
                    <a href='" . $verifyUrl . "' style='background-color: #d7263d; color: #f5efe3; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>
                        Verify Email
                    </a>
                    <p style='color: #666; font-size: 12px; margin-top: 20px;'>
                        If you didn't create this account, please ignore this email.
                    </p>
                </div>
            </body>
        </html>";
    }

    /**
     * Get password reset email template
     */
    private function getPasswordResetTemplate($resetUrl) {
        return "<html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #d7263d;'>Password Reset Request</h2>
                    <p>We received a request to reset your " . APP_NAME . " password.</p>
                    <p>Click the link below to reset your password:</p>
                    <a href='" . $resetUrl . "' style='background-color: #d7263d; color: #f5efe3; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>
                        Reset Password
                    </a>
                    <p style='color: #666; font-size: 12px; margin-top: 20px;'>
                        This link expires in 1 hour. If you didn't request this, please ignore this email.
                    </p>
                </div>
            </body>
        </html>";
    }

    /**
     * Get welcome email template
     */
    private function getWelcomeTemplate($username) {
        return "<html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #d7263d;'>Welcome, " . htmlspecialchars($username) . "!</h2>
                    <p>Your " . APP_NAME . " account has been successfully created.</p>
                    <p>Start building your manga collection today and track your reading progress!</p>
                    <p style='color: #666; font-size: 12px; margin-top: 20px;'>
                        Happy reading!
                    </p>
                </div>
            </body>
        </html>";
    }

    /**
     * Get notification email template
     */
    private function getNotificationTemplate($title, $message) {
        return "<html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #d7263d;'>" . htmlspecialchars($title) . "</h2>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
            </body>
        </html>";
    }
}

?>
