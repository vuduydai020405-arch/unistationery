<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/includes/database.php';

$message = '';

if (isset($_GET['registered'])) {
    $message = 'Đăng ký thành công! Hãy đăng nhập.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        // Phân hướng thông minh dựa theo vai trò (Role-based redirection)
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
            exit;
        } elseif ($user['role'] === 'staff') {
            header('Location: admin.php'); // Chuyển đến trang xử lý đơn hàng dành cho nhân viên
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    }

    $message = 'Email hoặc mật khẩu không chính xác.';
}

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

require __DIR__ . '/includes/header.php';
?>

<section class="container account-section">
    <h1>Đăng nhập</h1>
    <p>Chào mừng bạn quay lại UniStationery.</p>

    <?php if ($message !== ''): ?>
        <p class="form-message">
            <?php echo e($message); ?>
        </p>
    <?php endif; ?>

    <form method="post" action="login.php" class="account-form">
        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            autocomplete="email"
            required
        >

        <label for="password">Mật khẩu</label>
        <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            required
        >

        <button class="primary-button" type="submit">
            Đăng nhập
        </button>
    </form>

    <p>
        Chưa có tài khoản?
        <a href="register.php">Đăng ký ngay</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>