<?php
require_once '../includes/functions.php';
redirectIfNotAdmin();

// Get statistics
$stats = [];

// Total users
$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = $conn->query($users_query);
$stats['total_users'] = $users_result->fetch_assoc()['total'];

// Total equipment
$equip_query = "SELECT COUNT(*) as total FROM equipment";
$equip_result = $conn->query($equip_query);
$stats['total_equipment'] = $equip_result->fetch_assoc()['total'];

// Currently borrowed
$borrowed_query = "SELECT COUNT(*) as total FROM borrowing_records WHERE status = 'borrowed'";
$borrowed_result = $conn->query($borrowed_query);
$stats['currently_borrowed'] = $borrowed_result->fetch_assoc()['total'];

// Overdue items
$overdue_query = "SELECT COUNT(*) as total FROM borrowing_records WHERE status = 'overdue'";
$overdue_result = $conn->query($overdue_query);
$stats['overdue_items'] = $overdue_result->fetch_assoc()['total'];

// Get all users with their borrowing status
$users_list_query = "SELECT u.*, 
                     br.id as borrow_id, e.name as equipment_name, 
                     br.borrowed_date, br.due_date, br.status as borrow_status
                     FROM users u
                     LEFT JOIN borrowing_records br ON u.id = br.user_id AND br.status = 'borrowed'
                     LEFT JOIN equipment e ON br.equipment_id = e.id
                     ORDER BY u.full_name";
$users_list = $conn->query($users_list_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NURSEEQUIP TRACK</title>
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
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_inventory.php" class="menu-item">
                    <i class="fas fa-boxes"></i>
                    <span>Manage Inventory</span>
                </a>
                <a href="borrowed_records.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Borrowed Records</span>
                </a>
                <a href="returned_history.php" class="menu-item">
                    <i class="fas fa-history"></i>
                    <span>Returned History</span>
                </a>
                <a href="users_list.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Users List</span>
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
                                <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                            </div>
                            <span>Welcome, Admin <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        </div>
                        <a href="../auth/logout.php" class="btn btn-danger" style="padding: 8px 15px;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Equipment</h3>
                        <div class="stat-value"><?php echo $stats['total_equipment']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Currently Borrowed</h3>
                        <div class="stat-value"><?php echo $stats['currently_borrowed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Overdue Items</h3>
                        <div class="stat-value"><?php echo $stats['overdue_items']; ?></div>
                    </div>
                </div>
                
                <!-- Users List -->
                <h2>REGISTERED USERS</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact Number</th>
                                <th>Borrowed Equipment</th>
                                <th>Date Borrowed</th>
                                <th>Due Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_user = null;
                            while ($user = $users_list->fetch_assoc()): 
                                if ($current_user != $user['id']):
                                    $current_user = $user['id'];
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                    <td>
                                        <?php echo $user['equipment_name'] ? htmlspecialchars($user['equipment_name']) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <?php echo $user['borrowed_date'] ? date('M d, Y', strtotime($user['borrowed_date'])) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <?php echo $user['due_date'] ? date('M d, Y g:i A', strtotime($user['due_date'])) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['borrow_status']): ?>
                                            <span class="status-badge <?php echo $user['borrow_status']; ?>">
                                                <?php echo ucfirst($user['borrow_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge">Not yet borrowing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; endwhile; ?>
                        </tbody>
                    </table>
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
                <h4>Our Mission</h4>
                <p>To provide nursing students with easy access to essential medical equipment for their practical training and clinical duties, ensuring they have the tools they need to excel in their education.</p>
                
                <h4>Our Vision</h4>
                <p>To become the leading equipment tracking system in nursing education, promoting responsibility, organization, and efficient resource management among future healthcare professionals.</p>
                
                <h4>Admin Responsibilities</h4>
                <ul>
                    <li>Manage equipment inventory</li>
                    <li>Monitor borrowing activities</li>
                    <li>Track returned items</li>
                    <li>Send notifications to users</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        
        function showInfo() {
            document.getElementById('infoModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('infoModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('infoModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>