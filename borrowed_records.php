<?php
require_once '../includes/functions.php';
redirectIfNotAdmin();

// Handle return action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_id'])) {
    $record_id = intval($_POST['return_id']);
    $result = returnEquipment($record_id);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get all borrowed records
$query = "SELECT br.*, u.full_name, u.email, u.contact_number, e.name as equipment_name 
          FROM borrowing_records br 
          JOIN users u ON br.user_id = u.id 
          JOIN equipment e ON br.equipment_id = e.id 
          WHERE br.status = 'borrowed' OR br.status = 'overdue'
          ORDER BY br.due_date ASC";
$records = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Records - NURSEEQUIP TRACK</title>
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
                <a href="borrowed_records.php" class="menu-item active">
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
                
                <h2>CURRENTLY BORROWED EQUIPMENT</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Contact</th>
                                <th>Equipment</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($records->num_rows == 0): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No borrowed items at the moment</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($record = $records->fetch_assoc()): 
                                    $now = time();
                                    $due = strtotime($record['due_date']);
                                    $diff = $due - $now;
                                    $days_left = ceil($diff / (60 * 60 * 24));
                                    
                                    if ($diff <= 0) {
                                        $status = 'overdue';
                                        $status_text = 'Overdue';
                                    } elseif ($days_left <= 2) {
                                        $status = 'due-soon';
                                        $status_text = 'Due Soon';
                                    } else {
                                        $status = 'borrowed';
                                        $status_text = 'Borrowed';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['contact_number']); ?></td>
                                        <td><?php echo htmlspecialchars($record['equipment_name']); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($record['borrowed_date'])); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($record['due_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="return_id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Mark this item as returned?')">
                                                    <i class="fas fa-check"></i> Return
                                                </button>
                                            </form>
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
                <h4>Borrowed Records</h4>
                <p>This page shows all currently borrowed equipment. You can mark items as returned when students bring them back.</p>
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