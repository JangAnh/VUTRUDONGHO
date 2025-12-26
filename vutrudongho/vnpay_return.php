<?php
require_once("./vnpay_config.php");

// Nhận SecureHash và build lại chuỗi kiểm tra tính hợp lệ
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? "";
$inputData = [];

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);

$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// Lấy mã phản hồi
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? "";
$orderId = $_GET['vnp_TxnRef'] ?? "";


// ===== KIỂM TRA CHỮ KÝ HỢP LỆ =====
if ($secureHash !== $vnp_SecureHash) {

    // Chữ ký sai → KHÔNG tin tưởng giao dịch → báo lỗi
    header("Location: checkout.php?payment=invalid_signature");
    exit;
}


// ===== GIAO DỊCH THÀNH CÔNG =====
if ($vnp_ResponseCode === "00") {
    // Payment successful - now update inventory and clear cart
    include_once 'modules/connectDatabase.php';
    $conn = connectDatabase();
    
    if ($conn && !empty($orderId)) {
        $orderIdEsc = mysqli_real_escape_string($conn, $orderId);
        
        try {
            $conn->begin_transaction();
            
            // Update order status to S02 (Đã xác nhận)
            mysqli_query($conn, "UPDATE `order` SET OrderStatus='S02' WHERE OrderID='$orderIdEsc'");
            
            // Get UserID from order
            $orderResult = mysqli_query($conn, "SELECT UserID FROM `order` WHERE OrderID='$orderIdEsc'");
            if ($orderResult && mysqli_num_rows($orderResult) > 0) {
                $order = mysqli_fetch_array($orderResult);
                $userID = $order['UserID'];
                $userIDEsc = mysqli_real_escape_string($conn, $userID);
                
                // Get all order_line items and update inventory
                $lineResult = mysqli_query($conn, "SELECT ProductID, Quantity FROM order_line WHERE OrderID='$orderIdEsc'");
                if ($lineResult) {
                    while ($line = mysqli_fetch_array($lineResult)) {
                        $productID = mysqli_real_escape_string($conn, $line['ProductID']);
                        $quantity = (int)$line['Quantity'];
                        
                        // Get current product quantity
                        $qtyResult = mysqli_query($conn, "SELECT Quantity FROM product_quantity WHERE ProductID='$productID' ORDER BY Date DESC LIMIT 1");
                        if ($qtyResult && mysqli_num_rows($qtyResult) > 0) {
                            $qtyRow = mysqli_fetch_array($qtyResult);
                            $currentQty = (int)$qtyRow['Quantity'];
                            $newQty = $currentQty - $quantity;
                            mysqli_query($conn, "INSERT INTO product_quantity (ProductID, Date, Quantity) VALUES ('$productID', NOW(), '$newQty')");
                            // Also update product.Quantity for display
                            mysqli_query($conn, "UPDATE product SET Quantity = '$newQty' WHERE ProductID = '$productID'");
                        }
                    }
                }
                
                // Clear cart for this user
                mysqli_query($conn, "DELETE FROM `cart` WHERE UserID='$userIDEsc'");
            }
            
            $conn->commit();
        } catch (Throwable $th) {
            error_log("[vnpay_return] Error on success: " . $th->getMessage());
            if (isset($conn)) $conn->rollback();
        }
    }
    
    header("Location: checkout.php?payment=success&order_id=" . $orderId);
    exit;
}


// ===== GIAO DỊCH THẤT BẠI =====
// Delete order when payment is cancelled or fails
// NOTE: Inventory is NOT modified because it was never updated during order creation
include_once 'modules/connectDatabase.php';
$conn = connectDatabase();

if ($conn && !empty($orderId)) {
    $orderIdEsc = mysqli_real_escape_string($conn, $orderId);
    
    try {
        $conn->begin_transaction();
        
        // Delete order_line items
        mysqli_query($conn, "DELETE FROM order_line WHERE OrderID='$orderIdEsc'");
        
        // Delete order
        mysqli_query($conn, "DELETE FROM `order` WHERE OrderID='$orderIdEsc'");
        
        $conn->commit();
    } catch (Throwable $th) {
        if (isset($conn)) $conn->rollback();
    }
}

header("Location: checkout.php?payment=failed&order_id=" . $orderId);
exit;
