<?php
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $admin_id = trim($_POST['admin_id']);
    $contact_number = trim($_POST['contact_number']);
    
    // Validate input
    if (empty($full_name) || empty($email) || empty($admin_id) || empty($contact_number)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM admins WHERE email = ? OR admin_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $email, $admin_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email or Admin ID already registered.';
        } else {
            // Hash the admin_id (which is used as password)
            $hashed_password = password_hash($admin_id, PASSWORD_DEFAULT);
            
            // Insert new admin with hashed password
            $insert_query = "INSERT INTO admins (full_name, email, admin_id, password_hash, contact_number) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssss", $full_name, $email, $admin_id, $hashed_password, $contact_number);
            
            if ($insert_stmt->execute()) {
                $success = 'Admin registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - NURSEEQUIP TRACK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>NURSEEQUIP TRACK</h2>
                <p>Create admin account</p>
            </div>
            
            <div class="auth-tabs">
                <div class="auth-tab" onclick="location.href='register.php'">Student</div>
                <div class="auth-tab active" onclick="location.href='admin_register.php'">Admin</div>
                <div class="auth-tab" onclick="location.href='login.php'">Login</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Enter admin full name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter admin email">
                </div>
                
                <div class="form-group">
                    <label for="admin_id">Admin ID Number</label>
                    <input type="text" id="admin_id" name="admin_id" required placeholder="Enter admin ID">
                    <small style="color: #7f8c8d;">This will be used as your password</small>
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" required placeholder="Enter contact number">
                </div>
                
                <button type="submit" class="btn btn-primary">Register Admin</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                Already have an account? <a href="login.php" style="color: var(--secondary-color);">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>