<?php require 'header.php'; ?>

<main class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold mb-3">Câu hỏi thường gặp (FAQ)</h1>
                <p class="text-muted fs-5">Giải đáp nhanh các vấn đề bạn thường gặp phải khi sử dụng Noka Shop.</p>
            </div>

            <div class="accordion accordion-flush shadow-sm rounded-3 border" id="faqAccordion">
                <!-- FAQ 1 -->
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button fw-bold text-dark py-4" type="button" data-bs-toggle="collapse"
                            data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true"
                            aria-controls="collapseOne">
                            Nạp tiền vào web mất bao lâu thì có?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted lh-lg pb-4">
                            Hệ thống nạp tiền duyệt tự động hoàn toàn 24/7. Thường thao tác này mất chưa đến quá
                            <strong>1 - 3 phút</strong>. Nếu sau 10 phút bạn vẫn chưa thấy số dư tài khoản thay đổi, vui
                            lòng vào trang Hỗ Trợ gửi tin nhắn cho Zalo Admin cùng với hóa đơn chuyển khoản.
                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed fw-bold text-dark py-4" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false"
                            aria-controls="collapseTwo">
                            Tôi nhập nhầm Link/ID khi mua dịch vụ thì phải làm sao?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted lh-lg pb-4">
                            Theo <a href="<?= BASE_URL ?>dieu-khoan" class="text-decoration-none">Điều khoản dịch vụ</a>
                            hiện tại, website không hỗ trợ hoàn tiền tự động khi người mua nhập sai dữ liệu. Hệ thống sẽ
                            bỏ qua lệnh của bạn và không thể rollback đơn. Quý khách vui lòng kiểm tra kĩ mọi số liệu
                            trước khi thanh toán.
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed fw-bold text-dark py-4" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false"
                            aria-controls="collapseThree">
                            Key sản phẩm bản quyền là của chính hãng hay key lậu?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted lh-lg pb-4">
                            Tất cả mọi loại phần mềm,key được Noka Shop cung cấp đều đảm bảo cam kết là 100% key chính
                            thức. Mọi dịch vụ được bảo hành uy tín tuỳ theo từng mô tả nhóm hàng cụ thể.
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed fw-bold text-dark py-4" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false"
                            aria-controls="collapseFour">
                            Làm sao tôi có thể liên hệ lên Noka Shop?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted lh-lg pb-4">
                            Cách nhanh nhất là bạn có thể liên hệ thông qua tính năng nhắn tin trong <a
                                href="<?= BASE_URL ?>ho-tro" class="text-decoration-none fw-bold">Trang hỗ trợ</a>, hoặc
                            nhắn thẳng trực tiếp qua <strong>Discord</strong> hoặc <strong>Facebook (tnk.54264)</strong>
                            có ghim trong phần Footer dưới mỗi trang web.
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted mb-3">Vẫn không giải quyết được vấn đề?</p>
                <a href="https://facebook.com/pham.bao.484084" class="btn btn-dark fw-bold rounded-1 px-4 py-2">Liên hệ
                    Facebook</a>
            </div>

            <div class="mt-5 border-top pt-4">
                <a href="<?= BASE_URL ?>" class="btn btn-outline-dark fw-bold rounded-1 px-4"><i
                        class="fa-solid fa-arrow-left me-2"></i>Trở về trang chủ</a>
            </div>
        </div>
    </div>
</main>

<style>
    /* Style the accordion for a cleaner look */
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #000;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .125);
    }

    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0, 0, 0, .125);
    }
</style>

<?php require 'footer.php'; ?>