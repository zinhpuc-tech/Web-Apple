<?php
function saveCartToDB($conn, $user_id, $cart) {
    foreach ($cart as $item) {
        $product_id = $item['id'];
        $quantity   = $item['quantity'];

        // Kiểm tra đã có sản phẩm trong DB chưa
        $check = "SELECT * FROM cart 
                  WHERE user_id = '$user_id' AND product_id = '$product_id'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            // Nếu có rồi → update số lượng
            $update = "UPDATE cart 
                       SET quantity = quantity + $quantity 
                       WHERE user_id = '$user_id' AND product_id = '$product_id'";
            mysqli_query($conn, $update);
        } else {
            // Nếu chưa có → insert mới
            $insert = "INSERT INTO cart (user_id, product_id, quantity) 
                       VALUES ('$user_id', '$product_id', '$quantity')";
            mysqli_query($conn, $insert);
        }
    }
}
?>