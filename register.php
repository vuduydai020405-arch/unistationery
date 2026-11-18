<?php
session_start();
require __DIR__ . '/includes/database.php';

$message = '';
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $privacyConsent = isset($_POST['privacy_consent']); // Kiểm tra xem người dùng đã tích chọn chưa

    if ($fullName === '' || strlen($fullName) > 120) {
        $message = 'Vui lòng nhập họ tên hợp lệ.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $message = 'Email không hợp lệ.';
    } elseif (strlen($password) < 8) {
        $message = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } elseif (!$privacyConsent) {
        // Tuân thủ pháp lý TMĐT: Bắt buộc khách hàng phải tích đồng ý chính sách (không được đánh dấu sẵn)[cite: 3]
        $message = 'Bạn phải đồng ý với chính sách xử lý dữ liệu cá nhân để đăng ký tài khoản.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $message = 'Email này đã được đăng ký.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([$fullName, $email, $hash]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
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
    <h1>Tạo tài khoản UniStationery</h1>
    <p>Đăng ký để trải nghiệm cửa hàng văn phòng phẩm.</p>

    <?php if ($message !== ''): ?>
        <p class="form-message text-danger"><?php echo e($message); ?></p>
    <?php endif; ?>

    <form method="post" action="register.php" class="account-form">
        <label for="full_name">Họ và tên</label>
        <input
            id="full_name"
            name="full_name"
            type="text"
            maxlength="120"
            autocomplete="name"
            required
            value="<?php echo e($fullName); ?>"
        >

        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            maxlength="190"
            autocomplete="email"
            required
            value="<?php echo e($email); ?>"
        >

        <label for="password">Mật khẩu</label>
        <input
            id="password"
            name="password"
            type="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <!-- Thêm checkbox đồng ý xử lý dữ liệu cá nhân (Tuân thủ pháp lý TMĐT - Mục 5)[cite: 3] -->
        <div class="mb-3 form-check my-3" style="display: flex; align-items: flex-start; gap: 10px;">
            <input 
                type="checkbox" 
                class="form-check-input" 
                id="privacy_consent" 
                name="privacy_consent" 
                required
                style="margin-top: 4px;"
            >
            <label class="form-check-label" for="privacy_consent" style="font-size: 0.95rem;">
                Tôi đồng ý cho phép hệ thống thu thập và xử lý dữ liệu cá nhân theo <a href="privacy_policy.php" target="_blank">Chính sách bảo vệ dữ liệu cá nhân</a>. (Ô này không được đánh dấu sẵn theo quy định)[cite: 3]
            </label>
        </div>

        <button class="primary-button btn btn-primary w-100" type="submit">
            Đăng ký
        </button>
    </form>

    <p class="mt-3">Đã có tài khoản?
        <a href="login.php">Đăng nhập</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>