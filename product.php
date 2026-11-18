<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/includes/database.php';

// Lấy ID sản phẩm từ URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit('Sản phẩm không tồn tại.');
}

// Lấy thông tin sản phẩm
$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.image_emoji,
        c.name AS category
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.id = :id
      AND p.status = 'active'
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}

$productId = (int)$product['id'];

// Lấy danh sách biến thể đang hoạt động
$stmt = $pdo->prepare("
    SELECT
        id,
        sku,
        variant_name,
        price,
        stock
    FROM product_variants
    WHERE product_id = ?
      AND status = 'active'
    ORDER BY id ASC
");

$stmt->execute([$id]);
$variants = $stmt->fetchAll();

// Hàm bảo vệ dữ liệu HTML
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Xử lý gửi đánh giá sản phẩm
$reviewError = '';
$reviewSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $userId = $_SESSION['user_id'] ?? null;
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if (!$userId) {
        $reviewError = 'Bạn cần đăng nhập để gửi đánh giá.';
    } elseif ($rating < 1 || $rating > 5) {
        $reviewError = 'Vui lòng chọn số sao đánh giá từ 1 đến 5.';
    } else {
        // Kiểm tra xem người dùng đã từng mua và hoàn thành đơn hàng có chứa sản phẩm này chưa
        $checkOrderStmt = $pdo->prepare("
            SELECT oi.id AS order_item_id
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN product_variants pv ON oi.product_variant_id = pv.id
            WHERE o.user_id = ? AND pv.product_id = ? AND o.status = 'completed'
            LIMIT 1
        ");
        $checkOrderStmt->execute([$userId, $productId]);
        $boughtItem = $checkOrderStmt->fetch();

        if (!$boughtItem) {
            $reviewError = 'Bạn chỉ có thể đánh giá sản phẩm sau khi đã mua hàng thành công.';
        } else {
            $insertReviewStmt = $pdo->prepare("
                INSERT INTO reviews (user_id, product_id, order_item_id, rating, comment)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertReviewStmt->execute([$userId, $productId, $boughtItem['order_item_id'], $rating, $comment]);
            $reviewSuccess = 'Cảm ơn bạn đã gửi đánh giá cho sản phẩm!';
        }
    }
}

// Truy vấn danh sách đánh giá
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$productId]);
$reviewsList = $reviewsStmt->fetchAll();

// Điểm đánh giá trung bình
$avgRatingStmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
    FROM reviews 
    WHERE product_id = ?
");
$avgRatingStmt->execute([$productId]);
$ratingStats = $avgRatingStmt->fetch();
$avgRating = round((float)($ratingStats['avg_rating'] ?? 0), 1);
$totalReviews = (int)($ratingStats['total_reviews'] ?? 0);

// Tìm biến thể đầu tiên còn hàng
$selectedVariant = null;

foreach ($variants as $variant) {
    if ((int)$variant['stock'] > 0) {
        $selectedVariant = $variant;
        break;
    }
}

// Nếu tất cả hết hàng thì lấy biến thể đầu tiên
if ($selectedVariant === null && !empty($variants)) {
    $selectedVariant = $variants[0];
}

require __DIR__ . '/includes/header.php';
?>

<section class="container product-detail">

    <p class="eyebrow">CHI TIẾT SẢN PHẨM</p>

    <div class="detail-layout">

        <!-- HÌNH ẢNH SẢN PHẨM -->
        <div class="detail-image" aria-hidden="true">
            <?php echo e($product['image_emoji']); ?>
        </div>

        <!-- THÔNG TIN SẢN PHẨM -->
        <div class="detail-info">

            <span class="category">
                <?php echo e($product['category']); ?>
            </span>

            <h1>
                <?php echo e($product['name']); ?>
            </h1>

            <h3>Mô tả sản phẩm</h3>

            <p>
                <?php
                echo e(
                    $product['description']
                    ?: 'Chưa có mô tả sản phẩm.'
                );
                ?>
            </p>

            <?php if (!empty($variants)): ?>

                <!-- FORM CHỌN BIẾN THỂ -->
                <form
                    method="POST"
                    action="cart.php"
                    id="variant-form"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >

                    <h3>Chọn biến thể</h3>

                    <label for="variant_id">
                        Loại sản phẩm:
                    </label>

                    <select
                        name="variant_id"
                        id="variant_id"
                        required
                    >

                        <?php foreach ($variants as $variant): ?>

                            <option
                                value="<?php echo (int)$variant['id']; ?>"
                                data-price="<?php echo e($variant['price']); ?>"
                                data-stock="<?php echo (int)$variant['stock']; ?>"
                                data-sku="<?php echo e($variant['sku']); ?>"
                                <?php
                                echo (int)$selectedVariant['id'] ===
                                    (int)$variant['id']
                                    ? 'selected'
                                    : '';
                                ?>
                                <?php
                                echo (int)$variant['stock'] < 1
                                    ? 'disabled'
                                    : '';
                                ?>
                            >

                                <?php echo e($variant['variant_name']); ?>
                                <?php if ((int)$variant['stock'] < 1): ?>
                                    (Hết hàng)
                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <!-- GIÁ -->
                    <p class="detail-price">
                        Giá bán:

                        <strong id="variant-price">
                            <?php
                            echo number_format(
                                (float)$selectedVariant['price'],
                                0,
                                ',',
                                '.'
                            );
                            ?>₫
                        </strong>
                    </p>

                    <!-- SKU -->
                    <p>
                        Mã sản phẩm:

                        <strong id="variant-sku">
                            <?php echo e($selectedVariant['sku']); ?>
                        </strong>
                    </p>

                    <!-- TỒN KHO -->
                    <p>
                        Số lượng tồn kho:

                        <strong id="variant-stock">
                            <?php echo (int)$selectedVariant['stock']; ?>
                        </strong>
                    </p>

                    <!-- SỐ LƯỢNG MUA -->
                    <label for="quantity">
                        Số lượng:
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        max="<?php echo (int)$selectedVariant['stock']; ?>"
                        value="1"
                        required
                        <?php
                        echo (int)$selectedVariant['stock'] < 1
                            ? 'disabled'
                            : '';
                        ?>
                    >

                    <!-- NÚT THÊM GIỎ HÀNG -->
                    <button
                        type="submit"
                        class="primary-button"
                        id="add-cart-button"
                        <?php
                        echo (int)$selectedVariant['stock'] < 1
                            ? 'disabled'
                            : '';
                        ?>
                    >
                        Thêm vào giỏ hàng
                    </button>

                </form>

            <?php else: ?>

                <p class="error-message">
                    Sản phẩm hiện chưa có biến thể đang kinh doanh.
                </p>

            <?php endif; ?>

            <p>
                <a href="index.php#products">
                    Quay lại cửa hàng
                </a>
            </p>

        </div>

    </div>

    <!-- KHU VỰC ĐÁNH GIÁ SẢN PHẨM -->
    <div class="account-card p-4 bg-white shadow-sm rounded mt-5">
        <h3 class="mb-3">Đánh giá sản phẩm</h3>

        <div class="d-flex align-items-center mb-4">
            <div class="me-3 text-center">
                <h1 class="display-4 text-warning mb-0"><?php echo $avgRating; ?> / 5</h1>
                <p class="text-muted mb-0">(<?php echo $totalReviews; ?> đánh giá)</p>
            </div>
        </div>

        <?php if ($reviewSuccess !== ''): ?>
            <div class="alert alert-success"><?php echo e($reviewSuccess); ?></div>
        <?php endif; ?>
        <?php if ($reviewError !== ''): ?>
            <div class="alert alert-danger"><?php echo e($reviewError); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" action="product.php?id=<?php echo $productId; ?>" class="mb-5 p-3 border rounded bg-light">
                <input type="hidden" name="action" value="submit_review">
                <h5>Viết đánh giá của bạn</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Số sao đánh giá</label>
                    <select name="rating" class="form-control" required style="max-width: 200px;">
                        <option value="5">⭐⭐⭐⭐⭐ (5/5 - Rất tốt)</option>
                        <option value="4">⭐⭐⭐⭐ (4/5 - Tốt)</option>
                        <option value="3">⭐⭐⭐ (3/5 - Bình thường)</option>
                        <option value="2">⭐⭐ (2/5 - Tạm được)</option>
                        <option value="1">⭐ (1/5 - Kém)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Bình luận / Nhận xét</label>
                    <textarea name="comment" class="form-control" rows="3" placeholder="Chia sẻ trải nghiệm sử dụng sản phẩm..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
            </form>
        <?php else: ?>
            <p class="text-muted"><a href="login.php">Đăng nhập</a> để gửi đánh giá cho sản phẩm này.</p>
        <?php endif; ?>

        <div class="review-list">
            <?php if (empty($reviewsList)): ?>
                <p class="text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>
            <?php else: ?>
                <?php foreach ($reviewsList as $rev): ?>
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?php echo e($rev['full_name']); ?></strong>
                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($rev['created_at'])); ?></small>
                        </div>
                        <div class="text-warning my-1">
                            <?php echo str_repeat('⭐', (int)$rev['rating']); ?>
                        </div>
                        <p class="mb-0"><?php echo e($rev['comment']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</section>


<!-- JAVASCRIPT CẬP NHẬT BIẾN THỂ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const variantSelect = document.getElementById('variant_id');

    if (!variantSelect) {
        return;
    }

    const priceElement = document.getElementById('variant-price');
    const stockElement = document.getElementById('variant-stock');
    const skuElement = document.getElementById('variant-sku');

    const quantityInput = document.getElementById('quantity');
    const addButton = document.getElementById('add-cart-button');

    function updateVariant() {

        const option = variantSelect.selectedOptions[0];

        if (!option) {
            return;
        }

        const price = Number(option.dataset.price);
        const stock = Number(option.dataset.stock);
        const sku = option.dataset.sku;

        // Cập nhật giá
        priceElement.textContent =
            new Intl.NumberFormat('vi-VN').format(price) + '₫';

        // Cập nhật SKU
        skuElement.textContent = sku;

        // Cập nhật tồn kho
        stockElement.textContent = stock;

        // Đặt lại số lượng
        quantityInput.value = 1;

        // Cập nhật giới hạn số lượng
        quantityInput.max = stock;

        // Khóa nhập số lượng nếu hết hàng
        quantityInput.disabled = stock < 1;

        // Khóa nút thêm giỏ nếu hết hàng
        addButton.disabled = stock < 1;
    }

    // Khi khách thay đổi biến thể
    variantSelect.addEventListener('change', updateVariant);

    // Cập nhật thông tin ban đầu
    updateVariant();

});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>