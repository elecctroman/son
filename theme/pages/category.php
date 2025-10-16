<?php
use App\Helpers;

$categoryData = isset($categoryPage['category']) && is_array($categoryPage['category']) ? $categoryPage['category'] : array();
$categoryName = isset($categoryData['name']) && $categoryData['name'] !== '' ? (string)$categoryData['name'] : 'Kategoriler';
$categoryDescription = isset($categoryData['description']) ? trim((string)$categoryData['description']) : '';
$categoryAccent = isset($categoryData['accent']) && $categoryData['accent'] !== '' ? (string)$categoryData['accent'] : '#6366f1';
$categoryImage = isset($categoryData['image']) && $categoryData['image'] !== '' ? (string)$categoryData['image'] : '/theme/assets/images/site/KYQdPJDHaWihG3n7Sf3sHseGTRMT3xtmVGlDvNOj.webp';
$categoryUrl = isset($categoryData['url']) && $categoryData['url'] !== '' ? (string)$categoryData['url'] : Helpers::categoryUrl(isset($categoryData['path']) ? $categoryData['path'] : '');

$breadcrumbs = isset($categoryPage['breadcrumbs']) && is_array($categoryPage['breadcrumbs']) ? $categoryPage['breadcrumbs'] : array();
$children = isset($categoryPage['children']) && is_array($categoryPage['children']) ? $categoryPage['children'] : array();
$products = isset($categoryPage['products']) && is_array($categoryPage['products']) ? $categoryPage['products'] : array();
$productCount = isset($categoryPage['productCount']) ? (int)$categoryPage['productCount'] : count($products);
$canShop = !empty($isLoggedIn);
$hasCategory = isset($categoryData['id']) && $categoryData['id'] !== null;
$productCountLabel = $productCount > 0 ? number_format($productCount, 0, ',', '.') : '0';
$primaryBreadcrumb = $breadcrumbs ? reset($breadcrumbs) : null;
?>

<section class="category-hero" style="--category-accent: <?= htmlspecialchars($categoryAccent) ?>;">
    <div class="category-hero__media" aria-hidden="true">
        <img src="<?= htmlspecialchars($categoryImage) ?>" alt="">
    </div>
    <div class="category-hero__inner">
        <?php if ($breadcrumbs): ?>
            <nav class="category-hero__breadcrumbs" aria-label="Breadcrumb">
                <ol>
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <?php
                            $label = isset($crumb['label']) ? (string)$crumb['label'] : '';
                            $url = isset($crumb['url']) ? (string)$crumb['url'] : '';
                        ?>
                        <?php if ($label === '') { continue; } ?>
                        <li>
                            <?php if ($url !== '' && ($index + 1) < count($breadcrumbs)): ?>
                                <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($label) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <div class="category-hero__content">
            <div class="category-hero__heading">
                <h1><?= htmlspecialchars($categoryName) ?></h1>
                <?php if ($categoryDescription !== ''): ?>
                    <p><?= htmlspecialchars($categoryDescription) ?></p>
                <?php elseif ($primaryBreadcrumb && empty($primaryBreadcrumb['url'])): ?>
                    <p><?= htmlspecialchars('En sevilen ' . $categoryName . ' ürünleri burada.') ?></p>
                <?php endif; ?>
            </div>
            <div class="category-hero__meta">
                <div class="category-hero__meta-item">
                    <span class="category-hero__meta-value"><?= htmlspecialchars($productCountLabel) ?></span>
                    <span class="category-hero__meta-label">Ürün</span>
                </div>
                <div class="category-hero__meta-item">
                    <span class="category-hero__meta-value"><?= htmlspecialchars(count($children)) ?></span>
                    <span class="category-hero__meta-label">Alt kategori</span>
                </div>
                <?php if (!empty($isLoggedIn)): ?>
                    <div class="category-hero__meta-item">
                        <span class="category-hero__meta-value material-icons" aria-hidden="true">verified</span>
                        <span class="category-hero__meta-label">Üyelik avantajlı fiyatlar</span>
                    </div>
                <?php else: ?>
                    <a class="category-hero__cta" href="/login.php">
                        <span>Hemen giriş yapın</span>
                        <span class="material-icons" aria-hidden="true">arrow_forward</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($children): ?>
    <section class="category-sub">
        <header class="category-sub__header">
            <div>
                <h2>Alt kategoriler</h2>
                <p>İlginizi çeken gruba dokunarak ürünleri keşfedin.</p>
            </div>
        </header>
        <div class="category-sub__grid" data-scroll>
            <?php foreach ($children as $child): ?>
                <?php
                    $childName = isset($child['name']) ? (string)$child['name'] : 'Kategori';
                    $childUrl = isset($child['url']) && $child['url'] !== '' ? (string)$child['url'] : Helpers::categoryUrl(isset($child['path']) ? $child['path'] : (isset($child['slug']) ? $child['slug'] : ''));
                    $childCount = isset($child['productCount']) ? (int)$child['productCount'] : null;
                ?>
                <a class="category-chip" href="<?= htmlspecialchars($childUrl) ?>">
                    <span class="category-chip__title"><?= htmlspecialchars($childName) ?></span>
                    <?php if ($childCount !== null): ?>
                        <span class="category-chip__count"><?= htmlspecialchars(number_format($childCount, 0, ',', '.')) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($products): ?>
    <section class="category-products">
        <header class="category-products__header">
            <div>
                <h2><?= htmlspecialchars($categoryName) ?> ürünleri</h2>
                <p>Filtrelenmiş sonuçlara göz atın ve hesabınıza en uygun ürünü seçin.</p>
            </div>
            <?php if ($productCount > 4): ?>
                <a class="category-products__filter" href="#products">
                    <span class="material-icons" aria-hidden="true">filter_alt</span>
                    Filtrele
                </a>
            <?php endif; ?>
        </header>
        <div class="category-products__grid" id="products">
            <?php foreach ($products as $product): ?>
                <?php
                    $inStock = !empty($product['in_stock']);
                    $stockLabel = isset($product['stock_label']) ? (string)$product['stock_label'] : '';
                    $stockText = $stockLabel !== '' ? $stockLabel : ($inStock ? 'Stokta' : 'Stokta yok');
                    $price = isset($product['price_formatted']) ? (string)$product['price_formatted'] : (isset($product['price']) ? Helpers::formatCurrency((float)$product['price']) : '0,00');
                    $slug = isset($product['slug']) ? (string)$product['slug'] : '';
                    $id = isset($product['id']) ? (int)$product['id'] : 0;
                    $detailSlug = $slug !== '' ? $slug : ($id > 0 ? 'product-' . $id : '');
                    $detailUrl = $detailSlug !== '' ? Helpers::productUrl($detailSlug) : '#';
                    $summary = isset($product['summary']) && $product['summary'] !== '' ? (string)$product['summary'] : (isset($product['description']) ? (string)$product['description'] : '');
                    $categoryNameLabel = isset($product['category_name']) ? (string)$product['category_name'] : '';
                    $categoryLink = isset($product['category_url']) && $product['category_url'] !== '' ? (string)$product['category_url'] : (isset($product['category_path']) ? Helpers::categoryUrl($product['category_path']) : $categoryUrl);
                    $tag = isset($product['tag']) ? trim((string)$product['tag']) : '';
                ?>
                <article class="category-card<?= $inStock ? '' : ' is-disabled' ?>">
                    <a class="category-card__media" href="<?= htmlspecialchars($detailUrl) ?>">
                        <img src="<?= htmlspecialchars($product['image'] ?? $categoryImage) ?>" alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>">
                        <?php if ($tag !== ''): ?>
                            <span class="category-card__tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="category-card__content">
                        <div class="category-card__heading">
                            <h3><a href="<?= htmlspecialchars($detailUrl) ?>"><?= htmlspecialchars($product['name'] ?? 'Product') ?></a></h3>
                            <?php if ($categoryNameLabel !== ''): ?>
                                <a class="category-card__category" href="<?= htmlspecialchars($categoryLink) ?>"><?= htmlspecialchars($categoryNameLabel) ?></a>
                            <?php endif; ?>
                        </div>
                        <?php if ($summary !== ''): ?>
                            <p class="category-card__summary"><?= htmlspecialchars(mb_substr($summary, 0, 140)) ?><?= mb_strlen($summary) > 140 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <div class="category-card__footer">
                            <div class="category-card__price">
                                <span class="category-card__price-value"><?= htmlspecialchars($price) ?></span>
                                <span class="category-card__stock<?= $inStock ? ' is-available' : ' is-unavailable' ?>">
                                    <span class="material-icons" aria-hidden="true"><?= $inStock ? 'check_circle' : 'cancel' ?></span>
                                    <?= htmlspecialchars($stockText) ?>
                                </span>
                            </div>
                            <?php if ($canShop && $inStock): ?>
                                <button
                                    type="button"
                                    class="category-card__cta"
                                    data-add-to-cart
                                    data-product-id="<?= (int)($product['id'] ?? 0) ?>"
                                    data-product-name="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                                >
                                    Sepete ekle
                                </button>
                            <?php elseif (!$canShop): ?>
                                <a class="category-card__cta category-card__cta--ghost" href="/login.php">Giriş yap</a>
                            <?php else: ?>
                                <button class="category-card__cta is-disabled" type="button" disabled>Stokta yok</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <section class="category-empty">
        <div class="category-empty__inner">
            <h3>Bu kategoride ürün bulunamadı</h3>
            <p>Yeni ürünler eklendiğinde haberdar olmak için hesabınıza giriş yapabilir veya diğer kategorilere göz atabilirsiniz.</p>
            <div class="category-empty__actions">
                <a class="btn btn-primary" href="/kategori/">Tüm kategoriler</a>
                <a class="btn btn-outline-primary" href="/support.php">Destek alın</a>
            </div>
        </div>
    </section>
<?php endif; ?>
