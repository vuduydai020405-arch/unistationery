<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/database.php';

// Kiểm tra quyền Quản trị viên (Admin)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Tạo CSRF token nếu chưa có
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// Xử lý khi Admin thêm mã giảm giá mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $code = trim($_POST['code'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'fixed';
        $discountValue = trim($_POST['discount_value'] ?? '');
        $minOrderAmount = trim($_POST['min_order_amount'] ?? '0');
        $usageLimit = trim($_POST['usage_limit'] ?? '100');
        $expiresAt = trim($_POST['expires_at'] ?? '');

        if ($code === '' || $discountValue === '') {
            $error = 'Vui lòng nhập đầy đủ mã giảm giá và giá trị giảm.';
        } elseif (!is_numeric($discountValue) || (float)$discountValue <= 0) {
            $error = 'Giá trị giảm phải là số lớn hơn 0.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, usage_limit, expires_at, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([
                    strtoupper($code),
                    $discountType,
                    $discountValue,
                    $minOrderAmount,
                    $usageLimit,
                    $expiresAt !== '' ? $expiresAt : null
                ]);
                $message = 'Thêm mã giảm giá thành công!';
            } catch (PDOException $e) {
                $error = 'Mã giảm giá này đã tồn tại hoặc dữ liệu không hợp lệ.';
            }
        }
    }
}

// Lấy danh sách mã giảm giá từ CSDL
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
$coupons = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

require_once 'includes/header.php';
?>

<section class="container account-section my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Quản lý Mã giảm giá</h1>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary">← Quay lại Dashboard</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?php echo e($message); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <!-- Form thêm mã giảm giá mới -->
    <div class="account-card p-4 bg-white shadow-sm rounded mb-4">
        <h3>Thêm mã giảm giá mới</h3>
        <form method="POST" action="admin_coupons.php" class="row g-3 mt-2">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

            <div class="col-md-4">
                <label class="form-label">Mã giảm giá (Code)</label>
                <input type="text" class="form-control" name="code" required placeholder="VD: SALE50K">
            </div>
            <div class="col-md-4">
                <label class="form-label">Loại giảm giá</label>
                <select class="form-control" name="discount_type">
                    <option value="fixed">Giảm số tiền cố định (đ)</option>
                    <option value="percent">Giảm theo phần trăm (%)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Giá trị giảm</label>
                <input type="number" step="0.01" class="form-control" name="discount_value" required placeholder="VD: 10000 hoặc 15">
            </div>
            <div class="col-md-4">
                <label class="form-label">Đơn hàng tối thiểu (đ)</label>
                <input type="number" class="form-control" name="min_order_amount" value="0" placeholder="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Giới hạn lượt dùng</label>
                <input type="number" class="form-control" name="usage_limit" value="100" placeholder="100">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ngày hết hạn</label>
                <input type="datetime-local" class="form-control" name="expires_at">
            </div>
            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-primary">Tạo mã giảm giá</button>
            </div>
        </form>
    </div>

    <!-- Bảng danh sách mã giảm giá -->
    <div class="account-card p-4 bg-white shadow-sm rounded">
        <h3>Danh sách mã hiện có</h3>
        <?php if (empty($coupons)): ?>
            <p class="text-muted mt-2">Chưa có mã giảm giá nào.</p>
        <?php else: ?>
            <div class="table-responsive mt-3">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Mã Code</th>
                            <th>Loại giảm</th>
                            <th>Giá trị</th>
                            <th>Đơn tối thiểu</th>
                            <th>Đã dùng / Giới hạn</th>
                            <th>Hết hạn</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $c): ?>
                            <tr>
                                <td>#<?php echo e($c['id']); ?></td>
                                <td><strong><?php echo e($c['code']); ?></strong></td>
                                <td><?php echo $c['discount_type'] === 'fixed' ? 'Tiền mặt' : 'Phần trăm'; ?></td>
                                <td>
                                    <?php 
                                        if ($c['discount_type'] === 'fixed') {
                                            echo number_format($c['discount_value'], 0, ',', '.') . 'đ';
                                        } else {
                                            echo $c['discount_value'] . '%';
                                        }
                                    ?>
                                </td>
                                <td><?php echo number_format($c['min_order_amount'], 0, ',', '.'); ?>đ</td>
                                <td><?php echo e($c['used_count']); ?> / <?php echo e($c['usage_limit']); ?></td>
                                <td><?php echo e($c['expires_at'] ?? 'Không thời hạn'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $c['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo e($c['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>