<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'includes/database.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

// Xử lý cập nhật quyền nhân viên/khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $targetUserId = (int)$_POST['user_id'];
    $newRole = trim($_POST['role']);

    $allowedRoles = ['admin', 'staff', 'customer'];

    if (!in_array($newRole, $allowedRoles, true)) {
        $error = 'Vai trò không hợp lệ.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetUserId]);
            $success = 'Cập nhật phân quyền thành công cho tài khoản ID #' . $targetUserId;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Không thể cập nhật phân quyền.';
        }
    }
}

// Lấy danh sách toàn bộ người dùng
$stmt = $pdo->query("
    SELECT id, full_name, email, role, created_at
    FROM users
    ORDER BY id DESC
");
$users = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="container admin-staff-section py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Phân quyền Nhân viên</h1>
            <p class="text-muted small mb-0">Cấp quyền hoặc thay đổi vai trò truy cập hệ thống quản trị của người dùng.</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            ← Quay lại Dashboard
        </a>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success shadow-sm">✅ <?php echo e($success); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger shadow-sm">⚠️ <?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 bg-white overflow-hidden">
        <div class="card-body p-4">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <p class="text-muted mb-0">Chưa có tài khoản nào trong hệ thống.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="py-3 ps-3">ID</th>
                                <th class="py-3">Họ tên / Tài khoản</th>
                                <th class="py-3">Email</th>
                                <th class="py-3">Vai trò hiện tại</th>
                                <th class="py-3 text-end pe-3">Thao tác đổi quyền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-secondary">#<?php echo e($u['id']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                <?php echo mb_strtoupper(mb_substr($u['full_name'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo e($u['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo e($u['email']); ?></td>
                                    <td>
                                        <?php 
                                            $roleClass = 'bg-secondary';
                                            $roleLabel = 'Không xác định';
                                            if ($u['role'] === 'admin') {
                                                $roleClass = 'bg-danger';
                                                $roleLabel = 'Quản trị viên (Admin)';
                                            } elseif ($u['role'] === 'staff') {
                                                $roleClass = 'bg-primary';
                                                $roleLabel = 'Nhân viên (Staff)';
                                            } elseif ($u['role'] === 'customer') {
                                                $roleClass = 'bg-success';
                                                $roleLabel = 'Khách hàng (Customer)';
                                            }
                                        ?>
                                        <span class="badge <?php echo $roleClass; ?> bg-opacity-75 px-2 py-1">
                                            <?php echo $roleLabel; ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <form method="POST" class="d-inline-flex align-items-center gap-2 justify-content-end">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                            <select name="role" class="form-select form-select-sm" style="width: 160px;">
                                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                                <option value="staff" <?php echo $u['role'] === 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                                                <option value="customer" <?php echo $u['role'] === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-dark px-3" style="background: #2c3e50; border-color: #2c3e50;">
                                                Lưu
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>