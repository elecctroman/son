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

$renderText = static function ($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '<p class="text-muted">Bilgi bulunmuyor.</p>';
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

    return $html !== '' ? $html : '<p class="text-muted">Bilgi bulunmuyor.</p>';
};

if (!$product) : ?>
    <section class="product-detail product-detail--empty">
        <div class="product-detail__empty">
            <span class="material-icons" aria-hidden="true">inventory_2</span>
            <h1>Urun bulunamadi</h1>
            <p>Aradiginiz urunu bulamadik. Kataloga geri donerek diger secenekleri inceleyebilirsiniz.</p>
            <a class="btn btn-primary" href="/kategori/">Kataloğa Dön</a>
        </div>
    </section>
    <?php return; ?>
<?php endif;

$image = isset($product['image']) && $product['image'] !== '' ? $product['image'] : '/theme/assets/images/placeholder.png';
$priceFormatted = isset($product['price_formatted']) ? $product['price_formatted'] : Helpers::formatCurrency(0, 'TRY');
$stockLabel = isset($product['stock_label']) ? $product['stock_label'] : ($product['in_stock'] ? 'In stock' : 'Out of stock');
$viewsCount = isset($product['views_count']) ? (int)$product['views_count'] : 0;
$viewsMessage = $viewsCount === 1 ? '1 kisi bu urunu inceledi.' : sprintf('%d kisi bu urunu inceledi.', $viewsCount);
$productId = (int)$product['id'];
$productName = (string)$product['name'];
$slug = isset($product['slug']) ? (string)$product['slug'] : '';
$descriptionHtml = $renderText(isset($product['description']) ? $product['description'] : '');
$shortDescriptionHtml = $renderText(isset($product['short_description']) && $product['short_description'] !== '' ? $product['short_description'] : (isset($product['description']) ? $product['description'] : ''));
$commentCount = isset($product['comment_count']) ? (int)$product['comment_count'] : count($comments);

?>

<div class="container mt-5 mb-5 product-detail-page">
    <?php if ($breadcrumbs): ?>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                    <?php
                        $label = isset($breadcrumb['label']) ? $breadcrumb['label'] : '';
                        $url = isset($breadcrumb['url']) ? $breadcrumb['url'] : null;
                        $isLast = $index === count($breadcrumbs) - 1;
                    ?>
                    <li class="breadcrumb-item<?= $isLast ? ' active' : '' ?>"<?= $isLast ? ' aria-current="page"' : '' ?>>
                        <?php if ($url && !$isLast): ?>
                            <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($label) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
    <?php endif; ?>

    <div class="row g-5 align-items-start">
        <div class="col-md-6">
            <div class="bg-light rounded shadow-sm overflow-hidden">
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($productName) ?>" class="img-fluid w-100 object-fit-cover">
            </div>
            <?php if ($viewsCount > 0): ?>
                <p class="text-muted small mt-3 mb-0">
                    <?= htmlspecialchars($viewsMessage) ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <?php if (!empty($product['category']['name'])): ?>
                <span class="badge rounded-pill text-bg-light text-uppercase fw-semibold mb-3"><?= htmlspecialchars($product['category']['name']) ?></span>
            <?php endif; ?>
            <h1 class="h2 fw-semibold mb-3"><?= htmlspecialchars($productName) ?></h1>
            <?php if (!empty($product['sku'])): ?>
                <p class="text-muted small text-uppercase fw-semibold mb-3">Stok Kodu: <?= htmlspecialchars($product['sku']) ?></p>
            <?php endif; ?>

            <div class="mb-4 text-secondary lead">
                <?= $shortDescriptionHtml ?>
            </div>

            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="fs-2 fw-bold text-primary"><?= htmlspecialchars($priceFormatted) ?></span>
                <span class="badge <?= $product['in_stock'] ? 'text-bg-success' : 'text-bg-danger' ?> px-3 py-2">
                    <?= htmlspecialchars($stockLabel) ?>
                </span>
            </div>

            <form action="/cart.php" method="post" class="card shadow-sm border-0">
                <div class="card-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-4 col-md-3">
                            <label for="productQuantity" class="form-label text-muted small text-uppercase">Adet</label>
                            <input type="number" id="productQuantity" name="quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-sm-8 col-md-9 d-grid d-sm-flex gap-3">
                            <button type="submit" class="btn btn-primary btn-lg flex-fill" <?= $product['in_stock'] ? '' : 'disabled' ?>>Sepete Ekle</button>
                            <a class="btn btn-outline-secondary flex-fill" href="/kategori/">Diğer Ürünler</a>
                        </div>
                    </div>
                </div>
            </form>

            <ul class="list-unstyled d-flex flex-wrap gap-3 text-muted small mt-3 mb-0">
                <li><span class="material-icons align-middle me-1">local_shipping</span> Hızlı dijital teslimat</li>
                <li><span class="material-icons align-middle me-1">verified_user</span> Güvenli ödeme altyapısı</li>
            </ul>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <h3 class="h4 mb-3">Ürün Açıklaması</h3>
            <div class="border-top pt-4 product-description">
                <?= $descriptionHtml ?>
            </div>
        </div>
    </div>

    <div class="row mt-5" id="product-comments">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h4 mb-0">Yorumlar</h3>
                <span class="text-muted small"><?= $commentCount ?> yorum</span>
            </div>

            <?php if ($commentSuccess !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($commentSuccess) ?></div>
            <?php endif; ?>

            <?php if ($commentErrors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($commentErrors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($comments): ?>
                <div class="list-group mb-4 shadow-sm">
                    <?php foreach ($comments as $comment): ?>
                        <article class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong><?= htmlspecialchars($comment['author']) ?></strong>
                                <time class="text-muted small" datetime="<?= htmlspecialchars($comment['created_at']) ?>">
                                    <?= htmlspecialchars($comment['created_at_human']) ?>
                                </time>
                            </div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Bu ürün için henüz yorum yapılmadı. İlk yorumu siz yazın!</p>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="product_comment">
                            <input type="hidden" name="csrf_token" value="<?= Helpers::sanitize(Helpers::csrfToken()) ?>">
                            <div class="mb-3">
                                <label for="commentBody" class="form-label">Yorumunuz</label>
                                <textarea id="commentBody" name="comment" rows="4" class="form-control" required placeholder="Deneyiminizi paylaşın..."><?= htmlspecialchars($commentOld) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Yorumu Gönder</button>
                        </form>
                    <?php else: ?>
                        <p class="mb-0">
                            Yorum yapabilmek için lütfen <a href="/login.php">giriş yapın</a> veya <a href="/register.php">hesap oluşturun</a>.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

