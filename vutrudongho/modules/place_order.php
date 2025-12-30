<?php
    include 'connectDatabase.php';
    include 'get_product_by_id.php';
    include 'cartFunction.php';
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    if(isset($_POST['UserID'])){
        // Log received POST for debugging
        error_log('[place_order] POST received: ' . json_encode(array_keys($_POST)));
        $conn = connectDatabase();

        if(!$conn){
            header("Location: ../cart.php?error=db_connect_failed");
            exit;
        }
        
        // Get the maximum numeric part of existing OrderIDs and increment to avoid duplicates
        $result = $conn->query("SELECT MAX(CAST(SUBSTRING(OrderID, 3) AS UNSIGNED)) as max_id FROM `order`");
        $row = $result->fetch_assoc();
        $nextId = ($row['max_id'] ?? 0) + 1;
        $orderID = "OD" . sprintf('%08d', $nextId);

        $userID = trim($_POST['UserID']);
        $orderDate = date("Y-m-d H:i:s");
        $shippingFee = $_POST['ShippingFee'] ?? '';
        $orderDiscount = $_POST['OrderDiscount'] ?? '';
        $orderTotal = $_POST['Total'] ?? '';
        $address = $_POST['Address'] ?? '';
        $paymentID = $_POST['PaymentID'] ?? '';
        $voucherID = $_POST['VoucherID'] ?? '';

        // Validate required fields early to avoid FK errors
        if ($userID === '' || $address === '' || $paymentID === '' || $orderTotal === '') {
            header("Location: ../cart.php?error=missing_fields");
            exit;
        }

        // Normalize numeric inputs
        $shippingFee = floatval(str_replace(',', '', $shippingFee));
        $orderDiscount = floatval(str_replace(',', '', $orderDiscount));
        $orderTotal = floatval(str_replace(',', '', $orderTotal));

        // Escape text inputs to prevent SQL injection
        $userID = mysqli_real_escape_string($conn, $userID);
        $address = mysqli_real_escape_string($conn, $address);
        $paymentID = mysqli_real_escape_string($conn, $paymentID);
        $voucherID = mysqli_real_escape_string($conn, $voucherID);

        // Confirm PaymentID exists to avoid FK violation
        $paymentCheck = $conn->query("SELECT 1 FROM payment WHERE PaymentID='{$paymentID}' LIMIT 1");
        if (!$paymentCheck || $paymentCheck->num_rows === 0) {
            header("Location: ../cart.php?error=invalid_payment_method");
            exit;
        }

        $cart = mysqli_query($conn,"SELECT * FROM `cart` where UserID ='$userID'");

        // Handle voucher: if "NULL" string or empty, use NULL keyword; otherwise use quoted value
        // VoucherID has a FK constraint, so NULL is valid when no voucher is selected
        $voucherValue = (trim($voucherID) === 'NULL' || trim($voucherID) === '') ? 'NULL' : "'{$voucherID}'";

        // Flag to know if this is an immediate PayPal capture flow
        $isPaypalCapture = ($paymentID === 'PA02');
        
        $sqlOrder = "INSERT INTO `order` (`OrderID`, `UserID`, `OderDate`, `ShippingFee`, `OrderDiscount`, `OrderTotal`, `Address`, `PaymentID`, `VoucherID`, `OrderStatus`) 
                     VALUES ('$orderID', '$userID', '$orderDate', '$shippingFee', '$orderDiscount', '$orderTotal', '$address', '$paymentID', {$voucherValue}, 'S01')";
    
        try {
            // Start single transaction for entire order process
            $conn->begin_transaction();

            // 1. Insert order
            $rs1 = $conn->query($sqlOrder);
            if(!$rs1){
                $err = $conn->error;
                error_log("[place_order] insert order failed: " . $err);
                $conn->rollback();
                // Return an error page with message for debugging (can be removed in production)
                header("Location: ../cart.php?error=order_insert_failed&detail=" . urlencode($err));
                exit;
            }

            // 2. Process all cart items - only create order_line records, don't update inventory yet
            $allItemsSuccess = true;
            while($item = mysqli_fetch_array($cart)){
                $product = get_product_by_id($item['ProductID']);

                // Validate stock availability - just check, don't update yet
                $Quantity = get_quanty_product_byID($item['ProductID']);
                $inStock = (int) $Quantity['Quantity'];
                $lastInStock = $inStock - (int) $item['Quantity'];
                
                if($lastInStock < 0){
                    $allItemsSuccess = false;
                    break;
                }

                $product_Price = $product["PriceToSell"] - (int) $product["PriceToSell"]* (int) $product['Discount']/100;
                
                // Store product snapshot in order_line to preserve product info at order time
                $productName = mysqli_real_escape_string($conn, $product['ProductName']);
                $model = mysqli_real_escape_string($conn, $product['Model']);
                $color = mysqli_real_escape_string($conn, $product['Color']);
                $gender = mysqli_real_escape_string($conn, $product['Gender']);
                $productImg = mysqli_real_escape_string($conn, $product['ProductImg']);
                $discount = (int) $product['Discount'];
                
                $rs4 = $conn->query("INSERT INTO `order_line` (`OrderID`, `ProductID`, `Quantity`, `UnitPrice`, `ProductName`, `Model`, `Color`, `Gender`, `ProductImg`, `Discount`) VALUES ('$orderID', '". $product['ProductID'] ."', '". $item['Quantity'] ."', '$product_Price', '$productName', '$model', '$color', '$gender', '$productImg', '$discount')");
                
                if(!$rs4){
                    $err = $conn->error;
                    error_log("[place_order] insert order_line failed: " . $err);
                    $allItemsSuccess = false;
                    break;
                }
            }

            // 3. If PayPal was already captured on client, finalize order now (set status, update stock, clear cart)
            if ($allItemsSuccess && $isPaypalCapture) {
                $orderIdEsc = mysqli_real_escape_string($conn, $orderID);

                // Set order as confirmed
                $conn->query("UPDATE `order` SET OrderStatus='S02' WHERE OrderID='$orderIdEsc'");

                // Update inventory based on order_line snapshot
                $lineResult = $conn->query("SELECT ProductID, Quantity FROM order_line WHERE OrderID='$orderIdEsc'");
                if ($lineResult) {
                    while ($line = mysqli_fetch_array($lineResult)) {
                        $productID = mysqli_real_escape_string($conn, $line['ProductID']);
                        $quantity = (int)$line['Quantity'];

                        $qtyResult = mysqli_query($conn, "SELECT Quantity FROM product_quantity WHERE ProductID='$productID' ORDER BY Date DESC LIMIT 1");
                        if ($qtyResult && mysqli_num_rows($qtyResult) > 0) {
                            $qtyRow = mysqli_fetch_array($qtyResult);
                            $currentQty = (int)$qtyRow['Quantity'];
                            $newQty = $currentQty - $quantity;
                            mysqli_query($conn, "INSERT INTO product_quantity (ProductID, Date, Quantity) VALUES ('$productID', NOW(), '$newQty')");
                            mysqli_query($conn, "UPDATE product SET Quantity = '$newQty' WHERE ProductID = '$productID'");
                        }
                    }
                }

                // Clear cart for this user
                mysqli_query($conn, "DELETE FROM `cart` WHERE UserID='$userID'");
            }

            // 4. Commit or rollback based on all operations success
            if($allItemsSuccess){
                $conn->commit();

                // VNPay flow: redirect to payment gateway
                if (isset($_GET['vnp']) && isset($_GET['amount'])) {
                    $amount = urlencode($_GET['amount']);
                    header("Location: ../vnpay_create_payment.php?amount={$amount}&order_id={$orderID}", true, 303);
                    exit;
                }

                // PayPal captured: go to success screen
                if ($isPaypalCapture) {
                    header("Location: ../checkout.php?payment=success&method=paypal&order_id={$orderID}", true, 303);
                    exit;
                }

                // Default fallback
                header("Location: ../checkout.php",true,303);
                exit;
            }
            else{
                $conn->rollback();
                header("Location: ../cart.php?error=order_processing_failed");
                exit;
            }
        } catch (Throwable $th) {
            error_log("[place_order] exception: " . $th->getMessage());
            if (isset($conn) && $conn->connect_errno == 0) $conn->rollback();
            header("Location: ../cart.php?error=exception");
            exit;
        }
    }
    
?>