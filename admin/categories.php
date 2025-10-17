<?php
require __DIR__ . '/../bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

function detectFeaturedSupport(PDO $pdo)
{
    try {
        $columnCheck = $pdo->query("SHOW COLUMNS FROM categories LIKE 'is_featured'");
        return $columnCheck !== false && $columnCheck->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (Throwable $exception) {
        return false;
    }
}

function fetchCategories(PDO $pdo, bool $supportsFeatured)
{
    $categories = array();

    try {
        $query = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
        if ($query !== false) {
            $categories = $query->fetchAll(PDO::FETCH_ASSOC) ?: array();
        }
    } catch (Throwable $exception) {
        $categories = array();
    }

    foreach ($categories as &$category) {
        $category['id'] = isset($category['id']) ? (int)$category['id'] : 0;
        $category['parent_id'] = isset($category['parent_id']) ? (int)$category['parent_id'] : null;
        $category['name'] = isset($category['name']) ? (string)$category['name'] : '';
        $category['description'] = isset($category['description']) ? (string)$category['description'] : '';
        $category['icon'] = isset($category['icon']) ? (string)$category['icon'] : '';
        $category['image'] = isset($category['image']) ? (string)$category['image'] : '';
        $category['is_featured'] = $supportsFeatured && !empty($category['is_featured']) ? 1 : 0;
    }
    unset($category);

    return $categories;
}

function indexCategoriesById(array $categories)
{
    $indexed = array();
    foreach ($categories as $category) {
        $indexed[$category['id']] = $category;
    }

    return $indexed;
}

function buildCategoryTree(array $categories)
{
    $childrenMap = array();
    foreach ($categories as $category) {
        $parentId = $category['parent_id'] ?? 0;
        if (!isset($childrenMap[$parentId])) {
            $childrenMap[$parentId] = array();
        }
        $childrenMap[$parentId][] = $category;
    }

    return buildCategoryBranch($childrenMap, 0);
}

function buildCategoryBranch(array $childrenMap, int $parentId)
{
    $branch = array();

    if (!isset($childrenMap[$parentId])) {
        return $branch;
    }

    foreach ($childrenMap[$parentId] as $category) {
        $category['children'] = buildCategoryBranch($childrenMap, (int)$category['id']);
        $branch[] = $category;
    }

    return $branch;
}

function sanitizeIdList($value)
{
    if (!is_array($value)) {
        return array();
    }

    $ids = array();
    foreach ($value as $item) {
        $itemId = (int)$item;
        if ($itemId > 0) {
            $ids[] = $itemId;
        }
    }

    return $ids;
}

function safeRedirect($path)
{
    Helpers::redirect($path);
    exit;
}

function listIconOptions($directory)
{
    $icons = array();

    if (is_string($directory) && is_dir($directory)) {
        $files = glob(rtrim($directory, '/\\') . '/*.svg');
        if ($files) {
            foreach ($files as $file) {
                $icons[] = basename($file, '.svg');
            }
            sort($icons, SORT_NATURAL | SORT_FLAG_CASE);
        }
    }

    return $icons;
}

function renderCategoryOptions(array $tree, $selectedId, $currentId, $level = 0)
{
    foreach ($tree as $category) {
        $isSelected = $selectedId !== null && (int)$selectedId === (int)$category['id'];
        $isDisabled = $currentId !== null && (int)$currentId === (int)$category['id'];

        echo '<option value="' . (int)$category['id'] . '"';
        if ($isSelected) {
            echo ' selected';
        }
        if ($isDisabled) {
            echo ' disabled';
        }
        echo '>' . str_repeat('&nbsp;&nbsp;', $level) . htmlspecialchars($category['name']) . '</option>';

        if (!empty($category['children'])) {
            renderCategoryOptions($category['children'], $selectedId, $currentId, $level + 1);
        }
    }
}

function renderCategoryRows(array $tree, bool $supportsFeatured, int $level = 0)
{
    foreach ($tree as $category) {
        echo '<tr>';
        echo '<td><input type="checkbox" class="form-check-input" name="category_ids[]" value="' . (int)$category['id'] . '"></td>';
        echo '<td>';
        if (!empty($category['image'])) {
            echo '<img src="' . htmlspecialchars($category['image']) . '" alt="" class="img-fluid rounded" style="width: 40px; height: 40px; object-fit: cover;">';
        } elseif (!empty($category['icon'])) {
            echo '<span class="fs-3"><i class="' . htmlspecialchars($category['icon']) . '"></i></span>';
        }
        echo '</td>';
        echo '<td>' . str_repeat('&mdash; ', $level) . htmlspecialchars($category['name']) . '</td>';
        echo '<td>' . htmlspecialchars(Helpers::truncate($category['description'], 50)) . '</td>';
        if ($supportsFeatured) {
            echo '<td class="text-center">';
            if (!empty($category['is_featured'])) {
                echo '<span class="badge bg-success-soft">Evet</span>';
            } else {
                echo '<span class="badge bg-light text-dark">Hayır</span>';
            }
            echo '</td>';
        }
        echo '<td class="text-end">';
        echo '<a href="?edit=' . (int)$category['id'] . '" class="btn btn-sm btn-outline-primary">Düzenle</a> ';
        echo '<button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal" data-category-id="' . (int)$category['id'] . '">Sil</button>';
        echo '</td>';
        echo '</tr>';

        if (!empty($category['children'])) {
            renderCategoryRows($category['children'], $supportsFeatured, $level + 1);
        }
    }
}

Auth::requireRoles(array('super_admin', 'admin', 'content'));

$pageTitle = 'Kategoriler';
$feedback = array(
    'errors' => array(),
    'success' => '',
);

$pdo = Database::connection();
$supportsFeaturedCategories = detectFeaturedSupport($pdo);

$categories = fetchCategories($pdo, $supportsFeaturedCategories);
$categoriesById = indexCategoriesById($categories);
$categoryTree = buildCategoryTree($categories);

$iconDirectory = dirname(__DIR__) . '/theme/assets/images/icon';
$availableIconOptions = listIconOptions($iconDirectory);

$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0 && isset($categoriesById[$editId])) {
        $editCategory = $categoriesById[$editId];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    $csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';

    if (!Helpers::verifyCsrf($csrfToken)) {
        $feedback['errors'][] = 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.';
    } else {
        switch ($action) {
            case 'create_category':
            case 'update_category':
                $categoryId = $action === 'update_category' ? (int)($_POST['category_id'] ?? 0) : 0;
                $name = trim((string)($_POST['name'] ?? ''));
                $parentInput = $_POST['parent_id'] ?? '';
                $parentId = $parentInput !== '' ? (int)$parentInput : null;
                $icon = trim((string)($_POST['icon'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $isFeatured = $supportsFeaturedCategories && isset($_POST['is_featured']) && (int)$_POST['is_featured'] === 1;

                if ($name === '') {
                    $feedback['errors'][] = 'Kategori adı boş olamaz.';
                }

                if ($parentId !== null && $parentId > 0 && !isset($categoriesById[$parentId])) {
                    $feedback['errors'][] = 'Geçersiz üst kategori seçimi.';
                }

                if ($categoryId > 0 && !isset($categoriesById[$categoryId])) {
                    $feedback['errors'][] = 'Düzenlenecek kategori bulunamadı.';
                }

                $imagePath = $categoryId > 0 && isset($categoriesById[$categoryId]['image'])
                    ? $categoriesById[$categoryId]['image']
                    : '';

                if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $upload = Helpers::uploadImage($_FILES['image'], 'categories');
                    if (!empty($upload['success'])) {
                        $imagePath = $upload['path'];
                    } elseif (!empty($upload['errors'])) {
                        $feedback['errors'] = array_merge($feedback['errors'], $upload['errors']);
                    }
                }

                if (empty($feedback['errors'])) {
                    try {
                        if ($categoryId > 0) {
                            $updateSql = 'UPDATE categories SET name = :name, parent_id = :parent_id, icon = :icon, description = :description, image = :image';
                            $updateParams = array(
                                'id' => $categoryId,
                                'name' => $name,
                                'parent_id' => $parentId > 0 ? $parentId : null,
                                'icon' => $icon,
                                'description' => $description,
                                'image' => $imagePath !== '' ? $imagePath : null,
                            );

                            if ($supportsFeaturedCategories) {
                                $updateSql .= ', is_featured = :is_featured';
                                $updateParams['is_featured'] = $isFeatured ? 1 : 0;
                            }

                            $updateSql .= ', updated_at = NOW() WHERE id = :id';
                            $statement = $pdo->prepare($updateSql);
                            $statement->execute($updateParams);
                            safeRedirect('/admin/categories.php?success=1');
                        } else {
                            $insertColumns = array('name', 'parent_id', 'icon', 'description', 'image');
                            $insertValues = array(':name', ':parent_id', ':icon', ':description', ':image');
                            $insertParams = array(
                                'name' => $name,
                                'parent_id' => $parentId > 0 ? $parentId : null,
                                'icon' => $icon,
                                'description' => $description,
                                'image' => $imagePath !== '' ? $imagePath : null,
                            );

                            if ($supportsFeaturedCategories) {
                                $insertColumns[] = 'is_featured';
                                $insertValues[] = ':is_featured';
                                $insertParams['is_featured'] = $isFeatured ? 1 : 0;
                            }

                            $insertColumns[] = 'created_at';
                            $insertValues[] = 'NOW()';

                            $insertSql = sprintf(
                                'INSERT INTO categories (%s) VALUES (%s)',
                                implode(', ', $insertColumns),
                                implode(', ', $insertValues)
                            );

                            $statement = $pdo->prepare($insertSql);
                            $statement->execute($insertParams);
                            safeRedirect('/admin/categories.php?success=1');
                        }
                    } catch (PDOException $exception) {
                        $feedback['errors'][] = 'Kategori kaydedilirken bir hata oluştu.';
                    }
                }
                break;

            case 'delete_category':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                if ($categoryId > 0 && isset($categoriesById[$categoryId])) {
                    try {
                        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(array('id' => $categoryId));
                        $pdo->prepare('UPDATE categories SET parent_id = NULL WHERE parent_id = :parent_id')->execute(array('parent_id' => $categoryId));
                        safeRedirect('/admin/categories.php?deleted=1');
                    } catch (PDOException $exception) {
                        $feedback['errors'][] = 'Kategori silinirken bir hata oluştu.';
                    }
                } else {
                    $feedback['errors'][] = 'Silinecek kategori bulunamadı.';
                }
                break;

            case 'bulk_action':
                $bulkAction = isset($_POST['bulk_action_name']) ? (string)$_POST['bulk_action_name'] : '';
                $bulkIds = sanitizeIdList($_POST['category_ids'] ?? array());

                if (empty($bulkIds)) {
                    $feedback['errors'][] = 'İşlem yapılacak kategori seçilmedi.';
                    break;
                }

                $placeholders = implode(',', array_fill(0, count($bulkIds), '?'));

                try {
                    switch ($bulkAction) {
                        case 'delete':
                            $pdo->prepare("DELETE FROM categories WHERE id IN ($placeholders)")->execute($bulkIds);
                            $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id IN ($placeholders)")->execute($bulkIds);
                            safeRedirect('/admin/categories.php?bulk_success=1');
                            break;

                        case 'feature':
                            if (!$supportsFeaturedCategories) {
                                $feedback['errors'][] = 'Öne çıkarma özelliği kullanılabilir değil.';
                                break;
                            }
                            $pdo->prepare("UPDATE categories SET is_featured = 1 WHERE id IN ($placeholders)")->execute($bulkIds);
                            safeRedirect('/admin/categories.php?bulk_success=1');
                            break;

                        case 'unfeature':
                            if (!$supportsFeaturedCategories) {
                                $feedback['errors'][] = 'Öne çıkarma özelliği kullanılabilir değil.';
                                break;
                            }
                            $pdo->prepare("UPDATE categories SET is_featured = 0 WHERE id IN ($placeholders)")->execute($bulkIds);
                            safeRedirect('/admin/categories.php?bulk_success=1');
                            break;

                        default:
                            $feedback['errors'][] = 'Geçerli bir toplu işlem seçin.';
                            break;
                    }
                } catch (PDOException $exception) {
                    $feedback['errors'][] = 'Toplu işlem uygulanırken bir hata oluştu.';
                }
                break;
        }
    }
}

if (isset($_GET['success'])) {
    $feedback['success'] = 'İşlem başarıyla tamamlandı.';
}
if (isset($_GET['deleted'])) {
    $feedback['success'] = 'Kategori silindi.';
}
if (isset($_GET['bulk_success'])) {
    $feedback['success'] = 'Toplu işlem başarıyla uygulandı.';
}

require __DIR__ . '/templates/header.php';

?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?= $editCategory ? 'Kategoriyi Düzenle' : 'Yeni Kategori Oluştur' ?></h5>
            </div>
            <div class="card-body">
                <form action="/admin/categories.php<?= $editCategory ? '?edit=' . (int)$editCategory['id'] : '' ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $editCategory ? 'update_category' : 'create_category' ?>">
                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?= (int)$editCategory['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Kategori Adı</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Üst Kategori <span class="text-muted">(isteğe bağlı)</span></label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Seçim yok</option>
                            <?php
                            $selectedParent = $editCategory['parent_id'] ?? null;
                            $currentId = $editCategory['id'] ?? null;
                            renderCategoryOptions($categoryTree, $selectedParent, $currentId);
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="icon" class="form-label">Simge <span class="text-muted">(isteğe bağlı, ör: bi bi-tag)</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control icon-picker-input" id="icon" name="icon" value="<?= htmlspecialchars($editCategory['icon'] ?? '') ?>">
                            <button class="btn btn-outline-secondary icon-picker-button" type="button" data-bs-toggle="modal" data-bs-target="#iconPickerModal" data-icon-input="#icon"></button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Görsel <span class="text-muted">(isteğe bağlı)</span></label>
                        <input type="file" class="form-control" id="image" name="image">
                        <?php if ($editCategory && !empty($editCategory['image'])): ?>
                            <div class="mt-2">
                                <img src="<?= htmlspecialchars($editCategory['image']) ?>" alt="Mevcut görsel" style="max-width: 100px; height: auto;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama <span class="text-muted">(isteğe bağlı)</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
                    </div>

                    <?php if ($supportsFeaturedCategories): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="is_featured" name="is_featured" <?= $editCategory && !empty($editCategory['is_featured']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">
                                Öne Çıkan Kategori
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end">
                        <?php if ($editCategory): ?>
                            <a href="/admin/categories.php" class="btn btn-link me-2">İptal</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Güncelle' : 'Oluştur' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Tüm Kategoriler</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($feedback['success'])): ?>
                    <div class="alert alert-success"><?= $feedback['success'] ?></div>
                <?php endif; ?>
                <?php if (!empty($feedback['errors'])): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($feedback['errors'] as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="/admin/categories.php" method="post" id="bulkActionForm">
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <select name="bulk_action_name" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Toplu İşlemler</option>
                                <option value="delete">Sil</option>
                                <?php if ($supportsFeaturedCategories): ?>
                                    <option value="feature">Öne Çıkar</option>
                                    <option value="unfeature">Öne Çıkarandan Kaldır</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary ms-2">Uygula</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 1rem;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                                    <th style="width: 4rem;">Görsel</th>
                                    <th>Ad</th>
                                    <th>Açıklama</th>
                                    <?php if ($supportsFeaturedCategories): ?>
                                        <th class="text-center">Öne Çıkan</th>
                                    <?php endif; ?>
                                    <th class="text-end">Eylemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categoryTree)): ?>
                                    <?php renderCategoryRows($categoryTree, $supportsFeaturedCategories); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= $supportsFeaturedCategories ? 6 : 5 ?>" class="text-center">Henüz kategori eklenmemiş.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Icon Picker Modal -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iconPickerModalLabel">Simge Seç</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="icon-picker-search mb-3">
                    <input type="text" class="form-control" placeholder="Simge ara...">
                </div>
                <div class="icon-picker-list">
                    <div class="text-center p-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Kategoriyi Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bu kategoriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
            </div>
            <div class="modal-footer">
                <form action="/admin/categories.php" method="post">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$script = <<<JS
"use strict";

document.addEventListener('DOMContentLoaded', function() {
    var deleteCategoryModal = document.getElementById('deleteCategoryModal');
    if (deleteCategoryModal) {
        deleteCategoryModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }
            var categoryId = button.getAttribute('data-category-id');
            var modalCategoryIdInput = deleteCategoryModal.querySelector('#deleteCategoryId');
            if (modalCategoryIdInput) {
                modalCategoryIdInput.value = categoryId || '';
            }
        });
    }

    var checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('#bulkActionForm tbody input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = checkAll.checked;
            });
        });
    }
});
JS;

require __DIR__ . '/templates/footer.php';
?>
