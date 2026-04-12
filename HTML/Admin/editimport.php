<?php
session_start();
include __DIR__ . '/../../PHP/db_connect.php';

// Check admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='admin-login.php';</script>";
    exit();
}

// Kiểm tra id tồn tại
if (!isset($_GET['id'])) {
    die("Thiếu ID!");
}

$id = (int)$_GET['id'];

// Lấy dữ liệu
$sql = "SELECT i.*, p.name 
        FROM import_details i
        JOIN products p ON p.id = i.product_id
        WHERE i.id = $id";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Không tìm thấy dữ liệu!");
}

$row = $result->fetch_assoc();

// Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_POST['quantity'], $_POST['price'])) {
        die("Thiếu dữ liệu!");
    }

    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $sql_update = "
        UPDATE import_details 
        SET quantity = $quantity, price = $price
        WHERE id = $id
    ";

    if ($conn->query($sql_update)) {
        $product_id = $row['product_id'];

        // 1. Tính giá nhập trung bình
        $res = $conn->query("
            SELECT AVG(price) as avg_price 
            FROM import_details 
            WHERE product_id = $product_id
        ");

        $data = $res->fetch_assoc();
        $avg_price = $data['avg_price'] ?? 0;

        // 2. Update lại cost_price
        $conn->query("
            UPDATE products 
            SET 
                cost_price = $avg_price,
                price = $avg_price * (1 + profit_margin / 100)
            WHERE id = $product_id
        ");

        $res = $conn->query("
            SELECT SUM(quantity) as total_qty
            FROM import_details
            WHERE product_id = $product_id
        ");

        $data = $res->fetch_assoc();
        $total_qty = $data['total_qty'] ?? 0;

        $conn->query("
            UPDATE products
            SET quantity = $total_qty
            WHERE id = $product_id
        ");

        echo "<script>alert('Cập nhật thành công!'); window.location='historyimport.php';</script>";
        exit();
    } else {
        echo "Lỗi SQL: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa phiếu nhập</title>

    <link rel="icon" href="../../hinhanh/apple-icon.ico">

    <style>
        body {
            background-color: white;
            margin: 0;
        }

        header {
            background-color: black;
            padding: 15px 40px;
            color: white;
        }

        .header-menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-logo {
            display: flex;
            gap: 10px;
            align-items: center;
            font-weight: bold;
            font-size: 20px;
        }

        .header-logo img {
            width: 50px;
            height: 50px;
            border-radius: 30px;
        }

        main {
            padding: 30px;
            display: flex;
            justify-content: center;
        }

        .box {
            border: 2px solid black;
            border-radius: 12px;
            padding: 35px;
            width: 500px;
            box-shadow: 0 4px 12px;
        }

        .box h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        label {
            font-size: 20px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid black;
        }

        .btn {
            text-align: center;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 12px;
            width: 40%;
            background-color: rgb(19, 62, 111);
            color: white;
            font-size: 15px;
            margin: 5px;
        }

        button:hover {
            background-color: rgb(13, 41, 73);
            transition: 0.3s;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-menu">
            <div class="header-logo">
                <img src="../../hinhanh/apple-icon.ico">
                <h1>Itronic</h1>
            </div>
        </div>
    </header>

    <main>
        <div class="box">
            <h2>Chỉnh sửa phiếu nhập</h2>

            <form method="POST">

                <label>Sản phẩm</label>
                <input type="text" value="<?= $row['name'] ?>" readonly>

                <label>Số lượng</label>
                <input type="number" name="quantity" value="<?= $row['quantity'] ?>" required>

                <label>Giá nhập</label>
                <input type="number" name="price" value="<?= $row['price'] ?>" required>

                <div class="btn">
                    <button type="submit">Lưu</button>
                    <button type="button" onclick="window.location.href='historyimport.php'">Quay về</button>
                </div>

            </form>
        </div>
    </main>

</body>

</html>