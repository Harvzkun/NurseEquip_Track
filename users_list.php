<?php
require_once '../includes/functions.php';
redirectIfNotAdmin();

// Get all users with their borrowing stats
$query = "SELECT u.*, 
          COUNT(CASE WHEN br.status = 'borrowed' THEN 1 END) as currently_borrowed,
          COUNT(CASE WHEN br.status = 'overdue' THEN 1 END) as overdue_items
          FROM users u
          LEFT JOIN borrowing_records br ON u.id = br.user_id
          GROUP BY u.id
          ORDER BY u.full_name";
$users = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users List - NURSEEQUIP TRACK</title>
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
                <a href="users_list.php" class="menu-item active">
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
                
                <h2>REGISTERED USERS</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Student ID</th>
                                <th>Contact Number</th>
                                <th>Borrow Chances</th>
                                <th>Currently Borrowed</th>
                                <th>Overdue Items</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <!-- STUDENT ID IS HASHED FOR SECURITY -->
                                        <span style="font-family: monospace; color: #7f8c8d;" title="Hidden for security - used as password">
                                            <i class="fas fa-lock" style="font-size: 12px; margin-right: 5px;"></i>
                                            <?php 
                                            // Show only first 2 and last 2 digits, rest as asterisks
                                            $id = $user['student_id'];
                                            if (strlen($id) > 4) {
                                                $hidden = substr($id, 0, 2) . str_repeat('*', strlen($id) - 4) . substr($id, -2);
                                            } else {
                                                $hidden = str_repeat('*', strlen($id));
                                            }
                                            echo $hidden;
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <?php echo $user['borrow_chance']; ?>/<?php echo MAX_BORROW_PER_WEEK; ?>
                                            <div style="display: flex; gap: 2px;">
                                                <?php for($i = 1; $i <= MAX_BORROW_PER_WEEK; $i++): ?>
                                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $i <= $user['borrow_chance'] ? '#27ae60' : '#e74c3c'; ?>;"></div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $user['currently_borrowed']; ?></td>
                                    <td>
                                        <?php if ($user['overdue_items'] > 0): ?>
                                            <span class="status-badge overdue"><?php echo $user['overdue_items']; ?> overdue</span>
                                        <?php else: ?>
                                            <span class="status-badge returned">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
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
                <h4>Security Features</h4>
                <p>To protect user privacy and security:</p>
                <ul>
                    <li>Student IDs are partially hidden in admin view</li>
                    <li>Passwords are hashed using bcrypt</li>
                    <li>Full IDs are only visible to the user</li>
                    <li>All data is encrypted in transmission</li>
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