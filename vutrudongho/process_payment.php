<?php
session_start();
include './modules/connectDatabase.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: ./login.php");
    exit;
}

$userID     = $_SESSION['UserID'];
$address    = $_POST['address'] ?? '';
$paymentID  = $_POST['paymentID'] ?? null;

$voucherID = "";
$orderDiscount = 0;
$shippingFee = 0;

$conn = connectDatabase();

// 1. Lấy giỏ hàng
$cart = mysqli_query($conn, "SELECT * FROM cart WHERE UserID='$userID'");
if (mysqli_num_rows($cart) == 0) {
    header("Location: cart.php");
    exit;
}

// Tính tổng tiền
$orderTotal = 0;
$items = [];

while ($row = mysqli_fetch_assoc($cart)) {
    $items[] = $row;
    $productID = $row['ProductID'];
    $qty = $row['Quantity'];

    $priceQ = mysqli_query($conn, "SELECT PriceToSell FROM product WHERE ProductID='$productID'");
    $price = mysqli_fetch_assoc($priceQ)['PriceToSell'];

    $orderTotal += $price * $qty;
}

$orderTotal = $orderTotal + $shippingFee - $orderDiscount;

// 2. Tạo OrderID
function createOrderID($conn) {
    while (true) {
        $id = "O" . rand(100000, 999999);
        $check = mysqli_query($conn, "SELECT * FROM `order` WHERE OrderID='$id'");
        if (mysqli_num_rows($check) == 0) return $id;
    }
}
$orderID = createOrderID($conn);

// 3. Insert order
$sqlOrder = "
    INSERT INTO `order` 
    (OrderID, UserID, OrderDate, ShippingFee, OrderDiscount, OrderTotal, Address, PaymentID, VoucherID, OrderStatus)
    VALUES 
    ('$orderID', '$userID', NOW(), '$shippingFee', '$orderDiscount', '$orderTotal', '$address', '$paymentID', '$voucherID', 'PENDING')
";

mysqli_query($conn, $sqlOrder);

// 4. Insert order_line
foreach ($items as $row) {
    $pID = $row['ProductID'];
    $qty = $row['Quantity'];

    $pq = mysqli_query($conn, "SELECT PriceToSell FROM product WHERE ProductID='$pID'");
    $price = mysqli_fetch_assoc($pq)['PriceToSell'];

    mysqli_query($conn, "
        INSERT INTO order_line (OrderID, ProductID, Quantity, UnitPrice)
        VALUES ('$orderID', '$pID', '$qty', '$price')
    ");
}

// 5. Xóa giỏ hàng
mysqli_query($conn, "DELETE FROM cart WHERE UserID='$userID'");

// 6. Chuyển trang
header("Location: ./my_order.php?success=1");
exit;

?>
