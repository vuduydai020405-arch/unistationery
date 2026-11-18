<?php
// Khởi tạo session nếu chưa được khởi tạo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UniStationery - Văn phòng phẩm cho sinh viên</title>

    <meta name="description" content="Khám phá văn phòng phẩm, sổ tay, bút viết và combo học tập tại UniStationery.">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header class="site-header">
    <div class="container header-content">

        <!-- Logo -->
        <a class="logo" href="index.php">
            Uni<span>Stationery</span>
        </a>

        <!-- Thanh điều hướng -->
        <nav class="navbar">
            <div class="nav-links">

                <a href="index.php">Trang chủ</a>

                <a href="index.php#products">Sản phẩm</a>

                <!-- Form tìm kiếm nhanh trên header -->
<form action="search.php" method="GET" style="display: flex; align-items: center; background: #fff; border: 1px solid #ddd; border-radius: 20px; overflow: hidden; padding: 2px 10px; max-width: 200px; margin: 0 10px;">
    <input type="search" name="q" placeholder="Tìm sản phẩm..." required style="border: none; outline: none; font-size: 13px; width: 100%; background: transparent; padding: 4px 0;">
    <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 14px; padding: 0 0 0 5px; color: #666;" title="Tìm kiếm">🔍</button>
</form>

                <a href="cart.php">Giỏ hàng 🛒</a>

                <?php if (isset($_SESSION['user_id'])): ?>

                    <?php 
                    $userRole = $_SESSION['user_role'] ?? 'customer';
                    ?>

                    <!-- Menu dành cho Khách hàng & Nhân viên (có thể mua hàng/xem đơn của mình) -->
                    <?php if ($userRole !== 'admin'): ?>
                        <a href="my_orders.php">Đơn hàng của tôi</a>
                    <?php endif; ?>

                    <!-- Các chức năng dành riêng cho Nhân viên (Staff) -->
                    <?php if ($userRole === 'staff'): ?>
                        <a href="admin.php">Xử lý đơn hàng</a>
                    <?php endif; ?>

                    <!-- Các chức năng dành riêng cho Admin -->
                    <?php if ($userRole === 'admin'): ?>
                        <a href="admin.php">Quản trị đơn hàng</a>
                        <a href="admin_products.php">Quản lý sản phẩm</a>
                        <a href="admin_staff.php">Phân quyền nhân viên</a>
                        <a href="admin_dashboard.php">Thống kê</a>
                    <?php endif; ?>

                    <!-- Thông tin tài khoản -->
                    <span class="welcome-user">
                        Xin chào,
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Người dùng',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                        <?php if ($userRole === 'admin'): ?>
                            <span class="badge bg-danger" style="font-size: 0.75rem;">(Admin)</span>
                        <?php elseif ($userRole === 'staff'): ?>
                            <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">(Nhân viên)</span>
                        <?php endif; ?>
                    </span>

                    <a href="logout.php" class="nav-account">
                        Đăng xuất
                    </a>

                <?php else: ?>

                    <a href="login.php" class="nav-account">
                        Đăng nhập
                    </a>

                    <a href="register.php" class="nav-account">
                        Đăng ký
                    </a>

                <?php endif; ?>

            </div>
        </nav>

    </div>
</header>

<main>