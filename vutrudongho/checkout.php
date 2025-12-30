<?php
// Xử lý trạng thái thanh toán
$paymentStatus = "";
$paymentMethod = $_GET['method'] ?? '';

if (isset($_GET['payment'])) {
    if ($_GET['payment'] === 'success') {
        $paymentStatus = "Thanh toán thành công";
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

        <?php if ($paymentStatus === "Thanh toán thành công"): ?>
            <div class="placement_body">
                <img src="assets/Img/icons/icons8-checkmark-200.png" alt="">
                <h2>Đặt hàng thành công!</h2>
                <p>Thanh toán qua <?php echo $paymentMethod === 'paypal' ? 'PayPal' : 'VNPay'; ?> đã hoàn tất.</p>
                <a style="text-decoration: none; color: white;" href="index.php">
                    <button>OK</button>
                </a>
            </div>

        <?php elseif ($paymentStatus === "Thanh toán thất bại" || $paymentStatus === "Dữ liệu giao dịch không hợp lệ (chữ ký sai)") : ?>
            <div class="placement_body">
                <img src="assets/Img/icons/icons8-cancel-200.png" alt="">
                <h2>Đã hủy thanh toán!</h2>
                <p>Cảm ơn bạn đã tin tưởng lunarveil.com</p>
                <a style="text-decoration: none; color: white;" href="cart.php">
                    <button>Quay lại giỏ hàng</button>
                </a>
            </div>

        <?php else: ?>
            <!-- Fallback: no payment param — treat as canceled/closed -->
            <div class="placement_body">
                <img src="assets/Img/icons/icons8-cancel-200.png" alt="">
                <h2>Thanh toán đã bị hủy</h2>
                <p>Bạn đã hủy thanh toán. Giỏ hàng của bạn vẫn còn nguyên.</p>
                <a style="text-decoration: none; color: white;" href="cart.php">
                    <button>Quay lại giỏ hàng</button>
                </a>
            </div>
        <?php endif; ?>

    </div>

    <div id="my-footer">
        <?php include("components/footer.php"); ?>
    </div>
</body>

</html>
