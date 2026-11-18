<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/database.php';

// Chỉ khách hàng đã đăng nhập mới được truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// Kiểm tra xem đơn hàng có tồn tại, thuộc quyền sở hữu của user và đang ở trạng thái pending không
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    die("Đơn hàng không tồn tại hoặc bạn không có quyền truy cập.");
}

if ($order['status'] !== 'pending') {
    // Nếu đơn hàng đã được xử lý trước đó rồi, chuyển hướng về trang lịch sử
    header("Location: my_orders.php?id={$orderId}&notice=already_processed");
    exit;
}

// Xử lý khi người dùng bấm nút trên cổng giả lập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'success') {
        // Cập nhật trạng thái đơn hàng thành 'confirmed' (hoặc 'paid') khi thanh toán thành công
        $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$orderId]);

        header("Location: my_orders.php?id={$orderId}&paid=success");
        exit;
    } elseif ($action === 'cancel') {
        // Giữ nguyên trạng thái pending hoặc thông báo hủy giao dịch cổng thanh toán
        header("Location: my_orders.php?id={$orderId}&paid=cancelled");
        exit;
    }
}

require_once 'includes/header.php';
?>

<section class="container my-5" style="max-width: 600px;">
    <div class="card shadow border-primary">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0">CỔNG THANH TOÁN TRỰC TUYẾN (SANDBOX)</h4>
        </div>
        <div class="card-body p-4 text-center">
            <div class="mb-4">
                <span style="font-size: 3rem;">💳</span>
                <h5 class="text-secondary mt-2">Mô phỏng VNPAY / MoMo Payment Gateway</h5>
                <p class="text-muted small">Môi trường kiểm thử phục vụ bảo vệ đồ án UniStationery.</p>
            </div>
            
            <ul class="list-group text-start mb-4">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Mã đơn hàng:</span>
                    <strong>#<?php echo $orderId; ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Đơn vị nhận:</span>
                    <strong>UniStationery Store</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Số tiền cần thanh toán:</span>
                    <strong class="text-danger"><?php echo number_format($amount > 0 ? $amount : $order['total_amount'], 0, ',', '.'); ?>đ</strong>
                </li>
            </ul>

            <form method="POST" class="d-grid gap-2">
                <button type="submit" name="action" value="success" class="btn btn-success btn-lg">
                    ✅ Mô phỏng thanh toán THÀNH CÔNG
                </button>
                <button type="submit" name="action" value="cancel" class="btn btn-outline-danger">
                    ❌ Hủy giao dịch / Quay lại
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>