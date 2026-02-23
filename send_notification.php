<?php
require_once '../includes/functions.php';

// This API endpoint is called by cron jobs or manually by admins
// It checks for overdue items and sends notifications

header('Content-Type: application/json');

// Check if this is a cron job or admin request
$is_cron = isset($_GET['cron']) && $_GET['cron'] == 'true';
$is_admin = isAdmin();

if (!$is_cron && !$is_admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$notifications_sent = [];
$errors = [];

// Check for overdue items
$overdue_query = "SELECT br.*, u.email, u.full_name, e.name as equipment_name 
                  FROM borrowing_records br 
                  JOIN users u ON br.user_id = u.id 
                  JOIN equipment e ON br.equipment_id = e.id 
                  WHERE br.status = 'borrowed' 
                  AND br.due_date < NOW() 
                  AND (br.notification_sent = FALSE OR br.notification_sent IS NULL)";
$overdue_result = $conn->query($overdue_query);

while ($record = $overdue_result->fetch_assoc()) {
    // Send email to user
    $user_subject = "⚠️ OVERDUE: Equipment Return Required - NURSEEQUIP TRACK";
    $user_message = "Dear {$record['full_name']},\n\n";
    $user_message .= "This is a notification that you have overdue equipment:\n\n";
    $user_message .= "Equipment: {$record['equipment_name']}\n";
    $user_message .= "Borrowed Date: " . date('F j, Y g:i A', strtotime($record['borrowed_date'])) . "\n";
    $user_message .= "Due Date: " . date('F j, Y g:i A', strtotime($record['due_date'])) . "\n";
    $user_message .= "Current Time: " . date('F j, Y g:i A') . "\n\n";
    $user_message .= "Please return this equipment immediately to avoid penalties.\n";
    $user_message .= "Your borrowing privileges may be affected if you continue to have overdue items.\n\n";
    $user_message .= "Thank you for your prompt attention to this matter.\n";
    $user_message .= "NURSEEQUIP TRACK Administration";
    
    $user_email_sent = sendEmail($record['email'], $user_subject, $user_message);
    
    if ($user_email_sent) {
        $notifications_sent[] = "Overdue notification sent to {$record['full_name']}";
        
        // Update notification sent flag
        $update_query = "UPDATE borrowing_records SET notification_sent = TRUE WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $record['id']);
        $update_stmt->execute();
        
        // Update status to overdue
        $status_query = "UPDATE borrowing_records SET status = 'overdue' WHERE id = ?";
        $status_stmt = $conn->prepare($status_query);
        $status_stmt->bind_param("i", $record['id']);
        $status_stmt->execute();
    } else {
        $errors[] = "Failed to send email to {$record['full_name']}";
    }
}

// Check for due soon items (48 hours before due)
$duesoon_query = "SELECT br.*, u.email, u.full_name, e.name as equipment_name,
                  TIMESTAMPDIFF(HOUR, NOW(), br.due_date) as hours_until_due
                  FROM borrowing_records br 
                  JOIN users u ON br.user_id = u.id 
                  JOIN equipment e ON br.equipment_id = e.id 
                  WHERE br.status = 'borrowed' 
                  AND br.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
                  AND (br.notification_sent = FALSE OR br.notification_sent IS NULL)";
$duesoon_result = $conn->query($duesoon_query);

while ($record = $duesoon_result->fetch_assoc()) {
    // Send reminder email
    $subject = "⏰ REMINDER: Equipment Due Soon - NURSEEQUIP TRACK";
    $message = "Dear {$record['full_name']},\n\n";
    $message .= "This is a friendly reminder that you have equipment due soon:\n\n";
    $message .= "Equipment: {$record['equipment_name']}\n";
    $message .= "Due Date: " . date('F j, Y g:i A', strtotime($record['due_date'])) . "\n";
    $message .= "Time Remaining: " . floor($record['hours_until_due']) . " hours\n\n";
    $message .= "Please remember to return the equipment on time to avoid penalties.\n";
    $message .= "You can return it to the nursing equipment desk during operating hours.\n\n";
    $message .= "Thank you for using NURSEEQUIP TRACK!";
    
    $email_sent = sendEmail($record['email'], $subject, $message);
    
    if ($email_sent) {
        $notifications_sent[] = "Reminder sent to {$record['full_name']} for {$record['equipment_name']}";
        
        // Update notification sent flag
        $update_query = "UPDATE borrowing_records SET notification_sent = TRUE WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $record['id']);
        $update_stmt->execute();
    }
}

// Send summary to admins if any notifications were sent
if (count($notifications_sent) > 0 || count($errors) > 0) {
    $admin_query = "SELECT email FROM admins";
    $admin_result = $conn->query($admin_query);
    
    $admin_subject = "📊 Notification Summary - NURSEEQUIP TRACK";
    $admin_message = "Notification System Report\n";
    $admin_message .= "Generated: " . date('F j, Y g:i A') . "\n\n";
    
    if (count($notifications_sent) > 0) {
        $admin_message .= "✅ SUCCESSFUL NOTIFICATIONS:\n";
        foreach ($notifications_sent as $sent) {
            $admin_message .= "  • " . $sent . "\n";
        }
    }
    
    if (count($errors) > 0) {
        $admin_message .= "\n❌ ERRORS:\n";
        foreach ($errors as $error) {
            $admin_message .= "  • " . $error . "\n";
        }
    }
    
    while ($admin = $admin_result->fetch_assoc()) {
        sendEmail($admin['email'], $admin_subject, $admin_message);
    }
}

// Return response
echo json_encode([
    'success' => true,
    'notifications_sent' => $notifications_sent,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>