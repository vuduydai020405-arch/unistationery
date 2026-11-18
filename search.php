<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php';

// Lấy từ khóa tìm kiếm và danh mục từ URL
$keyword = trim($_GET['q'] ?? '');
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

// Lấy danh sách danh mục để hiển thị bộ lọc
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll();

// Xây dựng câu truy vấn tìm kiếm linh hoạt, đồng bộ kết nối biến thể và lấy emoji
$sql = "
    SELECT 
        p.id,
        p.name,
        p.description,
        p.image_emoji,
        c.name AS category_name,
        MIN(v.price) AS variant_price
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_variants v ON v.product_id = p.id AND v.status = 'active' AND v.stock > 0
    WHERE p.status = 'active'
";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

$sql .= " GROUP BY p.id, p.name, p.description, p.image_emoji, c.name ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

require_once 'includes/header.php';
?>

<section class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="fw-bold mb-3">Tìm kiếm sản phẩm</h1>
            <!-- Form tìm kiếm nâng cao -->
            <form action="search.php" method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Nhập tên bút, sổ tay, văn phòng phẩm..." value="<?php echo e($keyword); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-select">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryId === (int)$cat['id'] ? 'selected' : ''; ?>>
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Tìm kiếm</button>
                </div>
            </form>
        </div>
    </div>

    <hr class="mb-4">

    <div class="mb-4">
        <h3 class="h5 text-secondary">
            Kết quả tìm kiếm 
            <?php if ($keyword !== ''): ?> cho từ khóa "<strong><?php echo e($keyword); ?></strong>"<?php endif; ?>
        </h3>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-warning py-3">Không tìm thấy sản phẩm nào phù hợp với yêu cầu của bạn.</div>
    <?php else: ?>
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
            <?php foreach ($products as $product): ?>
                <?php 
                    $price = $product['variant_price'] ?? 0;
                ?>
                <article class="product-card" style="background: #fff; border-radius: 10px; border: 1px solid #eaeaea; box-shadow: 0 2px 5px rgba(0,0,0,0.02); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.02)';">
                    
                    <!-- Khung hiển thị Emoji giống hệt trang chủ -->
                    <div class="product-image-wrap" style="position: relative; background: #faf9f6; height: 180px; width: 100%; overflow: hidden;">
                        <span class="discount-badge" style="position: absolute; top: 10px; right: 10px; background: rgba(238, 77, 45, 0.1); color: #ee4d2d; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 4px; z-index: 2;">Chính hãng</span>
                        
                        <a href="product.php?id=<?php echo (int)$product['id']; ?>" class="product-image" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <span class="product-emoji" style="font-size: 56px; line-height: 1;"><?php echo e($product['image_emoji']); ?></span>
                        </a>
                    </div>

                    <!-- Phần thông tin sản phẩm -->
                    <div class="product-info" style="padding: 15px; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between;">
                        <div>
                            <span class="category" style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo e($product['category_name']); ?></span>
                            <h3 style="font-size: 14px; margin: 6px 0 10px 0; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; line-height: 1.3;">
                                <a href="product.php?id=<?php echo (int)$product['id']; ?>" style="color: #333; text-decoration: none;"><?php echo e($product['name']); ?></a>
                            </h3>
                        </div>
                        
                        <div class="product-bottom" style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f5f5f5; padding-top: 10px; margin-top: auto;">
                            <strong class="product-price" style="color: #ee4d2d; font-size: 16px; font-weight: 600;"><?php echo number_format((float)$price, 0, ',', '.'); ?>₫</strong>
                            <a href="product.php?id=<?php echo (int)$product['id']; ?>" class="add-button" style="background: #fff; border: 1px solid #ee4d2d; color: #ee4d2d; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 500; text-decoration: none;">Chi tiết</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>