<?php


function get_quanty_product_byID($productID){
    $conn = connectDatabase();
    // Get current quantity from product table (single value)
    $inStock = mysqli_query($conn,"select Quantity from product where ProductID='$productID'");
    $inStock = mysqli_fetch_array($inStock);
    return $inStock;
}

function updateQuantyInCart($userID, $productID, $quanty) {
    $conn = connectDatabase();
    if($conn){
        $inStock = get_quanty_product_byID($productID);
        //echo var_dump($inStock );

        if( $quanty > (int) $inStock['Quantity'] ){
            closeDatabase($conn);
            return "Chỉ được thêm tối đa " . (int) $inStock['Quantity'] . " sản phẩm!";
        }
        else{
            $result = mysqli_query($conn,"update cart set Quantity='$quanty' where UserID='$userID' and ProductID='$productID'");
            closeDatabase($conn);
            return (bool) $result;
        }
        
    }
    closeDatabase($conn);
    return false;
}

//echo updateQuantyInCart("US000001","PR000008", 30);

?>