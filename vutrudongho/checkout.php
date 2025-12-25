<?php
// Xử lý trạng thái thanh toán VNPay (nếu có)
$paymentStatus = "";

if (isset($_GET['payment'])) {
    if ($_GET['payment'] === 'success') {
        $paymentStatus = "Thanh toán VNPay thành công";
    } elseif ($_GET['payment'] === 'failed') {
        $paymentStatus = "Thanh toán thất bại";
    } elseif ($_GET['payment'] === 'invalid_signature') {
        $paymentStatus = "Dữ liệu giao dịch không hợp lệ (chữ ký sai)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/CSS/checkout.css">
    <link rel="stylesheet" href="assets/CSS/header.css">
    <link rel="stylesheet" href="assets/CSS/footer.css">
    <title>Checkout</title>
</head>

<body>
    <div id="bar-header">
        <?php include("components/header.php"); ?>
    </div>

    <div class="main_container">

        <?php if ($paymentStatus !== ""): ?>
            
        <?php endif; ?>

        <div class="placement_body">
            <img src="assets/Img/icons/icons8-checkmark-200.png" alt="">
            <h2>Đặt hàng thành công!</h2>
            <p>Cảm ơn bạn đã tin tưởng lunarveil.com</p>
            <a style="text-decoration: none; color: white;" href="index.php">
                <button>OK</button>
            </a>
        </div>

    </div>

    <div id="my-footer">
        <?php include("components/footer.php"); ?>
    </div>
</body>

</html>
