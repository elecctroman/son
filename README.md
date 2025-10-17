# Proje Kodlama Standartları

## UTF-8 Kodlama Politikası
- Tüm PHP/HTML şablonlarında `<head>` etiketi açıldıktan hemen sonra `<meta charset="UTF-8">` etiketinin bulunduğunu doğrulayın.
- Tüm içerik yanıtlayan şablonlar HTML çıktısından önce `header('Content-Type: text/html; charset=utf-8');` çağrısını yapar. Yeni şablonlar eklerken aynı çağrıyı eklemeyi unutmayın.
- `bootstrap.php` aracılığıyla `mb_internal_encoding('UTF-8')` ve `mb_http_output('UTF-8')` çağrıları otomatik olarak yapılır; manuel olarak kaldırmayın.
- Projedeki metin tabanlı dosyaların tamamını UTF-8 (BOM'suz) olarak kaydedin. Farklı bir kodlamadan gelen dosyalar var ise düzenleyicinizin “UTF-8 (without BOM)” seçeneği ile tekrar kaydedin.
- PHP tarafında çok baytlı karakter güvenliği için `strlen`, `substr`, `strtoupper`, `strtolower` gibi fonksiyonlar yerine `mb_` ile başlayan karşılıkları kullanılmalıdır. Yeni kodda da bu yaklaşımı takip edin.

## Veritabanı Karakter Seti
- `app/Database.php` UTF-8 bağlantılarını `utf8mb4_unicode_ci` karşılaştırmasıyla kuracak şekilde yapılandırılmıştır. Farklı bir DSN kullanmanız gerekiyorsa `charset=utf8mb4` parametresini koruyun.
- Mevcut tabloları `utf8mb4` standardına taşımak için `resources/sql/utf8mb4-migration.sql` dosyasındaki komutları sırasıyla çalıştırın (veritabanı adınızı özelleştirmeyi unutmayın).

## SQL Şema Dönüşümü
Aşağıdaki komutlar veri kaybını önlemek için bakım penceresinde çalıştırılmalıdır. Her tabloyu dönüştürmeden önce tam yedek almanız önerilir.

## SEO Dostu Ürün URL'leri
- `.htaccess` dosyasında yer alan `RewriteRule ^product/([a-zA-Z0-9-]+)/?$ product.php?slug=$1 [L,QSA]` kuralı sayesinde `/product/urun-adi` yapısındaki bağlantılar `product.php` dosyasına yönlendirilir. Yeni kurallar eklerken bu bölümü koruyun.
- Ürün detay sayfaları artık `products.slug` sütununu kullanır. Yeni ürün oluştururken slug değeri otomatik üretilir; manuel müdahale etmeniz gerekmez.
- Mevcut veritabanlarını güncellemek için `resources/sql/products-slug-migration.sql` betiğini çalıştırın. Betik önce `slug` sütununu ekler, ardından mevcut kayıtlar için benzersiz değerler üretir.
- Listeleme şablonları ürün kartlarındaki `url` alanını kullanarak bağlantı oluşturur. Yeni şablon geliştirirken `Helpers::productUrl($slug)` yardımcı metodundan yararlanabilirsiniz.
