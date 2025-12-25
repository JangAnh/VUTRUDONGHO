<?php
include("searching.php");
include("menu.php");

?>
<div id="main">
    <?php
    include 'connect.php';
    include("sidebar.php");

    function slugify($text) {
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    return trim($text, '-');
    }

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    // $product = mysqli_query($conn, "select product.*, brand.BrandName as 'brandName' from product join brand on product.BrandID = brand.BrandID where product.ProductName LIKE '%$search%'");
    $item_page = !empty($_GET['per_page']) ? $_GET['per_page'] : 9;
    $cur_page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($cur_page - 1) * $item_page;
    $page = mysqli_query($conn, "select * from product where product.Status = 1 order by ProductID asc LIMIT " . $item_page . " OFFSET " . $offset);
    $total = mysqli_query($conn, "select * from product where product.Status = 1 and product.ProductName LIKE '%$search%'");
    $total = $total->num_rows;
    $total_page = ceil($total / $item_page);
    //-----

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $sql = "select product.*, brand.BrandName as 'brandName' from product join brand on product.BrandID = brand.BrandID where product.Status = 1 and product.ProductName LIKE '%$search%' order by ProductID asc LIMIT " . $item_page . " OFFSET " . $offset;
        $product = mysqli_query($conn, $sql);
    }

    ?>
    <?php if (mysqli_num_rows($product) > 0) { ?>
        <div class="maincontent">
            <?php foreach ($product as $key => $value) : ?>
                <div class="card">
                    <div class="product-top">
                        <class="product-thumb">
                            <img src="/vutrudongho/vutrudongho/assets/Img/productImg/<?php echo $value['ProductImg'] ?>"></img>
                            <?php $slug = slugify($value['ProductName']); ?>

                            <button class="info-detail"
                                    onclick="location.href='/vutrudongho/vutrudongho/product/<?php echo $value['ProductID'] . '/' . $slug; ?>'">
                                Xem Thêm
                            </button>

                        </class="product-thumb">
                    </div>
                    <p>
                        <?php echo $value['ProductName'] ?>
                    </p>
                    <span class="price">
                        <?php if ($value['Discount'] == 0) { ?>
                            <strong>
                                <?php echo number_format($value['PriceToSell'], 0, ",", ".") ?> $
                            </strong>
                        <?php } else { ?>
                            <strong>
                                <?php echo number_format($value['PriceToSell'] - $value['PriceToSell'] * $value['Discount'] / 100, 0, ",", ".") ?> $
                            </strong>
                            <strike>
                                <?php echo number_format($value['PriceToSell'], 0, ",", ".") ?> $
                            </strike>
                        <?php } ?>
                    </span>
                    <!-- Nút thêm vào giỏ hàng -->
                    <button class="btn-add-cart" 
                            data-id="<?= $value['ProductID'] ?>" 
                            data-name="<?= $value['ProductName'] ?>" 
                            data-price="<?= $value['PriceToSell'] ?>" 
                            data-img="<?= $value['ProductImg'] ?>">
                        Thêm vào giỏ
                    </button>
                </div>
            <?php endforeach ?>
            <div class="pagination">
                <?php
                if ($cur_page > 2) {
                    $first_page = 1;
                ?>
                    <a class="page-item" href="?page=<?= $first_page ?><?php echo ($search != '') ? "&search=$search" : '' ?>">First</a>
                <?php
                }
                if ($cur_page > 1) {
                    $prev_page = $cur_page - 1;
                ?>
                    <a class="page-item" href="?page=<?= $prev_page ?><?php echo ($search != '') ? "&search=$search" : '' ?>">Prev</a>
                <?php }
                ?>

                <?php for ($num = 1; $num <= $total_page; $num++) { ?>
                    <?php if ($num != $cur_page) { ?>
                        <?php if ($num > $cur_page - 2 && $num < $cur_page + 2) { ?>
                            <a class="page-item" href="?page=<?= $num ?><?php echo ($search != '') ? "&search=$search" : '' ?>"><?= $num ?></a>
                        <?php } ?>
                    <?php } else { ?>
                        <strong class="cur-page page-item">
                            <?= $num ?>
                        </strong>
                    <?php } ?>
                <?php } ?>
                <?php
                if ($cur_page < $total_page - 1) {
                    $next_page = $cur_page + 1; ?>
                    <a class="page-item" href="?page=<?= $next_page ?><?php echo ($search != '') ? "&search=$search" : '' ?>">Next</a>
                <?php }
                if ($cur_page < $total_page - 2) {
                    $end_page = $total_page;
                ?>
                    <a class="page-item" href="?page=<?= $end_page ?><?php echo ($search != '') ? "&search=$search" : '' ?>">Last</a>
                <?php }
                ?>
            </div>
        </div>
    <?php } else {
    ?>
        <div class="alert-not-found">
        <img src="/vutrudongho/vutrudongho/assets/Img/icons/icons8-nothing-found-100.png" alt="Not found" class="ic-not-found">
        <p class="not-found">Không tìm thấy sản phẩm</p>
        </div>
    <?php    }

    ?>

</div>

<div class="popup-overlay" id="popupCart">
    <div class="popup-box">
        <h3>Chọn số lượng</h3>
        <div class="popup-info">
            <img id="popupImg" src="" alt="">
            <p id="popupName"></p>
        </div>

        <div class="quantity-box">
            <button id="btnMinus">-</button>
            <input type="text" id="qtyInput" value="1">
            <button id="btnPlus">+</button>
        </div>

        <button id="btnConfirmAdd">Thêm vào giỏ hàng</button>
        <button id="btnClosePopup">Hủy</button>
    </div>
</div>

<script>
    let chosenProduct = {};

    document.querySelectorAll(".btn-add-cart").forEach(btn => {
        btn.addEventListener("click", function () {
            chosenProduct = {
                id: this.dataset.id,
                name: this.dataset.name,
                price: this.dataset.price,
                img: this.dataset.img
            };

            document.getElementById("popupName").innerText = chosenProduct.name;
            document.getElementById("popupImg").src = "/vutrudongho/vutrudongho/assets/Img/productImg/" + chosenProduct.img;
            document.getElementById("qtyInput").value = 1;

            document.getElementById("popupCart").style.display = "flex";
        });
    });

    // Nút đóng popup
    document.getElementById("btnClosePopup").onclick = () => {
        document.getElementById("popupCart").style.display = "none";
    };

    // Nút tăng
    document.getElementById("btnPlus").onclick = () => {
        let qty = parseInt(document.getElementById("qtyInput").value);
        document.getElementById("qtyInput").value = qty + 1;
    };

    // Nút giảm
    document.getElementById("btnMinus").onclick = () => {
        let qty = parseInt(document.getElementById("qtyInput").value);
        if (qty > 1) document.getElementById("qtyInput").value = qty - 1;
    };

    // 👉 Khi xác nhận thêm sản phẩm (KHÔNG chuyển trang, popup chỉ tắt)
    document.getElementById("btnConfirmAdd").onclick = () => {
        const qty = document.getElementById("qtyInput").value;

        // Gửi request âm thầm -> cart.php xử lý session như bình thường
        fetch(`cart.php?ProductID=${chosenProduct.id}&Quantity=${qty}`)
            .then(() => {
                // Tắt popup ngay lập tức
                document.getElementById("popupCart").style.display = "none";
            });
    };
</script>
