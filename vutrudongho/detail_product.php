<?php
include 'modules/connectDatabase.php';
include 'modules/get_product_by_id.php';
include 'modules/cartFunction.php';
include 'lib_session.php';

function slugify($text) {
    $text = trim($text);

    // chuyển thành chữ thường
    $text = mb_strtolower($text, 'UTF-8');

    // bỏ dấu tiếng Việt
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

    // thay ký tự không phải chữ hoặc số thành dấu gạch ngang
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);

    // bỏ dấu - dư ở đầu và cuối
    $text = trim($text, '-');

    return $text;
}

if (isset($_GET['ProductID'])) {
    $product = get_product_by_id($_GET['ProductID']);
    $productSlug = slugify($product['ProductName']);
    if (isset($product)) {

        // Lấy stock trước khi render <head> để dùng trong schema
        $inStock = get_quanty_product_byID($product["ProductID"]);

        // Tạo base URL động 
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        // dirname($_SERVER['SCRIPT_NAME']) trả về đường dẫn thư mục chứa file hiện tại
        $rootPath = rtrim($protocol . $host . dirname($_SERVER['SCRIPT_NAME']), '/');

        // Tạo các URL dùng trong OG / Schema
        $productUrl = $rootPath . '/vutrudongho/vutrudongho/product/' . $product['ProductID'] . '/' . $productSlug;

        $imageUrl = $rootPath . '/vutrudongho/vutrudongho/assets/Img/productImg/' . rawurlencode($product['ProductImg']);
        $homeUrl = $rootPath . '/vutrudongho/vutrudongho/index.php';
        $productsPageUrl = $rootPath . '/vutrudongho/vutrudongho/product.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="/vutrudongho/vutrudongho/assets/CSS/detail_product.css">
    <link rel="stylesheet" href="/vutrudongho/vutrudongho/assets/CSS/header.css">
    <link rel="stylesheet" href="/vutrudongho/vutrudongho/assets/CSS/footer.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/vutrudongho/vutrudongho/assets/Img/logo.png" type="image/x-icon">

    <!-- SEO TITLE & DESCRIPTION -->
    <title><?= htmlspecialchars($product['ProductName']) ?> | Lunar Veil</title>

    <?php
    // Chọn mô tả meta: ưu tiên ShortDescription nếu có, fallback ProductDescription
    $metaDescription = !empty($product['ShortDescription']) ? $product['ShortDescription'] : $product['Description'];
    ?>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">

    <!-- OPEN GRAPH (FACEBOOK / ZALO / MESSENGER SHARE) -->
    <meta property="og:title" content="<?= htmlspecialchars($product['ProductName']) ?> | Lunar Veil">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= $imageUrl ?>">
    <meta property="og:type" content="product">
    <meta property="og:url" content="<?= $productUrl ?>">

    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($product['ProductName']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= $imageUrl ?>">

    <!-- SCHEMA BREADCRUMB -->
    <?php
    $breadcrumb = [
      "@context" => "https://schema.org",
      "@type" => "BreadcrumbList",
      "itemListElement" => [
        [
          "@type" => "ListItem",
          "position" => 1,
          "name" => "Trang chủ",
          "item" => $homeUrl
        ],
        [
          "@type" => "ListItem",
          "position" => 2,
          "name" => "Sản phẩm",
          "item" => $productsPageUrl
        ],
        [
          "@type" => "ListItem",
          "position" => 3,
          "name" => $product['ProductName'],
          "item" => $productUrl
        ]
      ]
    ];
    ?>
    <script type="application/ld+json">
    <?= json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>

    <!-- SCHEMA PRODUCT (RICH RESULTS) -->
    <?php
    // Chuyển price sang số (không dùng number_format) để schema hợp lệ
    $priceNumeric = 0;
    if (isset($product['PriceToSell'])) {
        // loại bỏ dấu phẩy hoặc ký tự khác, cast sang float
        $priceNumeric = (float) str_replace(',', '', $product['PriceToSell']);
    }

    $availability = (isset($inStock['Quantity']) && (int)$inStock['Quantity'] > 0) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock";

    $productSchema = [
      "@context" => "https://schema.org/",
      "@type" => "Product",
      "name" => $product['ProductName'],
      "image" => [$imageUrl],
      "description" => $metaDescription,
      "sku" => $product['ProductID'],
      "brand" => [
        "@type" => "Brand",
        "name" => !empty($product['Brand']) ? $product['Brand'] : $product['Model']
      ],
      "offers" => [
        "@type" => "Offer",
        "url" => $productUrl,
        "priceCurrency" => "USD", 
        "price" => $priceNumeric,
        "availability" => $availability,
        "itemCondition" => "https://schema.org/NewCondition"
      ]
    ];
    ?>
    <script type="application/ld+json">
    <?= json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>

</head>

<style>
    .material-symbols-outlined {
      font-variation-settings:
      'FILL' 0,
      'wght' 300,
      'GRAD' 0,
      'opsz' 24;
      padding-right: 6px;
    }
</style>
<body>
    <div id="bar-header">
        <?php
        include("components/header.php");
        ?>
    </div>
    <div class="detail_container">
        <div class="detail_product">
            <div class="product_img">
                <img src="/vutrudongho/vutrudongho/assets/Img/productImg/<?php echo htmlspecialchars($product['ProductImg']); ?>"
                     alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
            </div>
            <div class="product_name">
                <p class="product_title"><?php echo htmlspecialchars($product['ProductName']) ?></p>
                <p class="product_price">
                    <?php
                        if ((int)$product['Discount'] == 0) {
                    ?>
                            <span class="product_sale"><?php echo number_format($product["PriceToSell"]) ?> $</span>
                    <?php
                        } else {
                    ?>
                            <span class="product_sale"><?php echo number_format((int)$product["PriceToSell"] - (int)$product["PriceToSell"] * (int)$product['Discount'] / 100) ?> $</span>
                            <span class="product_nosale"><?php echo number_format($product["PriceToSell"]) ?> $</span>
                            <label class="product_price_label"><?php echo $product['Discount'] ?>%</label>
                    <?php
                        }
                    ?>
                </p>
                <p class="product_state">
                    Tình trạng:
                    <?php
                        if (isset($inStock)) {
                            if ((int)$inStock['Quantity'] > 0) {
                    ?>
                                <span style="color: green;">còn hàng</span>
                    <?php
                            } else {
                    ?>
                                <span style="color: red;">hết hàng</span>
                    <?php
                            }
                        } else {
                            // fallback
                            echo '<span>Không xác định</span>';
                        }
                    ?>
                </p>
                <p class="product_model">Loại máy: <?php echo htmlspecialchars($product['Model']) ?></p>
                <p class="product_gender">Giới tính: <?php echo htmlspecialchars($product['Gender']) ?></p>
                <div class="product_policy">
                    <div class="product_policy_container">
                        <div class="product_policy_header">
                            <p>Chính sách mua hàng tại lunarveil.com</p>
                        </div>
                        <div class="product_policy_content">
                            <div class="product_policy_group1">
                                <div class="product_policy_shipping">
                                    <span class="material-symbols-outlined">local_shipping</span>
                                    Giao hàng toàn quốc
                                </div>
                                <div class="product_policy_exchange">
                                    <span class="material-symbols-outlined">currency_exchange</span>
                                    Đổi trả hàng trong 7 ngày</div>
                            </div>
                            <div class="product_policy_group2">
                                <div class="product_policy_guarantee">
                                    <span class="material-symbols-outlined">verified_user</span>
                                    Bảo hành 5 năm</div>
                                <div class="product_policy_authentic">
                                    <span class="material-symbols-outlined">thumb_up</span>
                                    Cam kết chính hãng</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="add_cart_button">
                    <button type="submit" id="add_cart" name="addToCart" data-id="<?php echo $product["ProductID"] ?>"
                        <?php if (!isset($inStock) || (int)$inStock['Quantity'] == 0) { echo 'class="disabled_button" disabled';} else { echo 'class="enabled_button"';} ?> >
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>

        <div class="product_description">
            Mô tả:
            <p class="product_description_content">
                <?php echo nl2br(htmlspecialchars($product['Description'])); ?>
            </p>
        </div>
    </div>
    <div id="my-footer">
        <?php
        include("components/footer.php");
        ?>
    </div>

    <!-- POPUP ADD TO CART -->
    <div class="popup-overlay" id="popupOverlay">
        <div class="popup-box">
            <div class="popup-info">
                <img id="popupImg" src="" alt="">
                <h3 id="popupName"></h3>
                <p id="popupPrice"></p>
            </div>

            <div class="quantity-box">
                <button id="btnMinus" class="qty-btn">-</button>
                <input type="number" id="qtyInput" value="1" min="1">
                <button id="btnPlus" class="qty-btn">+</button>
            </div>

            <div class="popup-actions">
                <button id="btnConfirmAdd">Xác nhận</button>
                <button id="btnClosePopup">Hủy</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js" ></script>

    <script>
        // Lấy nút thêm vào giỏ
        const btnAdd = document.getElementById("add_cart");

        // Popup
        const popup = document.getElementById("popupOverlay");
        const popupImg = document.getElementById("popupImg");
        const popupName = document.getElementById("popupName");
        const popupPrice = document.getElementById("popupPrice");

        // Input số lượng
        const qtyInput = document.getElementById("qtyInput");
        const btnMinus = document.getElementById("btnMinus");
        const btnPlus = document.getElementById("btnPlus");

        // Mở popup
        if (btnAdd) {
            btnAdd.onclick = function(){
                let id = this.getAttribute("data-id");

                // Gắn thông tin sản phẩm lên popup
                popupImg.src = "/vutrudongho/vutrudongho/assets/Img/productImg/<?php echo rawurlencode($product['ProductImg']); ?>";
                popupName.innerText = "<?php echo addslashes($product['ProductName']); ?>";
                popupPrice.innerText = "Giá: <?php echo number_format($product['PriceToSell']); ?> $";

                qtyInput.value = 1;

                popup.classList.add("show");
                popup.setAttribute("data-id", id);
            };
        }

        // Đóng popup
        document.getElementById("btnClosePopup").onclick = () => {
            popup.classList.remove("show");
        };

        // Tăng giảm số lượng
        btnMinus.onclick = () => {
            let v = parseInt(qtyInput.value);
            if(v > 1) qtyInput.value = v - 1;
        };
        btnPlus.onclick = () => {
            qtyInput.value = parseInt(qtyInput.value) + 1;
        };

        // Xác nhận thêm
        document.getElementById("btnConfirmAdd").onclick = () => {
            let id = popup.getAttribute("data-id");
            let qty = qtyInput.value;

            // Gửi request tới cart.php để thêm sản phẩm
            fetch(`cart.php?ProductID=${id}&Quantity=${qty}`)
                .then(() => {
                    popup.classList.remove("show"); // tắt popup
                });
        };
    </script>
    <!-- FACEBOOK FLOAT BUTTON -->
    <?php
    include($_SERVER['DOCUMENT_ROOT'] . "/vutrudongho/vutrudongho/components/FB-social-chat.php");
    ?>
    <!-- TIDIO CHAT BUTTON -->
     <!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/695b5ed7f4b504197f1c7646/1je6ekv7t';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
</body>
</html>

<?php
    }
}
?>
