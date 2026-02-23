<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mailer.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

function isUser() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'auth/login.php');
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit();
    }
}

function redirectIfNotUser() {
    if (!isUser()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit();
    }
}

/**
 * Verify user password
 * @param int $user_id User ID
 * @param string $password Password to verify
 * @return bool True if password is correct
 */
function verifyUserPassword($user_id, $password) {
    global $conn;
    $query = "SELECT password_hash FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        return password_verify($password, $user['password_hash']);
    }
    return false;
}

/**
 * Verify admin password
 * @param int $admin_id Admin ID
 * @param string $password Password to verify
 * @return bool True if password is correct
 */
function verifyAdminPassword($admin_id, $password) {
    global $conn;
    $query = "SELECT password_hash FROM admins WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        return password_verify($password, $admin['password_hash']);
    }
    return false;
}

// User functions
function getUserBorrowChance($user_id) {
    global $conn;
    
    // Check if we need to reset weekly chances
    $query = "SELECT borrow_chance, last_chance_reset FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $today = date('Y-m-d');
    if ($user['last_chance_reset'] != $today && date('w') == 1) { // Reset on Monday
        $reset_query = "UPDATE users SET borrow_chance = ?, last_chance_reset = ? WHERE id = ?";
        $reset_stmt = $conn->prepare($reset_query);
        $max_chance = MAX_BORROW_PER_WEEK;
        $reset_stmt->bind_param("isi", $max_chance, $today, $user_id);
        $reset_stmt->execute();
        return MAX_BORROW_PER_WEEK;
    }
    
    return $user['borrow_chance'];
}

function deductBorrowChance($user_id) {
    global $conn;
    $query = "UPDATE users SET borrow_chance = borrow_chance - 1 WHERE id = ? AND borrow_chance > 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// Equipment functions
function getAvailableEquipment() {
    global $conn;
    $query = "SELECT * FROM equipment WHERE available_quantity > 0 ORDER BY name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateEquipmentQuantity($equipment_id, $change) {
    global $conn;
    $query = "UPDATE equipment SET available_quantity = available_quantity + ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $change, $equipment_id);
    return $stmt->execute();
}

// Borrowing functions
function borrowEquipment($user_id, $equipment_id) {
    global $conn;
    
    // Check if user has borrow chance
    $chance = getUserBorrowChance($user_id);
    if ($chance <= 0) {
        return ['success' => false, 'message' => 'You have no borrowing chances left this week.'];
    }
    
    // Check equipment availability
    $equip_query = "SELECT available_quantity FROM equipment WHERE id = ?";
    $equip_stmt = $conn->prepare($equip_query);
    $equip_stmt->bind_param("i", $equipment_id);
    $equip_stmt->execute();
    $equip_result = $equip_stmt->get_result();
    $equipment = $equip_result->fetch_assoc();
    
    if ($equipment['available_quantity'] <= 0) {
        return ['success' => false, 'message' => 'Equipment not available.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create borrowing record
        $borrowed_date = date('Y-m-d H:i:s');
        $due_date = date('Y-m-d H:i:s', strtotime('+' . BORROW_DURATION_DAYS . ' days'));
        
        $insert_query = "INSERT INTO borrowing_records (user_id, equipment_id, borrowed_date, due_date) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiss", $user_id, $equipment_id, $borrowed_date, $due_date);
        $insert_stmt->execute();
        
        // Update equipment quantity
        updateEquipmentQuantity($equipment_id, -1);
        
        // Deduct borrow chance
        deductBorrowChance($user_id);
        
        $conn->commit();
        
        // Send notification
        sendBorrowNotification($user_id, $equipment_id, $borrowed_date, $due_date);
        
        return ['success' => true, 'message' => 'Equipment borrowed successfully.'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error borrowing equipment: ' . $e->getMessage()];
    }
}

function returnEquipment($record_id) {
    global $conn;
    
    $return_date = date('Y-m-d H:i:s');
    
    // Get record details
    $query = "SELECT * FROM borrowing_records WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    
    if (!$record) {
        return ['success' => false, 'message' => 'Record not found.'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Update return date and status
        $status = ($return_date <= $record['due_date']) ? 'returned' : 'overdue';
        $update_query = "UPDATE borrowing_records SET return_date = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $return_date, $status, $record_id);
        $update_stmt->execute();
        
        // Update equipment quantity
        updateEquipmentQuantity($record['equipment_id'], 1);
        
        $conn->commit();
        
        // Send notification
        sendReturnNotification($record['user_id'], $record['equipment_id'], $return_date, $status);
        
        return ['success' => true, 'message' => 'Equipment returned successfully.', 'status' => $status];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error returning equipment: ' . $e->getMessage()];
    }
}

// Notification functions
function sendBorrowNotification($user_id, $equipment_id, $borrowed_date, $due_date) {
    global $conn;
    
    // Initialize mailer
    $mailer = new Mailer();
    
    // Get user details
    $user_query = "SELECT email, full_name FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    
    // Get equipment name
    $equip_query = "SELECT name FROM equipment WHERE id = ?";
    $equip_stmt = $conn->prepare($equip_query);
    $equip_stmt->bind_param("i", $equipment_id);
    $equip_stmt->execute();
    $equipment = $equip_stmt->get_result()->fetch_assoc();
    
    // Format dates
    $borrowed_formatted = date('F j, Y g:i A', strtotime($borrowed_date));
    $due_formatted = date('F j, Y g:i A', strtotime($due_date));
    
    // Send email to user
    $user_sent = $mailer->sendBorrowConfirmation(
        $user['email'],
        $user['full_name'],
        $equipment['name'],
        $borrowed_formatted,
        $due_formatted
    );
    
    // Get all admin emails
    $admin_query = "SELECT email FROM admins";
    $admin_result = $conn->query($admin_query);
    $admin_emails = [];
    while ($admin = $admin_result->fetch_assoc()) {
        $admin_emails[] = $admin['email'];
    }
    
    // Send notification to all admins
    if (!empty($admin_emails)) {
        $details = "Borrowed on: $borrowed_formatted\nDue Date: $due_formatted";
        $mailer->sendAdminNotification(
            $admin_emails,
            'borrowed',
            $user['full_name'],
            $equipment['name'],
            $details
        );
    }
    
    return $user_sent;
}

function sendReturnNotification($user_id, $equipment_id, $return_date, $status) {
    global $conn;
    
    // Initialize mailer
    $mailer = new Mailer();
    
    // Get user details
    $user_query = "SELECT email, full_name FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    
    // Get equipment name
    $equip_query = "SELECT name FROM equipment WHERE id = ?";
    $equip_stmt = $conn->prepare($equip_query);
    $equip_stmt->bind_param("i", $equipment_id);
    $equip_stmt->execute();
    $equipment = $equip_stmt->get_result()->fetch_assoc();
    
    // Format return date
    $return_formatted = date('F j, Y g:i A', strtotime($return_date));
    
    // Send email to user
    $user_sent = $mailer->sendReturnConfirmation(
        $user['email'],
        $user['full_name'],
        $equipment['name'],
        $return_formatted,
        $status
    );
    
    // Get all admin emails
    $admin_query = "SELECT email FROM admins";
    $admin_result = $conn->query($admin_query);
    $admin_emails = [];
    while ($admin = $admin_result->fetch_assoc()) {
        $admin_emails[] = $admin['email'];
    }
    
    // Send notification to all admins
    if (!empty($admin_emails)) {
        $return_status = ($status == 'returned') ? 'On Time' : 'Late';
        $details = "Returned on: $return_formatted\nStatus: $return_status";
        $mailer->sendAdminNotification(
            $admin_emails,
            'returned',
            $user['full_name'],
            $equipment['name'],
            $details
        );
    }
    
    return $user_sent;
}

function checkOverdueItems() {
    global $conn;
    
    $mailer = new Mailer();
    $now = date('Y-m-d H:i:s');
    
    // Check for overdue items (items past due date)
    $overdue_query = "SELECT br.*, u.email, u.full_name, e.name as equipment_name,
                      DATEDIFF(NOW(), br.due_date) as days_overdue
                      FROM borrowing_records br 
                      JOIN users u ON br.user_id = u.id 
                      JOIN equipment e ON br.equipment_id = e.id 
                      WHERE br.status = 'borrowed' AND br.due_date < ? AND (br.notification_sent = FALSE OR br.notification_sent IS NULL)";
    
    $stmt = $conn->prepare($overdue_query);
    $stmt->bind_param("s", $now);
    $stmt->execute();
    $overdue_items = $stmt->get_result();
    
    while ($record = $overdue_items->fetch_assoc()) {
        // Format due date
        $due_formatted = date('F j, Y g:i A', strtotime($record['due_date']));
        
        // Send overdue notification to user
        $mailer->sendOverdueReminder(
            $record['email'],
            $record['full_name'],
            $record['equipment_name'],
            $due_formatted,
            $record['days_overdue']
        );
        
        // Update notification sent and status
        $update_query = "UPDATE borrowing_records SET notification_sent = TRUE, status = 'overdue' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $record['id']);
        $update_stmt->execute();
        
        // Notify admins about overdue item
        $admin_query = "SELECT email FROM admins";
        $admin_result = $conn->query($admin_query);
        $admin_emails = [];
        while ($admin = $admin_result->fetch_assoc()) {
            $admin_emails[] = $admin['email'];
        }
        
        if (!empty($admin_emails)) {
            $details = "Overdue by: {$record['days_overdue']} days\nDue Date: $due_formatted";
            $mailer->sendAdminNotification(
                $admin_emails,
                'overdue',
                $record['full_name'],
                $record['equipment_name'],
                $details
            );
        }
    }
    
    // Check for due soon items (within 48 hours)
    $duesoon_query = "SELECT br.*, u.email, u.full_name, e.name as equipment_name,
                      TIMESTAMPDIFF(HOUR, NOW(), br.due_date) as hours_until_due
                      FROM borrowing_records br 
                      JOIN users u ON br.user_id = u.id 
                      JOIN equipment e ON br.equipment_id = e.id 
                      WHERE br.status = 'borrowed' 
                      AND br.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
                      AND br.notification_sent = FALSE";
    
    $duesoon_result = $conn->query($duesoon_query);
    
    while ($record = $duesoon_result->fetch_assoc()) {
        // Format due date
        $due_formatted = date('F j, Y g:i A', strtotime($record['due_date']));
        
        // Send due soon reminder
        $mailer->sendDueSoonReminder(
            $record['email'],
            $record['full_name'],
            $record['equipment_name'],
            $due_formatted,
            $record['hours_until_due']
        );
        
        // Update notification sent
        $update_query = "UPDATE borrowing_records SET notification_sent = TRUE WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $record['id']);
        $update_stmt->execute();
    }
}

// Keep the old sendEmail function for backward compatibility but redirect to Mailer
function sendEmail($to, $subject, $message) {
    $mailer = new Mailer();
    
    // Convert plain text to HTML (simple conversion)
    $htmlMessage = nl2br($message);
    
    $template = $mailer->getTemplate($subject, "<p>" . $htmlMessage . "</p>", 'User');
    
    return $mailer->sendEmail($to, $subject, $template, $message);
}
?>