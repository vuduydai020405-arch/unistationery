<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php';

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT);

if (!$orderId) {
    header('Location: index.php');
    exit;
}

// Xử lý khi khách bấm nút xác nhận đã chuyển tiền thành công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    // Cập nhật trạng thái đơn hàng từ 'pending' sang 'confirmed' (đã thanh toán / đã xác nhận)
    $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$orderId]);

    header("Location: my_orders.php?id={$orderId}&payment=success");
    exit;
}

require_once 'includes/header.php';
?>

<section class="container my-5" style="max-width: 600px;">
    <div class="card shadow p-4 text-center bg-white rounded">
        <h3 class="text-danger mb-2">Cổng thanh toán MoMo Sandbox</h3>
        <p class="text-muted small">Đơn hàng số: <strong>#<?php echo (int)$orderId; ?></strong></p>
        
        <div class="alert alert-warning py-2">
            Số tiền cần thanh toán: <strong class="text-danger fs-5"><?php echo number_format($amount, 0, ',', '.'); ?>đ</strong>
        </div>

        <div class="my-3">
            <!-- Mã QR VietQR tích hợp MoMo -->
            <img src="https://img.vietqr.io/image/momo-0983461048-compact2.png?amount=<?php echo $amount; ?>&addInfo=UNISTATIONERY%20DH<?php echo $orderId; ?>&accountName=VU%20DUY%20DAI" 
                 alt="QR Code MoMo" 
                 class="img-fluid border rounded shadow-sm" 
                 style="max-width: 240px; height: auto;">
            <p class="small text-muted mt-2">Mở ứng dụng MoMo để quét mã thanh toán</p>
        </div>

        <ul class="list-unstyled small text-start d-inline-block mx-auto mb-3 bg-light p-3 border rounded">
            <li><strong>Ví / Ngân hàng:</strong> MoMo</li>
            <li><strong>Chủ tài khoản:</strong> VU DUY DAI</li>
            <li><strong>Số điện thoại:</strong> 0983461048</li>
            <li><strong>Nội dung chuyển khoản:</strong> <code>UNISTATIONERY DH<?php echo (int)$orderId; ?></code></li>
        </ul>

        <form method="POST">
            <button type="submit" name="confirm_payment" class="btn btn-success btn-lg w-100 mb-2">
                ✅ Đã quét và chuyển tiền thành công
            </button>
        </form>

        <a href="my_orders.php" class="btn btn-link text-muted small">Bỏ qua / Xem lại danh sách đơn hàng</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>