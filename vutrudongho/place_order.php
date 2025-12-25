<?php
session_start();
include '../vutrudongho/modules/connectDatabase.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: ./login.php");
    exit;
}

$conn = connectDatabase();

$userID        = $_SESSION['UserID'];
$shippingFee   = $_POST['ShippingFee'] ?? 0;
$orderDiscount = $_POST['OrderDiscount'] ?? 0;
$address       = $_POST['Address'] ?? '';
$paymentID     = $_POST['PaymentID'] ?? null;
$voucherID     = $_POST['VoucherID'] ?? null;
$total         = $_POST['Total'] ?? 0;

date_default_timezone_set('Asia/Ho_Chi_Minh');

// 1. Generate OrderID
function createOrderID($conn) {
    while (true) {
        $id = "OD" . rand(100000, 999999);
        $check = mysqli_query($conn, "SELECT * FROM `order` WHERE OrderID='$id'");
        if (mysqli_num_rows($check) == 0) return $id;
    }
}
$orderID = createOrderID($conn);

// 2. Insert ORDER
$sqlOrder = "
    INSERT INTO `order` 
    (OrderID, UserID, OrderDate, ShippingFee, OrderDiscount, OrderTotal, Address, PaymentID, VoucherID, OrderStatus)
    VALUES 
    ('$orderID', '$userID', NOW(), '$shippingFee', '$orderDiscount', '$total', '$address', '$paymentID', '$voucherID', 'PENDING')
";

if (!mysqli_query($conn, $sqlOrder)) {
    die("Lỗi khi thêm order: " . mysqli_error($conn));
}

// 3. Lấy giỏ hàng
$cart = mysqli_query($conn, "SELECT * FROM cart WHERE UserID='$userID'");
if (mysqli_num_rows($cart) == 0) {
    die("Giỏ hàng trống!");
}

// 4. Insert order_line (tính giá trực tiếp từ DB)
while ($item = mysqli_fetch_assoc($cart)) {

    $productID = $item['ProductID'];

    // Lấy giá và giảm giá từ bảng product
    $queryProd = mysqli_query($conn, "
        SELECT PriceToSell, Discount 
        FROM product 
        WHERE ProductID = '$productID'
    ");

    if (mysqli_num_rows($queryProd) == 0) {
        die("Không tìm thấy sản phẩm ID: $productID");
    }

    $prod = mysqli_fetch_assoc($queryProd);

    $price = $prod['PriceToSell'] - ($prod['PriceToSell'] * $prod['Discount'] / 100);

    // Insert order_line
    $sqlLine = "
        INSERT INTO order_line (OrderID, ProductID, Quantity, UnitPrice)
        VALUES ('$orderID', '$productID', '{$item['Quantity']}', '$price')
    ";

    if (!mysqli_query($conn, $sqlLine)) {
        die("Lỗi khi thêm order_line: " . mysqli_error($conn));
    }
}

// 5. Xóa giỏ hàng
mysqli_query($conn, "DELETE FROM cart WHERE UserID='$userID'");

// 6. Điều hướng
header("Location: ./my_order.php?success=1");
exit;

?>
