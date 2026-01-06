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

        // Utility: strip Vietnamese diacritics for PayPal payloads
        function stripVN($str) {
            $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            $str = preg_replace('/[^A-Za-z0-9\s\-\,\.]/', '', $str);
            return trim($str);
        }

        // Build cart data for PayPal (sanitized names, unit prices)
        $cartItems = [];
        if($conn){
            $cartForPaypal = mysqli_query($conn, "SELECT c.Quantity, p.ProductID, p.ProductName, p.PriceToSell, p.Discount FROM cart c JOIN product p ON c.ProductID = p.ProductID WHERE c.UserID='$userID'");
            while($item = mysqli_fetch_assoc($cartForPaypal)){
                $unitPrice = (float)$item['PriceToSell'] - (float)$item['PriceToSell'] * (float)$item['Discount'] / 100;
                $cartItems[] = [
                    'name'       => stripVN($item['ProductName']),
                    'sku'        => $item['ProductID'],
                    'quantity'   => (int)$item['Quantity'],
                    'unit_price' => (float)$unitPrice
                ];
            }
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
                    $fee1 = 1;
                    $fee2 = 2;
                    $date1 = date_create(date("y-m-d"));
                    date_add($date1, date_interval_create_from_date_string("3 days"));
                    $date2 = date_create(date("y-m-d"));
                    date_add($date2, date_interval_create_from_date_string("2 days"));

                    
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
                    <div class="icon_clicked">
                        <span class="material-symbols-outlined">done</span>
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
                    <div class="payment_card card_active" data-id="PA02">
                        <div class="payment_icon">
                            <img src="assets/Img/icons/paypal.png" alt="PayPal" style="width:40px; height:auto;">
                        </div>
                        <div class="payment_name">Thanh toán qua PayPal</div>
                        <div class="icon_clicked">
                            <span class="material-symbols-outlined">done</span>
                        </div>
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

                <div class="button">
                    <input type="hidden" id="UserID"        name="UserID"        value="<?php echo $userID ?>">
                    <input type="hidden" id="OrderID"       name="OrderID"       value="">
                    <input type="hidden" id="ShippingFee"   name="ShippingFee"   value="">
                    <input type="hidden" id="OrderDiscount" name="OrderDiscount" value="0">
                    <input type="hidden" id="Address"       name="Address"       value="<?php echo $user['HouseRoadAddress'] ?>#<?php echo $user['Ward'] ?>#<?php echo $user['District'] ?>#<?php echo $user['Province'] ?>">
                    <input type="hidden" id="PaymentID"     name="PaymentID"     value="PA02"> <!-- Default PayPal -->
                    <input type="hidden" id="VoucherID"     name="VoucherID"     value="NULL">
                    <input type="hidden" id="Total"         name="Total"         value="">
                    <input type="hidden" id="CartItems"     value='<?php echo json_encode($cartItems); ?>'>

                    <!-- Nút thanh toán -->
                    <button type="button" id="payBtn" class="payment_button">Thanh Toán</button>

                    <!-- PayPal container (ẩn) -->
                    <div id="paypal-button-container" style="display:none; margin-top: 20px;"></div>
                </div>
                <script>
                    /* PayPal SDK loader with proper error handling */
                    // IMPORTANT: Replace the CLIENT_ID below with your actual PayPal Client ID from https://developer.paypal.com/
                    // You need a PayPal Business or Developer account to get this ID
                    // Sandbox testing: Use a sandbox client ID from PayPal Developer Dashboard
                    // Production: Use your live client ID
                    
                    // FOR TESTING: If you don't have a valid Client ID, create one at:
                    // 1. Go to https://developer.paypal.com/dashboard/
                    // 2. Create an app in Sandbox
                    // 3. Copy the Client ID from the app settings
                    
                    const PAYPAL_CLIENT_ID = "AZkY4SHfzGEVrGmd9kAjv4t2l-2EEgyLZZj1KfBZE-5nuMItvT7HnAuiF6trjVCv7HLT6Pyb6L2opNHk";
                    const PAYPAL_SDK_URL = "https://www.paypal.com/sdk/js?client-id=" + PAYPAL_CLIENT_ID + "&currency=USD&components=buttons&intent=capture";
                    let paypalButtonsRendered = false;
                    let paypalLoadAttempts = 0;
                    const MAX_PAYPAL_RETRIES = 2;
                    let PAYPAL_ORDER_ID = null; // Will be populated from backend

                    // Generate order ID from backend before PayPal submission
                    async function generateOrderID() {
                        try {
                            const response = await fetch('modules/generate_order_id.php');
                            const data = await response.json();
                            if (data.success && data.order_id) {
                                PAYPAL_ORDER_ID = data.order_id;
                                console.log('[PayPal] Generated OrderID:', PAYPAL_ORDER_ID);
                                // Store in hidden field for place_order.php
                                document.getElementById('OrderID').value = PAYPAL_ORDER_ID;
                                return PAYPAL_ORDER_ID;
                            } else {
                                console.error('[PayPal] Failed to generate order ID:', data.error);
                                return null;
                            }
                        } catch (error) {
                            console.error('[PayPal] Error generating order ID:', error);
                            return null;
                        }
                    }

                    // Strip Vietnamese diacritics for PayPal-friendly strings
                    function stripDiacritics(str) {
                        if (!str) return '';
                        return str.normalize('NFD')
                                  .replace(/[\u0300-\u036f]/g, '')
                                  .replace(/[đĐ]/g, 'd');
                    }

                    function loadPayPalSdk(onReady, onError) {
                        onError = onError || (() => {});
                        
                        if (window.paypal) {
                            console.log('[PayPal] SDK already loaded');
                            onReady();
                            return;
                        }

                        let script = document.getElementById('paypal-sdk');
                        if (script) {
                            console.log('[PayPal] SDK script tag exists, waiting for load');
                            return;
                        }

                        console.log('[PayPal] Creating SDK script tag');
                        script = document.createElement('script');
                        script.id = 'paypal-sdk';
                        script.src = PAYPAL_SDK_URL;
                        script.async = true;
                        
                        script.onload = () => {
                            console.log('[PayPal] SDK script loaded successfully');
                            // Wait a moment for PayPal object to initialize
                            setTimeout(() => {
                                if (window.paypal) {
                                    onReady();
                                } else {
                                    console.error('[PayPal] SDK loaded but paypal object not available');
                                    onError('SDK loaded but paypal object unavailable');
                                }
                            }, 500);
                        };
                        
                        script.onerror = () => {
                            console.error('[PayPal] Failed to load SDK. URL:', PAYPAL_SDK_URL);
                            paypalLoadAttempts++;
                            
                            if (paypalLoadAttempts < MAX_PAYPAL_RETRIES) {
                                console.log('[PayPal] Retrying SDK load, attempt ' + (paypalLoadAttempts + 1));
                                // Remove failed script and retry
                                script.remove();
                                document.getElementById('paypal-sdk')?.remove();
                                setTimeout(() => loadPayPalSdk(onReady, onError), 2000);
                            } else {
                                console.error('[PayPal] Max retries reached');
                                onError('Không thể tải PayPal SDK sau nhiều lần thử. Vui lòng kiểm tra client ID hoặc sử dụng phương thức thanh toán khác.');
                            }
                        };
                        
                        document.head.appendChild(script);
                    }

                    function renderPayPalButtons() {
                        if (paypalButtonsRendered) {
                            console.log('[PayPal] Buttons already rendered');
                            return;
                        }
                        
                        console.log('[PayPal] Starting renderPayPalButtons');
                        
                        loadPayPalSdk(() => {
                            console.log('[PayPal] loadPayPalSdk callback reached');
                            
                            if (!window.paypal) {
                                console.error('[PayPal] SDK loaded but paypal object not found');
                                document.getElementById("payBtn").style.display = "block";
                                document.getElementById("paypal-button-container").style.display = "none";
                                alert('Không thể khởi tạo PayPal. Vui lòng chọn phương thức khác.');
                                return;
                            }

                            try {
                                const totalInput = document.getElementById('Total');
                                const userIdInput = document.getElementById('UserID');
                                const shippingFeeInput = document.getElementById('ShippingFee');
                                const addressInput = document.getElementById('Address');
                                const paymentIdInput = document.getElementById('PaymentID');
                                const orderDiscountInput = document.getElementById('OrderDiscount');
                                const voucherIdInput = document.getElementById('VoucherID');

                                console.log('[PayPal] Creating buttons with paypal.Buttons');
                                
                                window.paypal.Buttons({
                                    createOrder: function(data, actions) {
                                        const totalStr        = totalInput.value || '0';
                                        const shippingStr     = shippingFeeInput.value || '0';
                                        const discountStr     = orderDiscountInput.value || '0';
                                        const cartItemsRaw    = document.getElementById('CartItems').value || '[]';

                                        const shippingFee   = parseFloat(shippingStr.toString().replace(/,/g, "")) || 0;
                                        const orderDiscount = parseFloat(discountStr.toString().replace(/,/g, "")) || 0;

                                        // Validate all required form fields before creating order
                                        if (!userIdInput.value || !addressInput.value || shippingFeeInput.value === '') {
                                            console.error('[PayPal] Missing required fields');
                                            alert('Vui lòng hoàn tất các bước thanh toán trước khi tiếp tục.');
                                            throw new Error('Missing required fields');
                                        }

                                        // Build item list with diacritics stripped
                                        const cartItems = JSON.parse(cartItemsRaw).map(item => ({
                                            name: stripDiacritics(item.name),
                                            sku: item.sku,
                                            quantity: item.quantity.toString(),
                                            unit_amount: {
                                                currency_code: "USD",
                                                value: Number(item.unit_price).toFixed(2)
                                            }
                                        }));

                                        const itemTotal = cartItems.reduce((sum, i) => sum + Number(i.unit_amount.value) * Number(i.quantity), 0);

                                        // Recompute grand total to avoid PayPal AMOUNT_MISMATCH
                                        const grandTotalRaw = itemTotal + shippingFee - orderDiscount;
                                        const grandTotal = Number(Math.max(grandTotalRaw, 0).toFixed(2));

                                        const descRaw = `Order ${PAYPAL_ORDER_ID || 'Pending'} - ${cartItems.map(i => i.name).join(', ')}`;
                                        const description = stripDiacritics(descRaw).slice(0, 127);
                                        const invoiceId = stripDiacritics(PAYPAL_ORDER_ID || 'GEN-' + Date.now()).slice(0, 127);
                                        const customId = stripDiacritics(userIdInput.value || 'guest').slice(0, 127);

                                        console.log('[PayPal] createOrder breakdown', { itemTotal, shippingFee, orderDiscount, grandTotal });

                                        return actions.order.create({
                                            purchase_units: [{
                                                amount: {
                                                    currency_code: "USD",
                                                    value: grandTotal.toFixed(2),
                                                    breakdown: {
                                                        item_total: { currency_code: "USD", value: itemTotal.toFixed(2) },
                                                        shipping:   { currency_code: "USD", value: shippingFee.toFixed(2) },
                                                        discount:   { currency_code: "USD", value: orderDiscount.toFixed(2) }
                                                    }
                                                },
                                                description: description,
                                                invoice_id: invoiceId,
                                                custom_id: customId,
                                                items: cartItems
                                            }]
                                        });
                                    },
                                    onApprove: function(data, actions) {
                                        console.log('[PayPal] onApprove called, orderID:', data.orderID);
                                        
                                        return actions.order.capture().then(function(orderData) {
                                            console.log('[PayPal] Payment captured successfully');
                                            
                                            // Ensure PaymentID is set to PA02 (PayPal)
                                            paymentIdInput.value = 'PA02';
                                            
                                            const form = document.getElementById('paymentForm');
                                            form.action = 'modules/place_order.php';
                                            form.method = 'POST';
                                            
                                            // Log for debugging
                                            console.log('[PayPal] Submitting form with data:', {
                                                UserID: userIdInput.value,
                                                ShippingFee: shippingFeeInput.value,
                                                Total: totalInput.value,
                                                PaymentID: paymentIdInput.value,
                                                Address: addressInput.value
                                            });
                                            
                                            form.submit();
                                        });
                                    },
                                    onCancel: function() {
                                        console.log('[PayPal] User cancelled payment');
                                        window.location.href = 'checkout.php?payment=failed';
                                    },
                                    onError: function(err) {
                                        console.error('[PayPal] Payment error:', err);
                                        alert('Có lỗi xảy ra với PayPal. Vui lòng thử lại hoặc chọn phương thức khác.');
                                    }
                                }).render('#paypal-button-container').catch(err => {
                                    console.error('[PayPal] Error rendering buttons:', err);
                                    document.getElementById("payBtn").style.display = "block";
                                    document.getElementById("paypal-button-container").style.display = "none";
                                    alert('Không thể hiển thị nút PayPal. Vui lòng chọn phương thức khác.');
                                });

                                paypalButtonsRendered = true;
                                console.log('[PayPal] Buttons rendered successfully');
                            } catch (error) {
                                console.error('[PayPal] Error in renderPayPalButtons:', error);
                                alert('Lỗi khởi tạo PayPal: ' + error.message);
                                document.getElementById("payBtn").style.display = "block";
                                document.getElementById("paypal-button-container").style.display = "none";
                            }
                        }, (errorMsg) => {
                            console.error('[PayPal] SDK load failed:', errorMsg);
                            document.getElementById("payBtn").style.display = "block";
                            document.getElementById("paypal-button-container").style.display = "none";
                            alert(errorMsg || 'Không thể tải PayPal SDK. Vui lòng thử lại hoặc chọn phương thức khác.');
                        });
                    }

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
                            console.log('[Payment Button] Click detected');

                            const paymentID = paymentIDEl.value;

                            let total = totalEl.value;
                            total = parseFloat(total.toString().replace(/,/g, "")) || 0;

                            // Validate required fields before payment
                            const shippingFeeInput = document.getElementById('ShippingFee');
                            const addressInput = document.getElementById('Address');
                            
                            if (!shippingFeeInput.value || !addressInput.value) {
                                alert('Vui lòng chọn phương thức giao hàng trước khi thanh toán.');
                                return;
                            }

                            /* PAYPAL */
                            if (paymentID === "PA02") {
                                console.log('[Payment Button] PayPal selected, generating order ID');
                                // Ensure PaymentID is set correctly
                                paymentIDEl.value = "PA02";
                                payBtn.disabled = true;
                                payBtn.textContent = "Đang tải PayPal...";
                                
                                // Generate OrderID first, then render buttons
                                generateOrderID().then((orderId) => {
                                    if (orderId) {
                                        renderPayPalButtons();
                                        payBtn.style.display = "none";
                                        document.getElementById("paypal-button-container").style.display = "block";
                                    } else {
                                        alert('Không thể tạo mã đơn hàng. Vui lòng thử lại.');
                                        payBtn.disabled = false;
                                        payBtn.textContent = "Thanh Toán";
                                    }
                                });
                                return;
                            }

                            /* VNPAY */
                            if (paymentID === "PA03") {
                                const usdToVndRate = 25000;
                                const amountVND = Math.round(total * usdToVndRate);

                                if (Number.isNaN(amountVND) || amountVND <= 0) {
                                    alert("Giá trị đơn hàng không hợp lệ. Vui lòng tải lại trang và thử lại.");
                                    return;
                                }

                                const form = document.getElementById('paymentForm');
                                form.action = 'modules/place_order.php?vnp=1&amount=' + amountVND;
                                form.method = 'POST';
                                form.submit();
                                return;
                            }

                            alert("Vui lòng chọn phương thức thanh toán.");
                        });

                    });

                    /* ============ CHỌN PHƯƠNG THỨC THANH TOÁN ============ */
                    const paymentCards = document.querySelectorAll('.payment_card');

                    paymentCards.forEach(card => {
                        card.addEventListener('click', () => {
                            paymentCards.forEach(c => {
                                c.classList.remove('card_active');
                                const existingIcon = c.querySelector('.icon_clicked');
                                if (existingIcon) {
                                    existingIcon.remove();
                                }
                            });
                            
                            card.classList.add('card_active');
                            
                            if (!card.querySelector('.icon_clicked')) {
                                const iconDiv = document.createElement('div');
                                iconDiv.className = 'icon_clicked';
                                iconDiv.innerHTML = '<span class="material-symbols-outlined">done</span>';
                                card.appendChild(iconDiv);
                            }

                            document.getElementById("PaymentID").value = card.dataset.id;

                            if (card.dataset.id === "PA03") {
                                document.getElementById("payBtn").innerText = "Thanh toán VNPay";
                            } else {
                                document.getElementById("payBtn").innerText = "Thanh toán PayPal";
                                renderPayPalButtons();
                            }

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