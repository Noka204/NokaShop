<?php
// includes/key_generator.php
// Tự động tạo Key/Tài khoản và đồng bộ lên Github API theo logic giống key_manager.py

// Load env if not loaded
if (!function_exists('load_env')) {
    require_once __DIR__ . '/../config/env.php';
}

class KeyGenerator
{
    private static function getDecryptedConfig()
    {
        return [
            'general' => [
                'token' => env('GITHUB_GENERAL_TOKEN', ''),
                'owner' => env('GITHUB_GENERAL_OWNER', ''),
                'repo' => env('GITHUB_GENERAL_REPO', ''),
            ],
            'files' => [
                'fakelag' => 'fps_key.json', // Mặc định
                'fixlag' => 'fps_key.json', // Tạm gắn giống nhau
                'ngungdong' => 'fps_key.json',
                'fakefps' => 'fps_key.json',
                'mangcali' => 'mangcali.json'
            ],
            'panel' => [
                'token' => env('GITHUB_PANEL_TOKEN', ''),
                'owner' => env('GITHUB_PANEL_OWNER', ''),
                'repo' => env('GITHUB_PANEL_REPO', ''),
                'file' => env('GITHUB_PANEL_FILE', 'anox_aimbot.json')
            ]
        ];
    }

    private static function fetchGitHubData($url, $token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: NokaShop-KeyGen",
            "Authorization: Bearer " . $token,
            "Accept: application/vnd.github+json",
            "X-GitHub-Api-Version: 2022-11-28"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private static function pushGitHubData($url, $token, $sha, $data)
    {
        $payload = [
            "message" => "Mua key tu dong NokaShop - " . date("Y-m-d H:i:s"),
            "content" => base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)),
            "sha" => $sha
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: NokaShop-KeyGen",
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "Accept: application/vnd.github+json"
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpcode == 200 || $httpcode == 201;
    }

    /**
     * Tạo Key mới và đẩy lên GitHub.
     * $productType: 'panel', 'ngungdong', hoac 'fixlag'
     * $usernameOrPrefix: Chuỗi tên muốn gán (Ví dụ: Tên viết tắt của user)
     * Trả về: array kết quả hoặc false nếu gặp lỗi.
     */
    public static function generateAndPushKey($productType, $usernameOrPrefix = "NOKA", $days = 30)
    {
        $config = self::getDecryptedConfig();

        // Tạo chuỗi 8 ký tự ngẫu nhiên
        $randomStr = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $usernameOrPrefix); // Làm sạch ký tự
        if (empty($prefix)) {
            $prefix = "USER";
        }

        $keyName = "{$prefix}-{$randomStr}";

        $nowLocal = date("Y-m-d H:i:s");
        $nowUtcIso = gmdate("Y-m-d\TH:i:s\Z");

        // Set mốc hết hạn mặt định 30 ngày cho những loại yêu cầu check ngày, Lifetime thì +100 năm
        $expiresUtcIsoCustom = gmdate("Y-m-d\TH:i:s\Z", strtotime("+$days days"));
        $expiresUtcIso30Days = gmdate("Y-m-d\TH:i:s\Z", strtotime("+30 days"));
        $expiresUtcIsoLifetime = gmdate("Y-m-d\TH:i:s\Z", strtotime("+36500 days"));

        if ($productType === 'panel') {
            $url = "https://api.github.com/repos/{$config['panel']['owner']}/{$config['panel']['repo']}/contents/{$config['panel']['file']}";
            $token = $config['panel']['token'];

            $panelType = "30_days";
            if ($days == 7) {
                $panelType = "7_days";
            }
            
            $newKey = [
                "username" => $keyName,
                "key" => "1", // Mặc định password panel là 1
                "type" => $panelType, 
                "devices_id" => "",
                "device_id" => null,
                "devices" => [],
                "created_at" => $nowLocal,
                "expires_at" => $expiresUtcIsoCustom
            ];

        }
        else if ($productType === 'fakelag') {
            $url = "https://api.github.com/repos/{$config['general']['owner']}/{$config['general']['repo']}/contents/{$config['files']['fakelag']}";
            $token = $config['general']['token'];

            $newKey = [
                "key" => $keyName,
                "gia" => "",
                "ngay" => $nowLocal,
                "devices_id" => "",
                "type" => "lifetime",
                "created_at" => $nowLocal,
                "expires_at" => $expiresUtcIsoLifetime,
                "note" => "Auto generated via Shop",
                "devices" => []
            ];

        }
        else if ($productType === 'ngungdong') {
            $url = "https://api.github.com/repos/{$config['general']['owner']}/{$config['general']['repo']}/contents/{$config['files']['ngungdong']}";
            $token = $config['general']['token'];

            $newKey = [
                "key" => $keyName,
                "gia" => "ALL",
                "ngay" => $nowLocal,
                "devices_id" => "",
                "type" => "lifetime",
                "created_at" => $nowLocal,
                "expires_at" => null, // Lifetime không set hết hạn
                "note" => "Auto generated via Shop",
                "devices" => []
            ];

        }
        else if ($productType === 'fixlag') {
            $url = "https://api.github.com/repos/{$config['general']['owner']}/{$config['general']['repo']}/contents/{$config['files']['fixlag']}";
            $token = $config['general']['token'];

            $newKey = [
                "key" => $keyName,
                "gia" => "",
                "ngay" => $nowLocal,
                "devices_id" => "",
                "type" => "lifetime",
                "created_at" => $nowLocal,
                "expires_at" => $expiresUtcIsoLifetime,
                "note" => "Auto generated via Shop",
                "devices" => [],
                "products" => [
                    [
                        "id" => "fixlag",
                        "purchased_at" => $nowUtcIso,
                        "status" => "active",
                        "expires_at" => null,
                        "price" => "150"
                    ]
                ]
            ];

        }
        else if ($productType === 'fakefps') {
            // FakeFPS - Tool category, dùng fps_key.json trong repo quanlykey
            $url = "https://api.github.com/repos/{$config['general']['owner']}/{$config['general']['repo']}/contents/{$config['files']['fakefps']}";
            $token = $config['general']['token'];

            $newKey = [
                "key" => $keyName,
                "gia" => "",
                "ngay" => $nowLocal,
                "devices_id" => "",
                "type" => "lifetime",
                "created_at" => $nowLocal,
                "expires_at" => $expiresUtcIsoLifetime,
                "note" => "Auto generated via Shop",
                "devices" => []
            ];

        }
        else if ($productType === 'mangcali') {
            // Mạng Cali - Tool category, dùng mangcali.json trong repo quanlykey
            $url = "https://api.github.com/repos/{$config['general']['owner']}/{$config['general']['repo']}/contents/{$config['files']['mangcali']}";
            $token = $config['general']['token'];

            $newKey = [
                "key" => $keyName,
                "gia" => "",
                "ngay" => $nowLocal,
                "devices_id" => "",
                "type" => "lifetime",
                "created_at" => $nowLocal,
                "expires_at" => $expiresUtcIsoLifetime,
                "note" => "Auto generated via Shop",
                "devices" => []
            ];

        }
        else {
            return false;
        }

        // 1. Fetch Dữ Liệu Từ GitHub
        $fileData = self::fetchGitHubData($url, $token);
        if (!$fileData || !isset($fileData['content'])) {
            return false;
        }

        $sha = $fileData['sha'];
        $contentObj = json_decode(base64_decode($fileData['content']), true);

        if (!isset($contentObj['keys'])) {
            $contentObj['keys'] = [];
        }

        // 2. Nối thêm key vừa tạo vào mảng keys hiện có
        $contentObj['keys'][] = $newKey;

        // 3. Đẩy file đã cập nhật lên lại lên API Github (Push)
        $success = self::pushGitHubData($url, $token, $sha, $contentObj);

        if ($success) {
            // Trả result tuỳ loại
            if ($productType === 'panel') {
                return [
                    'username' => $newKey['username'],
                    'password' => $newKey['key']
                ];
            }
            else {
                return [
                    'key' => $keyName
                ];
            }
        }
        return false;
    }
}
?>
