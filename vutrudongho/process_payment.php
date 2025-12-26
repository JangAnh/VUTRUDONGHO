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

// 3. Insert order & process all items in transaction
try {
    $conn->begin_transaction();
    
    if (!mysqli_query($conn, $sqlOrder)) {
        $conn->rollback();
        die("Lỗi khi thêm order: " . mysqli_error($conn));
    }

    // 4. Insert order_line for all items
    $allSuccess = true;
    foreach ($items as $row) {
        $pID = $row['ProductID'];
        $qty = $row['Quantity'];

        $pq = mysqli_query($conn, "SELECT PriceToSell, Discount, ProductName, Model, Color, Gender, ProductImg FROM product WHERE ProductID='$pID'");
        $prod = mysqli_fetch_assoc($pq);
        
        if (!$prod) {
            $allSuccess = false;
            break;
        }
        
        $price = $prod['PriceToSell'];
        
        // Escape product snapshot data
        $productName = mysqli_real_escape_string($conn, $prod['ProductName']);
        $model = mysqli_real_escape_string($conn, $prod['Model']);
        $color = mysqli_real_escape_string($conn, $prod['Color']);
        $gender = mysqli_real_escape_string($conn, $prod['Gender']);
        $productImg = mysqli_real_escape_string($conn, $prod['ProductImg']);
        $discount = (int) $prod['Discount'];

        if (!mysqli_query($conn, "
            INSERT INTO order_line (OrderID, ProductID, Quantity, UnitPrice, ProductName, Model, Color, Gender, ProductImg, Discount)
            VALUES ('$orderID', '$pID', '$qty', '$price', '$productName', '$model', '$color', '$gender', '$productImg', '$discount')
        ")) {
            $allSuccess = false;
            break;
        }
    }

    if (!$allSuccess) {
        $conn->rollback();
        die("Lỗi khi thêm order_line!");
    }

    // 5. Xóa giỏ hàng
    if (!mysqli_query($conn, "DELETE FROM cart WHERE UserID='$userID'")) {
        $conn->rollback();
        die("Lỗi khi xóa giỏ hàng!");
    }

    // 6. Commit transaction
    $conn->commit();

    // 7. Chuyển trang
    header("Location: ./my_order.php?success=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Lỗi giao dịch: " . $e->getMessage());
}

?>
