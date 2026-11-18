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

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '') {
        $error = 'Họ và tên không được để trống.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $userId]);
        $_SESSION['user_name'] = $fullName; // Cập nhật lại session
        $message = 'Cập nhật thông tin cá nhân thành công!';
    }
}

// Xử lý thêm địa chỉ mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_address') {
    $recipientName = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addressDetails = trim($_POST['address_details'] ?? '');

    if ($recipientName === '' || $phone === '' || $addressDetails === '') {
        $error = 'Vui lòng điền đầy đủ thông tin địa chỉ giao hàng.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO addresses (user_id, recipient_name, phone, address_details)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $recipientName, $phone, $addressDetails]);
        $message = 'Thêm địa chỉ giao hàng mới thành công!';
    }
}

// Lấy thông tin user hiện tại
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch();

// Lấy danh sách sổ địa chỉ của user
$addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

require_once 'includes/header.php';
?>

<section class="container account-section my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Tài khoản của tôi & Sổ địa chỉ</h1>
        <a href="index.php" class="btn btn-outline-secondary">← Về trang chủ</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?php echo e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Cập nhật thông tin cá nhân -->
        <div class="col-md-6 mb-4">
            <div class="account-card p-4 bg-white shadow-sm rounded h-100">
                <h3>Thông tin tài khoản</h3>
                <form method="POST" action="profile.php" class="mt-3">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label class="form-label">Email đăng nhập</label>
                        <input type="email" class="form-control" value="<?php echo e($currentUser['email']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo e($currentUser['full_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo e($currentUser['phone'] ?? ''); ?>" placeholder="Nhập số điện thoại">
                    </div>

                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </form>
            </div>
        </div>

        <!-- Thêm địa chỉ mới -->
        <div class="col-md-6 mb-4">
            <div class="account-card p-4 bg-white shadow-sm rounded h-100">
                <h3>Thêm địa chỉ giao hàng</h3>
                <form method="POST" action="profile.php" class="mt-3">
                    <input type="hidden" name="action" value="add_address">
                    
                    <div class="mb-3">
                        <label class="form-label">Họ tên người nhận</label>
                        <input type="text" class="form-control" name="recipient_name" value="<?php echo e($currentUser['full_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số điện thoại nhận hàng</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo e($currentUser['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Địa chỉ chi tiết (Số nhà, đường, phường/xã...)</label>
                        <textarea name="address_details" class="form-control" rows="2" required placeholder="VD: Số 12 Chùa Bộc, Đống Đa, Hà Nội"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Thêm địa chỉ</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách sổ địa chỉ đã lưu -->
    <div class="account-card p-4 bg-white shadow-sm rounded">
        <h3>Sổ địa chỉ đã lưu</h3>
        <?php if (empty($addresses)): ?>
            <p class="text-muted mt-2">Bạn chưa lưu địa chỉ giao hàng nào.</p>
        <?php else: ?>
            <div class="row mt-3">
                <?php foreach ($addresses as $addr): ?>
                    <div class="col-md-4 mb-3">
                        <div class="border p-3 rounded bg-light">
                            <h5 class="mb-1"><?php echo e($addr['recipient_name']); ?></h5>
                            <p class="mb-1 text-muted">📞 <?php echo e($addr['phone']); ?></p>
                            <p class="mb-0">📍 <?php echo e($addr['address_details']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>