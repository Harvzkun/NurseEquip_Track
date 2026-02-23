<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nurseequip_track'); // Make sure this matches your database name

// Application configuration
define('SITE_NAME', 'NURSEEQUIP TRACK');
define('SITE_URL', 'http://localhost/Nurseequip-track/');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USER', 'wynardzaragoza@gmail.com');
define('SMTP_PASS', 'pztp vbjy yqdi cbqj');
define('SMTP_FROM', 'wynardzaragoza@gmail.com');
define('SMTP_FROM_NAME', 'NURSEEQUIP TRACK System');

// Borrowing rules
define('MAX_BORROW_PER_WEEK', 5);
define('BORROW_DURATION_TIME', 4);

// Session configuration
session_start();
?>