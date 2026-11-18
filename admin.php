<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'includes/database.php';

// Cho phép cả Admin và Nhân viên (staff) truy cập
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['admin', 'staff'], true)
) {
    http_response_code(403);
    exit('Bạn không có quyền truy cập trang quản trị.');
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function statusText($status) {
    $statuses = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy',
        'refunded' => 'Trả hàng/Hoàn tiền'
    ];

    return $statuses[$status] ?? 'Không xác định';
}

function statusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-warning text-dark';
        case 'confirmed': return 'bg-info text-dark';
        case 'shipping': return 'bg-primary text-white';
        case 'completed': return 'bg-success text-white';
        case 'cancelled': return 'bg-danger text-white';
        case 'refunded': return 'bg-secondary text-white';
        default: return 'bg-light text-dark';
    }
}

$error = '';
$success = '';

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $orderId = filter_input(
        INPUT_POST,
        'order_id',
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST['status'] ?? '';

    $allowedStatuses = [
        'pending',
        'confirmed',
        'shipping',
        'completed',
        'cancelled',
        'refunded'
    ];

    if (
        !hash_equals($_SESSION['csrf_token'], $token) ||
         !$orderId ||
         !in_array($newStatus, $allowedStatuses, true)
    ) {
        $error = 'Thông tin cập nhật không hợp lệ.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([$newStatus, $orderId]);

            $success = 'Cập nhật trạng thái đơn hàng thành công.';

        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = 'Không thể cập nhật đơn hàng.';
        }
    }
}

// Lấy chi tiết đơn hàng nếu được chọn
$selectedOrder = null;
$orderItems = [];

if (isset($_GET['id'])) {

    $detailId = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if ($detailId) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM orders
            WHERE id = ?
        ");

        $stmt->execute([$detailId]);
        $selectedOrder = $stmt->fetch();

        if ($selectedOrder) {
            $stmt = $pdo->prepare("
                SELECT *
                FROM order_items
                WHERE order_id = ?
            ");

            $stmt->execute([$detailId]);
            $orderItems = $stmt->fetchAll();
        }
    }
}

// Lấy danh sách đơn hàng
$stmt = $pdo->query("
    SELECT *
    FROM orders
    ORDER BY created_at DESC
");

$orders = $stmt->fetchAll();

// Thống kê
$totalOrders = count($orders);
$pendingOrders = 0;
$completedOrders = 0;

foreach ($orders as $order) {
    if ($order['status'] === 'pending') {
        $pendingOrders++;
    }

    if ($order['status'] === 'completed') {
        $completedOrders++;
    }
}

require_once 'includes/header.php';
?>

<section class="container admin-section py-4">

    <h1 class="mb-4" style="font-weight: 700; color: #2c3e50;">Xử lý & Quản lý đơn hàng</h1>

    <div class="admin-stats row mb-4">
        <div class="col-md-4 mb-3">
            <div class="admin-stat-card bg-white p-3 rounded-3 shadow-sm border">
                <h3 class="text-muted fs-6">Tổng đơn hàng</h3>
                <strong class="fs-3 text-dark"><?php echo $totalOrders; ?></strong>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="admin-stat-card bg-white p-3 rounded-3 shadow-sm border">
                <h3 class="text-muted fs-6">Chờ xác nhận</h3>
                <strong class="fs-3 text-warning"><?php echo $pendingOrders; ?></strong>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="admin-stat-card bg-white p-3 rounded-3 shadow-sm border">
                <h3 class="text-muted fs-6">Đã hoàn thành</h3>
                <strong class="fs-3 text-success"><?php echo $completedOrders; ?></strong>
            </div>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger shadow-sm">⚠️ <?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success shadow-sm">✅ <?php echo e($success); ?></div>
    <?php endif; ?>

    <?php if ($selectedOrder): ?>

        <div class="admin-detail-card bg-white p-4 rounded-3 shadow-sm border">

            <h2 class="mb-3" style="font-size: 1.3rem; font-weight: 600;">
                Chi tiết đơn hàng #<?php echo e($selectedOrder['id']); ?>
            </h2>

            <p class="mb-1"><strong>Người nhận:</strong> <?php echo e($selectedOrder['recipient_name']); ?></p>
            <p class="mb-1"><strong>Số điện thoại:</strong> <?php echo e($selectedOrder['phone']); ?></p>
            <p class="mb-1"><strong>Địa chỉ:</strong> <?php echo e($selectedOrder['address']); ?></p>
            <p class="mb-1"><strong>Phương thức thanh toán:</strong> <code><?php echo e($selectedOrder['payment_method'] ?? 'COD'); ?></code></p>
            <p class="mb-1"><strong>Ghi chú:</strong> <?php echo e($selectedOrder['note'] ?? 'Không có'); ?></p>
            <p class="mb-3"><strong>Ngày đặt:</strong> <?php echo e($selectedOrder['created_at']); ?></p>

            <h3 class="fs-5 mt-4 mb-3">Sản phẩm trong đơn hàng</h3>

            <div class="list-group mb-3">
                <?php foreach ($orderItems as $item): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo e($item['product_name']); ?></strong>
                            <?php if (!empty($item['variant_name'])): ?>
                                <small class="text-muted">(Phân loại: <?php echo e($item['variant_name']); ?>)</small>
                            <?php endif; ?>
                            <br><small class="text-muted">Số lượng: <?php echo (int) $item['quantity']; ?></small>
                        </div>
                        <strong class="text-danger">
                            <?php echo number_format((float) $item['subtotal'], 0, ',', '.'); ?>₫
                        </strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="fs-5 text-end mb-4">
                Tổng tiền: <strong class="text-danger"><?php echo number_format((float) $selectedOrder['total_amount'], 0, ',', '.'); ?>₫</strong>
            </h3>

            <hr>

            <h3 class="fs-5 mb-3">Cập nhật trạng thái đơn hàng</h3>

            <form method="POST" action="admin.php?id=<?php echo (int)$selectedOrder['id']; ?>">

                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="order_id" value="<?php echo (int) $selectedOrder['id']; ?>">

                <div class="mb-3" style="max-width: 400px;">
                    <select name="status" class="form-select" required>
                        <option value="pending" <?php echo $selectedOrder['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                        <option value="confirmed" <?php echo $selectedOrder['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                        <option value="shipping" <?php echo $selectedOrder['status'] === 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                        <option value="completed" <?php echo $selectedOrder['status'] === 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                        <option value="cancelled" <?php echo $selectedOrder['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                        <option value="refunded" <?php echo $selectedOrder['status'] === 'refunded' ? 'selected' : ''; ?>>Trả hàng/Hoàn tiền</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-dark px-4 py-2" style="background: #2c3e50; border-color: #2c3e50;">
                    Lưu trạng thái
                </button>
            </form>

            <a href="admin.php" class="btn btn-outline-secondary mt-3 d-inline-block">
                Quay lại danh sách
            </a>

        </div>

    <?php else: ?>

        <h2 class="fs-4 mb-3">Danh sách đơn hàng</h2>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">Chưa có đơn hàng nào.</div>
        <?php else: ?>
            <div class="admin-table-wrapper bg-white p-3 rounded-3 shadow-sm border">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Người nhận</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo e($order['id']); ?></strong></td>
                                <td><?php echo e($order['recipient_name']); ?></td>
                                <td><?php echo e($order['created_at']); ?></td>
                                <td><strong class="text-danger"><?php echo number_format((float) $order['total_amount'], 0, ',', '.'); ?>₫</strong></td>
                                <td>
                                    <span class="badge <?php echo statusBadgeClass($order['status']); ?>">
                                        <?php echo e(statusText($order['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="admin.php?id=<?php echo (int) $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</section>

<?php require_once 'includes/footer.php'; ?>