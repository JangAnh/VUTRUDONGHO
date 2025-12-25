<?php
// Thông tin kết nối cơ sở dữ liệu
$servername = "localhost"; 
$username = "root";    
$password = "";    
$dbname = "vutrudongho";


// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy dữ liệu từ form
$full_name = $_POST['full_name'];
$email = $_POST['email'];
$subject = $_POST['subject'];
$message = $_POST['message'];

// Chuẩn bị câu lệnh SQL để chèn dữ liệu
$sql = "INSERT INTO contacts (full_name, email, subject, message) VALUES (?, ?, ?, ?)";

// Sử dụng prepared statements để tránh SQL injection
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $full_name, $email, $subject, $message);

// Thực hiện câu lệnh
if ($stmt->execute()) {
    echo "Thông tin của bạn đã được gửi thành công.";
} else {
    echo "Lỗi khi gửi thông tin: " . $stmt->error;
}

// Đóng statement và kết nối
$stmt->close();
$conn->close();
?>
