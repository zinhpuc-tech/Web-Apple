<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='../HTML/User/Sign.php';</script>";
    exit();
}

// Khởi tạo biến để tránh lỗi Undefined
$message = ""; 

// 2. Lấy danh sách sản phẩm để đổ vào Select (Chỉ lấy sản phẩm đang bán status=1)
$products_res = $conn->query("SELECT id, name FROM products WHERE status = 1 ORDER BY name ASC");
$products_data = [];
while($row = $products_res->fetch_assoc()) {
    $products_data[] = $row;
}

// 3. Xử lý lưu phiếu nhập kho
if (isset($_POST['save_import'])) {
    $p_ids = $_POST['product_id'];
    $qtys = $_POST['quantity'];
    $prices = $_POST['import_price'];

    $conn->begin_transaction();
    try {
        for ($i = 0; $i < count($p_ids); $i++) {
            $id = (int)$p_ids[$i];
            $q = (int)$qtys[$i];
            $p_in = (float)$prices[$i];

            if ($id > 0 && $q > 0) {
                // Lấy % lợi nhuận hiện tại của sản phẩm đó
                $p_info_q = $conn->query("SELECT profit_margin FROM products WHERE id = $id");
                $p_info = $p_info_q->fetch_assoc();
                
                $margin = $p_info['profit_margin'] ?? 20; // Mặc định 20% nếu chưa có
                $new_retail = $p_in * (1 + ($margin / 100));

                // Cập nhật: Cộng kho + Cập nhật giá vốn mới + Cập nhật giá bán lẻ mới
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ?, cost_price = ?, price = ? WHERE id = ?");
                $stmt->bind_param("iddi", $q, $p_in, $new_retail, $id);
                $stmt->execute();
                
                // Lưu vào lịch sử nhập hàng (Để sau này không bị xóa nhầm sản phẩm)
                $conn->query("INSERT INTO import_details (product_id, quantity, price) VALUES ($id, $q, $p_in)");
            }
        }
        $conn->commit();
        $message = "Nhập hàng thành công! Hệ thống đã tự động cập nhật tồn kho và giá bán lẻ mới.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Nhập kho hàng về</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --apple-blue: #0071e3; 
            --apple-dark: #1d1d1f; 
            --apple-gray: #86868b; 
            --apple-bg: #f5f5f7; 
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--apple-bg); display: flex; min-height: 100vh; color: var(--apple-dark); overflow-x: hidden; }

        .sidebar { 
            width: var(--sidebar-width); background: var(--apple-dark); color: white; 
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; padding: 20px 0;
        }

        .logo-admin { 
            text-align: center; padding: 20px; font-size: 22px; font-weight: bold; 
            border-bottom: 1px solid #333; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }

        .menu a { display: flex; align-items: center; padding: 15px 25px; color: #ddd; text-decoration: none; transition: 0.3s; font-size: 14px; }
        .menu a i { margin-right: 12px; width: 20px; text-align: center; font-size: 16px; }
        .menu a:hover, .menu a.active { background: var(--apple-blue); color: white; }

        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; width: calc(100% - var(--sidebar-width)); }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }

        .card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02); }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; padding: 15px; background: #fbfbfd; color: var(--apple-gray); font-size: 12px; text-transform: uppercase; font-weight: 600; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #f2f2f2; }
        
        select, input { width: 100%; padding: 12px; border: 1px solid #d2d2d7; border-radius: 10px; outline: none; background: #fff; font-size: 14px; }

        .btn-add { background: #f5f5f7; border: 1px dashed var(--apple-blue); color: var(--apple-blue); padding: 15px; width: 100%; cursor: pointer; margin: 15px 0; border-radius: 12px; font-weight: 600; transition: 0.3s; }
        .btn-add:hover { background: #e8f4ff; }

        .btn-submit { background: var(--apple-blue); color: white; border: none; padding: 14px 35px; border-radius: 10px; cursor: pointer; font-weight: 600; float: right; transition: 0.3s; }
        .btn-submit:hover { background: #0077ed; }

        .btn-remove { color: #ff3b30; border: none; background: none; cursor: pointer; font-size: 20px; }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #e3f9e5; color: #1f7a28; border: 1px solid #cdedcf; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-admin"><i class="fa-brands fa-apple"></i> Itronic Admin</div>
    <div class="menu">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
        <a href="products.php"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
        <a href="import-goods.php" class="active"><i class="fas fa-file-import"></i> Nhập kho hàng</a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Tồn kho</a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn đặt hàng</a>
        <a href="../../PHP/logout-admin.php" style="color: #ff453a; border-top: 1px solid #333; margin-top: 20px;">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <h2>Nhập kho hàng Itronic</h2>
        <div style="color: var(--apple-gray); font-weight: 500;">
            <i class="far fa-calendar-alt"></i> <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <?php if(!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <table id="importTable">
                <thead>
                    <tr>
                        <th style="width: 45%;">Tên sản phẩm Apple</th>
                        <th style="width: 20%;">Số lượng</th>
                        <th style="width: 30%;">Giá nhập đơn vị (VNĐ)</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="importBody">
                    <tr>
                        <td>
                            <select name="product_id[]" required>
                                <option value="">-- Chọn sản phẩm Apple --</option>
                                <?php foreach($products_data as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="quantity[]" min="1" required placeholder="0"></td>
                        <td><input type="number" name="import_price[]" min="0" required placeholder="VNĐ"></td>
                        <td><button type="button" class="btn-remove" onclick="removeRow(this)"><i class="fas fa-times-circle"></i></button></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn-add" onclick="addRow()">
                <i class="fas fa-plus"></i> Thêm sản phẩm nhập kho
            </button>

            <button type="submit" name="save_import" class="btn-submit">
                <i class="fas fa-save"></i> Xác nhận & Cập nhật giá
            </button>
        </form>
    </div>
</div>

<script>
function addRow() {
    const tbody = document.getElementById('importBody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    
    // Reset các giá trị khi thêm hàng mới
    newRow.querySelectorAll('input').forEach(i => i.value = '');
    newRow.querySelector('select').selectedIndex = 0;
    
    tbody.appendChild(newRow);
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#importBody tr');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    } else {
        alert("Phiếu nhập cần có ít nhất một mặt hàng!");
    }
}
</script>

</body>
</html>