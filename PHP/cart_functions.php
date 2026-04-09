<?php
// PHP/cart_functions.php

function loadCartFromDB($conn, $user_id) {
    if (!$user_id) return [];
    
    // Sử dụng try-catch hoặc kiểm tra lỗi stmt để an toàn hơn
    $stmt = $conn->prepare("SELECT cart_data FROM user_carts WHERE user_id = ?");
    if (!$stmt) return []; 

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return json_decode($row['cart_data'], true) ?? [];
    }
    return [];
}

function saveCartToDB($conn, $user_id, $cart) {
    if (!$user_id) return;
    
    $cart_json = json_encode($cart, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO user_carts (user_id, cart_data) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE cart_data = ?");
    
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $cart_json, $cart_json);
        $stmt->execute();
    }
}
?>