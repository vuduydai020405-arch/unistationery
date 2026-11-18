<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/includes/database.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$cartError = '';
if (isset($_SESSION['cart_error'])) {
    $cartError = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}

$message = '';
if (isset($_SESSION['cart_message'])) {
    $message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}

// Xử lý các thao tác trong giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Thêm biến thể vào giỏ
    if ($action === 'add') {
        $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

        if (!$variantId || $variantId < 1 || $quantity < 1 || $quantity > 99) {
            $_SESSION['cart_error'] = 'Thông tin sản phẩm không hợp lệ.';
            header('Location: cart.php');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT v.id, v.stock, v.status AS variant_status, p.status AS product_status
            FROM product_variants v
            JOIN products p ON p.id = v.product_id
            WHERE v.id = ?
            LIMIT 1
        ");
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch();

        if (!$variant || $variant['variant_status'] !== 'active' || $variant['product_status'] !== 'active') {
            $_SESSION['cart_error'] = 'Biến thể sản phẩm hiện không khả dụng.';
            header('Location: cart.php');
            exit;
        }

        $currentQuantity = (int)($_SESSION['cart'][$variantId] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > (int)$variant['stock']) {
            $_SESSION['cart_error'] = 'Số lượng sản phẩm vượt quá tồn kho hiện có.';
            header('Location: cart.php');
            exit;
        }

        $_SESSION['cart'][$variantId] = $newQuantity;
        header('Location: cart.php');
        exit;
    }

    // Cập nhật số lượng
    if ($action === 'update') {
        $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if (!$variantId || !isset($_SESSION['cart'][$variantId])) {
            $_SESSION['cart_error'] = 'Sản phẩm trong giỏ không hợp lệ.';
            header('Location: cart.php');
            exit;
        }

        if ($quantity === false || $quantity === null || $quantity <= 0) {
            unset($_SESSION['cart'][$variantId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT v.stock, v.status AS variant_status, p.status AS product_status
                FROM product_variants v
                JOIN products p ON p.id = v.product_id
                WHERE v.id = ?
            ");
            $stmt->execute([$variantId]);
            $variant = $stmt->fetch();

            if (!$variant || $variant['variant_status'] !== 'active' || $variant['product_status'] !== 'active') {
                $_SESSION['cart_error'] = 'Biến thể hiện không khả dụng.';
                header('Location: cart.php');
                exit;
            }

            if ($quantity > (int)$variant['stock']) {
                $_SESSION['cart_error'] = 'Số lượng yêu cầu vượt quá tồn kho.';
                header('Location: cart.php');
                exit;
            }

            $_SESSION['cart'][$variantId] = $quantity;
        }

        header('Location: cart.php');
        exit;
    }

    // Xóa biến thể khỏi giỏ
    if ($action === 'remove') {
        $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
        if ($variantId) {
            unset($_SESSION['cart'][$variantId]);
        }
        header('Location: cart.php');
        exit;
    }

    // Xóa mã giảm giá
    if ($action === 'remove_coupon') {
        unset($_SESSION['applied_coupon']);
        $_SESSION['cart_message'] = 'Đã hủy mã giảm giá.';
        header('Location: cart.php');
        exit;
    }
}

// Lấy thông tin các biến thể trong giỏ và tính tổng tiền ($total) trước
$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $variantIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($variantIds), '?'));

    $sql = "
        SELECT
            v.id AS variant_id,
            v.sku,
            v.variant_name,
            v.price,
            v.stock,
            v.status AS variant_status,
            p.name,
            p.image_emoji,
            p.status AS product_status
        FROM product_variants v
        JOIN products p ON p.id = v.product_id
        WHERE v.id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($variantIds);
    $foundIds = [];

    foreach ($stmt->fetchAll() as $item) {
        $variantId = (int)$item['variant_id'];
        $foundIds[] = $variantId;

        if ($item['variant_status'] !== 'active' || $item['product_status'] !== 'active') {
            unset($_SESSION['cart'][$variantId]);
            continue;
        }

        $quantity = (int)$_SESSION['cart'][$variantId];

        if ($quantity > (int)$item['stock']) {
            $quantity = (int)$item['stock'];
            if ($quantity > 0) {
                $_SESSION['cart'][$variantId] = $quantity;
            } else {
                unset($_SESSION['cart'][$variantId]);
                continue;
            }
        }

        $item['quantity'] = $quantity;
        $item['subtotal'] = (float)$item['price'] * $quantity;
        $cartItems[] = $item;
        $total += $item['subtotal'];
    }

    foreach ($variantIds as $variantId) {
        if (!in_array((int)$variantId, $foundIds, true)) {
            unset($_SESSION['cart'][$variantId]);
        }
    }
}

// Xử lý áp dụng mã giảm giá (Có kiểm tra điều kiện min_order ở backend)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_coupon') {
    $couponCode = trim($_POST['coupon_code'] ?? '');

    if ($couponCode === '') {
        $_SESSION['cart_error'] = 'Vui lòng nhập mã giảm giá.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            $_SESSION['cart_error'] = 'Mã giảm giá không tồn tại hoặc đã hết hiệu lực.';
        } else {
            $minOrder = isset($coupon['min_order']) ? (float)$coupon['min_order'] : 0.0;
            
            if ($total < $minOrder) {
                $_SESSION['cart_error'] = 'Đơn hàng chưa đạt giá trị tối thiểu (' . number_format($minOrder, 0, ',', '.') . '₫) để sử dụng mã này.';
            } else {
                $discountType = $coupon['discount_type'] ?? 'percent';
                $discountValue = (float)($coupon['discount_value'] ?? $coupon['discount_percent'] ?? 10);

                $_SESSION['applied_coupon'] = [
                    'code' => $coupon['code'],
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue
                ];
                $_SESSION['cart_message'] = 'Áp dụng mã giảm giá thành công!';
            }
        }
    }
    header('Location: cart.php');
    exit;
}

// Tính toán giảm giá từ mã coupon nếu có (Chuẩn hóa cho cả percent và fixed)
$discountAmount = 0;
if (isset($_SESSION['applied_coupon']) && $total > 0) {
    $type = $_SESSION['applied_coupon']['discount_type'] ?? 'percent';
    $val = (float)($_SESSION['applied_coupon']['discount_value'] ?? 0);

    if ($type === 'fixed') {
        $discountAmount = $val;
    } else {
        $discountAmount = ($total * $val) / 100;
    }

    // Không cho phép số tiền giảm vượt quá tạm tính của giỏ hàng
    if ($discountAmount > $total) {
        $discountAmount = $total;
    }
}
$finalTotal = max(0, $total - $discountAmount);

require __DIR__ . '/includes/header.php';
?>

<section class="container cart-section my-4" style="max-width: 1000px;">

    <h2 class="mb-4" style="font-weight: 700; color: #2c3e50;">🛒 Giỏ hàng của bạn</h2>

    <?php if (!empty($cartError)): ?>
        <div class="alert alert-danger shadow-sm" style="background: #fff2f2; color: #ff4d4f; padding: 14px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffccc7;">
            ⚠️ <?php echo e($cartError); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success shadow-sm" style="background: #f6ffed; color: #52c41a; padding: 14px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #b7eb8f;">
            ✅ <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

        <div class="text-center py-5 bg-white shadow-sm rounded-3">
            <h3 style="color: #2c3e50;">Giỏ hàng đang trống.</h3>
            <p class="text-muted">Bạn chưa chọn sản phẩm nào vào giỏ hàng.</p>
            <a class="btn btn-dark mt-2 px-4 py-2" href="index.php#products" style="border-radius: 6px; font-weight: 600;">
                Tiếp tục mua sắm
            </a>
        </div>

    <?php else: ?>

        <!-- DANH SÁCH SẢN PHẨM TRONG GIỎ HÀNG -->
        <div class="mb-4 shadow-sm" style="background: #fff; border-radius: 12px; border: 1px solid #eaeaea; overflow: hidden;">
            <div style="padding: 16px 24px; background: #fdfbf7; border-bottom: 1px solid #eaeaea;">
                <h5 style="font-size: 17px; font-weight: 700; color: #2c3e50; margin: 0;">📦 Sản phẩm đã chọn (<?php echo count($cartItems); ?>)</h5>
            </div>

            <div style="padding: 0 24px;">
                <?php foreach ($cartItems as $index => $item): ?>
                    <article class="cart-item py-4 d-flex justify-content-between align-items-center <?php echo ($index < count($cartItems) - 1) ? 'border-bottom' : ''; ?>" style="border-color: #f1f1f1 !important;">

                        <!-- Thông tin sản phẩm -->
                        <div class="cart-product d-flex align-items-center gap-3" style="max-width: 450px;">
                            <div style="width: 65px; height: 65px; background: #fdf8f5; border: 1px solid #f0e6df; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                                <?php echo e($item['image_emoji']); ?>
                            </div>
                            <div>
                                <h5 class="mb-1" style="font-size: 16px; font-weight: 600; color: #2c3e50;"><?php echo e($item['name']); ?></h5>
                                <p class="mb-1 text-muted" style="font-size: 13px;">Phân loại: <strong style="color: #e67e22;"><?php echo e($item['variant_name']); ?></strong></p>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="text-muted" style="font-size: 12px;">SKU: <?php echo e($item['sku']); ?></span>
                                    <span class="text-muted" style="font-size: 12px;">Kho: <?php echo (int)$item['stock']; ?></span>
                                </div>
                                <p class="mb-0 mt-1" style="font-size: 13.5px; font-weight: 600; color: #e67e22;"><?php echo number_format((float)$item['price'], 0, ',', '.'); ?>₫ / sản phẩm</p>
                            </div>
                        </div>

                        <!-- Thao tác số lượng & Thành tiền -->
                        <div class="d-flex align-items-center gap-4">
                            
                            <!-- Form cập nhật số lượng -->
                            <form method="post" action="cart.php" class="d-flex align-items-center gap-2 mb-0">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="variant_id" value="<?php echo (int)$item['variant_id']; ?>">
                                <div class="input-group input-group-sm" style="width: 110px;">
                                    <input type="number" name="quantity" min="1" max="<?php echo (int)$item['stock']; ?>" value="<?php echo (int)$item['quantity']; ?>" class="form-control text-center" style="border-radius: 6px 0 0 6px; font-weight: 600;" required>
                                    <button type="submit" class="btn btn-outline-secondary" style="border-radius: 0 6px 6px 0; font-size: 11px; padding: 0 8px;" title="Cập nhật số lượng">🔄</button>
                                </div>
                            </form>

                            <!-- Thành tiền -->
                            <div class="text-end" style="min-width: 110px;">
                                <span style="font-size: 11px; color: #7f8c8d; display: block;">Thành tiền</span>
                                <strong style="color: #c0392b; font-size: 16px; font-weight: 700;"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>₫</strong>
                            </div>

                            <!-- Nút Xóa -->
                            <form method="post" action="cart.php" class="mb-0">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="variant_id" value="<?php echo (int)$item['variant_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-light text-danger border" style="border-radius: 6px; padding: 6px 10px; transition: all 0.2s;" onmouseover="this.style.background='#ffebee'" onmouseout="this.style.background='#f8f9fa'" title="Xóa sản phẩm">
                                    🗑️
                                </button>
                            </form>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- KHO MÃ GIẢM GIÁ THÔNG MINH (VOUCHER WALLET) -->
        <div class="mb-4 shadow-sm" style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #eaeaea;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 style="font-size: 17px; font-weight: 700; color: #2c3e50; margin: 0;">🎟️ Kho mã giảm giá UniStationery</h5>
                <span style="font-size: 13px; color: #7f8c8d;">Chọn mã ưu đãi tốt nhất cho đơn hàng</span>
            </div>

            <?php
            $couponQuery = $pdo->query("SELECT * FROM coupons WHERE status = 'active'");
            $allCoupons = $couponQuery->fetchAll();
            ?>

            <?php if (empty($allCoupons)): ?>
                <p style="color: #777; font-size: 14px; margin: 0;">Hiện không có mã giảm giá nào khả dụng.</p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
                    <?php foreach ($allCoupons as $cp): 
                        $type = $cp['discount_type'] ?? 'percent';
                        $val = (float)($cp['discount_value'] ?? $cp['discount_percent'] ?? 10);
                        $minOrder = isset($cp['min_order']) ? (float)$cp['min_order'] : 0.0;
                        
                        // Kiểm tra điều kiện: Tổng tiền giỏ hàng có đạt điều kiện tối thiểu không?
                        $isEligible = ($total >= $minOrder);
                    ?>
                        <div style="position: relative; border: 1px solid <?php echo $isEligible ? '#f39c12' : '#e0e0e0'; ?>; background: <?php echo $isEligible ? '#fffdfa' : '#f9f9f9'; ?>; padding: 14px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; opacity: <?php echo $isEligible ? '1' : '0.6'; ?>; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: all 0.2s;">
                            
                            <!-- Dải trang trí bên trái thẻ voucher -->
                            <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: <?php echo $isEligible ? '#e67e22' : '#bdc3c7'; ?>; border-top-left-radius: 8px; border-bottom-left-radius: 8px;"></div>

                            <div style="padding-left: 10px;">
                                <div class="d-flex align-items-center gap-2">
                                    <strong style="color: #2c3e50; font-size: 15px; font-weight: 700; letter-spacing: 0.5px;"><?php echo htmlspecialchars($cp['code']); ?></strong>
                                </div>
                                <p style="margin: 4px 0 2px 0; font-size: 13px; color: #e67e22; font-weight: 600;">
                                    Giảm: <?php echo ($type === 'fixed') ? number_format($val, 0, ',', '.') . '₫' : $val . '%'; ?>
                                </p>
                                <p style="margin: 0; font-size: 11.5px; color: #7f8c8d;">
                                    <?php echo $minOrder > 0 ? 'Đơn tối thiểu: ' . number_format($minOrder, 0, ',', '.') . '₫' : 'Áp dụng mọi đơn hàng'; ?>
                                </p>
                            </div>
                            
                            <div style="padding-left: 10px;">
                                <?php if ($isEligible): ?>
                                    <button type="button" onclick="document.getElementById('coupon_code_input').value='<?php echo htmlspecialchars($cp['code']); ?>'" style="background: #e67e22; color: #fff; border: none; padding: 7px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#d35400'" onmouseout="this.style.background='#e67e22'">
                                        Dùng mã
                                    </button>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: #e74c3c; background: #fde8e8; padding: 5px 10px; border-radius: 6px; font-weight: 600; white-space: nowrap; display: inline-block;">Chưa đủ ĐK</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['applied_coupon'])): ?>
                <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: #e8f8f5; border: 1px solid #a3e4d7;">
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size: 16px;">🎉</span>
                        <span style="color: #117a65; font-size: 14px;">Đang áp dụng mã thành công: <strong><?php echo e($_SESSION['applied_coupon']['code']); ?></strong></span>
                    </div>
                    <form method="post" action="cart.php" class="mb-0">
                        <input type="hidden" name="action" value="remove_coupon">
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="font-weight: 500;">Hủy mã</button>
                    </form>
                </div>
            <?php else: ?>
                <form method="post" action="cart.php" class="input-group" style="max-width: 450px;">
                    <input type="hidden" name="action" value="apply_coupon">
                    <input type="text" id="coupon_code_input" name="coupon_code" class="form-control" placeholder="Nhập hoặc bấm chọn mã ở trên..." style="border-top-left-radius: 6px; border-bottom-left-radius: 6px; padding: 10px 14px; font-size: 14px;" required>
                    <button class="btn btn-dark" type="submit" style="border-top-right-radius: 6px; border-bottom-right-radius: 6px; padding: 0 20px; font-weight: 600; background: #2c3e50; border-color: #2c3e50;">Áp dụng</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="cart-total shadow-sm bg-white p-4 rounded-3 text-end" style="border: 1px solid #eaeaea;">
            <p class="mb-1" style="font-size: 15px; color: #555;">Tạm tính: <strong><?php echo number_format($total, 0, ',', '.'); ?>₫</strong></p>
            <?php if ($discountAmount > 0): ?>
                <p class="mb-1 text-success" style="font-size: 15px;">Giảm giá: <strong>-<?php echo number_format($discountAmount, 0, ',', '.'); ?>₫</strong></p>
            <?php endif; ?>
            <h3 class="text-danger mb-4" style="font-weight: 700;">Tổng tiền thanh toán: <?php echo number_format($finalTotal, 0, ',', '.'); ?>₫</h3>
            <div class="cart-total-actions d-flex justify-content-end gap-3">
                <a class="btn btn-outline-secondary px-4 py-2" href="index.php#products" style="border-radius: 6px; font-weight: 600;">Tiếp tục mua sắm</a>
                <a href="checkout.php" class="btn btn-primary px-4 py-2" style="border-radius: 6px; font-weight: 600; background: #e67e22; border-color: #e67e22;">Tiến hành đặt hàng</a>
            </div>
        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>