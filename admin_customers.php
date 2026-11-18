<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'includes/database.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Lấy danh sách khách hàng và tổng số đơn hàng, tổng chi tiêu của từng người
$stmt = $pdo->query("
    SELECT 
        u.id, 
        u.full_name, 
        u.email, 
        u.phone, 
        u.created_at,
        COUNT(o.id) AS total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$customers = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="container admin-customers-section py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý khách hàng</h1>
            <p class="text-muted small mb-0">Theo dõi thông tin tài khoản và lịch sử chi tiêu của khách hàng.</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            ← Quay lại Dashboard
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 bg-white overflow-hidden">
        <div class="card-body p-4">
            <?php if (empty($customers)): ?>
                <div class="text-center py-5">
                    <p class="text-muted mb-0">Chưa có tài khoản khách hàng nào trong hệ thống.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase fs-7" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="py-3 ps-3">ID</th>
                                <th class="py-3">Họ và tên</th>
                                <th class="py-3">Email</th>
                                <th class="py-3">Số điện thoại</th>
                                <th class="py-3 text-center">Số đơn hàng</th>
                                <th class="py-3">Tổng chi tiêu</th>
                                <th class="py-3 pe-3">Ngày đăng ký</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-secondary">#<?php echo e($c['id']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-15 text-primary d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                <?php echo mb_strtoupper(mb_substr($c['full_name'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo e($c['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo e($c['email']); ?></td>
                                    <td>
                                        <?php if (!empty($c['phone'])): ?>
                                            <span><?php echo e($c['phone']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary fw-normal border">Chưa cập nhật</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info bg-opacity-15 text-info px-2 py-1 fw-semibold">
                                            <?php echo number_format($c['total_orders']); ?> đơn
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            <?php echo number_format($c['total_spent'], 0, ',', '.'); ?>đ
                                        </strong>
                                    </td>
                                    <td class="pe-3 text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>