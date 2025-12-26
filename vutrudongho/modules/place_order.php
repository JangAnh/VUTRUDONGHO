<?php
    include 'connectDatabase.php';
    include 'get_product_by_id.php';
    include 'cartFunction.php';
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    if(isset($_POST['UserID'])){
        // Log received POST for debugging
        error_log('[place_order] POST received: ' . json_encode(array_keys($_POST)));
        $conn = connectDatabase();
        
        $result = mysqli_query($conn,"SELECT * FROM `order`");
        $countOrder = mysqli_num_rows($result);
        $countOrderString = sprintf('%08d',$countOrder+1);

        $orderID = "OD" . $countOrderString;

        $userID = $_POST['UserID'];
        $orderDate = date("Y-m-d h:i:s");
        //echo $orderDate;
        $shippingFee = $_POST['ShippingFee'];
        $orderDiscount = $_POST['OrderDiscount'];
        $orderTotal = $_POST['Total'];
        $address = $_POST['Address'];
        $paymentID = $_POST['PaymentID'];
        $voucherID = $_POST['VoucherID'];
        // voucherID received - escape all POST inputs to prevent SQL injection
        $userID = mysqli_real_escape_string($conn, $userID);
        $shippingFee = mysqli_real_escape_string($conn, $shippingFee);
        $orderDiscount = mysqli_real_escape_string($conn, $orderDiscount);
        $orderTotal = mysqli_real_escape_string($conn, $orderTotal);
        $address = mysqli_real_escape_string($conn, $address);
        $paymentID = mysqli_real_escape_string($conn, $paymentID);
        $voucherID = mysqli_real_escape_string($conn, $voucherID);

        $cart = mysqli_query($conn,"SELECT * FROM `cart` where UserID ='$userID'");

        // Handle voucher: if "NULL" string or empty, use NULL keyword; otherwise use quoted value
        // VoucherID has a FK constraint, so NULL is valid when no voucher is selected
        $voucherValue = (trim($voucherID) === 'NULL' || trim($voucherID) === '') ? 'NULL' : "'{$voucherID}'";
        
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
                header("Location: ../cart.php?error=order_insert_failed");
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

            // 3. Don't update inventory or clear cart here - wait for payment confirmation
            // 4. Commit or rollback based on all operations success
            if($allItemsSuccess){
                $conn->commit();
                // If this request came from VNPay flow, redirect to vnpay_create_payment with order id and amount
                if (isset($_GET['vnp']) && isset($_GET['amount'])) {
                    $amount = urlencode($_GET['amount']);
                    header("Location: ../vnpay_create_payment.php?amount={$amount}&order_id={$orderID}", true, 303);
                    exit;
                }

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