<?php
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isUser()) {
        header('Location: ../user/dashboard.php');
    } else {
        header('Location: ../admin/dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $login_type = $_POST['login_type']; // 'user' or 'admin'
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if ($login_type == 'user') {
            // Get user by email only
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
        } else {
            // Get admin by email only
            $query = "SELECT * FROM admins WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify the password against the hash
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct
                if ($login_type == 'user') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    header('Location: ../user/dashboard.php');
                } else {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['admin_email'] = $user['email'];
                    header('Location: ../admin/dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NURSEEQUIP TRACK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>NURSEEQUIP TRACK</h2>
                <p>Login to your account</p>
            </div>
            
            <div class="auth-tabs">
                <div class="auth-tab" onclick="location.href='register.php'">Student</div>
                <div class="auth-tab" onclick="location.href='admin_register.php'">Admin</div>
                <div class="auth-tab active" onclick="location.href='login.php'">Login</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="login_type">Login as</label>
                    <select id="login_type" name="login_type" class="form-control" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px;">
                        <option value="user">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <small style="color: #7f8c8d;">Use your Student ID or Admin ID as password</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                Don't have an account? <a href="register.php" style="color: var(--secondary-color);">Register as Student</a><br>
                <a href="admin_register.php" style="color: var(--secondary-color);">Register as Admin</a>
            </p>
        </div>
    </div>
</body>
</html>