<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/database.php';

// Chỉ khách hàng đã đăng nhập mới được xem đơn hàng
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

// Xử lý hủy đơn hàng nếu khách bấm nút hủy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancelOrderId = (int)$_POST['cancel_order_id'];

    try {
        // Kiểm tra xem đơn có đúng của user này và đang ở trạng thái pending không
        $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$cancelOrderId, $userId]);
        $orderToCancel = $stmt->fetch();

        if (!$orderToCancel) {
            $error = 'Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn này.';
        } elseif ($orderToCancel['status'] !== 'pending') {
            $error = 'Chỉ có thể hủy đơn hàng khi đang ở trạng thái Chờ xác nhận.';
        } else {
            // Bắt đầu Transaction để hoàn lại tồn kho và cập nhật trạng thái
            $pdo->beginTransaction();

            // Lấy danh sách sản phẩm trong đơn để hoàn lại stock
            $itemsStmt = $pdo->prepare("SELECT product_variant_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$cancelOrderId]);
            $orderItemsList = $itemsStmt->fetchAll();

            $updateStock = $pdo->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");
            foreach ($orderItemsList as $it) {
                $updateStock->execute([$it['quantity'], $it['product_variant_id']]);
            }

            // Cập nhật trạng thái đơn thành cancelled
            $cancelStmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $cancelStmt->execute([$cancelOrderId]);

            $pdo->commit();
            
            // Chuyển hướng lại trang chi tiết để thấy trạng thái mới
            header("Location: my_orders.php?id=" . $cancelOrderId . "&success=cancelled");
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($e->getMessage());
        $error = 'Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại.';
    }
}

// Thông báo thành công khi hủy
if (($_GET['success'] ?? '') === 'cancelled') {
    $message = 'Đã hủy đơn hàng thành công và hoàn lại tồn kho.';
}

// Hàm bảo vệ nội dung hiển thị
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Chuyển trạng thái sang tiếng Việt
function orderStatus($status) {
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

// Lấy danh sách đơn hàng của người đang đăng nhập
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Nếu khách chọn xem chi tiết một đơn hàng
$selectedOrder = null;
$orderItems = [];

if (isset($_GET['id'])) {
    $orderId = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if ($orderId) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM orders
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([$orderId, $userId]);
        $selectedOrder = $stmt->fetch();

        if ($selectedOrder) {
            $stmt = $pdo->prepare("
                SELECT *
                FROM order_items
                WHERE order_id = ?
                ORDER BY id ASC
            ");

            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll();
        }
    }
}

require_once 'includes/header.php';
?>

<section class="container orders-section my-4">

    <h1>Đơn hàng của tôi</h1>

    <!-- Thông báo kết quả từ Cổng thanh toán trực tuyến (VNPAY Sandbox) -->
    <?php if (isset($_GET['paid']) && $_GET['paid'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <strong>Thanh toán thành công!</strong> Giao dịch qua cổng trực tuyến đã được xác nhận tự động.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['paid']) && $_GET['paid'] === 'cancelled'): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <strong>Đã hủy giao dịch!</strong> Bạn đã hủy quá trình thanh toán trực tuyến. Đơn hàng đang ở trạng thái chờ.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success mt-3"><?php echo e($message); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger mt-3"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($selectedOrder): ?>

        <!-- Chi tiết đơn hàng -->
        <div class="order-detail-card mt-3 bg-white p-4 shadow-sm rounded">

            <h2>Chi tiết đơn hàng #<?php echo e($selectedOrder['id']); ?></h2>

            <p>
                <strong>Ngày đặt:</strong>
                <?php echo e($selectedOrder['created_at']); ?>
            </p>

            <p>
                <strong>Trạng thái:</strong>
                <span class="badge bg-secondary"><?php echo e(orderStatus($selectedOrder['status'])); ?></span>
            </p>

            <p>
                <strong>Phương thức thanh toán:</strong>
                <?php 
                    $pm = $selectedOrder['payment_method'] ?? 'COD';
                    if ($pm === 'BANK_TRANSFER') echo 'Chuyển khoản MoMo (QR)';
                    elseif ($pm === 'VNPAY') echo 'Cổng thanh toán trực tuyến (VNPAY/MoMo)';
                    else echo 'Thanh toán khi nhận hàng (COD)';
                ?>
            </p>

            <hr>

            <h3>Thông tin nhận hàng</h3>

            <p>
                <strong>Người nhận:</strong>
                <?php echo e($selectedOrder['recipient_name']); ?>
            </p>

            <p>
                <strong>Số điện thoại:</strong>
                <?php echo e($selectedOrder['phone']); ?>
            </p>

            <p>
                <strong>Địa chỉ:</strong>
                <?php echo e($selectedOrder['address']); ?>
            </p>

            <?php if (!empty($selectedOrder['note'])): ?>
                <p>
                    <strong>Ghi chú:</strong>
                    <?php echo e($selectedOrder['note']); ?>
                </p>
            <?php endif; ?>

            <hr>

            <h3>Sản phẩm đã đặt</h3>

            <?php if (empty($orderItems)): ?>
                <p>Không có sản phẩm trong đơn hàng.</p>
            <?php else: ?>

                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item-row d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>
                                <?php echo e($item['product_name']); ?>
                            </strong>

                            <?php if (!empty($item['variant_name'])): ?>
                                <small class="text-muted d-block">
                                    Phân loại: <?php echo e($item['variant_name']); ?>
                                </small>
                            <?php endif; ?>

                            <small class="text-muted">
                                Số lượng: <?php echo (int) $item['quantity']; ?>
                            </small>
                        </div>

                        <strong>
                            <?php
                            echo number_format(
                                (float) $item['subtotal'],
                                0,
                                ',',
                                '.'
                            );
                            ?>₫
                        </strong>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

            <div class="order-detail-total text-end mt-3">
                Tổng tiền:
                <strong>
                    <?php
                    echo number_format(
                        (float) $selectedOrder['total_amount'],
                        0,
                         ',',
                        '.'
                    );
                    ?>₫
                </strong>
            </div>

            <!-- Nút Hủy đơn hàng nếu đang ở trạng thái Chờ xác nhận -->
            <?php if ($selectedOrder['status'] === 'pending'): ?>
                <form method="POST" action="my_orders.php?id=<?php echo (int)$selectedOrder['id']; ?>" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');" class="mt-3">
                    <input type="hidden" name="cancel_order_id" value="<?php echo (int) $selectedOrder['id']; ?>">
                    <button type="submit" class="btn btn-danger">Hủy đơn hàng</button>
                </form>
            <?php endif; ?>

            <a href="my_orders.php" class="btn btn-outline-secondary mt-3 d-inline-block">
                Quay lại danh sách đơn hàng
            </a>

        </div>

    <?php else: ?>

        <!-- Danh sách đơn hàng -->
        <?php if (empty($orders)): ?>

            <div class="order-empty mt-3 text-center py-5 bg-white shadow-sm rounded">
                <h3>Bạn chưa có đơn hàng nào.</h3>
                <a href="index.php" class="btn btn-primary mt-2">
                    Mua sắm ngay
                </a>
            </div>

        <?php else: ?>

            <div class="orders-list mt-3">

                <?php foreach ($orders as $order): ?>

                    <article class="order-card card mb-3 p-3 shadow-sm">

                        <div class="order-card-header d-flex justify-content-between align-items-center mb-2">
                            <strong>
                                Đơn hàng #<?php echo e($order['id']); ?>
                            </strong>

                            <span class="badge bg-info text-dark">
                                <?php echo e(orderStatus($order['status'])); ?>
                            </span>
                        </div>

                        <p class="mb-1 text-muted small">
                            Ngày đặt: <?php echo e($order['created_at']); ?>
                        </p>

                        <p class="mb-1">
                            Người nhận: <?php echo e($order['recipient_name']); ?>
                        </p>

                        <p class="mb-2">
                            Tổng tiền: 
                            <strong class="text-danger">
                                <?php
                                echo number_format(
                                    (float) $order['total_amount'],
                                    0,
                                    ',',
                                    '.'
                                );
                                ?>₫
                            </strong>
                        </p>

                        <div>
                            <a href="my_orders.php?id=<?php echo (int) $order['id']; ?>" class="btn btn-sm btn-primary">
                                Xem chi tiết
                            </a>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</section>

<?php require_once 'includes/footer.php'; ?>