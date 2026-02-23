<?php
require_once '../includes/functions.php';
redirectIfNotUser();

$user_id = $_SESSION['user_id'];
$borrow_chance = getUserBorrowChance($user_id);
$equipment = getAvailableEquipment();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Equipment - NURSEEQUIP TRACK</title>
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
                <a href="available_equipment.php" class="menu-item active">
                    <i class="fas fa-boxes"></i>
                    <span>Available Equipment</span>
                </a>
                <a href="my_borrowed.php" class="menu-item">
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
                
                <!-- Borrow Chance Indicator -->
                <div class="borrow-chance">
                    <span>Your borrowing chances this week:</span>
                    <div class="chance-dots">
                        <?php for($i = 1; $i <= MAX_BORROW_PER_WEEK; $i++): ?>
                            <div class="dot <?php echo $i <= $borrow_chance ? 'active' : 'expired'; ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <span>(<?php echo $borrow_chance; ?> remaining)</span>
                </div>
                
                <?php if ($borrow_chance <= 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        You have used all your borrowing chances for this week. 
                        New chances will be available on Monday.
                    </div>
                <?php endif; ?>
                
                <h2>AVAILABLE EQUIPMENT & ITEMS</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Description</th>
                                <th>Available Quantity</th>
                                <th>Total Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No equipment available at the moment</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipment as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo $item['available_quantity']; ?></td>
                                        <td><?php echo $item['total_quantity']; ?></td>
                                        <td>
                                            <?php if ($borrow_chance > 0 && $item['available_quantity'] > 0): ?>
                                                <button class="btn btn-primary" onclick="borrowItem(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-hand-holding"></i> Borrow
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <?php 
                                                    if ($borrow_chance <= 0) {
                                                        echo 'No Chances Left';
                                                    } else {
                                                        echo 'Out of Stock';
                                                    }
                                                    ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
                <h4>Borrowing Rules</h4>
                <ul>
                    <li>You can borrow up to <?php echo MAX_BORROW_PER_WEEK; ?> items per week</li>
                    <li>Items must be returned within <?php echo BORROW_DURATION_TIME; ?> days</li>
                    <li>Late returns will affect your borrowing chances</li>
                    <li>Chances reset every Monday</li>
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
        
        function borrowItem(equipmentId) {
            if (confirm('Are you sure you want to borrow this item?')) {
                fetch('../api/borrow_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        equipment_id: equipmentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Success! ' + data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error borrowing item. Please try again.');
                });
            }
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>