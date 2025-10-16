<?php
require_once 'bootstrap.php';

try {
    $pdo = App\Database::connection();

    $statements = [
        "ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
        "ALTER TABLE categories MODIFY name VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;",
        "ALTER TABLE categories MODIFY icon VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;",
        "ALTER TABLE categories MODIFY description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;"
    ];

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    echo "Veritabanı tablo karakter seti başarıyla güncellendi. <br>";
    echo "Lütfen bu dosyayı şimdi silin: <strong>fix_encoding.php</strong>";

} catch (Exception $e) {
    http_response_code(500);
    echo "Hata oluştu: " . $e->getMessage();
}
