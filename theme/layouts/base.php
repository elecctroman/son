<?php
use App\Helpers;

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

$canonicalUrl = isset($GLOBALS['pageCanonicalUrl']) && $GLOBALS['pageCanonicalUrl']
    ? $GLOBALS['pageCanonicalUrl']
    : Helpers::canonicalUrl();

$themeCssPath = '/theme/assets/css/main.css';
$cssVersion = @filemtime(__DIR__ . '/../assets/css/main.css');
if ($cssVersion) {
    $themeCssPath .= '?v=' . $cssVersion;
}

$themeJsPath = '/theme/assets/js/main.js';
$jsVersion = @filemtime(__DIR__ . '/../assets/js/main.js');
if ($jsVersion) {
    $themeJsPath .= '?v=' . $jsVersion;
}

$initialNotifications = array();
if (isset($GLOBALS['theme_notifications']) && is_array($GLOBALS['theme_notifications'])) {
    $initialNotifications = array_values($GLOBALS['theme_notifications']);
}

$notificationPayload = array(
    'items' => $initialNotifications,
    'unread' => array_reduce($initialNotifications, function ($carry, $item) {
        return $carry + (!empty($item['is_read']) ? 0 : 1);
    }, 0),
);
$notificationJson = json_encode($notificationPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$siteName = Helpers::siteName();
$pageHeading = isset($pageTitle) && $pageTitle !== '' ? $pageTitle : $siteName;
$documentTitle = $pageHeading !== $siteName ? $pageHeading . ' | ' . $siteName : $siteName;
$metaDescription = Helpers::seoDescription();
$metaKeywords = Helpers::seoKeywords();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://code.iconify.design" crossorigin>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//code.iconify.design">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQ0C0lYen6MNDozCE3BzKBd0x1L6NfFf4xMFG9gp3vlaPF4r1Rbqu+Ksx" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preload" href="<?= htmlspecialchars($themeCssPath, ENT_QUOTES, 'UTF-8') ?>" as="style">
    <link rel="stylesheet" href="<?= htmlspecialchars($themeCssPath, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <?php include __DIR__ . '/../partials/nav.php'; ?>
    <main class="page">
        <?php if (isset($viewFile)) { include $viewFile; } ?>
    </main>
    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js" defer></script>
    <script src="<?= htmlspecialchars($themeJsPath, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <script>
        window.__APP_NOTIFICATIONS = <?= $notificationJson !== false ? $notificationJson : '{"items":[],"unread":0}' ?>;
    </script>
</body>
</html>
