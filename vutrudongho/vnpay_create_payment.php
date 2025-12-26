<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * VNPAY Payment Gateway Redirect
 * Receives order_id and amount from place_order.php redirect
 */

// Get parameters from GET (redirected from place_order.php)
$amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// Validate amount and order_id
if ($amount <= 0 || empty($order_id)) {
    error_log("[vnpay_create_payment] Invalid amount or order_id: amount=$amount, order_id=$order_id");
    header("Location: ./checkout.php?payment=failed");
    exit;
}

require_once("./vnpay_config.php");

// Check if VNPAY config is loaded
if (!isset($vnp_Url) || !isset($vnp_Returnurl) || !isset($vnp_HashSecret) || !isset($vnp_TmnCode)) {
    error_log("[vnpay_create_payment] VNPAY config not properly loaded");
    header("Location: ./checkout.php?payment=failed");
    exit;
}

// Use order_id as transaction reference
$vnp_TxnRef = $order_id;
$vnp_Amount = $amount * 100;  // VNPAY expects amount in cents
$vnp_Locale = 'vn';  // Default locale
$vnp_BankCode = '';  // Optional bank code
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

// Set expiration time (15 minutes from now)
$expire = date('YmdHis', strtotime('+15 minutes'));

$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,      
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => "Thanh toan GD: " . $vnp_TxnRef,
    "vnp_OrderType" => "other",
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef,
    "vnp_ExpireDate" => $expire
);

if ($vnp_BankCode != "") {
    $inputData['vnp_BankCode'] = $vnp_BankCode;
}

ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnp_Url = $vnp_Url . "?" . $query;
if (isset($vnp_HashSecret)) {
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
}

error_log("[vnpay_create_payment] Redirecting to VNPAY: order_id=$vnp_TxnRef, amount=$amount");
header('Location: ' . $vnp_Url);
exit;

