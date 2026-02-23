<?php
require_once '../includes/functions.php';
redirectIfNotUser();

$user_id = $_SESSION['user_id'];

// Get user's borrowing history
$query = "SELECT br.*, e.name as equipment_name 
          FROM borrowing_records br 
          JOIN equipment e ON br.equipment_id = e.id 
          WHERE br.user_id = ? 
          ORDER BY br.borrowed_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed_items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed - NURSEEQUIP TRACK</title>
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
                <a href="my_borrowed.php" class="menu-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span>My Borrowed</span>
                </a>
                <a href="admin_list.php" class="menu-item">
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
                
                <h2>MY BORROWED EQUIPMENT & ITEMS</h2>
                
                <!-- Currently Borrowed -->
                <h3>Currently Borrowed</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Time Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $has_current = false;
                            while ($item = $borrowed_items->fetch_assoc()): 
                                if ($item['status'] == 'borrowed' || $item['status'] == 'overdue'):
                                    $has_current = true;
                                    $now = time();
                                    $due = strtotime($item['due_date']);
                                    $diff = $due - $now;
                                    
                                    if ($diff <= 0) {
                                        $time_remaining = 'Overdue';
                                        $status_class = 'overdue';
                                        $status_text = 'Overdue';
                                    } else {
                                        $days = floor($diff / (60 * 60 * 24));
                                        $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
                                        
                                        if ($days > 0) {
                                            $time_remaining = $days . ' day' . ($days > 1 ? 's' : '') . ' left';
                                        } else {
                                            $time_remaining = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' left';
                                        }
                                        
                                        if ($days <= 2) {
                                            $status_class = 'due-soon';
                                            $status_text = 'Due Soon';
                                        } else {
                                            $status_class = 'borrowed';
                                            $status_text = 'Borrowed';
                                        }
                                    }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($item['borrowed_date'])); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($item['due_date'])); ?></td>
                                    <td><?php echo $time_remaining; ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                </tr>
                            <?php endif; endwhile; ?>
                            
                            <?php if (!$has_current): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">You have no currently borrowed items</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Returned History -->
                <h3 style="margin-top: 40px;">Returned History</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Returned Date</th>
                                <th>Return Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset pointer to fetch again
                            $stmt->execute();
                            $returned_items = $stmt->get_result();
                            
                            $has_returned = false;
                            while ($item = $returned_items->fetch_assoc()): 
                                if ($item['status'] == 'returned' || $item['status'] == 'overdue'):
                                    $has_returned = true;
                                    $return_status = ($item['return_date'] <= $item['due_date']) ? 'On Time' : 'Late';
                                    $status_class = ($return_status == 'On Time') ? 'returned' : 'overdue';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($item['borrowed_date'])); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($item['due_date'])); ?></td>
                                    <td><?php echo $item['return_date'] ? date('M d, Y g:i A', strtotime($item['return_date'])) : 'Not returned'; ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $return_status; ?></span></td>
                                </tr>
                            <?php endif; endwhile; ?>
                            
                            <?php if (!$has_returned): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No returned items yet</td>
                                </tr>
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
                <h4>My Borrowed Items</h4>
                <p>Track all your borrowed equipment here. Make sure to return items on time to maintain your borrowing privileges.</p>
                
                <h4>Status Meanings:</h4>
                <ul>
                    <li><span class="status-badge borrowed">Borrowed</span> - Item is currently with you</li>
                    <li><span class="status-badge due-soon">Due Soon</span> - Item is due within 48 hours</li>
                    <li><span class="status-badge overdue">Overdue</span> - Item is past due date</li>
                    <li><span class="status-badge returned">Returned On Time</span> - Returned before or on due date</li>
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