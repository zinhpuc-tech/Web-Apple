<?php
session_start();
include __DIR__ . '/../../PHP/db_connect.php';

// Check admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='admin-login.php';</script>";
    exit();
}

// Xử lý thêm sản phẩm
if (isset($_POST['add_product'])) {

    $name = $_POST['name'];
    $price = (float)$_POST['price'];
    $cost_price = (float)$_POST['cost_price'];
    $quantity = (int)$_POST['quantity'];
    $category = $_POST['category'];
    $sku = $_POST['sku'];
    $unit = $_POST['unit'];
    $technical_info = $_POST['technical_info'];
    $description = $_POST['description'];

    $profit_margin = 20;
    $status = 1;

    // Upload ảnh
    $image_name = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];
    $path = "hinhanh/" . $image_name;

    move_uploaded_file($tmp, "../../" . $path);

    // Insert DB
    $stmt = $conn->prepare("
        INSERT INTO products 
        (name, price, cost_price, quantity, image_url, category, technical_info, sku, unit, profit_margin, status, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sddisssssiss",
        $name,
        $price,
        $cost_price,
        $quantity,
        $path,
        $category,
        $technical_info,
        $sku,
        $unit,
        $profit_margin,
        $status,
        $description
    );

    if ($stmt->execute()) {
        echo "<script>alert('Thêm sản phẩm thành công!');</script>";
    } else {
        echo "<script>alert('Lỗi: " . $conn->error . "');</script>";
    }
}
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Add Product</title>
    
</head>


<style>
    body{
        background-color: white;
        margin: 0;
    }
    /* header */
    header{
        background-color: black;
        padding: 15px 40px;
        color: white;
    }
    .header-menu{
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-logo{
        display: flex;
        gap: 10px;
        align-items: center;
        font-family: 'Times New Roman', Times, serif;
        font-weight: bold;
        font-size: 20px;
    }
    .header-logo img{
        width: 50px;
        height: 50px;
        padding: 5px;
        border: none;
        border-radius: 30px;
    }
    /* main */
    main{
        padding: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .table_add_product{
        border: 2px solid black;
        border-radius: 12px;
        padding: 35px;
        height: 950px;
        width: 500px;
        box-shadow: 0 4px 12px;
    }
    .table_add_product h2{
        text-align: center;
        font-size: 30px;
        margin-bottom: 22px;
    }
    .table_add_product label{
        font-size: 25px;
    }
    .table_add_product input,
    .table_add_product select{
        width: 100%;
        padding: 8px;
        margin-bottom: 20px;
        border: 1px solid black;
        border-radius: 8px;
    }
    /* Nút */
    .btn{
        text-align: center;
    }
    .btn_add{
        border: none;
        border-radius: 8px;
        padding: 12px;
        width: 30%;
        background-color: rgb(19, 62, 111);
        color: white;
        font-size: 15px;
    }
    .btn_return{
        border: none;
        border-radius: 8px;
        padding: 12px;
        width: 30%;
        background-color: rgb(19, 62, 111);
        color: white;
        font-size: 15px;
    }
    .btn_add:hover{
        background-color: rgb(13, 41, 73);
        transition: 0.3s;
    }
    .btn_return:hover{
        background-color: rgb(13, 41, 73);
        transition: 0.3s;
    }
</style>


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
        <div class="table_add_product">
            <h2>Thêm danh mục</h2>    
                <form method="POST" enctype="multipart/form-data">

                    <label>Tên sản phẩm</label>
                    <input type="text" name="name" required>

                    <label>Danh mục</label>
                    <select name="category">
                        <option value="iphone">iPhone</option>
                        <option value="ipad">iPad</option>
                        <option value="mac">Mac</option>
                    </select>

                    <label>SKU (Mã sản phẩm)</label>
                    <input type="text" name="sku">

                    <label>Giá nhập</label>
                    <input type="number" name="cost_price">

                    <label>Giá bán</label>
                    <input type="number" name="price">

                    <label>Số lượng</label>
                    <input type="number" name="quantity">

                    <label>Đơn vị</label>
                    <input type="text" name="unit">

                    <label>Thông số kỹ thuật</label>
                    <input type="text" name="technical_info">

                    <label>Mô tả</label>
                    <input type="text" name="description">

                    <label>Ảnh</label>
                    <input type="file" name="image">

                    <div class="btn">
                        <button type="submit" name="add_product" class="btn_add">Thêm</button>
                        <button type="button" class="btn_return" onclick="window.location.href='import-goods.php'">Quay về</button>
                    </div>

                </form>
            </div>
    </main>
</body>
</html>