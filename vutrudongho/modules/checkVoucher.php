<?php
header('Content-Type: application/json');
include 'connectDatabase.php';

// Accept VoucherName parameter and return JSON with voucher info
if (isset($_GET['VoucherName'])) {
    $voucherName = trim($_GET['VoucherName']);
    if ($voucherName === '') {
        echo json_encode(['success' => false, 'message' => 'Empty voucher name']);
        exit;
    }

    if ($conn = connectDatabase()) {
        $nameEsc = mysqli_real_escape_string($conn, $voucherName);
        $sql = "SELECT * FROM voucher WHERE VoucherName = '$nameEsc' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $voucher = mysqli_fetch_assoc($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Voucher not found']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    date_default_timezone_set('Asia/Ho_Chi_Minh');
    if ($voucher['Status'] == "1") {
        $now = date('Y-m-d');
        // inclusive date check: DateFrom <= now <= DateTo
        if (strtotime($voucher['DateFrom']) <= strtotime($now) && strtotime($now) <= strtotime($voucher['DateTo'])) {
            echo json_encode([
                'success' => true,
                'VoucherID' => $voucher['VoucherID'],
                'VoucherName' => $voucher['VoucherName'],
                'Discount' => $voucher['Discount'],
                'Unit' => $voucher['Unit']
            ]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Voucher expired or inactive']);
    exit;
}

// If no expected parameter provided
echo json_encode(['success' => false, 'message' => 'Missing parameter']);
exit;

?>