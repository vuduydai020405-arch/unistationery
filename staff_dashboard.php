<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php';

// Kiểm tra quyền nhân viên hoặc admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';
?>

<section class="container my-4">
    <div class="p-5 bg-light rounded-3 shadow-sm">
        <h1>Khu vực làm việc của Nhân viên (Staff Dashboard)</h1>
        <p class="lead">Chào mừng nhân viên <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Nhân viên'); ?></strong> đã đăng nhập hệ thống.</p>
        <hr class="my-4">
        <p>Tại đây bạn có thể thực hiện các nghiệp vụ hỗ trợ quản lý cửa hàng:</p>
        <div class="d-flex gap-3">
            <a href="admin.php" class="btn btn-primary btn-lg">📦 Kiểm tra & Xử lý đơn hàng</a>
            <a href="admin_products.php" class="btn btn-outline-secondary btn-lg">🛍️ Xem danh sách sản phẩm</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>