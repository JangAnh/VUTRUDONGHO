
<?php
//Hàm lọc các người truy cập
function updateOnlineUsers($conn) {
    $session_id = session_id();
    $timeout = 300; // Thời gian hết hạn phiên (giây)

    // Xóa các phiên cũ
    $sql = "DELETE FROM online_users WHERE last_activity < NOW() - INTERVAL $timeout SECOND";
    $conn->query($sql);

    // Kiểm tra xem phiên hiện tại đã tồn tại chưa
    $sql = "SELECT * FROM online_users WHERE session_id = '$session_id'";
    $result = $conn->query($sql);

    if ($result->num_rows == 0) {
        // Nếu chưa có, thêm phiên mới
        $sql = "INSERT INTO online_users (session_id) VALUES ('$session_id')";
        $conn->query($sql);
    } else {
        // Cập nhật thời gian hoạt động của phiên hiện tại
        $sql = "UPDATE online_users SET last_activity = NOW() WHERE session_id = '$session_id'";
        $conn->query($sql);
    }
}
?>
<?php
//hàm đếm người truy cập
function getOnlineUsersCount($conn) {
    $timeout = 300; // Thời gian hết hạn phiên (giây)
    $sql = "SELECT COUNT(*) AS online_count FROM online_users WHERE last_activity >= NOW() - INTERVAL $timeout SECOND";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['online_count'];
    } else {
        return 0;
    }
}
?>
<?php
//run
// session_start();
include 'conect_user.php'; // Kết nối cơ sở dữ liệu

updateOnlineUsers($conn);

// Sau đó, bạn có thể sử dụng hàm getOnlineUsersCount() để lấy số lượt khách đang truy cập
$online_users_count = getOnlineUsersCount($conn);
echo "Số lượt khách đang truy cập: " . $online_users_count;
?>
