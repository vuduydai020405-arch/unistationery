<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/database.php';

// Kiểm tra giỏ hàng
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Tạo mã bảo mật cho biểu mẫu
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;
$orderId = null;
$totalAmount = 0;
$discountAmount = 0;
$appliedCouponCode = '';

// Hàm chống lỗi hiển thị HTML
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Xử lý khi khách nhấn nút đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recipientName = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $couponCodeInput = trim($_POST['coupon_code'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'COD';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($recipientName === '' || $phone === '' || $address === '') {
        $error = 'Vui lòng điền đầy đủ thông tin nhận hàng.';
    } elseif (!preg_match('/^[0-9+\-\s]{9,15}$/', $phone)) {
        $error = 'Số điện thoại không hợp lệ.';
    } elseif (!in_array($paymentMethod, ['COD', 'BANK_TRANSFER', 'VNPAY'])) {
        $error = 'Phương thức thanh toán không hợp lệ.';
    } else {

        try {
            $pdo->beginTransaction();

            $items = [];
            $subtotalAmount = 0;

            // Kiểm tra tồn kho và khóa từng phân loại sản phẩm
            foreach ($cart as $variantId => $quantity) {

                $variantId = (int)$variantId;
                $quantity = (int)$quantity;

                if ($variantId <= 0 || $quantity <= 0) {
                    throw new Exception('Thông tin sản phẩm trong giỏ hàng không hợp lệ.');
                }

                $stmt = $pdo->prepare("
                    SELECT 
                        pv.id, 
                        pv.sku, 
                        pv.variant_name, 
                        pv.price, 
                        pv.stock, 
                        pv.status AS variant_status, 
                        p.status AS product_status, 
                        p.name AS product_name 
                    FROM product_variants pv
                    JOIN products p ON pv.product_id = p.id
                    WHERE pv.id = ?
                    FOR UPDATE
                ");

                $stmt->execute([$variantId]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception('Có sản phẩm không còn tồn tại.');
                }

                if ($product['variant_status'] !== 'active' || $product['product_status'] !== 'active') {
                    throw new Exception('Sản phẩm "' . $product['product_name'] . '" hiện không còn được bán.');
                }

                if ((int)$product['stock'] < $quantity) {
                    throw new Exception('Sản phẩm "' . $product['product_name'] . ' - ' . $product['variant_name'] . '" không đủ số lượng tồn kho.');
                }

                $itemSubtotal = (float)$product['price'] * $quantity;
                $subtotalAmount += $itemSubtotal;

                $items[] = [
                    'variant_id' => $variantId,
                    'product_name' => $product['product_name'],
                    'variant_name' => $product['variant_name'],
                    'sku' => $product['sku'],
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal
                ];
            }

            $totalAmount = $subtotalAmount;
            $validCouponId = null;

            // Xử lý mã giảm giá (UC05)
            if ($couponCodeInput !== '') {
                $couponStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' FOR UPDATE");
                $couponStmt->execute([$couponCodeInput]);
                $coupon = $couponStmt->fetch();

                if (!$coupon) {
                    throw new Exception('Mã giảm giá không tồn tại hoặc đã bị khóa.');
                }
                if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
                    throw new Exception('Mã giảm giá đã hết hạn.');
                }
                if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
                    throw new Exception('Mã giảm giá đã hết lượt sử dụng.');
                }
                if ($subtotalAmount < $coupon['min_order_amount']) {
                    throw new Exception('Đơn hàng chưa đạt giá trị tối thiểu (' . number_format($coupon['min_order_amount'], 0, ',', '.') . 'đ) để dùng mã này.');
                }

                if ($coupon['discount_type'] === 'fixed') {
                    $discountAmount = (float)$coupon['discount_value'];
                } else {
                    $discountAmount = $subtotalAmount * ((float)$coupon['discount_value'] / 100);
                }

                if ($discountAmount > $totalAmount) {
                    $discountAmount = $totalAmount;
                }

                $totalAmount -= $discountAmount;
                $validCouponId = $coupon['id'];
                $appliedCouponCode = $coupon['code'];
                $note = trim("Mã giảm giá: {$appliedCouponCode}. " . $note);
            }

            // Lấy ID người dùng nếu đã đăng nhập
            $userId = $_SESSION['user_id'] ?? null;

            // Tạo đơn hàng
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, 
                    recipient_name, 
                    phone, 
                    address, 
                    note, 
                    total_amount, 
                    payment_method, 
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $orderStmt->execute([
                $userId,
                $recipientName,
                $phone,
                $address,
                $note,
                $totalAmount,
                $paymentMethod
            ]);

            $orderId = $pdo->lastInsertId();

            // Lưu chi tiết đơn hàng và trừ tồn kho
            $insertItem = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, 
                    product_variant_id, 
                    product_name, 
                    variant_name, 
                    sku, 
                    unit_price, 
                    quantity, 
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $updateStock = $pdo->prepare("
                UPDATE product_variants 
                SET stock = stock - ? 
                WHERE id = ? AND stock >= ?
            ");

            foreach ($items as $item) {
                $insertItem->execute([
                    $orderId,
                    $item['variant_id'],
                    $item['product_name'],
                    $item['variant_name'],
                    $item['sku'],
                    $item['price'],
                    $item['quantity'],
                    $item['subtotal']
                ]);

                $updateStock->execute([
                    $item['quantity'],
                    $item['variant_id'],
                    $item['quantity']
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new Exception('Không thể cập nhật tồn kho. Vui lòng thử lại.');
                }
            }

            // Cập nhật lượt sử dụng mã giảm giá nếu có
            if ($validCouponId) {
                $updateCoupon = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $updateCoupon->execute([$validCouponId]);
            }

            // Hoàn tất giao dịch
            $pdo->commit();

            // Xóa giỏ hàng sau khi đặt thành công
            unset($_SESSION['cart']);
            unset($_SESSION['applied_coupon']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // ĐIỀU HƯỚNG THEO PHƯƠNG THỨC THANH TOÁN
            if ($paymentMethod === 'BANK_TRANSFER') {
                header("Location: momo_sandbox.php?order_id={$orderId}&amount={$totalAmount}");
                exit;
            }

            if ($paymentMethod === 'VNPAY') {
                header("Location: vnpay_sandbox.php?order_id={$orderId}&amount={$totalAmount}");
                exit;
            }

            $success = true;

        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($exception->getMessage());
            $error = $exception instanceof PDOException 
                ? 'Đã xảy ra lỗi khi lưu đơn hàng. Vui lòng thử lại.' 
                : $exception->getMessage();
        }
    }
}

// Lấy thông tin sản phẩm để hiển thị giao diện giỏ hàng
$cartItems = [];
$total = 0;

if (!$success) {
    $cart = $_SESSION['cart'] ?? [];
    foreach ($cart as $variantId => $quantity) {
        $stmt = $pdo->prepare("
            SELECT 
                pv.id, 
                pv.sku, 
                pv.variant_name, 
                pv.price, 
                pv.stock, 
                p.name AS product_name, 
                p.image_emoji 
            FROM product_variants pv
            JOIN products p ON pv.product_id = p.id
            WHERE pv.id = ?
        ");
        $stmt->execute([(int)$variantId]);
        $product = $stmt->fetch();

        if ($product) {
            $product['quantity'] = (int)$quantity;
            $product['subtotal'] = (float)$product['price'] * (int)$quantity;
            $cartItems[] = $product;
            $total += $product['subtotal'];
        }
    }
}

require_once 'includes/header.php';
?>

<section class="container account-section my-4">
    <?php if ($success): ?>
        <div class="account-card p-4 bg-white shadow-sm rounded">
            <h2>Đặt hàng thành công!</h2>
            <p>Cảm ơn bạn đã mua hàng tại UniStationery.</p>
            <p>Mã đơn hàng của bạn: <strong>#<?php echo e($orderId); ?></strong></p>
            <p>Phương thức thanh toán: <strong>Thanh toán khi nhận hàng (COD)</strong></p>
            <p>Tổng tiền thanh toán: <strong><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</strong></p>

            <div class="mt-4">
                <a href="my_orders.php?id=<?php echo (int)$orderId; ?>" class="btn btn-primary me-2">Xem chi tiết đơn hàng</a>
                <a href="index.php" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
            </div>
        </div>
    <?php else: ?>
        <div class="account-card p-4 bg-white shadow-sm rounded checkout-card">
            <h2>Thông tin đặt hàng</h2>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger mt-3"><?php echo e($error); ?></div>
            <?php endif; ?>

            <h3 class="mt-4">Sản phẩm trong đơn hàng</h3>
            <div class="table-responsive">
                <?php foreach ($cartItems as $item): ?>
                    <div class="checkout-item d-flex justify-content-between align-items-center border-bottom py-2">
                        <span>
                            <?php echo e($item['image_emoji'] ?? '📦'); ?> 
                            <strong><?php echo e($item['product_name']); ?></strong> 
                            <?php echo e($item['variant_name']); ?> (x<?php echo e($item['quantity']); ?>)
                            <br><small class="text-muted">Mã SKU: <?php echo e($item['sku']); ?></small>
                        </span>
                        <strong><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>đ</strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="checkout-total my-3 text-end">
                <h4>Tổng cộng tạm tính: <strong id="cart-total-text"><?php echo number_format($total, 0, ',', '.'); ?>đ</strong></h4>
            </div>

            <form method="POST" action="checkout.php">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

                <div class="mb-3">
                    <label class="form-label">Họ và tên người nhận</label>
                    <input type="text" class="form-control" name="recipient_name" required 
                           value="<?php echo e($_POST['recipient_name'] ?? $_SESSION['user_name'] ?? ''); ?>" placeholder="Nhập họ và tên">
                </div>

                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" class="form-control" name="phone" required 
                           value="<?php echo e($_POST['phone'] ?? ''); ?>" placeholder="Nhập số điện thoại">
                </div>

                <div class="mb-3">
                    <label class="form-label">Địa chỉ nhận hàng</label>
                    <textarea class="form-control" name="address" rows="3" required placeholder="Nhập địa chỉ nhận hàng"><?php echo e($_POST['address'] ?? ''); ?></textarea>
                </div>

                <!-- Mã giảm giá -->
                <div class="mb-3">
                    <label for="coupon_code" class="form-label">Mã giảm giá (nếu có)</label>
                    <input type="text" class="form-control" id="coupon_code" name="coupon_code" 
                           value="<?php echo e($_POST['coupon_code'] ?? ''); ?>" placeholder="Nhập mã (VD: SALE50K)">
                </div>

                <!-- Phương thức thanh toán -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Phương thức thanh toán</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                        <label class="form-check-label" for="cod">Thanh toán khi nhận hàng (COD)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="BANK_TRANSFER" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'BANK_TRANSFER') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="bank_transfer">Chuyển khoản MoMo trước (Quét mã QR)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="vnpay" value="VNPAY" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'VNPAY') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="vnpay">Cổng thanh toán trực tuyến (VNPAY / MoMo giả lập)</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú thêm (nếu có)"><?php echo e($_POST['note'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Xác nhận đặt hàng</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>