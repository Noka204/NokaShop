<?php
/**
 * SePay API Utility (Native PHP)
 * Dựa trên tài liệu SePay Checkout v1
 */

class SePayAPI {
    private $merchant_id;
    private $secret_key;
    private $is_sandbox;

    public function __construct($merchant_id, $secret_key, $is_sandbox = true) {
        $this->merchant_id = $merchant_id;
        $this->secret_key = $secret_key;
        $this->is_sandbox = $is_sandbox;
    }

    /**
     * Tạo chữ ký SePay v1 (HMAC-SHA256)
     */
    private function generateSignature($params) {
        // Thứ tự các trường BẮT BUỘC cho SePay v1
        $fields = [
            'merchant',
            'operation',
            'payment_method',
            'order_amount',
            'currency',
            'order_invoice_number',
            'order_description',
            'customer_id',
            'success_url',
            'error_url',
            'cancel_url'
        ];

        $data = [];
        foreach ($fields as $field) {
            $value = $params[$field] ?? '';
            $data[] = "$field=$value";
        }

        // Nối các trường bằng dấu phẩy theo chuẩn SePay v1
        $signedString = implode(',', $data);
        
        // Hash với Secret Key (Base64 encoded raw binary hmac-sha256)
        return base64_encode(hash_hmac('sha256', $signedString, $this->secret_key, true));
    }

    /**
     * Tạo Form HTML để redirect sang SePay
     */
    public function generateCheckoutForm($orderData) {
        $endpoint = $this->is_sandbox 
            ? "https://pay-sandbox.sepay.vn/v1/checkout/init" 
            : "https://pay.sepay.vn/v1/checkout/init";

        // Các tham số bắt buộc cho Checkout v1
        $params = [
            'merchant'             => $this->merchant_id,
            'operation'            => 'PURCHASE',
            'payment_method'       => $orderData['payment_method'] ?? 'BANK_TRANSFER',
            'order_amount'         => $orderData['amount'],
            'currency'             => 'VND',
            'order_invoice_number' => $orderData['invoice_number'],
            'order_description'    => $orderData['description'],
            'customer_id'          => $orderData['user_id'] ?? '',
            'success_url'          => $orderData['success_url'],
            'error_url'            => $orderData['error_url'],
            'cancel_url'           => $orderData['cancel_url']
        ];

        // Tạo signature
        $params['signature'] = $this->generateSignature($params);

        // Build Form
        $html = '<form id="sepay_checkout_form" action="' . $endpoint . '" method="POST" style="display:none;">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
        }
        $html .= '</form>';
        $html .= '<script>document.getElementById("sepay_checkout_form").submit();</script>';

        return $html;
    }
}
