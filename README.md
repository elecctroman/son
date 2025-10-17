# Proje Kodlama Standartları

## UTF-8 Kodlama Politikası
- Tüm PHP/HTML şablonlarında `<head>` etiketi açıldıktan hemen sonra `<meta charset="UTF-8">` etiketinin bulunduğunu doğrulayın.
- Tüm içerik yanıtlayan şablonlar HTML çıktısından önce `header('Content-Type: text/html; charset=utf-8');` çağrısını yapar. Yeni şablonlar eklerken aynı çağrıyı eklemeyi unutmayın.
- `bootstrap.php` aracılığıyla `mb_internal_encoding('UTF-8')` ve `mb_http_output('UTF-8')` çağrıları otomatik olarak yapılır; manuel olarak kaldırmayın.
- Projedeki metin tabanlı dosyaların tamamını UTF-8 (BOM'suz) olarak kaydedin. Farklı bir kodlamadan gelen dosyalar var ise düzenleyicinizin “UTF-8 (without BOM)” seçeneği ile tekrar kaydedin.
- PHP tarafında çok baytlı karakter güvenliği için `strlen`, `substr`, `strtoupper`, `strtolower` gibi fonksiyonlar yerine `mb_` ile başlayan karşılıkları kullanılmalıdır. Yeni kodda da bu yaklaşımı takip edin.

## Veritabanı Karakter Seti
- `app/Database.php` UTF-8 bağlantılarını `utf8mb4_unicode_ci` karşılaştırmasıyla kuracak şekilde yapılandırılmıştır. Farklı bir DSN kullanmanız gerekiyorsa `charset=utf8mb4` parametresini koruyun.
- Mevcut tabloları `utf8mb4` standardına taşımak için aşağıdaki komutları sırasıyla uygulayın (veritabanı adınızı özelleştirmeyi unutmayın).

```sql
ALTER DATABASE `your_database_name`
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `api_tokens` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `packages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `package_orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `menus` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `menu_items` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `customers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `customer_addresses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `products` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `product_images` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `product_meta` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `product_comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `order_items` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `order_status_history` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `invoices` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `payments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `payment_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `payment_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `shipping_addresses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `shipping_methods` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `discounts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `discount_usages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `coupons` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `coupon_redemptions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `blog_posts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `blog_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `blog_post_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `blog_comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `page_blocks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `page_block_items` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `faqs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `faq_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tickets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ticket_messages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ticket_attachments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ticket_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ticket_priorities` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `announcements` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `announcement_views` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `email_templates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `email_queue` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `email_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `sms_templates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `sms_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `settings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `setting_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `feature_toggles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `failed_jobs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `jobs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `job_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `media` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `media_folders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `taggables` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bank_accounts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bank_transfer_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_sessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `balance_transactions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `balance_requests` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `password_resets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `system_settings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `admin_activity_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `notification_reads` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## SQL Şema Dönüşümü
Aşağıdaki komutlar veri kaybını önlemek için bakım penceresinde çalıştırılmalıdır. Her tabloyu dönüştürmeden önce tam yedek almanız önerilir.

## SEO Dostu Ürün URL'leri
- `.htaccess` dosyasında yer alan `RewriteRule ^product/([a-zA-Z0-9-]+)/?$ product.php?slug=$1 [L,QSA]` kuralı sayesinde `/product/urun-adi` yapısındaki bağlantılar `product.php` dosyasına yönlendirilir. Yeni kurallar eklerken bu bölümü koruyun.
- Ürün detay sayfaları artık `products.slug` sütununu kullanır. Yeni ürün oluştururken slug değeri otomatik üretilir; manuel müdahale etmeniz gerekmez.
- Mevcut veritabanlarında slug sütunu yoksa aşağıdaki komutlar ile hem sütunu ekleyin hem de benzersiz değerleri doldurun.

```sql
ALTER TABLE products
    ADD COLUMN slug VARCHAR(191) NOT NULL AFTER name,
    ADD UNIQUE KEY idx_products_slug (slug);

UPDATE products
SET slug = LOWER(
        REGEXP_REPLACE(
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                REPLACE(REPLACE(REPLACE(name,
                    'Ç', 'c'), 'ç', 'c'),
                    'Ğ', 'g'), 'ğ', 'g'),
                    'İ', 'i'), 'I', 'i'), 'ı', 'i'),
                    'Ö', 'o'), 'ö', 'o'),
                    'Ş', 's'), 'ş', 's'),
                    'Ü', 'u'), 'ü', 'u'),
            '[^a-z0-9]+', '-'
        )
    )
WHERE slug IS NULL OR slug = '';

UPDATE products p
JOIN (
    SELECT slug, id,
           ROW_NUMBER() OVER (PARTITION BY slug ORDER BY id) AS duplicate_rank
    FROM products
) dup ON dup.id = p.id
SET p.slug = CONCAT(p.slug, '-', p.id)
WHERE dup.duplicate_rank > 1;
```
- Listeleme şablonları ürün kartlarındaki `url` alanını kullanarak bağlantı oluşturur. Yeni şablon geliştirirken `Helpers::productUrl($slug)` yardımcı metodundan yararlanabilirsiniz.
