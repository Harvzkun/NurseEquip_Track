<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

echo "<h2>🔐 Password Hashing Migration</h2>";

// First, check if password_hash column exists and add it if not
$check_column = "SHOW COLUMNS FROM users LIKE 'password_hash'";
$result = $conn->query($check_column);
if ($result->num_rows == 0) {
    echo "Adding password_hash column to users table...<br>";
    $alter_users = "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER student_id";
    $conn->query($alter_users);
}

$check_admin_column = "SHOW COLUMNS FROM admins LIKE 'password_hash'";
$result = $conn->query($check_admin_column);
if ($result->num_rows == 0) {
    echo "Adding password_hash column to admins table...<br>";
    $alter_admins = "ALTER TABLE admins ADD COLUMN password_hash VARCHAR(255) NULL AFTER admin_id";
    $conn->query($alter_admins);
}

// Update users
echo "<h3>Updating Users...</h3>";
$user_query = "SELECT id, student_id FROM users WHERE password_hash IS NULL OR password_hash = ''";
$user_result = $conn->query($user_query);
$user_count = 0;

if ($user_result) {
    while ($user = $user_result->fetch_assoc()) {
        $hashed = password_hash($user['student_id'], PASSWORD_DEFAULT);
        $update = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("si", $hashed, $user['id']);
        if ($stmt->execute()) {
            $user_count++;
            echo "✅ User ID {$user['id']} updated<br>";
        }
    }
}
echo "✅ Updated $user_count users<br>";

// Update admins
echo "<h3>Updating Admins...</h3>";
$admin_query = "SELECT id, admin_id FROM admins WHERE password_hash IS NULL OR password_hash = ''";
$admin_result = $conn->query($admin_query);
$admin_count = 0;

if ($admin_result) {
    while ($admin = $admin_result->fetch_assoc()) {
        $hashed = password_hash($admin['admin_id'], PASSWORD_DEFAULT);
        $update = "UPDATE admins SET password_hash = ? WHERE id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("si", $hashed, $admin['id']);
        if ($stmt->execute()) {
            $admin_count++;
            echo "✅ Admin ID {$admin['id']} updated<br>";
        }
    }
}
echo "✅ Updated $admin_count admins<br>";

// Make columns NOT NULL after migration
echo "<h3>Finalizing...</h3>";
$conn->query("ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL");
$conn->query("ALTER TABLE admins MODIFY password_hash VARCHAR(255) NOT NULL");

echo "<p style='color:green; font-weight:bold; font-size:18px;'>✅ Password migration complete!</p>";
echo "<p>Users can now log in with their same credentials (student ID or admin ID).</p>";
echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
?>