<?php
require_once '../includes/functions.php';
redirectIfNotUser();

// Get all admins
$query = "SELECT full_name, email, contact_number, created_at FROM admins ORDER BY full_name";
$admins = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin List - NURSEEQUIP TRACK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>NURSEEQUIP TRACK</h3>
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="available_equipment.php" class="menu-item">
                    <i class="fas fa-boxes"></i>
                    <span>Available Equipment</span>
                </a>
                <a href="my_borrowed.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>My Borrowed</span>
                </a>
                <a href="admin_list.php" class="menu-item active">
                    <i class="fas fa-users-cog"></i>
                    <span>Admin List</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="content-wrapper">
                <!-- Top Bar -->
                <div class="top-bar">
                    <div class="top-bar-right">
                        <div class="info-icon" onclick="showInfo()">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </div>
                        <a href="../auth/logout.php" class="btn btn-danger" style="padding: 8px 15px;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <h2>ADMINISTRATORS</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email Address</th>
                                <th>Contact Number</th>
                                <th>Admin Since</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($admins->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No administrators found</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($admin = $admins->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($admin['email']); ?>" style="color: var(--secondary-color);">
                                                <?php echo htmlspecialchars($admin['email']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($admin['contact_number']); ?>" style="color: var(--secondary-color);">
                                                <?php echo htmlspecialchars($admin['contact_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('F j, Y', strtotime($admin['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Contact Information -->
                <div style="margin-top: 40px; padding: 20px; background: var(--light-bg); border-radius: 10px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">Need Help?</h3>
                    <p>If you encounter any issues with borrowing or returning equipment, please contact any administrator via email or phone.</p>
                    <p style="margin-top: 10px;">
                        <i class="fas fa-clock" style="color: var(--secondary-color);"></i>
                        Office Hours: Monday - Friday, 8:00 AM - 5:00 PM
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Info Modal -->
    <div id="infoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>About NURSEEQUIP TRACK</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <h4>Administrators</h4>
                <p>This page lists all system administrators who manage the equipment inventory and borrowing records.</p>
                
                <h4>Contacting Administrators</h4>
                <ul>
                    <li>Click on email addresses to send an email</li>
                    <li>Click on phone numbers to call</li>
                    <li>For urgent issues, please visit the nursing equipment office</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
        }
        
        function showInfo() {
            document.getElementById('infoModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('infoModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>