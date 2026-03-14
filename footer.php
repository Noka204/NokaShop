<!-- Footer -->
<footer class="pt-5 pb-4 mt-auto">
    <div class="container">
        <div class="row gy-4 mb-4">
            <div class="col-lg-5">
                <a class="navbar-brand d-block mb-3" href="<?= BASE_URL ?>"
                    style="font-size: 1.5rem; color: #000; font-weight: 900; text-decoration: none;">
                    NOKA SHOP
                </a>
                <p class="text-muted pe-lg-5">Thông tin minh bạch, chính sách hỗ trợ rõ ràng, ưu tiên trải nghiệm mua
                    hàng ổn định và lâu dài cho khách hàng.</p>
                <div class="d-flex gap-3 mt-4">
                    <!-- Text based social links -->
                    <a href="https://discord.gg/nSprjCSR"
                        class="text-dark fw-bold text-decoration-none border px-3 py-2 rounded-1 hover-bg-dark">Discord</a>
                    <a href="https://facebook.com/pham.bao.484084"
                        class="text-dark fw-bold text-decoration-none border px-3 py-2 rounded-1 hover-bg-dark">Facebook</a>
                </div>
            </div>
            <div class="col-lg-3 offset-lg-1 col-md-6">
                <h5 class="fw-bold mb-3 text-dark">Thông tin liên hệ</h5>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><span class="text-dark fw-bold me-2">Người sáng lập:</span> NokaPC</li>
                    <li><span class="text-dark fw-bold me-2">Discord:</span> https://discord.gg/nSprjCSR</li>
                    <li><span class="text-dark fw-bold me-2">Facebook:</span> tnk.54264</li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3 text-dark">Điều khoản</h5>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><a href="<?= BASE_URL ?>chinh-sach" class="text-muted text-decoration-none">Chính sách bảo mật</a></li>
                    <li><a href="<?= BASE_URL ?>dieu-khoan" class="text-muted text-decoration-none">Điều khoản dịch vụ</a></li>
                    <li><a href="<?= BASE_URL ?>faq" class="text-muted text-decoration-none">Câu hỏi thường gặp</a></li>
                </ul>
            </div>
        </div>
        <div class="border-top pt-4 text-center">
            <p class="text-muted mb-0 small">&copy; <?php echo date('Y'); ?> Noka Shop. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<style>
    .hover-bg-dark {
        transition: 0.2s;
    }

    .hover-bg-dark:hover {
        background-color: #000;
        color: #fff !important;
    }
</style>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- MODERN TOAST NOTIFICATION SYSTEM -->
<script>
    function showToast(type, message, duration = 5000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `noka-toast toast-${type}`;
        
        const icons = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-x-circle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        };
        const iconClass = icons[type] || icons['info'];
        const titleMap = {
            'success': 'Thành công',
            'error': 'Thất bại',
            'warning': 'Cảnh báo',
            'info': 'Thông báo'
        };
        
        toast.innerHTML = `
            <div class="d-flex p-3 gap-3 align-items-start">
                <div class="toast-icon-wrap">
                    <i class="bi ${iconClass}"></i>
                </div>
                <div class="flex-grow-1 min-width-0 text-start">
                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.95rem;">${titleMap[type] || 'Thông báo'}</h6>
                    <p class="mb-0 text-secondary" style="font-size: 0.85rem; line-height: 1.4;">${message}</p>
                </div>
                <button class="toast-close"><i class="bi bi-x"></i></button>
            </div>
            <div class="toast-progress">
                <div class="toast-progress-bar" style="animation-duration: ${duration}ms;"></div>
            </div>
        `;

        container.appendChild(toast);
        toast.offsetHeight;
        toast.classList.add('toast-show');

        let hideTimeout = setTimeout(() => hideToast(toast), duration);
        toast.querySelector('.toast-close').addEventListener('click', () => {
            clearTimeout(hideTimeout);
            hideToast(toast);
        });
    }

    function hideToast(toast) {
        toast.classList.remove('toast-show');
        toast.classList.add('toast-hide');
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, 400);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const flashMessages = document.querySelectorAll('.php-flash-message');
        flashMessages.forEach((el, index) => {
            setTimeout(() => {
                showToast(el.dataset.type, el.dataset.message);
            }, index * 200); 
            el.remove();
        });
    });
</script>
</body>

</html>