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

    // Nếu cần, bạn có thể update DB tại đây
    // update status đơn hàng -> Paid

    header("Location: checkout.php?payment=success&order_id=" . $orderId);
    exit;
}


// ===== GIAO DỊCH THẤT BẠI =====
header("Location: checkout.php?payment=failed&order_id=" . $orderId);
exit;
