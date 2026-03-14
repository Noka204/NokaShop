    </main>

    <!-- Footer -->
    <footer class="lnd-footer">
        <div class="lnd-container" bis_skin_checked="1">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:40px" bis_skin_checked="1">
                <div bis_skin_checked="1">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px" bis_skin_checked="1">
                         <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-lg">
                            <i class="fa-solid fa-bolt text-lg"></i>
                        </div>
                        <span style="color:#fff;font-size:1.5rem;font-weight:800">NokaService</span>
                    </div>
                    <p style="color:var(--text-sub);font-size:0.9rem;margin-bottom:20px">
                        Hệ thống cung cấp dịch vụ mạng xã hội, giải pháp marketing online chất lượng cao, uy tín và bảo mật.
                    </p>
                </div>
                <div bis_skin_checked="1">
                    <h4 style="color:#fff;font-size:1.2rem;margin-bottom:20px;font-weight:800">LIÊN KẾT NHANH</h4>
                    <ul style="list-style:none">
                        <li style="margin-bottom:12px">
                            <a href="<?= BASE_URL ?>" class="footer-link"><i class="fas fa-angle-right"></i> Trang chủ</a>
                        </li>
                        <li style="margin-bottom:12px">
                            <a href="#" class="footer-link"><i class="fas fa-angle-right"></i> Điều khoản dịch vụ</a>
                        </li>
                        <li style="margin-bottom:12px">
                            <a href="#" class="footer-link"><i class="fas fa-angle-right"></i> Chính sách bảo mật</a>
                        </li>
                    </ul>
                </div>
                <div bis_skin_checked="1">
                    <h4 style="color:#fff;font-size:1.2rem;margin-bottom:20px;font-weight:800">KẾT NỐI</h4>
                    <div style="display:flex;gap:12px" bis_skin_checked="1">
                        <a href="#" style="width:45px;height:45px;background:#3b5998;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:10px;text-decoration:none;font-size:1.2rem;transition:0.3s">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        
                        <a href="#" target="_blank" style="width:45px;height:45px;background:#0068ff;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:10px;text-decoration:none;font-size:1.4rem;font-weight:bold;transition:0.3s;border:1px solid rgba(255,255,255,0.2)">
                            Z
                        </a>
                        <a href="#" style="width:45px;height:45px;background:#0088cc;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:10px;text-decoration:none;font-size:1.2rem;transition:0.3s">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    </div>
                    <p style="color:var(--text-sub);font-size:0.9rem;margin-top:20px">Email: hotro@nokaservice.com</p>
                </div>
            </div>
            <div style="border-top:1px solid var(--glass-border);margin-top:50px;padding-top:30px;text-align:center;color:#475569;font-size:0.9rem" bis_skin_checked="1">
                &copy; <?= date('Y') ?> NokaService. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Any extra scripts go here -->
    <script>
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
                <div style="display: flex; padding: 1rem; gap: 1rem; align-items: flex-start;">
                    <div class="toast-icon-wrap" style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; background: ${type==='success'?'#dcfce7':type==='error'?'#fee2e2':'#e0e7ff'}; color: ${type==='success'?'#16a34a':type==='error'?'#dc2626':'#4f46e5'}">
                        <i class="bi ${iconClass}"></i>
                    </div>
                    <div style="flex-grow: 1; min-width: 0;">
                        <h6 style="font-weight: bold; margin-bottom: 0.25rem; color: #0f172a; font-size: 0.95rem;">${title}</h6>
                        <p style="margin: 0; color: #475569; font-size: 0.85rem; line-height: 1.4;">${message}</p>
                    </div>
                    <button class="toast-close" style="background: transparent; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; padding: 0.25rem;"><i class="bi bi-x"></i></button>
                </div>
                <div class="toast-progress" style="position: absolute; bottom: 0; left: 0; height: 4px; width: 100%; background: ${type==='success'?'#22c55e':type==='error'?'#ef4444':'#6366f1'};">
                    <div class="toast-progress-bar" style="height: 100%; width: 100%; background: rgba(255,255,255,0.7); transform-origin: right; animation: toastProgress ${duration}ms linear forwards;"></div>
                </div>
            `;

            container.appendChild(toast);
            toast.offsetHeight; // trigger reflow
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
