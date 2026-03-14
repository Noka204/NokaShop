<?php
// Standalone migration - connects directly
$pdo = new PDO("mysql:host=localhost;dbname=dichvu_db;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqls = [
    "CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
        value DECIMAL(15, 2) NOT NULL DEFAULT 0,
        min_order DECIMAL(15, 2) NOT NULL DEFAULT 0,
        max_uses INT NOT NULL DEFAULT 0,
        used_count INT NOT NULL DEFAULT 0,
        starts_at DATETIME DEFAULT NULL,
        expires_at DATETIME DEFAULT NULL,
        status ENUM('active', 'expired', 'disabled') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS promotion_uses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        promotion_id INT NOT NULL,
        user_id INT NOT NULL,
        order_id INT DEFAULT NULL,
        discount_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_promo_user (promotion_id, user_id),
        FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "INSERT IGNORE INTO routes (slug, target, is_api) VALUES ('admin/promotions', 'admin/promotions', 0)",
    "INSERT IGNORE INTO routes (slug, target, is_api) VALUES ('admin/tickets', 'admin/tickets', 0)",
];

foreach ($sqls as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "OK Query #" . ($i + 1) . "\n";
    } catch (Exception $e) {
        echo "ERR Query #" . ($i + 1) . ": " . $e->getMessage() . "\n";
    }
}
echo "Done!\n";
