<?php
    include 'modules/connectDatabase.php';
    include 'modules/get_product_by_id.php';
    session_start();

    // TEST
    //$_SESSION['current_userID'] = "US000001";

    if(isset($_SESSION['current_userID'])){
        $userID = $_SESSION['current_userID'];

        $conn = connectDatabase();

        if($conn){
            $user = mysqli_query($conn,"select * from user where UserID='$userID'");
            $user = mysqli_fetch_array($user);
        }

        if($conn){
            $cart = mysqli_query($conn,"select * from cart where UserID='$userID' ");
        }

        if(mysqli_num_rows($cart) <= 0){
            header("location: cart.php");
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/CSS/payment.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <title>Payment</title>
</head>
<style>
    .material-symbols-outlined {
      font-variation-settings:
      'FILL' 0,
      'wght' 500,
      'GRAD' 0,
      'opsz' 30
    }
</style>
<!-- SDK PayPal (nếu bạn định tích hợp thật) -->
<script src="https://www.paypal.com/sdk/js?client-id=AfH7LU1YDV8qQHfYCLc7802uj-9D810FUWzNPc6oJdxzalC6Ub4i1gF-anOPTcvHzBDK20-8eOvfEYbn"></script>

<body>
    <div class="payment_container">
        <div class="payment_content">
            <div class="payment_content_header">
                <span class="material-symbols-outlined">account_balance_wallet</span>
                Thông Tin Thanh Toán
            </div>
            <div class="delivery_method">
                Phương thức vận chuyển
            </div>
            <div class="address_label">
                <span class="material-symbols-outlined">home_pin</span>
                Địa chỉ nhận hàng</div>
            <div class="user_address">
                <p><?php echo $user['FullName'] ?> - <?php echo $user['NumberPhone'] ?></p>
                <span><?php echo $user['HouseRoadAddress'] ?>, <?php echo $user['Ward'] ?>, <?php echo $user['District'] ?>, <?php echo $user['Province'] ?></span>
                <a href="change_user_information.php">Thay đổi</a>
            </div>
            <div class="delivery_cards">
                <?php 
                    $fee1 = 0;
                    $fee2 = 0;
                    $date1 = date_create(date("y-m-d"));
                    date_add($date1, date_interval_create_from_date_string("3 days"));
                    $date2 = date_create(date("y-m-d"));
                    date_add($date2, date_interval_create_from_date_string("2 days"));

                    if( $user['Province'] == "Thành phố Hồ Chí Minh" || $user['Province'] == "Thành phố Hà Nội") {
                        $fee1 = 1; 
                        $fee2 = 2.28; 
                    }
                    else{
                        $fee1 = 35000;  
                        $fee2 = 120000; 
                    }
                ?>

                <!-- Giao hàng nhanh -->
                <div class="delivery_card card_active" data-deliveryfee="<?php echo $fee1 ?>">
                    <div class="delivery_title header_active">Giao hàng nhanh</div>
                    <div class="delivery_price"><?php echo number_format($fee1) ?> $</div>
                    <div class="delivery_time">
                        Nhận hàng vào 
                        <?php echo (date_format($date1,"d") . "-" ); 
                            date_add($date1, date_interval_create_from_date_string("2 days")); 
                            echo (date_format($date1,"d")); ?> 
                        thg <?php echo date("m") ?>
                    </div>
                    
                </div>

                <!-- Giao hàng hỏa tốc -->
                <div class="delivery_card" data-deliveryfee="<?php echo $fee2 ?>">
                    <div class="delivery_title">Giao hàng hỏa tốc</div>
                    <div class="delivery_price"><?php echo number_format($fee2) ?> $</div>
                    <div class="delivery_time">
                        Nhận hàng vào 
                        <?php echo (date_format($date2,"d") . "-" ); 
                            date_add($date2, date_interval_create_from_date_string("1 day")); 
                            echo (date_format($date2,"d")); ?> 
                        thg <?php echo date("m") ?>
                    </div>
                </div>
            </div>
            <div class="payment_method">
                Phương thức thanh toán
            </div>

            <div class="payment_cards">
                <div class="payment_cards_row">
                    <!-- Thanh toán qua PayPal -->
                    <div class="payment_card" data-id="PA02">
                        <div class="payment_icon">
                            <img src="assets/Img/icons/paypal.png" alt="PayPal" style="width:40px; height:auto;">
                        </div>
                        <div class="payment_name">Thanh toán qua PayPal</div>
                    </div>
                    <!-- Thanh toán qua VNpay -->
                    <div class="payment_card" data-id="PA03">
                        <div class="payment_icon">
                            <img src="assets/Img/icons/v-vnpay_.png" alt="VNPAY" style="width:40px; height:auto;">
                        </div>
                        <div class="payment_name">Thanh toán qua VNPAY</div>
                    </div>

                </div>
            </div>

            <!-- Voucher -->
            <div class="voucher">
                <div class="voucher_name">
                    <div class="voucher_name_container" id="voucher_name_container"></div>
                    <div class="voucher_discount" id="voucher_discount"></div>
                    <!-- clear button: hidden until a voucher is applied -->
                    <button type="button" id="voucher_clear_btn" style="display:none; margin-left:8px; background:transparent; border:none; font-size:18px; cursor:pointer;">✕</button>
                </div>

                <div class="voucher_submit">
                    <input type="text" id="voucher_input" class="voucher_input" placeholder="Nhập mã giảm giá">
                    <button class="submit_button">Áp dụng</button>
                </div>
            </div>

            <!-- Form thanh toán -->
            <form id="paymentForm" action="#" method="post">
                <!-- PayPal SDK -->
                <script src="https://www.paypal.com/sdk/js?client-id=AfH7LU1YDV8qQHfYCLc7802uj-9D810FUWzNPc6oJdxzalC6Ub4i1gF-anOPTcvHzBDK20-8eOvfEYbn&currency=USD"></script>

                <div class="button">
                    <input type="hidden" id="UserID"        name="UserID"        value="<?php echo $userID ?>">
                    <input type="hidden" id="ShippingFee"   name="ShippingFee"   value="">
                    <input type="hidden" id="OrderDiscount" name="OrderDiscount" value="0">
                    <input type="hidden" id="Address"       name="Address"       value="<?php echo $user['HouseRoadAddress'] ?>#<?php echo $user['Ward'] ?>#<?php echo $user['District'] ?>#<?php echo $user['Province'] ?>">
                    <input type="hidden" id="PaymentID"     name="PaymentID"     value="PA02"> <!-- Default PayPal -->
                    <input type="hidden" id="VoucherID"     name="VoucherID"     value="NULL">
                    <input type="hidden" id="Total"         name="Total"         value="">

                    <!-- Nút thanh toán -->
                    <button type="button" id="payBtn" class="payment_button">Thanh Toán</button>

                    <!-- PayPal container (ẩn) -->
                    <div id="paypal-button-container" style="display:none; margin-top: 20px;"></div>
                </div>
                <script>
                    /* ============ PAYPAL BUTTON ============ */
                    paypal.Buttons({
                        createOrder: function(data, actions) {
                            const total = document.getElementById('Total').value || '10.00';
                            return actions.order.create({
                                purchase_units: [{
                                    amount: { value: total }
                                }]
                            });
                        },
                        onApprove: function(data, actions) {
                            return actions.order.capture().then(function(details) {
                                window.location.href = "checkout.php";
                            });
                        },
                        onError: function(err) {
                            console.error(err);
                        }
                    }).render('#paypal-button-container');


                    /* ============ SỰ KIỆN CLICK NÚT THANH TOÁN ============ */
                    document.addEventListener("DOMContentLoaded", () => {

                        const payBtn = document.getElementById("payBtn");
                        const paymentIDEl = document.getElementById("PaymentID");
                        const totalEl = document.getElementById("Total");

                        if (!payBtn || !paymentIDEl || !totalEl) {
                            console.error("Không tìm thấy element cần thiết.");
                            return;
                        }

                        payBtn.addEventListener("click", function () {

                            const paymentID = paymentIDEl.value;

                            let total = totalEl.value;
                            total = parseFloat(total.toString().replace(/,/g, "")) || 0;

                            /* PAYPAL */
                            if (paymentID === "PA02") {
                                payBtn.style.display = "none";
                                document.getElementById("paypal-button-container").style.display = "block";
                                return;
                            }

                            /* VNPAY */
                            if (paymentID === "PA03") {
                                // total đang là USD → đổi sang VND
                                const usdToVndRate = 25000;   // hoặc 24,500 / 25,000 tùy bạn
                                const amountVND = Math.round(total * usdToVndRate);

                                window.location.href = "vnpay_create_payment.php?amount=" + amountVND;
                                return;
                            }

                            alert("Vui lòng chọn phương thức thanh toán.");
                        });

                    });

                    /* ============ CHỌN PHƯƠNG THỨC THANH TOÁN ============ */
                    const paymentCards = document.querySelectorAll('.payment_card');

                    paymentCards.forEach(card => {
                        card.addEventListener('click', () => {
                            // Xóa class active
                            paymentCards.forEach(c => c.classList.remove('card_active'));
                            card.classList.add('card_active');

                            // Gán PaymentID
                            document.getElementById("PaymentID").value = card.dataset.id;

                            /* Đổi text nút */
                            if (card.dataset.id === "PA03") {
                                document.getElementById("payBtn").innerText = "Thanh toán VNPay";
                            } else {
                                document.getElementById("payBtn").innerText = "Thanh toán PayPal";
                            }

                            // Reset UI PayPal
                            document.getElementById("paypal-button-container").style.display = "none";
                            document.getElementById("payBtn").style.display = "block";
                        });
                    });
                </script>
            </form>

             
        </div>
        <div class="product_list_container">
            <div class="product_list_header">
                Danh sách sản phẩm
            </div>
            <div class="product_list">
                <?php
                    $sum = 0;
                    
                    while($item = mysqli_fetch_array($cart)){
                        $product = get_product_by_id($item['ProductID']);
                        $productPrice = (int) $product["PriceToSell"] - (int) $product["PriceToSell"]* (int) $product['Discount']/100 ;
                        $sum += $productPrice * (int) $item['Quantity'];
                ?>
                <div class="product_item">
                    <div class="product_item_img"><img src="assets/Img/productImg/<?php echo $product['ProductImg'] ?>" alt=""></div>
                    <div class="product_detail">
                        <div class="product_item_name">
                            <?php echo $product['ProductName'] ?>
                        </div>
                        <div class="product_item_price_category">
                            <div class="product_item_category"><?php echo $product['Model'] ?>, <?php echo $product['Color'] ?></div>
                            <div class="product_item_price">
                                <?php echo number_format( $productPrice ) ?> $ 
                                x 
                                <?php echo $item['Quantity'] ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    }
                    
                ?>
            </div>
            <div class="payment_detail">
                <div class="payment_detail_pricetotal">
                    <span>Tổng tiền hàng:</span>
                    <p id="sum" data-sum="<?php echo $sum; ?>">
                        <?php echo number_format($sum); ?> $
                    </p>
                </div>
                <div class="payment_detail_pricetotal">
                    <span>Phí vận chuyển:</span>
                    <p id="deliveryfee">$ 0</p>
                </div>
                <div class="payment_detail_pricetotal" data-total="0">
                    <span>Khuyến mãi:</span>
                    <p id="discount"> $- 0</p>
                </div>
                <div class="payment_detail_total">
                    <span class="payment_detail_total_label">Tổng thanh toán:</span>
                    <p id="totalPrice" class="payment_detail_total_label_price">$0</p>
                </div>
            </div>
        </div>
    </div>
    <div id="sum" data-sum="<?php echo $sum ?>"></div>
     <!-- File JS xử lý chọn thanh toán, voucher, tổng tiền -->
    <script src="assets/JS/payment.js"></script>   
</body>
</html>

<?php
    }
?>