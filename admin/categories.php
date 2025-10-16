<?php
require __DIR__ . '/../bootstrap.php';

App\Auth::requireRoles(array('super_admin', 'admin', 'content'));

$pageTitle = 'Kategoriler';

$feedback = array(
    'errors' => array(),
    'success' => '',
);


}

/**
 * @return array{categories: array<int, array<string, mixed>>, indexed: array<int, array<string, mixed>>, tree: array<int, array<string, mixed>>}
 */
function loadCategories(PDO $pdo, bool $supportsFeatured)
{
    $categories = array();

    try {
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
        if ($stmt !== false) {
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
        }
    } catch (Throwable $exception) {
        $categories = array();
    }

    $indexed = array();
    foreach ($categories as &$category) {
        $category['id'] = isset($category['id']) ? (int)$category['id'] : 0;
        $category['parent_id'] = isset($category['parent_id']) ? (int)$category['parent_id'] : null;
        $category['name'] = isset($category['name']) ? (string)$category['name'] : '';
        $category['description'] = isset($category['description']) ? (string)$category['description'] : '';
        $category['icon'] = isset($category['icon']) ? (string)$category['icon'] : '';
        $category['image'] = isset($category['image']) ? (string)$category['image'] : '';
        $category['is_featured'] = $supportsFeatured && !empty($category['is_featured']) ? 1 : 0;

        $indexed[$category['id']] = $category;
    }
    unset($category);

    $children = array();
    foreach ($indexed as $category) {
        $parent = $category['parent_id'] ?: 0;
        if (!isset($children[$parent])) {
            $children[$parent] = array();
        }
        $children[$parent][] = $category;
    }

    $buildTree = function (array $items) use (&$buildTree, $children) {
        $result = array();
        foreach ($items as $item) {
            $itemId = $item['id'];
            if (isset($children[$itemId])) {
                $item['children'] = $buildTree($children[$itemId]);
            } else {
                $item['children'] = array();
            }
            $result[] = $item;
        }
        return $result;
    };

    $tree = isset($children[0]) ? $buildTree($children[0]) : array();

    return array(
        'categories' => $categories,
        'indexed' => $indexed,
        'tree' => $tree,
    );
}

/**
 * @param mixed $value
 * @return array<int>
 */
function sanitizeIdList($value)
{
    if (!is_array($value)) {
        return array();
    }

    $ids = array();
    foreach ($value as $item) {
        $ids[] = (int)$item;
    }

    return array_values(array_filter($ids, static function ($id) {
        return $id > 0;
    }));
}

function safeRedirect($path)
{
    App\Helpers::redirect($path);
    exit;
}

$pdo = App\Database::connection();
$supportsFeaturedCategories = detectFeaturedSupport($pdo);

$categoryData = loadCategories($pdo, $supportsFeaturedCategories);
$allCategories = $categoryData['categories'];
$categoriesById = $categoryData['indexed'];
$categoryTree = $categoryData['tree'];

$iconDirectory = dirname(__DIR__) . '/theme/assets/images/icon';
$availableIconOptions = array();
if (is_dir($iconDirectory)) {
    $iconFiles = glob($iconDirectory . '/*.svg');
    if ($iconFiles) {
        foreach ($iconFiles as $iconFile) {
            $availableIconOptions[] = basename($iconFile, '.svg');
        }
        sort($availableIconOptions, SORT_NATURAL | SORT_FLAG_CASE);
    }
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if (isset($categoriesById[$editId])) {
        $editCategory = $categoriesById[$editId];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!App\Helpers::verifyCsrf($csrfToken)) {
        $feedback['errors'][] = 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.';
    } else {
        switch ($action) {
            case 'create_category':
            case 'update_category':


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
                    : null;

                if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $upload = App\Helpers::uploadImage($_FILES['image'], 'categories');
                    if (!empty($upload['success'])) {
                        $imagePath = $upload['path'];
                    } elseif (!empty($upload['errors'])) {
                        $feedback['errors'] = array_merge($feedback['errors'], $upload['errors']);
                    }
                }

                if (empty($feedback['errors'])) {

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
                $bulkAction = $_POST['bulk_action_name'] ?? '';
                $bulkIds = sanitizeIdList($_POST['category_ids'] ?? array());

                if (empty($bulkIds)) {
                    $feedback['errors'][] = 'İşlem yapılacak kategori seçilmedi.';
                    break;
                }

                $placeholders = implode(',', array_fill(0, count($bulkIds), '?'));


                            break;
                        case 'feature':
                            if (!$supportsFeaturedCategories) {
                                $feedback['errors'][] = 'Öne çıkarma özelliği kullanılabilir değil.';
                                break;
                            }

                            break;
                        case 'unfeature':
                            if (!$supportsFeaturedCategories) {
                                $feedback['errors'][] = 'Öne çıkarma özelliği kullanılabilir değil.';
                                break;
                            }

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
                    <input type="hidden" name="csrf_token" value="<?= App\Helpers::csrfToken() ?>">
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
                            $displayCategories = function ($categories, $level = 0) use (&$displayCategories, $editCategory) {
                                foreach ($categories as $category) {
                                    $isSelected = $editCategory && isset($editCategory['parent_id']) && $editCategory['parent_id'] == $category['id'];
                                    $isDisabled = $editCategory && $editCategory['id'] == $category['id'];
                                    if ($isDisabled) continue;
                                    echo '<option value="' . $category['id'] . '"' . ($isSelected ? ' selected' : '') . '>' . str_repeat('&nbsp;&nbsp;', $level) . htmlspecialchars($category['name']) . '</option>';
                                    if (!empty($category['children'])) {
                                        $displayCategories($category['children'], $level + 1);
                                    }
                                }
                            };
                            $displayCategories($categoryTree);
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
                <?php if (isset($feedback['success']) && $feedback['success'] !== ''): ?>
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
                    <input type="hidden" name="csrf_token" value="<?= App\Helpers::csrfToken() ?>">
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
                                <?php
                                $renderCategoryRow = function ($category, $level = 0) use (&$renderCategoryRow, $supportsFeaturedCategories) {
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" name="category_ids[]" value="<?= $category['id'] ?>"></td>
                                        <td>
                                            <?php if (!empty($category['image'])): ?>
                                                <img src="<?= htmlspecialchars($category['image']) ?>" alt="" class="img-fluid rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php elseif (!empty($category['icon'])): ?>
                                                <span class="fs-3"><i class="<?= htmlspecialchars($category['icon']) ?>"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= str_repeat('&mdash; ', $level) . htmlspecialchars($category['name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars(App\Helpers::truncate($category['description'], 50)) ?></td>
                                        <?php if ($supportsFeaturedCategories): ?>
                                            <td class="text-center">
                                                <?php if (!empty($category['is_featured'])): ?>
                                                    <span class="badge bg-success-soft">Evet</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">Hayır</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="text-end">
                                            <a href="?edit=<?= $category['id'] ?>" class="btn btn-sm btn-outline-primary">Düzenle</a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal" data-category-id="<?= $category['id'] ?>">Sil</button>
                                        </td>
                                    </tr>
                                    <?php
                                    if (!empty($category['children'])) {
                                        foreach ($category['children'] as $child) {
                                            $renderCategoryRow($child, $level + 1);
                                        }
                                    }
                                };

                                if ($categoryTree) {
                                    foreach ($categoryTree as $category) {
                                        $renderCategoryRow($category);
                                    }
                                } else {
                                    $emptyColspan = $supportsFeaturedCategories ? 6 : 5;
                                    echo '<tr><td colspan="' . $emptyColspan . '" class="text-center">Henüz kategori eklenmemiş.</td></tr>';
                                }
                                ?>
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
                    <!-- Icons will be loaded here by JS -->
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
                    <input type="hidden" name="csrf_token" value="<?= App\Helpers::csrfToken() ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    const deleteCategoryModal = document.getElementById('deleteCategoryModal');
    deleteCategoryModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const categoryId = button.getAttribute('data-category-id');
        const modalCategoryIdInput = deleteCategoryModal.querySelector('#deleteCategoryId');
        modalCategoryIdInput.value = categoryId;
    });

    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#bulkActionForm tbody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
JS;

// Assume icon-picker.js is already included in the footer
require __DIR__ . '/templates/footer.php';
?>
