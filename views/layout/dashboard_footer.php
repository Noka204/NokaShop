        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Global Payment Listener (Smart Polling) -->
    <script>
        let lastNotifiedPaymentId = localStorage.getItem('last_payment_id') || 0;
        let paymentPollTimer = null; // Timer ID cho polling

        async function globCheckPayments() {
            try {
                const response = await fetch('<?= BASE_URL ?>api/check-new-payments');
                if (!response.ok) return;
                const data = await response.json();

                if (data.success) {
                    // 1. Luôn cập nhật số dư hiển thị nếu có thay đổi
                    const balanceElements = document.querySelectorAll('.user-balance span, #header_balance');
                    balanceElements.forEach(el => {
                        if (el.innerText !== data.balance_formatted) {
                            el.innerText = data.balance_formatted;
                        }
                    });

                    // 2. Thông báo nếu có giao dịch mới
                    if (data.new_payment && data.new_payment.id > lastNotifiedPaymentId) {
                        lastNotifiedPaymentId = data.new_payment.id;
                        localStorage.setItem('last_payment_id', lastNotifiedPaymentId);
                        showGlobalNotification(`Nạp tiền thành công! +${data.new_payment.amount_formatted}`);
                        
                        // Nếu đang ở trang nạp tiền, reload lịch sử
                        if (window.location.pathname.includes('nap-tien') || window.location.pathname.includes('deposit')) {
                             if (typeof checkPaymentStatus === 'function') checkPaymentStatus();
                        }
                    }

                    // 3. Nếu không còn đơn pending → dừng polling để tiết kiệm tài nguyên
                    if (!data.has_pending) {
                        stopPaymentPolling();
                    }
                }
            } catch (e) {
                // Im lặng, thử lại lần sau
            }
        }

        /**
         * Bắt đầu polling mỗi 10 giây.
         * Gọi hàm này khi có đơn nạp tiền mới được tạo.
         */
        function startPaymentPolling() {
            if (paymentPollTimer) return; // Đã đang poll, không tạo thêm
            console.log('[PaymentPoll] Bắt đầu theo dõi thanh toán...');
            globCheckPayments(); // Check ngay lập tức lần đầu
            paymentPollTimer = setInterval(globCheckPayments, 10000); // 10 giây/lần
        }

        /**
         * Dừng polling khi không còn đơn pending.
         */
        function stopPaymentPolling() {
            if (paymentPollTimer) {
                clearInterval(paymentPollTimer);
                paymentPollTimer = null;
                console.log('[PaymentPoll] Dừng theo dõi — không còn đơn chờ.');
            }
        }

        // Khi tải trang: check 1 lần xem có đơn pending không → nếu có thì bật polling
        (async function initPaymentPoll() {
            try {
                const response = await fetch('<?= BASE_URL ?>api/check-new-payments');
                if (!response.ok) return;
                const data = await response.json();
                if (data.success && data.has_pending) {
                    startPaymentPolling();
                }
            } catch(e) {}
        })();
        // ===== MODERN TOAST NOTIFICATION SYSTEM =====
        function showToast(type, message, duration = 5000) {
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `noka-toast toast-${type}`;
            
            // Icon mapping
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
            const title = titleMap[type] || 'Thông báo';

            toast.innerHTML = `
                <div class="d-flex p-3 gap-3 align-items-start">
                    <div class="toast-icon-wrap">
                        <i class="bi ${iconClass}"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.95rem;">${title}</h6>
                        <p class="mb-0 text-secondary" style="font-size: 0.85rem; line-height: 1.4;">${message}</p>
                    </div>
                    <button class="toast-close"><i class="bi bi-x"></i></button>
                </div>
                <div class="toast-progress">
                    <div class="toast-progress-bar" style="animation-duration: ${duration}ms;"></div>
                </div>
            `;

            container.appendChild(toast);

            // Trigger reflow for animation
            toast.offsetHeight;
            toast.classList.add('toast-show');

            // Auto close logic
            let hideTimeout = setTimeout(() => hideToast(toast), duration);

            // Close button click
            toast.querySelector('.toast-close').addEventListener('click', () => {
                clearTimeout(hideTimeout);
                hideToast(toast);
            });
        }

        function hideToast(toast) {
            toast.classList.remove('toast-show');
            toast.classList.add('toast-hide');
            setTimeout(() => {
                if (toast.parentNode) toast.remove();
            }, 400); // Wait for CSS animation
        }

        // Thay thế hàm cũ để hệ thống payment xài chung UI này
        function showGlobalNotification(msg) {
            showToast('success', msg, 10000); // Payment thường quan trọng, cho chạy 10s
        }

        // Tự động đọc Flash Messages từ PHP (ẩn trong luồng render)
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.php-flash-message');
            flashMessages.forEach((el, index) => {
                // Thêm độ trễ nhẹ nếu có nhiều mssg cùng lúc để xếp tầng đẹp mắt
                setTimeout(() => {
                    showToast(el.dataset.type, el.dataset.message);
                }, index * 200); 
                el.remove(); // Dọn rác DOM
            });
        });        
        document.addEventListener('DOMContentLoaded', function() {
            const toggleOpen  = document.getElementById('sidebarToggleOpen');
            const toggleClose = document.getElementById('sidebarToggleClose');
            const overlay     = document.getElementById('sidebarOverlay');
            
            const openSidebar = () => document.body.classList.add('sidebar-open');
            const closeSidebar = () => document.body.classList.remove('sidebar-open');
            
            if (toggleOpen)  toggleOpen.addEventListener('click', openSidebar);
            if (toggleClose) toggleClose.addEventListener('click', closeSidebar);
            if (overlay)     overlay.addEventListener('click', closeSidebar);
            
            // Đóng sidebar khi click vào link nav trên mobile
            const navLinks = document.querySelectorAll('.nav-item');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        closeSidebar();
                    }
                });
            });
        });
    </script>
</body>
</html>
