```php
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatMoney($amount) {
    return number_format((float) $amount, 0, ',', '.') . '₫';
}

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    header('Location: my_orders.php');
    exit;
}

// Chỉ lấy đơn hàng thuộc về tài khoản đang đăng nhập
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.');
}

// Lấy sản phẩm trong đơn hàng
$stmt = $pdo->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$steps = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao hàng',
    'completed' => 'Đã hoàn thành'
];

$statusOrder = array_keys($steps);
$currentStatus = $order['status'];
$isCancelled = ($currentStatus === 'cancelled');

$currentIndex = array_search($currentStatus, $statusOrder, true);

require_once 'includes/header.php';
?>

<section class="container order-detail-page">

    <div class="order-detail-heading">
        <h1>Chi tiết đơn hàng</h1>

        <p>
            Mã đơn hàng:
            <strong>#<?php echo e($order['id']); ?></strong>
        </p>

        <p>
            Ngày đặt:
            <?php echo e($order['created_at']); ?>
        </p>
    </div>

    <div class="order-tracking-card">

        <h2>Trạng thái đơn hàng</h2>

        <?php if ($isCancelled): ?>

            <div class="cancelled-status">
                <strong>Đơn hàng đã bị hủy</strong>
                <p>Đơn hàng này không còn trong quá trình xử lý.</p>
            </div>

        <?php else: ?>

            <div class="tracking-list">

                <?php foreach ($steps as $status => $label): ?>

                    <?php
                    $stepIndex = array_search($status, $statusOrder, true);
                    $isDone = $currentIndex !== false
                        && $stepIndex <= $currentIndex;
                    ?>

                    <div class="tracking-step <?php echo $isDone ? 'completed' : ''; ?>">

                        <div class="tracking-circle">
                            <?php echo $isDone ? '✓' : ($stepIndex + 1); ?>
                        </div>

                        <div class="tracking-label">
                            <?php echo e($label); ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

    <div class="order-info-card">

        <h2>Thông tin nhận hàng</h2>

        <p>
            <strong>Người nhận:</strong>
            <?php echo e($order['recipient_name']); ?>
        </p>

        <p>
            <strong>Số điện thoại:</strong>
            <?php echo e($order['phone']); ?>
        </p>

        <p>
            <strong>Địa chỉ:</strong>
            <?php echo e($order['address']); ?>
        </p>

        <?php if (!empty($order['note'])): ?>
            <p>
                <strong>Ghi chú:</strong>
                <?php echo e($order['note']); ?>
            </p>
        <?php endif; ?>

    </div>

    <div class="order-products-card">

        <h2>Sản phẩm đã đặt</h2>

        <?php if (empty($items)): ?>

            <p>Đơn hàng chưa có thông tin sản phẩm.</p>

        <?php else: ?>

            <?php foreach ($items as $item): ?>

                <div class="order-product-row">

                    <div class="order-product-info">

                        <strong>
                            <?php echo e($item['product_name']); ?>
                        </strong>

                        <?php if (!empty($item['variant_name'])): ?>
                            <p>
                                Phân loại:
                                <?php echo e($item['variant_name']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($item['sku'])): ?>
                            <p>
                                SKU: <?php echo e($item['sku']); ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            Số lượng:
                            <?php echo (int) $item['quantity']; ?>
                        </p>

                    </div>

                    <strong>
                        <?php echo formatMoney($item['subtotal']); ?>
                    </strong>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

        <div class="order-total-row">
            <span>Tổng tiền</span>

            <strong>
                <?php echo formatMoney($order['total_amount']); ?>
            </strong>
        </div>

    </div>

    <a href="my_orders.php" class="primary-button">
        ← Quay lại đơn hàng của tôi
    </a>

</section>

<?php require_once 'includes/footer.php'; ?>
```