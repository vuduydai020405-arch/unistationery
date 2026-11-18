<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'includes/database.php';

// Chỉ admin mới được truy cập
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return number_format((float) $value, 0, ',', '.') . '₫';
}

// Tổng số đơn hàng
$totalOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

// Số đơn đang chờ xác nhận
$pendingOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE status = 'pending'
")->fetchColumn();

// Tổng số khách hàng
$totalCustomers = (int) $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'customer'
")->fetchColumn();

// Tổng số sản phẩm
$totalProducts = (int) $pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

// Doanh thu từ đơn hàng đã hoàn thành
$totalRevenue = (float) $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status = 'completed'
")->fetchColumn();

// Doanh thu 6 tháng gần nhất
$startDate = new DateTimeImmutable('first day of this month');
$startDate = $startDate->modify('-5 months');

$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        SUM(total_amount) AS revenue
    FROM orders
    WHERE status = 'completed'
      AND created_at >= ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month_key ASC
");

$stmt->execute([$startDate->format('Y-m-d 00:00:00')]);
$monthlyRows = $stmt->fetchAll();

$monthlyRevenue = [];

foreach ($monthlyRows as $row) {
    $monthlyRevenue[$row['month_key']] = (float) $row['revenue'];
}

$months = [];

for ($i = 0; $i < 6; $i++) {
    $month = $startDate->modify("+{$i} months");
    $key = $month->format('Y-m');

    $months[] = [
        'key' => $key,
        'label' => $month->format('m/Y'),
        'revenue' => $monthlyRevenue[$key] ?? 0
    ];
}

$maxRevenue = 0;

foreach ($months as $month) {
    $maxRevenue = max($maxRevenue, $month['revenue']);
}

require_once 'includes/header.php';
?>

<section class="container admin-dashboard">

    <div class="admin-dashboard-heading">
        <h1>Tổng quan cửa hàng</h1>
        <p>Chào mừng bạn đến với trang quản trị UniStationery.</p>
    </div>

    <div class="dashboard-stats">

        <div class="dashboard-stat-card">
            <div class="stat-icon">📦</div>
            <p>Tổng đơn hàng</p>
            <h2><?php echo number_format($totalOrders); ?></h2>
        </div>

        <div class="dashboard-stat-card">
            <div class="stat-icon">⏳</div>
            <p>Đơn chờ xác nhận</p>
            <h2><?php echo number_format($pendingOrders); ?></h2>
        </div>

        <div class="dashboard-stat-card">
            <div class="stat-icon">👥</div>
            <p>Khách hàng</p>
            <h2><?php echo number_format($totalCustomers); ?></h2>
        </div>

        <div class="dashboard-stat-card">
            <div class="stat-icon">🖊️</div>
            <p>Sản phẩm</p>
            <h2><?php echo number_format($totalProducts); ?></h2>
        </div>

        <div class="dashboard-stat-card revenue-card">
            <div class="stat-icon">💰</div>
            <p>Doanh thu đã hoàn thành</p>
            <h2><?php echo money($totalRevenue); ?></h2>
        </div>

    </div>

    <div class="dashboard-chart-card">

        <h2>Doanh thu 6 tháng gần nhất (UC14)</h2>

        <p class="dashboard-description">
            Chỉ tính các đơn hàng có trạng thái Đã hoàn thành.
        </p>

        <div class="revenue-chart">

            <?php foreach ($months as $month): ?>

                <?php
                $height = $maxRevenue > 0
                    ? ($month['revenue'] / $maxRevenue) * 100
                    : 0;
                ?>

                <div class="revenue-chart-column">

                    <div class="revenue-chart-value">
                        <?php echo money($month['revenue']); ?>
                    </div>

                    <div class="revenue-chart-bar-area">
                        <div
                            class="revenue-chart-bar"
                            style="height: <?php echo $height; ?>%;"
                            title="<?php echo e(money($month['revenue'])); ?>"
                        ></div>
                    </div>

                    <div class="revenue-chart-label">
                        <?php echo e($month['label']); ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <?php if ($maxRevenue <= 0): ?>
            <p class="dashboard-empty">
                Chưa có doanh thu từ đơn hàng hoàn thành trong khoảng thời gian này.
            </p>
        <?php endif; ?>

    </div>

    <div class="dashboard-quick-links">

        <h2>Quản lý nhanh</h2>

        <div class="dashboard-link-list">

            <a href="admin.php" class="dashboard-link">
                <span>📋 Quản lý đơn hàng (UC11)</span>
                <span>→</span>
            </a>

            <a href="admin_products.php" class="dashboard-link">
                <span>🛍️ Quản lý sản phẩm (UC10)</span>
                <span>→</span>
            </a>

            <a href="admin_customers.php" class="dashboard-link">
                <span>👥 Quản lý khách hàng (UC13)</span>
                <span>→</span>
            </a>

            <a href="admin_staff.php" class="dashboard-link">
                <span>🛡️ Quản lý Phân quyền Nhân viên</span>
                <span>→</span>
            </a>

        </div>

    </div>

</section>

<?php require_once 'includes/footer.php'; ?>