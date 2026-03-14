<?php require 'header.php'; ?>

<main class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-4">Chính sách bảo mật</h1>
            <p class="text-muted mb-4"><i class="fa-regular fa-clock me-2"></i>Cập nhật lần cuối:
                <?php echo date('d/m/Y'); ?></p>
            <hr class="mb-5 border-light">

            <div class="policy-content text-muted lh-lg">
                <h4 class="text-dark fw-bold mb-3 mt-4">1. Mục đích thu thập thông tin</h4>
                <p>Noka Shop thu thập thông tin cá nhân của người dùng để mục đích duy nhất là tạo tài khoản, xác thực
                    danh tính và hỗ trợ trong quá trình người dùng sử dụng dịch vụ tại hệ thống.</p>

                <h4 class="text-dark fw-bold mb-3 mt-4">2. Phạm vi sử dụng thông tin</h4>
                <p>Thông tin của bạn sẽ được sử dụng trong các nghiệp vụ sau:</p>
                <ul>
                    <li>Gửi thông báo về đơn hàng và giao dịch.</li>
                    <li>Hỗ trợ kỹ thuật và giải đáp thắc mắc (qua hệ thống ticket/live chat).</li>
                    <li>Nâng cấp trải nghiệm cá nhân hóa của người dùng.</li>
                </ul>

                <h4 class="text-dark fw-bold mb-3 mt-4">3. Bảo mật thông tin</h4>
                <p>Quản trị viên (NokaPC) tuyệt đối không bán, chia sẻ hay trao đổi thông tin khách hàng cho bên thứ ba
                    vì mục đích thương mại.</p>

                <h4 class="text-dark fw-bold mb-3 mt-4">4. Thời gian lưu trữ</h4>
                <p>Dữ liệu của người dùng sẽ được lưu trữ trên máy chủ của Noka Shop cho đến khi có yêu cầu hủy bỏ hoặc
                    người dùng tự thực hiện việc xóa tài khoản (nếu có).</p>
            </div>

            <div class="mt-5 border-top pt-4">
                <a href="<?= BASE_URL ?>" class="btn btn-outline-dark fw-bold rounded-1 px-4"><i
                        class="fa-solid fa-arrow-left me-2"></i>Trở về trang chủ</a>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>