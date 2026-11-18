<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/database.php';

if (!isset($_SESSION['user_id'])) {
    exit('Bạn chưa đăng nhập.');
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('Tài khoản hiện tại không có quyền admin.');
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = '';

// Lấy danh sách danh mục
$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll();

// Thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Phiên làm việc không hợp lệ. Hãy tải lại trang.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $emoji = trim($_POST['image_emoji'] ?? '📚');
        $variantName = trim($_POST['variant_name'] ?? 'Mặc định');
        $sku = trim($_POST['sku'] ?? '');
        $price = $_POST['price'] ?? '';
        $stock = $_POST['stock'] ?? '';

        if ($name === '') {
            $errors[] = 'Vui lòng nhập tên sản phẩm.';
        }

        if (!in_array($categoryId, array_column($categories, 'id'))) {
            $errors[] = 'Danh mục không hợp lệ.';
        }

        if ($sku === '' || strlen($sku) > 80) {
            $errors[] = 'SKU không được để trống và tối đa 80 ký tự.';
        }

        if (!is_numeric($price) || (float)$price <= 0) {
            $errors[] = 'Giá sản phẩm phải lớn hơn 0.';
        }

        if (
            filter_var($stock, FILTER_VALIDATE_INT) === false ||
            (int)$stock < 0
        ) {
            $errors[] = 'Số lượng tồn kho không hợp lệ.';
        }

        if (strlen($emoji) > 20) {
            $errors[] = 'Biểu tượng quá dài.';
        }

        if ($variantName === '' || strlen($variantName) > 100) {
            $errors[] = 'Tên biến thể không hợp lệ.';
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO products
                    (category_id, name, description, image_emoji, status)
                    VALUES (?, ?, ?, ?, 'active')"
                );

                $stmt->execute([
                    $categoryId,
                    $name,
                    $description,
                    $emoji
                ]);

                $productId = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    "INSERT INTO product_variants
                    (product_id, sku, variant_name, price, stock, status)
                    VALUES (?, ?, ?, ?, ?, 'active')"
                );

                $stmt->execute([
                    $productId,
                    $sku,
                    $variantName,
                    $price,
                    $stock
                ]);

                $pdo->commit();

                header('Location: admin_products.php?success=added');
                exit;

            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($exception->getMessage());
                $errors[] = 'Không thể thêm sản phẩm. Hãy kiểm tra SKU có bị trùng không.';
            }
        }
    }
}

// Thông báo thành công
if (($_GET['success'] ?? '') === 'added') {
    $success = 'Thêm sản phẩm thành công!';
}

// Lấy danh sách sản phẩm theo từng biến thể (INNER JOIN để hiển thị chuẩn xác từng dòng)
$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.image_emoji,
        p.status AS product_status,
        c.name AS category_name,
        v.id AS variant_id,
        v.sku,
        v.variant_name,
        v.price,
        v.stock,
        v.status AS variant_status
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN product_variants v ON v.product_id = p.id
    ORDER BY v.id DESC
";

$products = $pdo->query($sql)->fetchAll();

require_once 'includes/header.php';
?>

<main class="admin-products-page py-4">
    <div class="container">
        <h1 class="mb-2" style="font-weight: 700; color: #2c3e50;">Quản lý sản phẩm</h1>
        <p class="text-muted mb-4">Thêm sản phẩm mới và quản lý kho hàng của UniStationery.</p>

        <?php if ($success): ?>
            <div class="alert alert-success shadow-sm">
                ✅ <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger shadow-sm">
                ⚠️ <?php echo e($error); ?>
            </div>
        <?php endforeach; ?>

        <!-- Form thêm sản phẩm -->
        <section class="admin-product-form bg-white p-4 rounded-3 shadow-sm mb-5" style="border: 1px solid #eaeaea;">
            <h2 class="mb-3" style="font-size: 1.25rem; font-weight: 600; color: #2c3e50;">Thêm sản phẩm mới</h2>

            <form method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo e($_SESSION['csrf_token']); ?>"
                >

                <div class="mb-3">
                    <label class="form-label fw-bold">Tên sản phẩm</label>
                    <input type="text" name="name" class="form-control" required placeholder="Nhập tên sản phẩm...">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Danh mục</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['id']; ?>">
                                <?php echo e($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Mô tả sản phẩm</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả chi tiết..."></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Biểu tượng sản phẩm (emoji)</label>
                        <input
                            type="text"
                            name="image_emoji"
                            class="form-control"
                            value="📚"
                            maxlength="20"
                        >
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tên biến thể</label>
                        <input
                            type="text"
                            name="variant_name"
                            class="form-control"
                            value="Mặc định"
                            maxlength="100"
                            required
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Mã SKU</label>
                        <input type="text" name="sku" class="form-control" maxlength="80" required placeholder="VD: SKU-SP-01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Giá bán (VNĐ)</label>
                        <input
                            type="number"
                            name="price"
                            class="form-control"
                            min="1"
                            step="0.01"
                            required
                            placeholder="VD: 45000"
                        >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Số lượng tồn kho</label>
                        <input
                            type="number"
                            name="stock"
                            class="form-control"
                            min="0"
                            step="1"
                            required
                            placeholder="VD: 50"
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-dark px-4 py-2 mt-2" style="font-weight: 600; background: #2c3e50; border-color: #2c3e50;">
                    Thêm sản phẩm
                </button>
            </form>
        </section>

        <!-- Danh sách sản phẩm -->
        <section class="admin-product-list bg-white p-4 rounded-3 shadow-sm" style="border: 1px solid #eaeaea;">
            <h2 class="mb-3" style="font-size: 1.25rem; font-weight: 600; color: #2c3e50;">Danh sách sản phẩm & Biến thể</h2>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>SKU</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo (int)$product['id']; ?></td>

                                <td>
                                    <span style="font-size: 1.2rem; margin-right: 6px;"><?php echo e($product['image_emoji']); ?></span>
                                    <strong><?php echo e($product['name']); ?></strong>
                                    <?php if (!empty($product['variant_name'])): ?>
                                        <br><small class="text-muted">Phân loại: <?php echo e($product['variant_name']); ?></small>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo e($product['category_name']); ?></td>

                                <td>
                                    <code><?php echo e($product['sku']); ?></code>
                                </td>

                                <td>
                                    <strong class="text-danger"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?>₫</strong>
                                </td>

                                <td>
                                    <span class="badge bg-secondary"><?php echo (int)$product['stock']; ?></span>
                                </td>

                                <td>
                                    <?php if ($product['product_status'] === 'active'): ?>
                                        <span class="badge bg-success">Đang hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Đã ẩn</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <a
                                        href="edit_product.php?id=<?php echo (int)$product['id']; ?>&variant_id=<?php echo (int)$product['variant_id']; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        style="white-space: nowrap;"
                                    >
                                        ✏️ Sửa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$products): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Chưa có sản phẩm nào trong cơ sở dữ liệu.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>