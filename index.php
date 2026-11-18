<?php
require __DIR__ . '/includes/database.php';

// Lấy sản phẩm đang hoạt động và có ít nhất một biến thể còn hàng
$sql = "
    SELECT 
        p.id,
        p.name,
        p.description,
        p.image_emoji,
        c.name AS category,
        v.price
    FROM products p
    JOIN categories c ON c.id = p.category_id
    JOIN product_variants v 
        ON v.id = (
            SELECT pv.id 
            FROM product_variants pv 
            WHERE pv.product_id = p.id 
              AND pv.status = 'active' 
              AND pv.stock > 0 
            ORDER BY pv.price ASC, pv.id ASC 
            LIMIT 1
        )
    WHERE p.status = 'active'
    ORDER BY p.id ASC
";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/includes/header.php';
?>

<!-- BANNER GIỚI THIỆU HIỆN ĐẠI -->
<section class="hero" style="background: linear-gradient(135deg, #fcf8f5 0%, #f4ebd9 100%); padding: 60px 0; margin-bottom: 30px; border-bottom: 1px solid #eee;">
    <div class="container hero-content" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 30px;">
        <div class="hero-text" style="flex: 1; min-width: 280px;">
            <p class="eyebrow" style="color: #c8815f; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; margin-bottom: 8px;">UNISTATIONERY · STUDY IN STYLE</p>
            <h1 style="font-size: 38px; line-height: 1.2; color: #222; font-weight: 700; margin-bottom: 15px;">
                Không gian học tập đẹp, <span style="color: #c8815f; display: block;">bắt đầu từ những điều nhỏ nhất.</span>
            </h1>
            <p class="hero-description" style="color: #666; font-size: 16px; margin-bottom: 25px; line-height: 1.5;">
                Khám phá những món văn phòng phẩm tinh tế, giúp mỗi ngày học tập và sáng tạo trở nên thú vị hơn.
            </p>
            <div class="hero-actions" style="display: flex; gap: 15px; align-items: center;">
                <a href="#products" class="primary-button" style="background: #222; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: background 0.2s;">Khám phá bộ sưu tập →</a>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true" style="flex: 1; display: flex; justify-content: center; min-width: 280px;">
            <div class="hero-circle" style="width: 240px; height: 240px; background: rgba(200, 129, 95, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative;">
                <div class="hero-stationery" style="font-size: 64px;">📚✏️📓</div>
            </div>
        </div>
    </div>
</section>

<!-- ĐIỂM NỔI BẬT -->
<section class="benefits container" id="about" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <div class="benefit-item" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #f0f0f0; display: flex; align-items: center; gap: 15px;">
        <span class="benefit-icon" style="font-size: 24px; color: #c8815f;">✧</span>
        <div><strong style="font-size: 14px; color: #333;">Thiết kế tinh tế</strong><p style="margin: 0; font-size: 12px; color: #777;">Đơn giản và thanh lịch</p></div>
    </div>
    <div class="benefit-item" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #f0f0f0; display: flex; align-items: center; gap: 15px;">
        <span class="benefit-icon" style="font-size: 24px; color: #c8815f;">♡</span>
        <div><strong style="font-size: 14px; color: #333;">Đồ dùng hữu ích</strong><p style="margin: 0; font-size: 12px; color: #777;">Đồng hành cùng việc học</p></div>
    </div>
    <div class="benefit-item" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #f0f0f0; display: flex; align-items: center; gap: 15px;">
        <span class="benefit-icon" style="font-size: 24px; color: #c8815f;">✦</span>
        <div><strong style="font-size: 14px; color: #333;">Chọn lựa dễ dàng</strong><p style="margin: 0; font-size: 12px; color: #777;">Tìm món đồ bạn yêu thích</p></div>
    </div>
</section>

<!-- DANH SÁCH SẢN PHẨM -->
<section class="container product-section" id="products" style="margin-bottom: 60px;">
    <div class="section-heading" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; border-bottom: 2px solid #f5f5f5; padding-bottom: 15px;">
        <div>
            <p class="eyebrow" style="color: #c8815f; font-weight: 600; text-transform: uppercase; font-size: 12px; margin-bottom: 4px;">OUR COLLECTION</p>
            <h2 style="font-size: 26px; color: #222; margin: 0; font-weight: 700;">Sản phẩm nổi bật</h2>
            <p class="section-subtitle" style="margin: 4px 0 0 0; color: #777; font-size: 14px;">Những món đồ nhỏ xinh dành cho góc học tập của bạn.</p>
        </div>
        <span class="product-count" style="background: #f4ebd9; color: #555; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 500;"><?php echo count($products); ?> sản phẩm</span>
    </div>

    <!-- SẢN PHẨM (Phong cách Shopee chuyên nghiệp, hiệu ứng mượt mà) -->
    <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">

        <?php foreach ($products as $product): ?>

            <article class="product-card" style="background: #fff; border-radius: 10px; border: 1px solid #eaeaea; box-shadow: 0 2px 5px rgba(0,0,0,0.02); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.02)';">
                
                <!-- Khung hình ảnh sản phẩm -->
                <div class="product-image-wrap" style="position: relative; background: #faf9f6; height: 180px; width: 100%; overflow: hidden;">
                    <span class="discount-badge" style="position: absolute; top: 10px; right: 10px; background: rgba(238, 77, 45, 0.1); color: #ee4d2d; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 4px; z-index: 2;">Chính hãng</span>
                    
                    <a href="product.php?id=<?php echo (int)$product['id']; ?>" class="product-image" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <span class="product-emoji" style="font-size: 56px; line-height: 1; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"><?php echo e($product['image_emoji']); ?></span>
                    </a>
                </div>

                <!-- Phần thông tin sản phẩm -->
                <div class="product-info" style="padding: 15px; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between;">
                    <div>
                        <span class="category" style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo e($product['category']); ?></span>
                        <h3 style="font-size: 14px; margin: 6px 0 10px 0; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; line-height: 1.3;">
                            <a href="product.php?id=<?php echo (int)$product['id']; ?>" style="color: #333; text-decoration: none;"><?php echo e($product['name']); ?></a>
                        </h3>
                    </div>
                    
                    <div class="product-bottom" style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f5f5f5; padding-top: 10px; margin-top: auto;">
                        <strong class="product-price" style="color: #ee4d2d; font-size: 16px; font-weight: 600;"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?>₫</strong>
                        <a href="product.php?id=<?php echo (int)$product['id']; ?>" class="add-button" style="background: #fff; border: 1px solid #ee4d2d; color: #ee4d2d; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 500; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#ee4d2d'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='#ee4d2d';">Chi tiết</a>
                    </div>
                </div>
            </article>

        <?php endforeach; ?>

    </div>

    <?php if (count($products) === 0): ?>
        <div class="empty-message" style="text-align: center; padding: 40px; background: #fafafa; border-radius: 8px; color: #777;">Hiện chưa có sản phẩm nào còn hàng.</div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>