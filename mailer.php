<?php
require_once 'config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

class Mailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host       = SMTP_HOST;
        $this->mail->SMTPAuth   = SMTP_AUTH;
        $this->mail->Username   = SMTP_USER;
        $this->mail->Password   = SMTP_PASS;
        $this->mail->SMTPSecure = SMTP_SECURE;
        $this->mail->Port       = SMTP_PORT;
        
        // Set charset
        $this->mail->CharSet = 'UTF-8';
        
        // Sender info
        $this->mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        
        // Default to HTML
        $this->mail->isHTML(true);
    }
    
    public function sendEmail($to, $subject, $htmlContent, $textContent = '') {
        try {
            // Clear all recipients
            $this->mail->clearAddresses();
            
            // Add recipient
            if (is_array($to)) {
                foreach ($to as $email) {
                    $this->mail->addAddress($email);
                }
            } else {
                $this->mail->addAddress($to);
            }
            
            // Email subject
            $this->mail->Subject = $subject;
            
            // HTML content
            $this->mail->Body = $htmlContent;
            
            // Plain text alternative
            if (empty($textContent)) {
                $textContent = strip_tags($htmlContent);
            }
            $this->mail->AltBody = $textContent;
            
            // Send email
            return $this->mail->send();
            
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    // Generate email template
    public function getTemplate($title, $content, $userName = '') {
        $year = date('Y');
        
        $template = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .header p {
                    margin: 10px 0 0;
                    opacity: 0.9;
                }
                .content {
                    padding: 30px;
                    background: white;
                }
                .content h2 {
                    color: #2c3e50;
                    margin-top: 0;
                    border-bottom: 2px solid #f0f0f0;
                    padding-bottom: 15px;
                }
                .info-box {
                    background: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .info-box strong {
                    color: #2c3e50;
                    display: inline-block;
                    min-width: 120px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: 600;
                    margin: 20px 0;
                }
                .button:hover {
                    opacity: 0.9;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                table th {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 12px;
                    text-align: left;
                }
                table td {
                    padding: 12px;
                    border-bottom: 1px solid #f0f0f0;
                }
                .status-badge {
                    padding: 5px 10px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .status-badge.success {
                    background: #d4edda;
                    color: #155724;
                }
                .status-badge.warning {
                    background: #fff3cd;
                    color: #856404;
                }
                .status-badge.danger {
                    background: #f8d7da;
                    color: #721c24;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    color: #7f8c8d;
                    font-size: 14px;
                    border-top: 1px solid #e0e0e0;
                }
                .footer a {
                    color: #667eea;
                    text-decoration: none;
                }
                .footer a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🏥 NURSEEQUIP TRACK</h1>
                    <p>Nursing Equipment Tracking System</p>
                </div>
                <div class="content">
                    <h2>$title</h2>
                    <p>Hello, <strong>$userName</strong></p>
                    $content
                </div>
                <div class="footer">
                    <p>&copy; $year NURSEEQUIP TRACK. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                    <p><a href="mailto:support@nurseequip-track.com">Contact Support</a></p>
                </div>
            </div>
        </body>
        </html>
        HTML;
        
        return $template;
    }
    
    // Borrowing confirmation email
    public function sendBorrowConfirmation($userEmail, $userName, $equipmentName, $borrowedDate, $dueDate) {
        $content = <<<HTML
        <div class="info-box">
            <p>You have successfully borrowed the following equipment:</p>
        </div>
        
        <table>
            <tr>
                <th>Equipment</th>
                <th>Borrowed Date</th>
                <th>Due Date</th>
            </tr>
            <tr>
                <td><strong>$equipmentName</strong></td>
                <td>$borrowedDate</td>
                <td><span class="status-badge warning">$dueDate</span></td>
            </tr>
        </table>
        
        <p><strong>⚠️ Important Reminders:</strong></p>
        <ul>
            <li>Please return the equipment on or before the due date</li>
            <li>Late returns will affect your borrowing privileges</li>
            <li>You will receive email reminders 48 hours before the due date</li>
        </ul>
        
        <p>Thank you for using NURSEEQUIP TRACK!</p>
        HTML;
        
        $subject = "✅ Equipment Borrowed Confirmation - NURSEEQUIP TRACK";
        $htmlContent = $this->getTemplate("Equipment Borrowed Successfully", $content, $userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlContent);
    }
    
    // Return confirmation email
    public function sendReturnConfirmation($userEmail, $userName, $equipmentName, $returnDate, $status) {
        $statusClass = ($status == 'returned') ? 'success' : 'danger';
        $statusText = ($status == 'returned') ? 'On Time' : 'Late';
        
        $content = <<<HTML
        <div class="info-box">
            <p>You have returned the following equipment:</p>
        </div>
        
        <table>
            <tr>
                <th>Equipment</th>
                <th>Return Date</th>
                <th>Return Status</th>
            </tr>
            <tr>
                <td><strong>$equipmentName</strong></td>
                <td>$returnDate</td>
                <td><span class="status-badge $statusClass">$statusText</span></td>
            </tr>
        </table>
        
        <p>Thank you for returning the equipment!</p>
        HTML;
        
        $subject = "📦 Equipment Returned - NURSEEQUIP TRACK";
        $htmlContent = $this->getTemplate("Equipment Returned", $content, $userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlContent);
    }
    
    // Overdue reminder email
    public function sendOverdueReminder($userEmail, $userName, $equipmentName, $dueDate, $daysOverdue) {
        $content = <<<HTML
        <div class="info-box" style="border-left-color: #e74c3c;">
            <p style="color: #e74c3c;"><strong>⚠️ URGENT: Equipment Overdue</strong></p>
        </div>
        
        <table>
            <tr>
                <th>Equipment</th>
                <th>Due Date</th>
                <th>Days Overdue</th>
            </tr>
            <tr>
                <td><strong>$equipmentName</strong></td>
                <td>$dueDate</td>
                <td><span class="status-badge danger">$daysOverdue days</span></td>
            </tr>
        </table>
        
        <p><strong>Please return this equipment immediately to avoid:</strong></p>
        <ul>
            <li>Reduced borrowing privileges</li>
            <li>Academic penalties</li>
            <li>Account suspension</li>
        </ul>
        
        <a href="https://nurseequip-track.com/user/dashboard.php" class="button">Return Now</a>
        HTML;
        
        $subject = "⚠️ OVERDUE: Equipment Return Required - NURSEEQUIP TRACK";
        $htmlContent = $this->getTemplate("Equipment Overdue Notice", $content, $userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlContent);
    }
    
    // Due soon reminder email
    public function sendDueSoonReminder($userEmail, $userName, $equipmentName, $dueDate, $hoursLeft) {
        $content = <<<HTML
        <div class="info-box" style="border-left-color: #f39c12;">
            <p><strong>⏰ Friendly Reminder: Equipment Due Soon</strong></p>
        </div>
        
        <table>
            <tr>
                <th>Equipment</th>
                <th>Due Date</th>
                <th>Time Remaining</th>
            </tr>
            <tr>
                <td><strong>$equipmentName</strong></td>
                <td>$dueDate</td>
                <td><span class="status-badge warning">$hoursLeft hours</span></td>
            </tr>
        </table>
        
        <p>Please remember to return the equipment on time to avoid penalties.</p>
        
        <a href="https://nurseequip-track.com/user/dashboard.php" class="button">View Details</a>
        HTML;
        
        $subject = "⏰ REMINDER: Equipment Due Soon - NURSEEQUIP TRACK";
        $htmlContent = $this->getTemplate("Equipment Due Soon Reminder", $content, $userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlContent);
    }
    
    // Admin notification email
    public function sendAdminNotification($adminEmails, $type, $userName, $equipmentName, $details) {
        $icon = $type == 'borrowed' ? '📋' : ($type == 'returned' ? '📦' : '⚠️');
        $title = ucfirst($type) . " Equipment Notification";
        
        $content = <<<HTML
        <div class="info-box">
            <p><strong>$icon $type Notification</strong></p>
        </div>
        
        <table>
            <tr>
                <th>User</th>
                <th>Equipment</th>
                <th>Details</th>
            </tr>
            <tr>
                <td><strong>$userName</strong></td>
                <td>$equipmentName</td>
                <td>$details</td>
            </tr>
        </table>
        
        <p><a href="https://nurseequip-track.com/admin/dashboard.php" class="button">View in Admin Panel</a></p>
        HTML;
        
        $subject = "$icon Admin Notification: $type - NURSEEQUIP TRACK";
        $htmlContent = $this->getTemplate($title, $content, 'Administrator');
        
        return $this->sendEmail($adminEmails, $subject, $htmlContent);
    }
}
?>