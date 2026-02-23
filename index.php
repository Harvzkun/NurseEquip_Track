<?php
require_once __DIR__ . '/includes/functions.php';

// Redirect to appropriate dashboard if logged in
if (isLoggedIn()) {
    if (isUser()) {
        header('Location: user/dashboard.php');
    } else {
        header('Location: admin/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NURSEEQUIP TRACK - Nursing Equipment Tracking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 600px;">
            <div class="auth-header">
                <h2>NURSEEQUIP TRACK</h2>
                <p>Nursing Equipment Tracking System</p>
            </div>
            
            <div style="text-align: center; margin: 40px 0;">
                <i class="fas fa-stethoscope" style="font-size: 80px; color: var(--secondary-color); margin-bottom: 20px;"></i>
                <h3 style="color: var(--primary-color); margin-bottom: 20px;">Welcome to NURSEEQUIP TRACK</h3>
                <p style="color: #7f8c8d; margin-bottom: 30px;">
                    A comprehensive equipment tracking system designed specifically for nursing students. 
                    Borrow, track, and manage medical equipment with ease.
                </p>
            </div>
            
            <div style="display: flex; gap: 20px; justify-content: center;">
                <a href="auth/login.php" class="btn btn-primary" style="padding: 15px 30px;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="auth/register.php" class="btn btn-secondary" style="padding: 15px 30px;">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
            
            <div style="margin-top: 40px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
                <div>
                    <i class="fas fa-boxes" style="font-size: 30px; color: var(--secondary-color);"></i>
                    <h4>Track Equipment</h4>
                    <p style="font-size: 12px;">Monitor available equipment in real-time</p>
                </div>
                <div>
                    <i class="fas fa-clock" style="font-size: 30px; color: var(--secondary-color);"></i>
                    <h4>Due Date Alerts</h4>
                    <p style="font-size: 12px;">Get notified before items are due</p>
                </div>
                <div>
                    <i class="fas fa-bell" style="font-size: 30px; color: var(--secondary-color);"></i>
                    <h4>Email Notifications</h4>
                    <p style="font-size: 12px;">Receive updates on all transactions</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>