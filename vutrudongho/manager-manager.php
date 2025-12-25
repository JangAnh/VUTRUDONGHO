<?php
session_start();

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là admin
if(!isset($_SESSION['AdminID'])) {
    echo "<script>
            alert('Bạn không có quyền truy cập trang này.');
            window.location.href = 'sidebar.php';
        </script>";
    exit();
}

include './connectdb.php';
// Thêm quản lý mới
if (isset($_GET['enableQuery']) && isset($_POST['submit']) && $_POST['submit'] == 'insert') {
    $manager_id = $_POST['manager-id'];
    $manager_name = trim($_POST['manager-name']);
    $manager_email = trim($_POST['manager-email']);
    $manager_password = sha1(trim($_POST['manager-password']));
    $manager_status = $_POST['status'];

    // Kiểm tra đầu vào cơ bản
    if (empty($manager_name) || empty($manager_email) || empty($manager_password)) {
        echo "<script>
            alert('Vui lòng điền đầy đủ thông tin!');
            window.location.href = 'manager-manager.php';
        </script>";
        return;
    }


    // Kiểm tra email trùng lặp
    $stmt = $con->prepare("SELECT Email FROM `manager` WHERE Email = ?");
    $stmt->bind_param("s", $manager_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>
            alert('Thêm quản lý không thành công do email đã tồn tại trong hệ thống! Hãy thử một email khác!');
            window.location.href = 'manager-manager.php';
        </script>";
        $stmt->close();
        return;
    }
    $stmt->close();

    // Thêm quản lý mới
    $stmt = $con->prepare("INSERT INTO `manager` (`ManagerID`, `Name`, `Email`, `Password`, `Status`) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $manager_id, $manager_name, $manager_email, $manager_password, $manager_status);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        echo "<script>
            alert('Thêm quản lý mới có mã {$manager_id} thành công!');
            window.location.href = 'manager-manager.php';
        </script>";
    } else {
        echo "<script>
            alert('Có lỗi xảy ra, vui lòng thử lại sau!');
            window.location.href = 'manager-manager.php';
        </script>";
    }
}

// Sửa thông tin quản lý
if (isset($_GET['enableQuery']) && isset($_POST['submit']) && $_POST['submit'] == 'edit') {
    $manager_id = $_POST['manager-id'];
    $manager_name = trim($_POST['manager-name']);
    $manager_email = trim($_POST['manager-email']);
    $manager_password = sha1(trim($_POST['manager-password']));
    $manager_status = $_POST['status'];

    // Kiểm tra đầu vào cơ bản
    if (empty($manager_name) || empty($manager_email)) {
        echo "<script>
            alert('Vui lòng điền đầy đủ thông tin!');
            window.location.href = 'manager-manager.php';
        </script>";
        return;
    }

    // Kiểm tra email trùng lặp
    $stmt = $con->prepare("SELECT ManagerID, Email FROM `manager` WHERE Email = ? AND ManagerID != ?");
    $stmt->bind_param("ss", $manager_email, $manager_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>
            alert('Sửa quản lý không thành công do email đã tồn tại trong hệ thống! Hãy thử một email khác!');
            window.location.href = 'manager-manager.php';
        </script>";
        $stmt->close();
        return;
    }
    $stmt->close();

    // Cập nhật thông tin quản lý
    if (!empty($manager_password)) {
        $manager_password = password_hash($manager_password, PASSWORD_DEFAULT);
        $stmt = $con->prepare("UPDATE `manager` SET `Name` = ?, `Email` = ?, `Password` = ?, `Status` = ? WHERE `ManagerID` = ?");
        $stmt->bind_param($manager_name, $manager_email, $manager_password, $manager_status, $manager_id);
    } else {
        $stmt = $con->prepare("UPDATE `manager` SET `Name` = ?, `Email` = ?, `Status` = ? WHERE `ManagerID` = ?");
        $stmt->bind_param($manager_name, $manager_email, $manager_status, $manager_id);
    }

    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        echo "<script>
            alert('Sửa quản lý có mã {$manager_id} thành công!');
            window.location.href = 'manager-manager.php';
        </script>";
    } else {
        echo "<script>
            alert('Có lỗi xảy ra, vui lòng thử lại sau!');
            window.location.href = 'manager-manager.php';
        </script>";
    }
}

mysqli_close($con);
?>

<?php
include './sidebar.php';
include './container-header.php';
$keyWord = !empty($_GET['manager-search']) ? str_replace("\\", "", $_GET['manager-search']) : "";
?>

<script>
    eventForSideBar(6);
    setValueHeader("Quản Lý");
</script>

<div class="manager">
    <div class="manager__header">
        <form class="manager-header__search" autocomplete="off">
            <input name="manager-search" type="text" placeholder="Từ khóa tên, email,..." value="<?= $keyWord ?>">
            <button name="button-search" type="submit" class="manager-header-search__link"><span class="material-symbols-outlined">search</span></button>
        </form>
        <?php
        include './connectdb.php';
        $result = mysqli_query($con, "SELECT `ManagerID` FROM `manager` ORDER BY `ManagerID` DESC LIMIT 1");
        $row = mysqli_fetch_array($result);
        mysqli_close($con);
        if ($row != null) {
            $num = substr($row['ManagerID'], 6);
            $num++;
            $newManagerId = 'MG' . str_pad($num, 6, '0', STR_PAD_LEFT);
        } else {
            $newManagerId = 'MG000001';
        }
        ?>
        <button class="manager-header__insert" onclick="displayInsertManagerModal('<?= $newManagerId ?>');">Thêm quản lý</button>
    </div>

    <table class="manager__table">
        <thead>
            <th>Mã</th>
            <th>Tên quản lý</th>
            <th>Email</th>
            <th>Mật khẩu</th>
            <th>Trạng thái</th>
            <th>Sửa</th>
        </thead>
        <tbody>
            <!-- Load danh sách manager từ db -->
            <?php
            include './connectdb.php';
            $item_per_page = 8;
            $current_page = !empty($_GET['page']) ? $_GET['page'] : 1;
            $offset = ($current_page - 1) * $item_per_page;
            $records = mysqli_query($con, "SELECT * FROM `manager` WHERE Name REGEXP '{$keyWord}' OR Email REGEXP '{$keyWord}'");
            $num_page = ceil($records->num_rows / $item_per_page);

            $result = mysqli_query($con, "SELECT * FROM `manager` WHERE Name REGEXP '{$keyWord}' OR Email REGEXP '{$keyWord}' ORDER BY ManagerID DESC LIMIT {$item_per_page} OFFSET {$offset}");

            if ($result->num_rows > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    ?>
                    <tr id="<?= $row['ManagerID'] ?>">
                        <td><?= $row['ManagerID'] ?></td>
                        <td><?= $row['Name'] ?></td>
                        <td><?= $row['Email'] ?></td>
                        <td><?= $row['Password'] ?></td>
                        <td><?= $row['Status'] == 1 ? "Đang hoạt động" : "Đang khóa" ?></td>
                        <td onclick="displayEditManagerModal('<?= $row['ManagerID'] ?>');"><span class="brand-table__edit material-symbols-outlined">edit</span></td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="6" style="padding: 16px;">Không có quản lý nào để hiển thị!</td>
                </tr>
                <?php
            }
            mysqli_close($con);
            ?>
        </tbody>
    </table>

    <div class="paging">
        <?php
        if ($current_page > 3) {
            ?>
            <a href="?page=1&manager-search=<?= $keyWord ?>" class="paging__item paging__item--hover">First</a>
            <?php
        }
        for ($num = 1; $num <= $num_page; $num++) {
            if ($num != $current_page) {
                if ($num > $current_page - 3 && $num < $current_page + 3) {
                    ?>
                    <a href="?page=<?= $num ?>&manager-search=<?= $keyWord ?>" class="paging__item paging__item--hover"><?= $num ?></a>
                    <?php
                }
            } else {
                ?>
                <a href="?page=<?= $num ?>&manager-search=<?= $keyWord ?>" class="paging__item paging__item--active"><?= $num ?></a>
                <?php
            }
        }
        if ($current_page < $num_page - 2) {
            ?>
            <a href="?page=<?= $num_page ?>&manager-search=<?= $keyWord ?>" class="paging__item paging__item--hover">Last</a>
            <?php
        }
        ?>
    </div>

    <div class="modal-manager">
        <form class="modal-manager__container" action="manager-manager.php?enableQuery" method="POST" autocomplete="off">
            <div class="modal-manager-container__close">
                <span class="material-symbols-outlined">close</span>
            </div>
            <div class="modal-manager-container__content">
                <p class="modal-manager-container-content__heading">Thêm Quản Lý Mới</p>

                <label for="modal-manager-container-content-id">Mã</label>
                <input name="manager-id" type="text" id="modal-manager-container-content-id" readonly>

                <label for="modal-manager-container-content-name">Tên quản lý *</label>
                <input name="manager-name" type="text" id="modal-manager-container-content-name">
                <p style="display: none;" class="err modal-manager-container-content-name__err"></p>

                <label for="modal-manager-container-content-email">Email *</label>
                <input name="manager-email" type="text" id="modal-manager-container-content-email">
                <p style="display: none;" class="err modal-manager-container-content-email__err">*Trường này không được để trống</p>

                <label for="modal-manager-container-content-password">Mật khẩu *</label>
                <input name="manager-password" type="text" id="modal-manager-container-content-password">
                <p style="display: none;" class="err modal-manager-container-content-password__err">*Trường này không được để trống</p>

                <label for="modal-manager-container-content-status">Trạng thái *</label>
                <select name="status" id="modal-manager-container-content-status">
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Đang khóa</option>
                </select>
                <button type="submit" class="modal-manager-container-content__btn insert" name="submit" value="insert"  onclick="return checkUserForm('thêm');">Thêm</button>
                <button type="submit" class="modal-manager-container-content__btn edit" name="submit" value="edit" onclick="return checkUserForm('sửa');">Sửa</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Close modal on close button click
        document.querySelectorAll('.modal-manager-container__close').forEach(function (element) {
            element.addEventListener('click', function () {
                closeModal();
            });
        });

        // Close modal on outside click
        document.querySelectorAll('.modal-manager').forEach(function (element) {
            element.addEventListener('click', function (event) {
                if (event.target === element) {
                    closeModal();
                }
            });
        });
    });

    function displayInsertManagerModal(newManagerId) {
        document.querySelector('.modal-manager-container-content__heading').textContent = 'Thêm Quản Lý Mới';
        document.querySelector('button[value="insert"]').style.display = 'block';
        document.querySelector('button[value="edit"]').style.display = 'none';

        document.querySelector('#modal-manager-container-content-id').value = newManagerId;
        document.querySelector('#modal-manager-container-content-name').value = '';
        document.querySelector('#modal-manager-container-content-email').value = '';
        document.querySelector('#modal-manager-container-content-password').value = '';
        document.querySelector('#modal-manager-container-content-status').value = '1';

        openModal();
    }

    function displayEditManagerModal(managerId) {
        document.querySelector('.modal-manager-container-content__heading').textContent = 'Sửa Thông Tin Quản Lý';
        document.querySelector('button[value="insert"]').style.display = 'none';
        document.querySelector('button[value="edit"]').style.display = 'block';

        var row = document.getElementById(managerId);
        document.querySelector('#modal-manager-container-content-id').value = row.cells[0].textContent;
        document.querySelector('#modal-manager-container-content-name').value = row.cells[1].textContent;
        document.querySelector('#modal-manager-container-content-email').value = row.cells[2].textContent;
        document.querySelector('#modal-manager-container-content-password').value = row.cells[3].textContent;
        document.querySelector('#modal-manager-container-content-status').value = row.cells[4].textContent === 'Đang hoạt động' ? '1' : '0';

        openModal();
    }

    function openModal() {
        document.querySelector('.modal-manager').style.display = 'flex';
    }

    function closeModal() {
        document.querySelector('.modal-manager').style.display = 'none';
    }
</script>
