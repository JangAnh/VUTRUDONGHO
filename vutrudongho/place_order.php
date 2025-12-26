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

// 2. Insert ORDER & all order_line items in transaction
try {
    $conn->begin_transaction();
    
    if (!mysqli_query($conn, $sqlOrder)) {
        die("Lỗi khi thêm order: " . mysqli_error($conn));
    }

    // 3. Lấy giỏ hàng
    $cart = mysqli_query($conn, "SELECT * FROM cart WHERE UserID='$userID'");
    if (mysqli_num_rows($cart) == 0) {
        $conn->rollback();
        die("Giỏ hàng trống!");
    }

    // 4. Insert order_line for all items
    $allSuccess = true;
    while ($item = mysqli_fetch_assoc($cart)) {

        $productID = $item['ProductID'];

        // Lấy giá và giảm giá từ bảng product
        $queryProd = mysqli_query($conn, "
            SELECT PriceToSell, Discount, ProductName, Model, Color, Gender, ProductImg
            FROM product 
            WHERE ProductID = '$productID'
        ");

        if (mysqli_num_rows($queryProd) == 0) {
            $allSuccess = false;
            break;
        }

        $prod = mysqli_fetch_assoc($queryProd);

        $price = $prod['PriceToSell'] - ($prod['PriceToSell'] * $prod['Discount'] / 100);

        // Escape product snapshot data
        $productName = mysqli_real_escape_string($conn, $prod['ProductName']);
        $model = mysqli_real_escape_string($conn, $prod['Model']);
        $color = mysqli_real_escape_string($conn, $prod['Color']);
        $gender = mysqli_real_escape_string($conn, $prod['Gender']);
        $productImg = mysqli_real_escape_string($conn, $prod['ProductImg']);
        $discount = (int) $prod['Discount'];

        // Insert order_line with product snapshot
        $sqlLine = "
            INSERT INTO order_line (OrderID, ProductID, Quantity, UnitPrice, ProductName, Model, Color, Gender, ProductImg, Discount)
            VALUES ('$orderID', '$productID', '{$item['Quantity']}', '$price', '$productName', '$model', '$color', '$gender', '$productImg', '$discount')
        ";

        if (!mysqli_query($conn, $sqlLine)) {
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

    // 7. Điều hướng
    header("Location: ./my_order.php?success=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Lỗi giao dịch: " . $e->getMessage());
}

?>
