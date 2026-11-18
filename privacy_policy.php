<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/header.php';
?>

<section class="container account-section my-5">
    <div class="account-card p-4 bg-white shadow-sm rounded">
        <h1 class="mb-4">Chính sách bảo vệ dữ liệu cá nhân</h1>
        <p class="text-muted"><em>Cập nhật lần cuối: Tháng 10, 2026</em></p>

        <hr class="my-4">

        <h4>1. Mục đích thu thập dữ liệu cá nhân</h4>
        <p>
            UniStationery thu thập dữ liệu cá nhân của khách hàng chỉ nhằm mục đích phục vụ cho việc quản lý tài khoản, 
            xử lý đơn hàng, giao hàng, hỗ trợ chăm sóc khách hàng và cải thiện chất lượng dịch vụ mua sắm văn phòng phẩm trực tuyến.
        </p>

        <h4 class="mt-4">2. Phạm vi thu thập dữ liệu</h4>
        <p>Các thông tin cá nhân mà chúng tôi có thể thu thập bao gồm:</p>
        <ul>
            <li>Họ và tên</li>
            <li>Địa chỉ email, số điện thoại</li>
            <li>Địa chỉ giao nhận hàng hóa</li>
            <li>Lịch sử giao dịch và mua sắm trên hệ thống</li>
        </ul>

        <h4 class="mt-4">3. Quyền lợi và sự đồng ý của khách hàng</h4>
        <p>
            Theo quy định về bảo vệ dữ liệu cá nhân, việc thu thập thông tin dựa trên sự đồng ý tự nguyện của khách hàng. 
            Khi bạn tích chọn ô đồng ý tại trang đăng ký tài khoản, bạn đã xác nhận đồng ý cho phép hệ thống lưu trữ và xử lý dữ liệu theo chính sách này. 
            Bạn có quyền yêu cầu chúng tôi kiểm tra, cập nhật, điều chỉnh hoặc hủy bỏ dữ liệu cá nhân của mình bất cứ lúc nào.
        </p>

        <h4 class="mt-4">4. Cam kết bảo mật</h4>
        <p>
            Chúng tôi cam kết bảo mật tuyệt đối thông tin cá nhân của khách hàng, không chia sẻ, mua bán hoặc trao đổi dữ liệu 
            cho bên thứ ba nào khác ngoại trừ đơn vị vận chuyển phục vụ cho việc giao đơn hàng của bạn.
        </p>

        <div class="mt-4">
            <a href="register.php" class="btn btn-outline-secondary">← Quay lại trang Đăng ký</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>