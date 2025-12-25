<?php
    // Kết nối cơ sở dữ liệu
    include './connectdb.php';

    // Xử lý thêm và sửa thông tin liên hệ
    if (isset($_GET['enableQuery']) && isset($_POST['submit'])) {
        $action = $_POST['submit'];

        // Thêm thông tin liên hệ
        if ($action == 'insert') {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $subject = trim($_POST['subject']);
            $message = trim($_POST['message']);

            // Thực hiện thêm mới liên hệ vào cơ sở dữ liệu
            $result = mysqli_query($con, "INSERT INTO contacts (full_name, email, subject, message) VALUES ('{$full_name}', '{$email}', '{$subject}', '{$message}')");

            if ($result) {
                echo "<script>
                    alert('Thêm liên hệ mới thành công!');
                    window.location.href = 'reply-manager.php';
                </script>";
            } else {
                echo "<script>
                    alert('Lỗi khi thêm liên hệ: " . mysqli_error($con) . "');
                    window.location.href = 'reply-manager.php';
                </script>";
            }
        } 

        // Sửa thông tin liên hệ
        elseif ($action == 'edit' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $subject = trim($_POST['subject']);
            $message = trim($_POST['message']);

            // Thực hiện sửa thông tin liên hệ vào cơ sở dữ liệu
            $result = mysqli_query($con, "UPDATE contacts SET full_name = '{$full_name}', email = '{$email}', subject = '{$subject}', message = '{$message}' WHERE id = '{$id}'");

            if ($result) {
                echo "<script>
                    alert('Sửa liên hệ thành công!');
                    window.location.href = 'reply-manager.php';
                </script>";
            } else {
                echo "<script>
                    alert('Lỗi khi sửa liên hệ: " . mysqli_error($con) . "');
                    window.location.href = 'reply-manager.php';
                </script>";
            }
        }

        // Đóng kết nối cơ sở dữ liệu
        mysqli_close($con);
    }
?>
<?php
include './sidebar.php';
include './container-header.php';
$keyWord = !empty($_GET['reply-search']) ? mysqli_real_escape_string($con, $_GET['reply-search']) : '';
$query = "SELECT * FROM `contacts` WHERE full_name REGEXP '{$keyWord}' OR email REGEXP '{$keyWord}' OR subject REGEXP '{$keyWord}' OR message REGEXP '{$keyWord}'";
$result = mysqli_query($con, $query);
?>

<script>
    eventForSideBar(6);
    setValueHeader("Phản hồi");
</script>

<div class="user">
    <div class="user__header">
    <form class="user-header__search" autocomplete="off" method="GET" action="">
            <input name="reply-search" type="text" placeholder="Từ tên, email, chủ đề,..." value="<?= htmlspecialchars($keyWord, ENT_QUOTES, 'UTF-8') ?>">
            <button name="button-search" type="submit" class="user-header-search__link">
                <span class="material-symbols-outlined">search</span>
            </button>
        </form>
        <?php
            include './connectdb.php';
            $result = mysqli_query($con, "select `UserID` from `user` order by `UserID` desc limit 1");
            mysqli_close($con);
            $row = mysqli_fetch_array($result);
            if($row != null) {
                $num = substr($row['UserID'], 6);
                $num++;
                $newUserIdId = 'US' . str_pad($num, 6, '0', STR_PAD_LEFT);
            } else {
                $newUserIdId = 'US000001';
            }
        ?>
    </div>

    <table class="user__table">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Tên người dùng</th>
                <th>Email</th>
                <th>Chủ đề</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            <!-- Load danh sách liên hệ từ DB -->
            <?php
                include './connectdb.php';
                
                // Khởi tạo các biến phân trang
                $item_per_page = 8;
                $current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($current_page - 1) * $item_per_page;

                // Tìm kiếm từ khóa (nếu có)
                $keyWord = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

                // Tính tổng số trang
                $total_records_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `contacts` WHERE full_name REGEXP '{$keyWord}' OR email REGEXP '{$keyWord}' OR subject REGEXP '{$keyWord}'");
                $total_records = mysqli_fetch_assoc($total_records_query)['total'];
                $num_page = ceil($total_records / $item_per_page);

                // Truy vấn dữ liệu liên hệ với phân trang và tìm kiếm
                $query = "SELECT * FROM `contacts` WHERE full_name REGEXP '{$keyWord}' OR email REGEXP '{$keyWord}' OR subject REGEXP '{$keyWord}' ORDER BY id DESC LIMIT {$item_per_page} OFFSET {$offset}";
                $result = mysqli_query($con, $query);
                
                if ($result->num_rows > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr id="<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6" style="padding: 16px;">Không có liên hệ nào để hiển thị!</td>
                    </tr>
                    <?php
                }
                mysqli_close($con);
            ?>
        </tbody>
    </table>



    <div class="paging">
        <?php
        if($current_page > 3) {
            ?>
                <a href="?page=1&user-search=<?= $keyWord ?>" class="paging__item paging__item--hover">First</a>
            <?php
        }
        for ($num = 1; $num <= $num_page; $num++) {
            if($num != $current_page) {
                if($num > $current_page - 3 && $num < $current_page + 3) {
                ?>
                    <a href="?page=<?= $num ?>&user-search=<?= $keyWord ?>" class="paging__item paging__item--hover"><?= $num ?></a>
                <?php
                }
            } else {
                ?>
                <a href="?page=<?= $num ?>&user-search=<?= $keyWord ?>" class="paging__item paging__item--active"><?= $num ?></a>
                <?php
            }
        }
        if($current_page < $num_page - 2) {
            ?>
                <a href="?page=<?= $num_page ?>&user-search=<?= $keyWord ?>" class="paging__item paging__item--hover">Last</a>
            <?php
        }
        ?>
    </div>
    <script>
        eventCloseModal('modal-user', 'modal-user__container', 'modal-user-container__close');
    </script>
</div>

<?php include './container-footer.php' ?>