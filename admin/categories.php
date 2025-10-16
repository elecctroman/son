<?php
require __DIR__ . '/../bootstrap.php';

App\Auth::requireRoles(array('super_admin', 'admin', 'content'));

$pageTitle = 'Kategoriler';

require __DIR__ . '/templates/header.php';

$feedback = array(
    'errors' => array(),
    'success' => '',
);

$pdo = App\Database::connection();
$supportsFeaturedCategories = false;
try {
    $columnCheck = $pdo->query("SHOW COLUMNS FROM categories LIKE 'is_featured'");
    if ($columnCheck !== false && $columnCheck->fetch(PDO::FETCH_ASSOC)) {
        $supportsFeaturedCategories = true;
    }
} catch (PDOException $e) {
    $supportsFeaturedCategories = false;
}

$stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
$allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allCategories as &$categoryRow) {
    if ($supportsFeaturedCategories) {
        $categoryRow['is_featured'] = isset($categoryRow['is_featured']) && (int)$categoryRow['is_featured'] === 1 ? 1 : 0;
    } else {
        $categoryRow['is_featured'] = 0;
    }
}
unset($categoryRow);

$categoriesById = array();
foreach ($allCategories as $c) {
    $categoriesById[$c['id']] = $c;
}

$categoryTree = array();
$categoryChildren = array();

foreach ($allCategories as $c) {
    $parentId = isset($c['parent_id']) ? (int)$c['parent_id'] : 0;
    if (!isset($categoryChildren[$parentId])) {
        $categoryChildren[$parentId] = array();
    }
    $categoryChildren[$parentId][] = $c;
}

if (isset($categoryChildren[0])) {
    $buildTree = function (array $items) use (&$buildTree, &$categoryChildren) {
        $result = array();
        foreach ($items as $item) {
            $children = isset($categoryChildren[$item['id']]) ? $categoryChildren[$item['id']] : array();
            if ($children) {
                $item['children'] = $buildTree($children);
            }
            $result[] = $item;
        }
        return $result;
    };
    $categoryTree = $buildTree($categoryChildren[0]);
}

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
        $feedback['errors'][] = 'Oturum doÄŸrulamasÄ± baÅŸarÄ±sÄ±z. LÃ¼tfen tekrar deneyin.';
    } else {
        switch ($action) {
            case 'create_category':
            case 'update_category':
                $categoryId = $action === 'update_category' ? (isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0) : 0;
                $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
                $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $icon = isset($_POST['icon']) ? trim((string)$_POST['icon']) : '';
                $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
                $isFeatured = $supportsFeaturedCategories && isset($_POST['is_featured']) && $_POST['is_featured'] == 1;

                if ($name === '') {
                    $feedback['errors'][] = 'Kategori adı boş olamaz.';
                }

                if ($parentId !== null && $parentId > 0 && !isset($categoriesById[$parentId])) {
                    $feedback['errors'][] = 'Geçersiz üst kategori seçimi.';
                }

                if ($categoryId > 0 && !isset($categoriesById[$categoryId])) {
                    $feedback['errors'][] = 'Düzenlenecek kategori bulunamadı.';
                }

                $imagePath = $categoryId > 0 ? $categoriesById[$categoryId]['image'] : null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = App\Helpers::uploadImage($_FILES['image'], 'categories');
                    if ($upload['success']) {
                        $imagePath = $upload['path'];
                    } else {
                        $feedback['errors'] = array_merge($feedback['errors'], $upload['errors']);
                    }
                }

                if (empty($feedback['errors'])) {
                    if ($categoryId > 0) {
                        $updateSql = 'UPDATE categories SET name = :name, parent_id = :parent_id, icon = :icon, description = :description, image = :image';
                        $updateParams = array(
                            'id' => $categoryId,
                            'name' => $name,
                            'parent_id' => $parentId > 0 ? $parentId : null,
                            'icon' => $icon,
                            'description' => $description,
                            'image' => $imagePath,
                        );

                        if ($supportsFeaturedCategories) {
                            $updateSql .= ', is_featured = :is_featured';
                            $updateParams['is_featured'] = $isFeatured ? 1 : 0;
                        }

                        $updateSql .= ', updated_at = NOW() WHERE id = :id';
                        $stmt = $pdo->prepare($updateSql);
                        $stmt->execute($updateParams);
                        $feedback['success'] = 'Kategori başarıyla güncellendi.';
                    } else {
                        $insertColumns = array('name', 'parent_id', 'icon', 'description', 'image');
                        $insertValues = array(':name', ':parent_id', ':icon', ':description', ':image');
                        $insertParams = array(
                            'name' => $name,
                            'parent_id' => $parentId > 0 ? $parentId : null,
                            'icon' => $icon,
                            'description' => $description,
                            'image' => $imagePath,
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

                        $stmt = $pdo->prepare($insertSql);
                        $stmt->execute($insertParams);
                        $feedback['success'] = 'Kategori başarıyla oluşturuldu.';
                    }
                    App\Helpers::redirect('/admin/categories.php?success=1');
                }
                break;

            case 'delete_category':
                $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
                if ($categoryId > 0 && isset($categoriesById[$categoryId])) {
                    $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(array('id' => $categoryId));
                    $pdo->prepare('UPDATE categories SET parent_id = NULL WHERE parent_id = :parent_id')->execute(array('parent_id' => $categoryId));
                    App\Helpers::redirect('/admin/categories.php?deleted=1');
                }
                break;

            case 'bulk_action':
                $bulkAction = isset($_POST['bulk_action_name']) ? $_POST['bulk_action_name'] : '';
                $bulkIds = isset($_POST['category_ids']) && is_array($_POST['category_ids']) ? $_POST['category_ids'] : array();
                $placeholders = implode(',', array_fill(0, count($bulkIds), '?'));

                $bulkActionHandled = false;
                if ($bulkIds && $placeholders) {
                    switch ($bulkAction) {
                        case 'delete':
                            $stmt = $pdo->prepare("DELETE FROM categories WHERE id IN ($placeholders)");
                            $stmt->execute($bulkIds);
                            $stmt = $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id IN ($placeholders)");
                            $stmt->execute($bulkIds);
                            $feedback['success'] = 'Seçili kategoriler silindi.';
                            $bulkActionHandled = true;
                            break;
                        case 'feature':
                            if (!$supportsFeaturedCategories) {
                                $feedback['errors'][] = 'Öne çıkarma özelliği kullanılabilir değil.';
                                break;
                            }
                            $stmt = $pdo->prepare("UPDATE categories SET is_featured = 1 WHERE id IN ($placeholders)");
                            $stmt->execute($bulkIds);
                            $feedback['success'] = 'Seçili kategoriler öne çıkarıldı.';
                            $bulkActionHandled = true;
                            break;
                        case 'unfeature':
                            if (!$supportsFeaturedCategories) {
                                $feedback['errors'][] = 'Öne çıkarma özelliği kullanılabilir değil.';
                                break;
                            }
                            $stmt = $pdo->prepare("UPDATE categories SET is_featured = 0 WHERE id IN ($placeholders)");
                            $stmt->execute($bulkIds);
                            $feedback['success'] = 'Seçili kategoriler öne çıkarılmaktan kaldırıldı.';
                            $bulkActionHandled = true;
                            break;
                    }
                }

                if ($bulkActionHandled) {
                    App\Helpers::redirect('/admin/categories.php?bulk_success=1');
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
                                $renderCategoryRow = function ($category, $level = 0) use (&$renderCategoryRow) {
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
