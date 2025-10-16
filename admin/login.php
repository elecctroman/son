<?php
require __DIR__ . '/../bootstrap.php';

use App\Auth;
use App\Helpers;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['user']) && Auth::isAdminRole($_SESSION['user']['role'])) {
    Helpers::redirect('/admin/dashboard.php');
}

$errors = array();
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Lütfen e-posta/kullanıcı adı ve şifre alanlarını boş bırakmayın.';
    } else {
        $user = Auth::attempt($identifier, $password);
        if ($user && Auth::isAdminRole($user['role'])) {
            $_SESSION['user'] = $user;

            Helpers::redirect('/admin/dashboard.php');
        } else {
            $errors[] = 'Yetkili yönetici bilgileri doğrulanamadı.';
        }
    }
}

include __DIR__ . '/../templates/auth-header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="brand"><?= Helpers::sanitize(Helpers::siteName()) ?></div>
            <p class="text-muted mt-2">Yönetici kontrol paneline erişmek için giriş yapın.</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= Helpers::sanitize($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">E-posta Adresi veya Kullanıcı Adı</label>
                <input type="text" class="form-control" id="email" name="email" required value="<?= Helpers::sanitize($identifier) ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Yönetici Girişi Yap</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/auth-footer.php';
