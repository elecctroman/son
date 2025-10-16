<?php

use App\Helpers;
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$siteName = Helpers::siteName();
$metaDescription = Helpers::seoDescription();
$metaKeywords = Helpers::seoKeywords();
$pageTitle = isset($pageTitle) ? $pageTitle : $siteName;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::sanitize($pageTitle) ?><?= $pageTitle !== $siteName ? ' | ' . Helpers::sanitize($siteName) : '' ?></title>
    <meta name="description" content="<?= Helpers::sanitize($metaDescription) ?>">
    <meta name="keywords" content="<?= Helpers::sanitize($metaKeywords) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Montserrat:wght@500;600;700&display=swap&subset=latin-ext" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<main class="public-main-content py-4">
    <div class="container">
SESSION['user'])): ?>
                        <li class="nav-item nav-item-cta">
                            <a class="btn btn-outline-primary btn-sm nav-btn" href="/dashboard.php"><?= Helpers::sanitize('Hesabım') ?></a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item nav-item-cta">
                            <a class="btn btn-primary btn-sm nav-btn" href="/login.php"><?= Helpers::sanitize('Giriş Yap') ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<main class="public-main-content py-4">
    <div class="container">



