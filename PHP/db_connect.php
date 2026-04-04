<?php
// PHP/db_connect.php
$host     = 'localhost';
$dbname   = 'itronic_db';
$username = 'root';
$password = ''; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ====================== HELPER GIỎ HÀNG (THÊM PHẦN NÀY) ======================
function loadCartFromDB($conn, $user_id) {
    $stmt = $conn->prepare("SELECT cart_data FROM user_carts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return json_decode($row['cart_data'], true) ?? [];
    }
    return [];
}

function saveCartToDB($conn, $user_id, $cart) {
    $cart_json = json_encode($cart, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO user_carts (user_id, cart_data) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE cart_data = ?");
    $stmt->bind_param("iss", $user_id, $cart_json, $cart_json);
    $stmt->execute();
}
?>