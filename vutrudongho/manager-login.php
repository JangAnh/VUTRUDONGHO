<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/admin-common.css">
    <link rel="stylesheet" href="./assets/css/admin-login.css">
    <title>Manager Login</title>
</head>
<body>
    <?php
    //Dang nhap
    if (isset($_GET['enableQuery']) && isset($_POST['submit'])) {
        session_start();
        include './connectdb.php';

        $username = trim($_POST['username']);
        $password = sha1(trim($_POST['password']));
        $success = 0;

    //     // Kiểm tra đầu vào có rỗng không
    //     if (!empty($username) && !empty($password)) {
    //         $stmt = $con->prepare("SELECT * FROM `manager` WHERE `Email` = ?");
    //         $stmt->bind_param("s", $username);
    //         $stmt->execute();
    //         $result = $stmt->get_result();
    //         $stmt->close();
    //         mysqli_close($con);

    //         if ($result->num_rows === 1) {
    //             $row = $result->fetch_assoc();

    //             // Kiểm tra mật khẩu
    //             if (password_verify($password, $row['Password'])) {
    //                 $_SESSION['ManagerID'] = $row['ManagerID'];
    //                 $_SESSION['Name'] = $row['Name'];
    //                 $_SESSION['Email'] = $row['Email'];
    //                 header("Location: brand-manager.php");
    //                 exit;
    //             } else {
    //                 $error_message = 'Email hoặc mật khẩu không hợp lệ!';
    //             }
    //         } else {
    //             $error_message = 'Email hoặc mật khẩu không hợp lệ!';
    //         }
    //     } else {
    //         $error_message = 'Vui lòng nhập đầy đủ thông tin!';
    //     }

    //     if (isset($error_message)) {
    //         echo "<script>
    //                 alert('$error_message');
    //                 window.location.href = 'manager-login.php';
    //             </script>";
    //     }
    // }

    $result = mysqli_query($con, "select * from `manager`");
    mysqli_close($con);

    while($row = mysqli_fetch_array($result)) {
        if($row['Email'] == $username && $row['Password'] == $password) {
            $_SESSION['AdminID'] = $row['AdminID'];
            $_SESSION['FullName'] = $row['FullName'];
            $_SESSION['Email'] = $row['Email'];
            $_SESSION['Password'] = $row['Password'];
            $success = 1;
            break;
        }
    }

        if($success == 1) {
            header("Location: product-manage.php");
        } else {
            echo "<script>
                    alert('Email hoặc mật khẩu không hợp lệ!');
                    window.location.href = 'manager-login.php';
                </script>";
        }
    }
?>

    <div class="wrapper">
        <div class="background"></div>
        <div class="container">
            <form class="login" method="POST" action="manager-login.php?enableQuery" onsubmit="return checkManagerLoginForm();">
                <p class="login__heading">Chào Mừng Bạn Đến Với Trang Quản Lý!</p>

                <label for="username">Email *</label>
                <input name="username" id="username" type="text">
                <p style="display: none;" class="err username">*Email không được để trống</p>

                <label for="password">Mật khẩu *</label>
                <input name="password" id="password" type="password">
                <p style="display: none;" class="err password">*Mật khẩu không được để trống</p>
                
                <button type="submit" name="submit" class="login__btn">Đăng nhập</button>
            </form>
        </div>
    </div>
    
    <script>
        function checkManagerLoginForm() {
            let valid = true;

            let username = document.getElementById('username');
            let username_err = document.querySelector('.err.username');
            let password = document.getElementById('password');
            let password_err = document.querySelector('.err.password');

            username_err.style.display = 'none';
            password_err.style.display = 'none';

            if (username.value.trim() === '') {
                username_err.style.display = 'block';
                valid = false;
            }

            if (password.value.trim() === '') {
                password_err.style.display = 'block';
                valid = false;
            }

            return valid;
        }
    </script>
</body>
</html>
