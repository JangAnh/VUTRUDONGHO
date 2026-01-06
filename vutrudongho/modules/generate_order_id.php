<?php
/**
 * Generate unique OrderID for current session
 * Called by PayPal/VNPay payment gateways to get order reference
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
header('Content-Type: application/json');
session_start();

require_once './connectDatabase.php';

// Validate user is logged in
if (!isset($_SESSION['current_userID'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$conn = connectDatabase();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    // Generate new unique OrderID using MAX approach
    $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(OrderID, 3) AS UNSIGNED)) AS max_id FROM `order`");
    $row = mysqli_fetch_assoc($result);
    $nextId = ($row['max_id'] ?? 0) + 1;
    $newOrderID = 'OD' . str_pad($nextId, 8, '0', STR_PAD_LEFT);
    
    // Verify uniqueness (extra safety check)
    $check = mysqli_query($conn, "SELECT OrderID FROM `order` WHERE OrderID = '$newOrderID'");
    if (mysqli_num_rows($check) > 0) {
        // Collision - try next number
        $nextId++;
        $newOrderID = 'OD' . str_pad($nextId, 8, '0', STR_PAD_LEFT);
    }
    
    error_log("[generate_order_id] Generated OrderID: $newOrderID for UserID: " . $_SESSION['current_userID']);
    
    echo json_encode([
        'success' => true,
        'order_id' => $newOrderID
    ]);
    
} catch (Exception $e) {
    error_log("[generate_order_id] Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

closeDatabase($conn);
?>
