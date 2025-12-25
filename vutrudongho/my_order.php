<?php
session_start();
require_once('lib_session.php');

// Kết nối đến cơ sở dữ liệu
$conn = mysqli_connect("localhost", "root", "", "vutrudongho");

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

// Lấy userID từ session
$userID = $_SESSION['current_userID'];

// Số lượng mục hiển thị trên mỗi trang
$limit = 3;

// Truy vấn cơ sở dữ liệu để lấy số lượng tổng mục
$sql = sprintf("SELECT * FROM `order` WHERE UserID = '%s'", mysqli_real_escape_string($conn, $userID));
$result = mysqli_query($conn, $sql);
$total_items = mysqli_num_rows($result);

// Tính toán tổng số trang
$total_pages = ceil($total_items / $limit);

// Xác định trang hiện tại
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;

// Tính toán vị trí bắt đầu và số lượng mục cần lấy
$start = ($current_page - 1) * $limit;

// Truy vấn cơ sở dữ liệu để lấy dữ liệu
$query = sprintf("SELECT * FROM `order` WHERE UserID = '%s' ORDER BY OderDate DESC LIMIT %d, %d", mysqli_real_escape_string($conn, $userID), $start, $limit);
$result = mysqli_query($conn, $query);
?>

$userID = $_SESSION['current_userID'];

$orderList = mysqli_query($conn, "
    SELECT * FROM `order` 
    WHERE UserID='$userID' 
    ORDER BY OderDate DESC
");

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Của Tôi</title>
    <link rel="shortcut icon" href="assets/Img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/my_order.css">
    <link rel="stylesheet" href="assets/CSS/header.css">
    <link rel="stylesheet" href="assets/CSS/footer.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        #bar-header {
            background-color: #11676a;
            color: #fff;
            padding: 10px;
        }

        #main-user {
            margin: 20px;
        }

        #tab-bar-user {
            background-color: #f4f4f4;
            padding: 10px;
        }

        #tab-bar-user p {
            font-weight: bold;
        }

        #primary3 {
            list-style: none;
            padding: 0;
        }

        #primary3 li {
            display: inline;
            margin-right: 10px;
        }

        #primary3 a {
            text-decoration: none;
            color: #11676a;
        }

        #content-user {
            padding: 20px;
            background-color: #f9f9f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        table td a {
            color: #11676a;
            text-decoration: none;
        }

        table td a:hover {
            text-decoration: underline;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 7px 15px;
            margin: 0 5px;
            text-decoration: none;
            color: #11676a;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .pagination a:hover {
            background-color: #11676a;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div id="bar-header">
        <?php include("components/header.php"); ?>
    </div>

    <!-- Main Content -->
    <div id="main-user">
        <div id="main-content">
            <div id="tab-bar-user">
                <p class="content-tab-bar-userr">Chào bạn, <?php echo $_SESSION['current_fullName']; ?>!</p>
                <ul id="primary3">
                    <li><a href="user_information.php">Thông tin tài khoản</a></li>
                    <li><a href="my_order.php?page=1">Quản lý đơn hàng</a></li>
                </ul>
            </div>

            <div id="content-user">
                <p>Tổng số đơn hàng (<?php echo $total_items; ?>)</p>
                <?php if ($total_items == 0) {
                    echo '<p>Nhấn <a href="product.php">vào đây</a> để mua hàng.</p>';
                } ?>

                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Ngày</th>
                            <th>Phí vận chuyển</th>
                            <th>Giảm giá</th>
                            <th>Tổng</th>
                            <th>Phương thức thanh toán</th>
                            <th>Tình trạng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    while ($row = mysqli_fetch_array($result)) {
                        $orderID = $row['OrderID'];
                        $orderDate = $row['OderDate'];
                        $shippingFee = $row['ShippingFee'];
                        $orderDiscount = $row['OrderDiscount'];
                        $orderTotal = $row['OrderTotal'];
                        $paymentID = $row['PaymentID'];
                        $orderStatus = $row['OrderStatus'];

                        // Xác định phương thức thanh toán
                        $phuongthucThanhToan = "";
                        switch ($paymentID) {
                            case "PA01": $phuongthucThanhToan = "Thanh toán khi nhận hàng"; break;
                            case "PA02": $phuongthucThanhToan = "Internet Banking"; break;
                            case "PA03": $phuongthucThanhToan = "Thẻ tín dụng/Ghi nợ"; break;
                            case "PA04": $phuongthucThanhToan = "Ví điện tử MoMo"; break;
                            case "PA05": $phuongthucThanhToan = "Ví điện tử ZaloPay"; break;
                            default: $phuongthucThanhToan = "VNPAY-QR";
                        }

                        // Xác định trạng thái đơn hàng
                        $trangThaiDonHang = "";
                        switch ($orderStatus) {
                            case "S01": $trangThaiDonHang = "Chưa xác nhận"; break;
                            case "S02": $trangThaiDonHang = "Đã xác nhận"; break;
                            case "S03": $trangThaiDonHang = "Đang giao hàng"; break;
                            case "S04": $trangThaiDonHang = "Đã giao hàng"; break;
                            default: $trangThaiDonHang = "Đã hủy";
                        }

                        // Hiển thị đơn hàng
                        echo '<tr>
                            <td>' . $orderID . '</td>
                            <td>' . $orderDate . '</td>
                            <td>' . number_format($shippingFee, 0, ',', '.') . ' $</td>
                            <td>' . number_format($orderDiscount, 0, ',', '.') . ' $</td>
                            <td>' . number_format($orderTotal, 0, ',', '.') . ' $</td>
                            <td>' . $phuongthucThanhToan . '</td>
                            <td>' . $trangThaiDonHang . '</td>
                            <td>
                                <a href="detail_my_order.php?id=' . $orderID . '">Xem chi tiết</a>';

                        if ($orderStatus != "S04" && $orderStatus != "S03" && $orderStatus != "S05") {
                            echo '<form action="cancel_order.php" method="POST" style="display:inline; margin-left:10px;">
                                <input type="hidden" name="orderID" value="' . $orderID . '">
                                <input type="submit" value="Hủy đơn hàng" onclick="return confirmCancel();">
                            </form>';
                        }

                        echo '</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php
                    for ($page = 1; $page <= $total_pages; $page++) {
                        echo '<a class="page-number-' . $page . '" href="?page=' . $page . '">' . $page . '</a> ';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="my-footer">
        <?php include("components/footer.php"); ?>
    </div>

    <script>
        function confirmCancel() {
            return confirm("Bạn có chắc chắn muốn hủy đơn hàng này?");
        }

        // Highlight current page in pagination
        var currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;
        var currentElement = document.querySelector('.page-number-' + currentPage);
        if (currentElement) {
            currentElement.style.backgroundColor = 'purple';
            currentElement.style.color = '#fff';
        }
    </script>

    <?php
    if (isset($_SESSION['orderSuccess'])) {
        echo "<script>
            Swal.fire({
                title: 'Thông báo!',
                text: 'Đặt hàng thành công!',
                icon: 'success',
                confirmButtonText: 'OK'
                confirmButtonColor: '#1cabb0'
            });
            </script>";
        unset($_SESSION['orderSuccess']);
    }
    ?>
</body>

</html>
