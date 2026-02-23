<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$record_id = $data['record_id'] ?? 0;

if (!$record_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
    exit();
}

// Attempt to return equipment
$result = returnEquipment($record_id);
echo json_encode($result);
?>