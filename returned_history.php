<?php
require_once '../includes/functions.php';
redirectIfNotAdmin();

// Get returned records
$query = "SELECT br.*, u.full_name, u.email, e.name as equipment_name,
          CASE 
              WHEN br.return_date <= br.due_date THEN 'On Time'
              ELSE 'Late'
          END as return_status
          FROM borrowing_records br 
          JOIN users u ON br.user_id = u.id 
          JOIN equipment e ON br.equipment_id = e.id 
          WHERE br.status = 'returned' OR br.status = 'overdue'
          ORDER BY br.return_date DESC";
$records = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returned History - NURSEEQUIP TRACK</title>
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
                <a href="returned_history.php" class="menu-item active">
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
                
                <h2>RETURNED HISTORY</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Equipment</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Returned Date</th>
                                <th>Return Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($records->num_rows == 0): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No returned items yet</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($record = $records->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                                        <td><?php echo htmlspecialchars($record['equipment_name']); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($record['borrowed_date'])); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($record['due_date'])); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($record['return_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $record['return_status'] == 'On Time' ? 'returned' : 'overdue'; ?>">
                                                <?php echo $record['return_status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
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
                <h4>Returned History</h4>
                <p>This page shows all items that have been returned, including whether they were returned on time or late.</p>
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