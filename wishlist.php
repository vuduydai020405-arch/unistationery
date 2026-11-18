<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = '';

// Xử lý xóa sản phẩm khỏi wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $productId = (int)$_POST['product_id'];
    $delStmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
    $delStmt->execute([$userId, $productId]);
    $message = 'Đã xóa sản phẩm khỏi danh sách yêu thích.';
}

// Lấy danh sách yêu thích kèm thông tin sản phẩm
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

require_once 'includes/header.php';
?>

<section class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>❤️ Sản phẩm yêu thích của tôi</h1>
        <a href="index.php" class="btn btn-outline-secondary">← Tiếp tục mua sắm</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?php echo e($message); ?></div>
    <?php endif; ?>

    <?php if (empty($wishlistItems)): ?>
        <div class="text-center py-5 bg-white shadow-sm rounded">
            <h3>Danh sách yêu thích trống</h3>
            <p class="text-muted">Bạn chưa lưu sản phẩm nào vào danh sách quan tâm.</p>
            <a href="index.php" class="btn btn-primary mt-2">Khám phá sản phẩm ngay</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div style="font-size: 3rem;" class="mb-2"><?php echo e($item['image_emoji']); ?></div>
                            <span class="badge bg-secondary mb-2"><?php echo e($item['category_name']); ?></span>
                            <h5 class="card-title"><?php echo e($item['name']); ?></h5>
                            <p class="card-text text-muted small"><?php echo e(substr($item['description'], 0, 80)); ?>...</p>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center pb-3">
                            <a href="product.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-primary">Xem chi tiết</a>
                            <form method="POST" class="mb-0">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa sản phẩm này khỏi yêu thích?');">Xóa</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>