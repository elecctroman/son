<?php
use App\Helpers;

$pageData = isset($productPage) && is_array($productPage) ? $productPage : array();
$product = isset($pageData['product']) ? $pageData['product'] : null;
$breadcrumbs = isset($pageData['breadcrumbs']) && is_array($pageData['breadcrumbs']) ? $pageData['breadcrumbs'] : array();
$comments = isset($pageData['comments']) && is_array($pageData['comments']) ? $pageData['comments'] : array();
$commentFeedback = isset($pageData['commentFeedback']) && is_array($pageData['commentFeedback']) ? $pageData['commentFeedback'] : array();
$commentErrors = isset($commentFeedback['errors']) && is_array($commentFeedback['errors']) ? $commentFeedback['errors'] : array();
$commentSuccess = isset($commentFeedback['success']) ? (string)$commentFeedback['success'] : '';
$commentOld = isset($commentFeedback['old']['comment']) ? (string)$commentFeedback['old']['comment'] : '';
$isLoggedIn = !empty($isLoggedIn);
$currentUser = isset($user) && is_array($user) ? $user : null;

$renderParagraphs = static function ($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '<p class="product-detail__empty">Bilgi bulunmuyor.</p>';
    }

    $lines = preg_split("/\r\n|\r|\n/", $value);
    $html = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $html .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    return $html !== '' ? $html : '<p class="product-detail__empty">Bilgi bulunmuyor.</p>';
};

if (!$product) : ?>
    <section class="product-detail product-detail--empty">
        <div class="product-detail__empty-state">
            <span class="material-icons" aria-hidden="true">inventory_2</span>
            <h1>Ürün bulunamadı</h1>
            <p>Aradığınız ürün listemizde yer almıyor. Kataloğumuzu inceleyerek benzer seçenekleri keşfedebilirsiniz.</p>
            <a class="btn btn-primary" href="/kategori/">Kataloğa Dön</a>
        </div>
    </section>
    <?php return; ?>
<?php endif;

$normaliseImage = static function ($value) {
    if (is_array($value)) {
        foreach (array('url', 'image', 'src', 'thumbnail') as $key) {
            if (isset($value[$key]) && trim((string)$value[$key]) !== '') {
                return trim((string)$value[$key]);
            }
        }
    }

    if (is_string($value) || is_numeric($value)) {
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }

    return null;
};

$gallerySources = array();
if (!empty($product['gallery']) && is_array($product['gallery'])) {
    $gallerySources = array_merge($gallerySources, $product['gallery']);
}
if (!empty($product['media']) && is_array($product['media'])) {
    $gallerySources = array_merge($gallerySources, $product['media']);
}
if (!empty($product['images']) && is_array($product['images'])) {
    $gallerySources = array_merge($gallerySources, $product['images']);
}
if (!empty($product['image_variations']) && is_array($product['image_variations'])) {
    $gallerySources = array_merge($gallerySources, $product['image_variations']);
}
if (isset($product['image'])) {
    $gallerySources[] = $product['image'];
}

$gallery = array();
foreach ($gallerySources as $source) {
    $url = $normaliseImage($source);
    if ($url && !in_array($url, $gallery, true)) {
        $gallery[] = $url;
    }
}

if (!$gallery) {
    $gallery[] = '/theme/assets/images/placeholder.png';
}

$primaryImage = $gallery[0];
$productId = isset($product['id']) ? (int)$product['id'] : 0;
$productName = isset($product['name']) ? (string)$product['name'] : 'Ürün';
$slug = isset($product['slug']) ? (string)$product['slug'] : '';
$priceFormatted = isset($product['price_formatted']) ? (string)$product['price_formatted'] : Helpers::formatCurrency(0, 'TRY');
$stockLabel = isset($product['stock_label']) ? (string)$product['stock_label'] : (!empty($product['in_stock']) ? 'Stokta var' : 'Stokta yok');
$viewsCount = isset($product['views_count']) ? (int)$product['views_count'] : 0;
$viewsMessage = $viewsCount === 1 ? 'Bu ürünü 1 kişi inceliyor.' : sprintf('%d kişi bu ürünü inceledi.', $viewsCount);
$shortDescriptionHtml = $renderParagraphs(isset($product['short_description']) && $product['short_description'] !== '' ? $product['short_description'] : (isset($product['description']) ? $product['description'] : ''));
$descriptionHtml = $renderParagraphs(isset($product['description']) ? $product['description'] : '');
$commentCount = isset($product['comment_count']) ? (int)$product['comment_count'] : count($comments);
$category = isset($product['category']) && is_array($product['category']) ? $product['category'] : null;
$categoryName = $category && isset($category['name']) ? (string)$category['name'] : '';
$categoryUrl = $category ? Helpers::categoryUrl($category) : '/kategori/';
$pageUrl = Helpers::absoluteUrl($slug !== '' ? Helpers::productUrl($slug) : $_SERVER['REQUEST_URI']);

$metaItems = array();
if (!empty($product['sku'])) {
    $metaItems[] = array('label' => 'Stok Kodu', 'value' => (string)$product['sku']);
}
if (!empty($product['brand'])) {
    $metaItems[] = array('label' => 'Marka', 'value' => (string)$product['brand']);
}
if (!empty($product['delivery_time'])) {
    $metaItems[] = array('label' => 'Teslimat', 'value' => (string)$product['delivery_time']);
}
if (!empty($product['guarantee'])) {
    $metaItems[] = array('label' => 'Garanti', 'value' => (string)$product['guarantee']);
}

$highlightItems = array(
    array('icon' => 'rocket_launch', 'text' => 'Anında dijital teslimat'),
    array('icon' => 'verified_user', 'text' => '256-bit SSL ile güvenli ödeme'),
    array('icon' => 'headset_mic', 'text' => '7/24 uzman destek ekibi'),
);

?>

<section class="product-detail" data-product-root>
    <div class="container product-detail__container">
        <?php if ($breadcrumbs): ?>
            <nav aria-label="breadcrumb" class="product-detail__breadcrumbs">
                <ol>
                    <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                        <?php
                            $label = isset($breadcrumb['label']) ? (string)$breadcrumb['label'] : '';
                            $url = isset($breadcrumb['url']) ? $breadcrumb['url'] : null;
                            $isLast = $index === count($breadcrumbs) - 1;
                        ?>
                        <li<?= $isLast ? ' aria-current="page"' : '' ?>>
                            <?php if ($url && !$isLast): ?>
                                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <div class="product-detail__layout">
            <div class="product-detail__gallery" data-product-gallery>
                <figure class="product-detail__hero" data-gallery-hero>
                    <img
                        src="<?= htmlspecialchars($primaryImage, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?> görseli"
                        loading="lazy"
                        decoding="async"
                        data-gallery-main
                    >
                    <?php if (!empty($product['in_stock'])): ?>
                        <span class="product-detail__badge product-detail__badge--success">Stokta</span>
                    <?php else: ?>
                        <span class="product-detail__badge product-detail__badge--danger">Tükendi</span>
                    <?php endif; ?>
                </figure>

                <?php if (count($gallery) > 1): ?>
                    <div class="product-detail__thumbs" role="list">
                        <?php foreach ($gallery as $index => $imageUrl): ?>
                            <?php $isActive = $index === 0; ?>
                            <button
                                type="button"
                                class="product-detail__thumb<?= $isActive ? ' is-active' : '' ?>"
                                data-gallery-thumb
                                data-gallery-image="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                data-gallery-alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?> görseli"
                                aria-pressed="<?= $isActive ? 'true' : 'false' ?>"
                                role="listitem"
                            >
                                <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?> küçük görseli" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($viewsCount > 0): ?>
                    <p class="product-detail__stat">
                        <span class="material-icons" aria-hidden="true">visibility</span>
                        <?= htmlspecialchars($viewsMessage, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="product-detail__info">
                <?php if ($categoryName !== ''): ?>
                    <a class="product-detail__category" href="<?= htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>

                <h1 class="product-detail__title"><?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?></h1>

                <?php if (!empty($product['sku'])): ?>
                    <p class="product-detail__sku">Stok Kodu: <strong><?= htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                <?php endif; ?>

                <div class="product-detail__excerpt">
                    <?= $shortDescriptionHtml ?>
                </div>

                <div class="product-detail__price-row">
                    <div>
                        <span class="product-detail__price"><?= htmlspecialchars($priceFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="product-detail__tax-note">KDV dahildir</span>
                    </div>
                    <span class="product-detail__stock <?= !empty($product['in_stock']) ? 'is-in' : 'is-out' ?>">
                        <span class="material-icons" aria-hidden="true"><?= !empty($product['in_stock']) ? 'check_circle' : 'cancel' ?></span>
                        <?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <form action="/cart.php" method="post" id="productPurchaseForm" class="product-detail__purchase" data-product-purchase>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <input type="hidden" name="csrf_token" value="<?= Helpers::sanitize(Helpers::csrfToken()) ?>">
                    <label class="product-detail__quantity" for="productQuantity">
                        <span class="product-detail__quantity-label">Adet</span>
                        <input type="number" id="productQuantity" name="quantity" min="1" value="1" <?= !empty($product['in_stock']) ? '' : 'disabled' ?>>
                    </label>
                    <div class="product-detail__purchase-actions">
                        <button type="submit" class="btn btn-primary" <?= !empty($product['in_stock']) ? '' : 'disabled' ?>>Sepete Ekle</button>
                        <a class="btn btn-outline-secondary" href="/kategori/">Diğer Ürünler</a>
                    </div>
                </form>

                <ul class="product-detail__highlights">
                    <?php foreach ($highlightItems as $item): ?>
                        <li>
                            <span class="material-icons" aria-hidden="true"><?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($metaItems): ?>
                    <dl class="product-detail__meta">
                        <?php foreach ($metaItems as $meta): ?>
                            <div class="product-detail__meta-item">
                                <dt><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd><?= htmlspecialchars($meta['value'], ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>

                <div class="product-detail__share">
                    <span>Paylaş</span>
                    <div class="product-detail__share-links">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($pageUrl) ?>" target="_blank" rel="noopener" aria-label="Facebook'ta paylaş">
                            <span class="material-icons" aria-hidden="true">public</span>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($pageUrl) ?>&text=<?= rawurlencode($productName) ?>" target="_blank" rel="noopener" aria-label="X'te paylaş">
                            <span class="material-icons" aria-hidden="true">ios_share</span>
                        </a>
                        <button type="button" data-share-copy data-share-text="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>" data-share-url="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Bağlantıyı kopyala">
                            <span class="material-icons" aria-hidden="true">content_copy</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <section class="product-detail__section" id="product-description">
            <h2 class="product-detail__section-title">Ürün Açıklaması</h2>
            <div class="product-detail__section-body product-detail__description">
                <?= $descriptionHtml ?>
            </div>
        </section>

        <section class="product-detail__section" id="product-comments">
            <div class="product-detail__section-header">
                <h2 class="product-detail__section-title">Yorumlar</h2>
                <span class="product-detail__section-subtitle"><?= $commentCount ?> yorum</span>
            </div>

            <?php if ($commentSuccess !== ''): ?>
                <div class="product-detail__notice product-detail__notice--success" role="status">
                    <span class="material-icons" aria-hidden="true">check_circle</span>
                    <?= htmlspecialchars($commentSuccess, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($commentErrors): ?>
                <div class="product-detail__notice product-detail__notice--error" role="alert">
                    <span class="material-icons" aria-hidden="true">error</span>
                    <ul>
                        <?php foreach ($commentErrors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($comments): ?>
                <div class="product-detail__comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <article class="product-detail__comment" aria-label="<?= htmlspecialchars($comment['author'], ENT_QUOTES, 'UTF-8') ?> tarafından bırakılan yorum">
                            <header class="product-detail__comment-header">
                                <strong><?= htmlspecialchars($comment['author'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($comment['created_at_human'])): ?>
                                    <time datetime="<?= htmlspecialchars($comment['created_at'], ENT_QUOTES, 'UTF-8') ?>" class="product-detail__comment-date">
                                        <?= htmlspecialchars($comment['created_at_human'], ENT_QUOTES, 'UTF-8') ?>
                                    </time>
                                <?php endif; ?>
                            </header>
                            <p><?= nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="product-detail__empty">Bu ürün için henüz yorum yapılmadı. İlk yorumu siz yazın!</p>
            <?php endif; ?>

            <div class="product-detail__comment-card">
                <?php if ($isLoggedIn && $currentUser): ?>
                    <form method="post" class="product-detail__comment-form">
                        <input type="hidden" name="action" value="product_comment">
                        <input type="hidden" name="csrf_token" value="<?= Helpers::sanitize(Helpers::csrfToken()) ?>">
                        <label for="commentBody" class="product-detail__comment-label">Yorumunuz</label>
                        <textarea id="commentBody" name="comment" rows="4" required placeholder="Deneyiminizi paylaşın..."><?= htmlspecialchars($commentOld, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="submit" class="btn btn-primary">Yorumu Gönder</button>
                    </form>
                <?php else: ?>
                    <p class="product-detail__empty">
                        Yorum yapabilmek için lütfen <a href="/login.php">giriş yapın</a> veya <a href="/register.php">hesap oluşturun</a>.
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="product-detail__mobile-cta" data-mobile-cta>
        <div>
            <span class="product-detail__mobile-cta-label">Toplam</span>
            <span class="product-detail__mobile-cta-price" data-mobile-price><?= htmlspecialchars($priceFormatted, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <button
            type="submit"
            class="btn btn-primary"
            form="productPurchaseForm"
            <?= !empty($product['in_stock']) ? '' : 'disabled' ?>
        >
            Sepete Ekle
        </button>
    </div>
</section>
