<?php
require_once '../includes/functions.php';
redirectIfNotAdmin();

// Handle Add Equipment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $quantity = intval($_POST['quantity']);
        
        $query = "INSERT INTO equipment (name, description, total_quantity, available_quantity) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $name, $description, $quantity, $quantity);
        $stmt->execute();
        $success = "Equipment added successfully!";
    }
    
    elseif ($_POST['action'] == 'update') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $total_quantity = intval($_POST['total_quantity']);
        
        // Get current available quantity
        $current_query = "SELECT available_quantity FROM equipment WHERE id = ?";
        $current_stmt = $conn->prepare($current_query);
        $current_stmt->bind_param("i", $id);
        $current_stmt->execute();
        $current = $current_stmt->get_result()->fetch_assoc();
        
        // Calculate new available quantity (maintain borrowed items)
        $borrowed = $current['available_quantity'] - $total_quantity;
        $new_available = $total_quantity - $borrowed;
        
        $query = "UPDATE equipment SET name = ?, description = ?, total_quantity = ?, available_quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssiii", $name,$id, $description, $total_quantity, $new_available);
        $stmt->execute();
        $success = "Equipment updated successfully!";
    }
    
    elseif ($_POST['action'] == 'delete') {
        $id = intval($_POST['id']);
        
        // Check if equipment is currently borrowed
        $check_query = "SELECT id FROM borrowing_records WHERE equipment_id = ? AND status = 'borrowed'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Cannot delete equipment that is currently borrowed.";
        } else {
            $query = "DELETE FROM equipment WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $success = "Equipment deleted successfully!";
        }
    }
}

// Get all equipment
$equipment_query = "SELECT * FROM equipment ORDER BY name";
$equipment = $conn->query($equipment_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - NURSEEQUIP TRACK</title>
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
                <a href="manage_inventory.php" class="menu-item active">
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
                
                <!-- Add Equipment Button -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>MANAGE INVENTORY</h2>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Equipment
                    </button>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Equipment Table -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Equipment Name</th>
                                <th>Description</th>
                                <th>Total Quantity</th>
                                <th>Available Quantity</th>
                                <th>Borrowed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $equipment->fetch_assoc()): 
                                $borrowed = $item['total_quantity'] - $item['available_quantity'];
                            ?>
                                <tr>
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><?php echo $item['total_quantity']; ?></td>
                                    <td><?php echo $item['available_quantity']; ?></td>
                                    <td><?php echo $borrowed; ?></td>
                                    <td>
                                        <button class="btn btn-secondary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Equipment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Equipment</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Equipment Name</label>
                    <input type="text" name="name" required placeholder="Enter equipment name">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Enter description" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px;"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" required min="1" value="1">
                </div>
                
                <button type="submit" class="btn btn-primary">Add Equipment</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Equipment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Equipment</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Equipment Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px;"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Total Quantity</label>
                    <input type="number" name="total_quantity" id="edit_quantity" required min="1">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Equipment</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Equipment Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Equipment</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <p>Are you sure you want to delete "<span id="delete_name"></span>"?</p>
                <p style="color: var(--danger-color);">This action cannot be undone!</p>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
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
                <p>To provide nursing students with easy access to essential medical equipment for their practical training and clinical duties.</p>
                
                <h4>Our Vision</h4>
                <p>To become the leading equipment tracking system in nursing education, promoting responsibility and efficient resource management.</p>
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
        
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_description').value = item.description;
            document.getElementById('edit_quantity').value = item.total_quantity;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function openDeleteModal(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>