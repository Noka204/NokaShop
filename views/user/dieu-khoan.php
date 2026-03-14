<?php require 'header.php'; ?>

<main class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-4">Điều khoản dịch vụ</h1>
            <p class="text-muted mb-4"><i class="fa-regular fa-clock me-2"></i>Cập nhật lần cuối: <?php echo date('d/m/Y'); ?></p>
            <hr class="mb-5 border-light">

            <div class="policy-content text-muted lh-lg">
                <h4 class="text-dark fw-bold mb-3 mt-4">1. Quy định chung</h4>
                <p>Khi đăng ký, sử dụng tài khoản tại Noka Shop, bạn được hiểu là đồng ý với toàn bộ các điều khoản được hệ thống đưa ra dưới đây.</p>
                <p>Mọi hành vi gian lận, phá hoại hoặc lợi dụng lỗi hệ thống sẽ dẫn đến việc khóa tài khoản vĩnh viễn và thu hồi toàn bộ số dư.</p>

                <h4 class="text-dark fw-bold mb-3 mt-4">2. Chính sách nạp và thanh toán</h4>
                <p>Mọi giao dịch nạp tiền qua tài khoản ngân hàng được hệ thống xử lý hoàn toàn tự động trong 1 - 3 phút. Nếu có bất kỳ sự cố chậm trễ nào do lỗi API ngân hàng, bạn vui lòng liên hệ Admin qua kênh Hỗ trợ.</p>
                <p><strong>Không hỗ trợ hoàn tiền</strong> (refund) sau khi đã nạp tiền vào hệ thống thành công. Bạn chỉ có thể sử dụng số dư để mua các sản phẩm/dịch vụ tại Web.</p>

                <h4 class="text-dark fw-bold mb-3 mt-4">3. Chính sách sản phẩm và đơn hàng</h4>
                <ul>
                    <li>Sản phẩm đã mua và cấp phát (Link/Key) thành công sẽ không được đổi trả trừ khi xuất hiện lỗi từ phía hệ thống ngay lúc cung cấp.</li>
                    <li>Vui lòng đọc kỹ phần <strong>Lưu ý</strong> của mỗi gói dịch vụ trước khi thao tác "Mua ngay". Noka Shop từ chối bảo hành đối với các lỗi do người dùng nhập sai Link/ID thụ hưởng.</li>
                </ul>

                <h4 class="text-dark fw-bold mb-3 mt-4">4. Thay đổi điều khoản</h4>
                <p>Chúng tôi bảo lưu quyền thay đổi nội dung các điều khoản này theo thời gian. Mọi thay đổi sẽ có hiệu lực ngay khi được cập nhật tại trang này.</p>
            </div>
            
            <div class="mt-5 border-top pt-4">
                <a href="<?= BASE_URL ?>" class="btn btn-outline-dark fw-bold rounded-1 px-4"><i class="fa-solid fa-arrow-left me-2"></i>Trở về trang chủ</a>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>
