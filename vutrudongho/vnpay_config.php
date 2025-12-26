<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_TmnCode = "H9NCRVRB";
$vnp_HashSecret = "Y8TJ9A5S3PAYG4W1VX4S2LGPSJ1DAAAY";
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://localhost/vutrudongho/vutrudongho/vnpay_return.php";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
$apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";

$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));
?>
