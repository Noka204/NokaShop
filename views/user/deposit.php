<?php
// views/user/deposit.php
require 'views/layout/dashboard_header.php';

$error = '';

// Fetch deposit history
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$payments = $stmt->fetchAll();
?>

<!-- Tailwind CDN for this page -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#3b82f6',
                    'primary-dark': '#2563eb',
                    'slate-900': '#0f172a',
                },
                borderRadius: {
                    'card': '12px',
                }
            }
        }
    }
</script>

<style>
    .dash-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: all 0.25s ease;
        overflow: hidden;
    }
    .dash-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .btn-action {
        transition: all 0.15s ease;
        font-weight: 600;
        letter-spacing: -0.1px;
    }
    .btn-action:active {
        transform: scale(0.97);
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .anim-fade-up { animation: fadeUp 0.4s ease both; }
    @keyframes scan-line {
        0% { transform: translateY(-100%); }
        100% { transform: translateY(1000%); }
    }
    .animate-scan {
        animation: scan-line 3s linear infinite;
    }
    .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

    /* ===== MOBILE RESPONSIVE (inside page to override Tailwind) ===== */
    @media (max-width: 991.98px) {
        .max-w-6xl { padding: 0 !important; }
        .max-w-6xl .py-6 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
        .max-w-6xl .space-y-8 > * + * { margin-top: 1rem !important; }
        .max-w-6xl .text-2xl { font-size: 1.2rem !important; }

        /* Stack grid vertically */
        .grid.grid-cols-1.lg\:grid-cols-12 {
            display: flex !important;
            flex-direction: column !important;
            gap: 1rem !important;
        }
        .lg\:col-span-5, .lg\:col-span-7 {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Shrink card padding */
        .dash-card.p-6 { padding: 1rem !important; }
        .dash-card.p-10 { padding: 1.25rem !important; }

        /* Bank info grid: stack */
        .grid.grid-cols-2.md\:grid-cols-3 {
            grid-template-columns: 1fr !important;
            gap: 0.5rem !important;
        }

        /* Amount input */
        input.text-2xl { font-size: 1.2rem !important; padding: 0.75rem 1rem !important; }

        /* Button grid: stack */
        .grid.grid-cols-2.gap-3 {
            grid-template-columns: 1fr !important;
        }

        /* Transfer code text */
        code.text-xl { font-size: 0.9rem !important; word-break: break-all; }

        /* Transfer detail flex → stack on tiny screens */
        .p-5 .flex.justify-between { flex-wrap: wrap; gap: 0.5rem; }

        /* QR section */
        .min-h-\[500px\] { min-height: auto !important; }
        #qr_empty { padding: 1.5rem 1rem !important; min-height: auto !important; }
        #qr_empty .w-16 { width: 3rem !important; height: 3rem !important; }
        #qr_empty h5 { font-size: 0.9rem !important; }

        /* Deposit history table */
        .space-y-4 h3.text-lg { font-size: 1rem !important; }
        .dash-card .overflow-x-auto table,
        .table-responsive-custom table { min-width: 500px; }
    }

    @media (max-width: 575.98px) {
        .dash-card.p-6 { padding: 0.75rem !important; }
        .p-5 { padding: 0.75rem !important; }
        .p-4 { padding: 0.6rem !important; }
        .space-y-6 > * + * { margin-top: 0.65rem !important; }
        .space-y-5 > * + * { margin-top: 0.5rem !important; }
        .space-y-4 > * + * { margin-top: 0.4rem !important; }
        .gap-6 { gap: 0.75rem !important; }
        .text-2xl { font-size: 1.05rem !important; }
        code.text-xl { font-size: 0.8rem !important; }
    }
</style>

<div class="max-w-6xl mx-auto space-y-8 py-6">
    <!-- Header -->
    <div class="anim-fade-up">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Nạp Tiền Tự Động</h2>
        <div class="flex items-center gap-3 text-slate-500 text-sm font-medium mt-1">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> Trực tuyến 24/7
            </span>
            <span class="text-slate-300">•</span>
            <span>Cổng VietQR thông minh</span>
        </div>
    </div>

    <?php display_flash_message('success'); ?>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl flex items-center gap-3 anim-fade-up">
            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="font-semibold text-sm">Nạp tiền thành công! Số dư đã được cập nhật.</span>
        </div>
    <?php endif; ?>

    <!-- Main Section: Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Side: Workflow -->
        <div class="lg:col-span-5 space-y-5">
            <div class="dash-card p-6 space-y-6 anim-fade-up">
                
                <!-- Step 1 -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-primary uppercase tracking-widest">Bước 01</span>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase">Nhập số tiền</span>
                    </div>
                    
                    <div class="relative">
                        <style>
                            /* Ẩn mũi tên tăng giảm số */
                            input[type="number"]::-webkit-inner-spin-button,
                            input[type="number"]::-webkit-outer-spin-button {
                                -webkit-appearance: none;
                                margin: 0;
                            }
                            input[type="number"] {
                                -moz-appearance: textfield; /* Firefox */
                            }
                            /* Focus viền mảnh hơn */
                            #qr_amount:focus { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }
                        </style>
                        <input type="number" id="qr_amount" min="2000" max="1000000" step="1000" 
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-xl text-2xl font-normal text-slate-800 focus:border-primary outline-none placeholder:text-slate-300 font-mono transition-all" 
                            placeholder="0">
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 font-semibold text-slate-400 text-sm">VNĐ</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button id="btn_generate" class="btn-action bg-slate-900 hover:bg-primary text-white py-3.5 rounded-xl flex items-center justify-center gap-2 text-sm shadow-sm hover:shadow-md transition-all">
                            Xác nhận tạo mã
                            <span class="ml-1">→</span>
                        </button>
                        <button id="btn_reset" class="btn-action bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold px-5 rounded-xl hidden text-sm" onclick="handleReset()">
                            Làm mới
                        </button>
                    </div>
                </div>

                <!-- Step 2: Transfer Details -->
                <div id="qr_step_2" class="hidden space-y-5 pt-5 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-primary uppercase tracking-widest">Bước 02</span>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase">Thông tin chuyển khoản</span>
                    </div>

                    <div class="space-y-3">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase mb-1">Ngân hàng</p>
                                <p class="text-sm font-bold text-slate-900">MB BANK</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase mb-1">Số tài khoản</p>
                                <p class="text-sm font-bold text-slate-900">0353977178</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase mb-1">Chủ tài khoản</p>
                                <p class="text-sm font-bold text-slate-900">TRAN NHU KHANH</p>
                            </div>
                        </div>

                        <div class="p-5 bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-xl relative overflow-hidden">
                            <div class="flex justify-between items-center relative z-10">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-primary uppercase">Nội dung chuyển khoản</p>
                                    <code class="text-xl font-bold text-slate-900 tracking-wider font-mono" id="qr_content">...</code>
                                </div>
                                <button onclick="copyContent()" class="btn-action text-xs font-semibold px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-primary transition-colors">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="status_container" class="hidden items-center justify-between p-4 bg-slate-900 text-white rounded-xl overflow-hidden relative">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-2 h-2 bg-primary rounded-full animate-pulse"></div>
                            <span class="text-xs font-bold uppercase tracking-wider" id="status_text">Hệ thống đang chờ giao dịch...</span>
                        </div>
                        <div class="text-[10px] font-semibold opacity-40 relative z-10">POLLING</div>
                        <div class="absolute bottom-0 left-0 h-[2px] w-full" style="background: linear-gradient(90deg, transparent, #3b82f6, transparent); animation: shimmer 2s infinite;"></div>
                    </div>

                    <button id="btn_sync" onclick="manualSync()" class="hidden w-full py-3.5 border border-slate-200 text-slate-500 hover:border-primary hover:text-primary font-semibold text-xs uppercase tracking-wider rounded-xl transition-all">
                        Kiểm tra giao dịch thủ công
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Side: QR Visual -->
        <div class="lg:col-span-7 h-full min-h-[500px]">
            <div id="qr_sidebar" class="hidden h-full flex-col items-center justify-center dash-card p-10 relative overflow-hidden anim-fade-up">
                <div class="relative z-10 w-full max-w-sm">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-lg relative">
                        <div class="relative overflow-hidden bg-white rounded-xl">
                            <img id="qr_image" src="" alt="VietQR" class="w-full h-auto aspect-square">
                            <div class="absolute top-0 left-0 w-full h-0.5 bg-primary/50 shadow-[0_0_10px_#3b82f6] animate-scan opacity-60"></div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col items-center gap-3">
                        <img src="https://vietqr.net/portal-v2/img/VietQR-Logo.png" alt="VietQR" class="h-5 opacity-40 hover:opacity-100 transition-opacity">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-widest">Quét mã để thanh toán</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div id="qr_empty" class="h-full flex flex-col items-center justify-center bg-slate-50/50 border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center anim-fade-up">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-5">
                    <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
                <h5 class="font-bold text-slate-400 text-base">Chưa có mã QR</h5>
                <p class="text-xs text-slate-400 mt-2 max-w-[260px] leading-relaxed">Nhập số tiền và nhấn xác nhận để hệ thống tạo mã VietQR thanh toán.</p>
            </div>
        </div>

    </div>

    <!-- History Table -->
    <div class="space-y-4 anim-fade-up" style="animation-delay: 0.15s;">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Lịch sử giao dịch</h3>
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Gần nhất</span>
        </div>
        
        <div class="dash-card overflow-hidden">
        <div class="table-responsive-custom">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-6 py-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Mã giao dịch</th>
                            <th class="px-6 py-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Kênh nạp</th>
                            <th class="px-6 py-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Số tiền</th>
                            <th class="px-6 py-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-4 text-[11px] font-semibold text-slate-500 uppercase tracking-wider text-right">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody id="history_body" class="divide-y divide-slate-100">
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-slate-400 text-sm">
                                    Chưa có giao dịch nào.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md"><?= htmlspecialchars($pay['transaction_code']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">
                                        <?= htmlspecialchars($pay['payment_method']) ?>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-primary font-mono">
                                        <?= format_currency($pay['amount']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $p_status_styles = [
                                            'pending' => 'text-amber-600 bg-amber-50',
                                            'success' => 'text-emerald-600 bg-emerald-50',
                                            'failed' => 'text-rose-600 bg-rose-50'
                                        ];
                                        $p_status_labels = ['pending' => 'Đang chờ', 'success' => 'Thành công', 'failed' => 'Thất bại'];
                                        $p_color = $p_status_styles[$pay['status']] ?? 'text-slate-400 bg-slate-50';
                                        $p_label = $p_status_labels[$pay['status']] ?? $pay['status'];
                                        ?>
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md <?= $p_color ?>">
                                            <?= $p_label ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-slate-400 font-mono text-xs">
                                        <?= date('d/m/Y H:i', strtotime($pay['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    let qrCode = "";
    let qrToken = ""; // Token mã hoá để lấy QR qua proxy

    function generateRandomCode() {
        const digits = "0123456789";
        let code = "";
        for (let i = 0; i < 5; i++) {
            code += digits.charAt(Math.floor(Math.random() * digits.length));
        }
        return "NAP" + code;
    }

    async function getQrToken(amount, code) {
        try {
            const url = `<?= BASE_URL ?>index.php?route=api/qr-proxy&mode=token&amount=${amount}&code=${code}`;
            console.log("Fetching QR Token from:", url);
            const response = await fetch(url);
            const data = await response.json();
            console.log("Token response data:", data);
            return data.token || '';
        } catch (e) {
            console.error("Failed to fetch QR Token:", e);
            return '';
        }
    }

    function updateQR(token) {
        const proxyUrl = `<?= BASE_URL ?>index.php?route=api/qr-proxy&t=${encodeURIComponent(token)}`;
        console.log("Loading QR Image via Proxy:", proxyUrl);
        document.getElementById('qr_image').src = proxyUrl;
    }

    async function handleGenerate() {
        const amountInput = document.getElementById('qr_amount');
        const amount = amountInput.value;
        if (!amount || amount < 2000) {
            alert('Vui lòng nhập số tiền hợp lệ (tối thiểu 2,000đ)');
            return;
        }

        qrCode = generateRandomCode();
        document.getElementById('qr_content').innerText = qrCode;

        // Save to database
        try {
            const response = await fetch(`<?= BASE_URL ?>index.php?route=api/update-amount&code=${qrCode}&amount=${amount}`);
            const data = await response.json();

            if (data.status !== 'success') {
                alert('Có lỗi xảy ra khi khởi tạo giao dịch. Vui lòng thử lại.');
                return;
            }
        } catch (e) {
            console.error('Không thể cập nhật số tiền:', e);
            alert('Lỗi kết nối máy chủ.');
            return;
        }

        // Apply new sharp UI transitions
        document.getElementById('qr_step_2').classList.remove('hidden');
        document.getElementById('qr_sidebar').classList.remove('hidden');
        document.getElementById('qr_sidebar').classList.add('flex');
        document.getElementById('qr_empty').classList.add('hidden');
        document.getElementById('status_container').classList.remove('hidden');
        document.getElementById('status_container').classList.add('flex');
        document.getElementById('btn_sync').classList.remove('hidden');

        // Add scan animation effect
        const qrImg = document.getElementById('qr_image');
        qrImg.parentElement.classList.add('group'); // Trigger scan line via hover or leave it for auto-group

        // Update QR Image qua proxy (ẩn thông tin ngân hàng)
        qrToken = await getQrToken(amount, qrCode);
        if (!qrToken) {
            alert('Lỗi tạo mã QR. Vui lòng thử lại.');
            return;
        }
        updateQR(qrToken);

        // Disable inputs & Update Button to High Contrast State
        amountInput.readOnly = true;
        amountInput.classList.add('bg-slate-200', 'text-slate-400');
        
        const genBtn = document.getElementById('btn_generate');
        genBtn.disabled = true;
        genBtn.innerHTML = 'MONITORING...';
        genBtn.classList.replace('bg-slate-900', 'bg-slate-200');
        genBtn.classList.replace('text-white', 'text-slate-400');
        
        document.getElementById('btn_reset').classList.remove('hidden');

        // Add to history table dynamically (Minimalist version)
        const historyBody = document.getElementById('history_body');
        const emptyMsg = historyBody.querySelector('td[colspan="5"]');
        if (emptyMsg) emptyMsg.parentElement.remove();

        const now = new Date();
        const timeStr = now.getFullYear() + '-' + (now.getMonth() + 1).toString().padStart(2, '0') + '-' + now.getDate().toString().padStart(2, '0') + 
                        ' ' + now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

        const newRow = `
            <tr class="hover:bg-slate-50 transition-colors group">
                <td class="px-8 py-5">
                    <span class="font-mono text-xs font-black text-slate-900 bg-slate-100 border border-slate-200 px-3 py-1">${qrCode}</span>
                </td>
                <td class="px-8 py-5 text-xs font-extrabold text-slate-600 uppercase tracking-tight">VietQR</td>
                <td class="px-8 py-5 font-black text-primary font-mono text-base">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount)}</td>
                <td class="px-8 py-5">
                    <span class="text-[10px] font-black uppercase flex items-center gap-2 text-amber-600">
                        <span class="w-1.5 h-1.5 bg-current rounded-none"></span>
                        WAITING
                    </span>
                </td>
                <td class="px-8 py-5 text-right text-slate-400 font-mono text-[11px] font-bold">${timeStr}</td>
            </tr>
        `;
        historyBody.insertAdjacentHTML('afterbegin', newRow);

        // Bật global polling khi có đơn nạp tiền mới
        if (typeof startPaymentPolling === 'function') {
            startPaymentPolling();
        }
        // Poll riêng cho trang nạp tiền (check trạng thái QR cụ thể) mỗi 10 giây
        if (!window._depositPollTimer) {
            window._depositPollTimer = setInterval(checkPaymentStatus, 10000);
        }
    }

    async function checkPaymentStatus() {
        if (window.payment_finished) return;

        try {
            const response = await fetch(`<?= BASE_URL ?>index.php?route=api/check-deposit&code=${qrCode}`);
            const data = await response.json();

            const statusText = document.getElementById('status_text');
            const statusContainer = document.getElementById('status_container');

            if (data.status === 'success') {
                if (data.payment_status === 'success') {
                    window.payment_finished = true;
                    // Dừng poll local khi đã hoàn thành
                    if (window._depositPollTimer) {
                        clearInterval(window._depositPollTimer);
                        window._depositPollTimer = null;
                    }
                    statusContainer.className = "flex items-center justify-between p-4 bg-emerald-600 text-white relative overflow-hidden";
                    statusText.innerHTML = "VERIFIED: TRANSACTION COMPLETE";

                    // Update history row
                    const historyRows = document.querySelectorAll('#history_body tr');
                    for (let row of historyRows) {
                        if (row.innerText.includes(qrCode)) {
                            const indicator = row.querySelector('.text-amber-600');
                            if (indicator) {
                                indicator.className = 'text-[10px] font-black uppercase flex items-center gap-2 text-emerald-600';
                                indicator.innerHTML = '<span class="w-1.5 h-1.5 bg-current rounded-none"></span> VERIFIED';
                            }
                            // Cũng check nếu row là PHP-rendered (dùng class khác)
                            const phpBadge = row.querySelector('.text-amber-600.bg-amber-50');
                            if (phpBadge) {
                                phpBadge.className = 'text-[11px] font-semibold px-2.5 py-1 rounded-md text-emerald-600 bg-emerald-50';
                                phpBadge.innerText = 'Thành công';
                            }
                            break;
                        }
                    }

                    setTimeout(() => {
                        window.location.href = `<?= BASE_URL ?>index.php?route=deposit&status=success`;
                    }, 2500);
                } else {
                    statusText.innerHTML = "AWAITING TRANSACTION...";
                }
            }
        } catch (error) {
            console.error('Lỗi kiểm tra trạng thái:', error);
        }
    }

    function copyContent() {
        if (!qrCode) return;
        navigator.clipboard.writeText(qrCode).then(() => {
            const copyBtn = event.target;
            const originalText = copyBtn.innerText;
            copyBtn.innerText = 'DONE';
            copyBtn.classList.replace('bg-slate-900', 'bg-emerald-600');
            setTimeout(() => {
                copyBtn.innerText = originalText;
                copyBtn.classList.replace('bg-emerald-600', 'bg-slate-900');
            }, 1000);
        });
    }

    async function manualSync() {
        const btnSync = document.getElementById('btn_sync');
        const originalText = btnSync.innerHTML;
        btnSync.disabled = true;
        btnSync.innerHTML = 'SYNCING...';

        try {
            const response = await fetch(`<?= BASE_URL ?>index.php?route=api/sync-sepay`);
            const data = await response.json();

            if (data.success) {
                if (data.matched > 0) {
                    checkPaymentStatus();
                } else {
                    alert('Chưa thấy giao dịch mới. Vui lòng đợi 1-2 phút.');
                }
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (e) {
            console.error('Sync error:', e);
        } finally {
            btnSync.disabled = false;
            btnSync.innerHTML = originalText;
        }
    }

    function handleReset() {
        if (!confirm('Hệ thống đang theo dõi đơn hàng này. Bạn có chắc chắn muốn hủy và tạo mã mới?')) return;
        window.location.reload();
    }

    // Initialize
    document.getElementById('btn_generate').addEventListener('click', handleGenerate);
</script>

<?php require 'views/layout/dashboard_footer.php'; ?>