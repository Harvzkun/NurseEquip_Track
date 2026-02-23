<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isUser()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$equipment_id = $data['equipment_id'] ?? 0;

if (!$equipment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
    exit();
}

// Attempt to borrow equipment
$result = borrowEquipment($_SESSION['user_id'], $equipment_id);
echo json_encode($result);
?>