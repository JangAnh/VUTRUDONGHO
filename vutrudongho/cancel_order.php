<?php
// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vutrudongho";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Lấy mã đơn hàng từ yêu cầu POST
if (isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];

    // Xóa các bản ghi liên quan trong bảng `order_line`
    $sqlDeleteOrderLine = "DELETE FROM `order_line` WHERE OrderID = '$orderID'";
    if (!mysqli_query($conn, $sqlDeleteOrderLine)) {
        die("Error deleting order lines: " . mysqli_error($conn));
    }

    // Xóa đơn hàng từ bảng `order`
    $sqlDeleteOrder = "DELETE FROM `order` WHERE OrderID = '$orderID'";
    if (mysqli_query($conn, $sqlDeleteOrder)) {
        // Đặt thông báo thành công và chuyển hướng về trang `my_order.php`
        session_start();
        $_SESSION['cancelSuccess'] = true;
        header("Location: my_order.php");
        exit();
    } else {
        die("Error deleting order: " . mysqli_error($conn));
    }
} else {
    echo "Order ID not set.";
}

// Đóng kết nối cơ sở dữ liệu
mysqli_close($conn);
?>
