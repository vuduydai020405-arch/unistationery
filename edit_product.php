```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/database.php';

// Chỉ admin mới được truy cập
if (!isset($_SESSION['user_id'])) {
    exit('Bạn chưa đăng nhập.');
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('Bạn không có quyền quản trị.');
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$variantId = filter_input(INPUT_GET, 'variant_id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    exit('ID sản phẩm không hợp lệ.');
}

$errors = [];
$success = '';

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare(
    'SELECT id, category_id, name, description, image_emoji, status
     FROM products
     WHERE id = ?'
);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    exit('Không tìm thấy sản phẩm.');
}

// Lấy danh mục
$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll();

// Xử lý dữ liệu gửi lên
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Phiên làm việc không hợp lệ. Hãy tải lại trang.';
    } else {
        $action = $_POST['action'] ?? '';

        // XÓA TOÀN BỘ SẢN PHẨM
        if ($action === 'delete_product') {
            try {
                $pdo->beginTransaction();

                // Kiểm tra sản phẩm có xuất hiện trong đơn hàng không
                $stmt = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM order_items oi
                     INNER JOIN product_variants pv
                        ON oi.product_variant_id = pv.id
                     WHERE pv.product_id = ?'
                );
                $stmt->execute([$productId]);
                $orderCount = (int)$stmt->fetchColumn();

                if ($orderCount > 0) {
                    $pdo->rollBack();
                    $errors[] = 'Không thể xóa sản phẩm vì đã có biến thể xuất hiện trong đơn hàng. Bạn có thể chọn ngừng bán sản phẩm.';
                } else {
                    // Xóa các biến thể thuộc sản phẩm
                    $stmt = $pdo->prepare(
                        'DELETE FROM product_variants WHERE product_id = ?'
                    );
                    $stmt->execute([$productId]);

                    // Xóa sản phẩm
                    $stmt = $pdo->prepare(
                        'DELETE FROM products WHERE id = ?'
                    );
                    $stmt->execute([$productId]);

                    $pdo->commit();

                    header('Location: admin_products.php?success=product_deleted');
                    exit;
                }
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($exception->getMessage());
                $errors[] = 'Không thể xóa sản phẩm. Có thể dữ liệu đang được liên kết với bảng khác.';
            }
        }

        // XÓA MỘT BIẾN THỂ
        elseif ($action === 'delete_variant') {
            $deleteVariantId = filter_var(
                $_POST['delete_variant_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$deleteVariantId || $deleteVariantId < 1) {
                $errors[] = 'ID biến thể không hợp lệ.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Kiểm tra biến thể có thuộc sản phẩm này không
                    $stmt = $pdo->prepare(
                        'SELECT id
                         FROM product_variants
                         WHERE id = ? AND product_id = ?
                         FOR UPDATE'
                    );
                    $stmt->execute([$deleteVariantId, $productId]);
                    $variantToDelete = $stmt->fetch();

                    if (!$variantToDelete) {
                        $pdo->rollBack();
                        $errors[] = 'Không tìm thấy biến thể cần xóa.';
                    } else {
                        // Kiểm tra biến thể có trong đơn hàng không
                        $stmt = $pdo->prepare(
                            'SELECT COUNT(*)
                             FROM order_items
                             WHERE product_variant_id = ?'
                        );
                        $stmt->execute([$deleteVariantId]);
                        $orderCount = (int)$stmt->fetchColumn();

                        if ($orderCount > 0) {
                            $pdo->rollBack();
                            $errors[] = 'Không thể xóa biến thể vì biến thể này đã xuất hiện trong đơn hàng. Bạn có thể chọn ngừng bán.';
                        } else {
                            $stmt = $pdo->prepare(
                                'DELETE FROM product_variants
                                 WHERE id = ? AND product_id = ?'
                            );
                            $stmt->execute([$deleteVariantId, $productId]);

                            $pdo->commit();

                            header(
                                'Location: edit_product.php?id=' .
                                $productId .
                                '&success=variant_deleted'
                            );
                            exit;
                        }
                    }
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    error_log($exception->getMessage());
                    $errors[] = 'Không thể xóa biến thể. Hãy kiểm tra các liên kết dữ liệu.';
                }
            }
        }

        // CẬP NHẬT SẢN PHẨM VÀ BIẾN THỂ
        elseif ($action === 'update') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $emoji = trim($_POST['image_emoji'] ?? '📚');
            $productStatus = $_POST['product_status'] ?? '';

            $sku = trim($_POST['sku'] ?? '');
            $variantName = trim($_POST['variant_name'] ?? '');
            $price = $_POST['price'] ?? '';
            $stock = $_POST['stock'] ?? '';
            $variantStatus = $_POST['variant_status'] ?? '';

            $postedVariantId = filter_var(
                $_POST['variant_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            $validCategoryIds = array_map(
                'intval',
                array_column($categories, 'id')
            );

            if ($name === '' || strlen($name) > 180) {
                $errors[] = 'Tên sản phẩm không hợp lệ.';
            }

            if (!in_array($categoryId, $validCategoryIds, true)) {
                $errors[] = 'Danh mục không hợp lệ.';
            }

            if (!in_array($productStatus, ['active', 'inactive'], true)) {
                $errors[] = 'Trạng thái sản phẩm không hợp lệ.';
            }

            if (strlen($emoji) > 20) {
                $errors[] = 'Biểu tượng quá dài.';
            }

            if (!$postedVariantId) {
                $errors[] = 'Biến thể được chọn không hợp lệ.';
            }

            if ($sku === '' || strlen($sku) > 80) {
                $errors[] = 'SKU không được để trống và tối đa 80 ký tự.';
            }

            if ($variantName === '' || strlen($variantName) > 100) {
                $errors[] = 'Tên biến thể không hợp lệ.';
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

            if (!in_array($variantStatus, ['active', 'inactive'], true)) {
                $errors[] = 'Trạng thái biến thể không hợp lệ.';
            }

            if (!$errors) {
                try {
                    $pdo->beginTransaction();

                    // Kiểm tra biến thể thuộc sản phẩm
                    $stmt = $pdo->prepare(
                        'SELECT id
                         FROM product_variants
                         WHERE id = ? AND product_id = ?'
                    );
                    $stmt->execute([$postedVariantId, $productId]);

                    if (!$stmt->fetch()) {
                        throw new RuntimeException('Biến thể không hợp lệ.');
                    }

                    $stmt = $pdo->prepare(
                        'UPDATE products
                         SET category_id = ?,
                             name = ?,
                             description = ?,
                             image_emoji = ?,
                             status = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $categoryId,
                        $name,
                        $description !== '' ? $description : null,
                        $emoji,
                        $productStatus,
                        $productId
                    ]);

                    $stmt = $pdo->prepare(
                        'UPDATE product_variants
                         SET sku = ?,
                             variant_name = ?,
                             price = ?,
                             stock = ?,
                             status = ?
                         WHERE id = ? AND product_id = ?'
                    );

                    $stmt->execute([
                        $sku,
                        $variantName,
                        $price,
                        $stock,
                        $variantStatus,
                        $postedVariantId,
                        $productId
                    ]);

                    $pdo->commit();

                    header(
                        'Location: edit_product.php?id=' .
                        $productId .
                        '&variant_id=' . $postedVariantId .
                        '&success=updated'
                    );
                    exit;

                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    error_log($exception->getMessage());
                    $errors[] = 'Không thể cập nhật. Hãy kiểm tra SKU có bị trùng không.';
                }
            }
        }

        // THÊM BIẾN THỂ MỚI
        elseif ($action === 'add_variant') {
            $newVariantName = trim($_POST['new_variant_name'] ?? '');
            $newSku = trim($_POST['new_sku'] ?? '');
            $newPrice = $_POST['new_price'] ?? '';
            $newStock = $_POST['new_stock'] ?? '';

            if ($newVariantName === '' || strlen($newVariantName) > 100) {
                $errors[] = 'Tên biến thể không hợp lệ.';
            }

            if ($newSku === '' || strlen($newSku) > 80) {
                $errors[] = 'SKU không được để trống và tối đa 80 ký tự.';
            }

            if (!is_numeric($newPrice) || (float)$newPrice <= 0) {
                $errors[] = 'Giá biến thể phải lớn hơn 0.';
            }

            if (
                filter_var($newStock, FILTER_VALIDATE_INT) === false ||
                (int)$newStock < 0
            ) {
                $errors[] = 'Số lượng tồn kho không hợp lệ.';
            }

            if (!$errors) {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO product_variants
                         (product_id, sku, variant_name, price, stock, status)
                         VALUES (?, ?, ?, ?, ?, 'active')"
                    );

                    $stmt->execute([
                        $productId,
                        $newSku,
                        $newVariantName,
                        $newPrice,
                        $newStock
                    ]);

                    $newId = (int)$pdo->lastInsertId();

                    header(
                        'Location: edit_product.php?id=' .
                        $productId .
                        '&variant_id=' . $newId .
                        '&success=variant_added'
                    );
                    exit;

                } catch (PDOException $exception) {
                    error_log($exception->getMessage());
                    $errors[] = 'Không thể thêm biến thể. Hãy kiểm tra SKU có bị trùng không.';
                }
            }
        } else {
            $errors[] = 'Yêu cầu không hợp lệ.';
        }
    }
}

// Tải lại thông tin sản phẩm
$stmt = $pdo->prepare(
    'SELECT id, category_id, name, description, image_emoji, status
     FROM products WHERE id = ?'
);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    exit('Không tìm thấy sản phẩm.');
}

// Tải lại biến thể
$stmt = $pdo->prepare(
    'SELECT id, sku, variant_name, price, stock, status
     FROM product_variants
     WHERE product_id = ?
     ORDER BY id ASC'
);
$stmt->execute([$productId]);
$variants = $stmt->fetchAll();

// Chọn biến thể đang hiển thị
$variant = null;

foreach ($variants as $item) {
    if ((int)$item['id'] === (int)$variantId) {
        $variant = $item;
        break;
    }
}

if (!$variant && !empty($variants)) {
    $variant = $variants[0];
    $variantId = (int)$variant['id'];
}

// Thông báo
if (($_GET['success'] ?? '') === 'updated') {
    $success = 'Cập nhật sản phẩm thành công!';
} elseif (($_GET['success'] ?? '') === 'variant_added') {
    $success = 'Thêm biến thể thành công!';
} elseif (($_GET['success'] ?? '') === 'variant_deleted') {
    $success = 'Xóa biến thể thành công!';
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="admin-products-page">
    <div class="container">

        <h1>Quản lý sản phẩm</h1>

        <p>
            <a href="admin_products.php">← Quay lại danh sách sản phẩm</a>
        </p>

        <?php if ($success): ?>
            <p class="success-message"><?php echo e($success); ?></p>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <p class="error-message"><?php echo e($error); ?></p>
        <?php endforeach; ?>

        <!-- THÔNG TIN SẢN PHẨM -->
        <section class="admin-product-form">
            <h2>Thông tin sản phẩm</h2>

            <form method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?php echo e($_SESSION['csrf_token']); ?>">

                <input type="hidden" name="action" value="update">

                <input type="hidden" name="variant_id"
                       value="<?php echo $variant ? (int)$variant['id'] : 0; ?>">

                <label>Tên sản phẩm</label>
                <input type="text" name="name"
                       maxlength="180"
                       value="<?php echo e($product['name']); ?>" required>

                <label>Danh mục</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?php echo (int)$category['id']; ?>"
                            <?php echo (int)$product['category_id'] ===
                                (int)$category['id'] ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Mô tả</label>
                <textarea name="description" rows="4"><?php
                    echo e($product['description']);
                ?></textarea>

                <label>Emoji</label>
                <input type="text" name="image_emoji" maxlength="20"
                       value="<?php echo e($product['image_emoji']); ?>">

                <label>Trạng thái sản phẩm</label>
                <select name="product_status">
                    <option value="active"
                        <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>
                        Đang kinh doanh
                    </option>
                    <option value="inactive"
                        <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>
                        Đã ẩn
                    </option>
                </select>

                <hr>

                <h3>Chọn biến thể cần sửa</h3>

                <?php if ($variants): ?>
                    <select
                        onchange="window.location.href='edit_product.php?id=<?php
                            echo (int)$productId;
                        ?>&variant_id='+this.value">

                        <?php foreach ($variants as $item): ?>
                            <option
                                value="<?php echo (int)$item['id']; ?>"
                                <?php echo $variant &&
                                    (int)$variant['id'] === (int)$item['id']
                                    ? 'selected' : ''; ?>>
                                <?php echo e($item['variant_name']); ?>
                                - <?php echo e($item['sku']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($variant): ?>
                        <label>Tên biến thể</label>
                        <input type="text" name="variant_name"
                               maxlength="100"
                               value="<?php echo e($variant['variant_name']); ?>"
                               required>

                        <label>Mã SKU</label>
                        <input type="text" name="sku" maxlength="80"
                               value="<?php echo e($variant['sku']); ?>" required>

                        <label>Giá bán (VNĐ)</label>
                        <input type="number" name="price" min="1" step="0.01"
                               value="<?php echo e($variant['price']); ?>" required>

                        <label>Số lượng tồn kho</label>
                        <input type="number" name="stock" min="0" step="1"
                               value="<?php echo (int)$variant['stock']; ?>"
                               required>

                        <label>Trạng thái biến thể</label>
                        <select name="variant_status">
                            <option value="active"
                                <?php echo $variant['status'] === 'active' ? 'selected' : ''; ?>>
                                Đang bán
                            </option>
                            <option value="inactive"
                                <?php echo $variant['status'] === 'inactive' ? 'selected' : ''; ?>>
                                Ngừng bán
                            </option>
                        </select>

                        <button type="submit" class="primary-button">
                            Lưu thay đổi
                        </button>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Sản phẩm này chưa có biến thể. Hãy thêm biến thể bên dưới.</p>
                <?php endif; ?>
            </form>
        </section>

        <!-- THÊM BIẾN THỂ -->
        <section class="admin-product-form">
            <h2>Thêm biến thể mới</h2>

            <form method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?php echo e($_SESSION['csrf_token']); ?>">

                <input type="hidden" name="action" value="add_variant">

                <label>Tên biến thể</label>
                <input type="text" name="new_variant_name"
                       maxlength="100" placeholder="Ví dụ: Màu xanh" required>

                <label>Mã SKU</label>
                <input type="text" name="new_sku"
                       maxlength="80" placeholder="Ví dụ: UNI-PEN-BLUE" required>

                <label>Giá bán (VNĐ)</label>
                <input type="number" name="new_price"
                       min="1" step="0.01" required>

                <label>Số lượng tồn kho</label>
                <input type="number" name="new_stock"
                       min="0" step="1" required>

                <button type="submit" class="primary-button">
                    Thêm biến thể
                </button>
            </form>
        </section>

        <!-- DANH SÁCH BIẾN THỂ -->
        <section class="admin-product-list">
            <h2>Các biến thể của sản phẩm</h2>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tên biến thể</th>
                            <th>SKU</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($variants as $item): ?>
                            <tr>
                                <td><?php echo e($item['variant_name']); ?></td>
                                <td><?php echo e($item['sku']); ?></td>
                                <td>
                                    <?php echo number_format(
                                        (float)$item['price'], 0, ',', '.'
                                    ); ?>₫
                                </td>
                                <td><?php echo (int)$item['stock']; ?></td>
                                <td>
                                    <?php echo $item['status'] === 'active'
                                        ? 'Đang bán' : 'Ngừng bán'; ?>
                                </td>
                                <td>
                                    <form method="POST"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa biến thể này không?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?php echo e($_SESSION['csrf_token']); ?>">

                                        <input type="hidden" name="action"
                                               value="delete_variant">

                                        <input type="hidden"
                                               name="delete_variant_id"
                                               value="<?php echo (int)$item['id']; ?>">

                                        <button type="submit"
                                                class="danger-button">
                                            Xóa biến thể
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$variants): ?>
                            <tr>
                                <td colspan="6">Chưa có biến thể nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- XÓA TOÀN BỘ SẢN PHẨM -->
        <section class="admin-product-form">
            <h2>Xóa sản phẩm</h2>

            <p>
                Chức năng này sẽ xóa sản phẩm cùng toàn bộ biến thể của nó.
                Sản phẩm đã có trong đơn hàng sẽ không thể bị xóa.
            </p>

            <form method="POST"
                  onsubmit="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa toàn bộ sản phẩm và các biến thể của sản phẩm này không? Thao tác này không thể hoàn tác.');">

                <input type="hidden" name="csrf_token"
                       value="<?php echo e($_SESSION['csrf_token']); ?>">

                <input type="hidden" name="action"
                       value="delete_product">

                <button type="submit" class="danger-button">
                    Xóa toàn bộ sản phẩm
                </button>
            </form>
        </section>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
```