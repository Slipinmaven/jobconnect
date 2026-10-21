<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['user_theme'] ?? 'light' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png?v=2">
    <title><?= htmlspecialchars($title ?? 'JobConnect') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <!-- Main CSS -->
    <link rel="stylesheet" href="/../../../public/css/main.css">
    <link rel="stylesheet" href="/css/loader.css">

    <!-- Page Specific CSS -->
    <?php if (isset($css)): ?>
        <?php if (is_array($css)): ?>
            <?php foreach ($css as $file): ?>
                <?php
                $cssPath = dirname(__DIR__, 3) . '/public/css/' . $file . '.css';
                $cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '';
                ?>
                <link rel="stylesheet" href="/css/<?= $file ?>.css<?= $cssVersion !== '' ? '?v=' . $cssVersion : '' ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <?php
            $cssPath = dirname(__DIR__, 3) . '/public/css/' . $css . '.css';
            $cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '';
            ?>
            <link rel="stylesheet" href="/css/<?= $css ?>.css<?= $cssVersion !== '' ? '?v=' . $cssVersion : '' ?>">
        <?php endif; ?>
    <?php endif; ?>
</head>

<body>
    <!-- Your content here -->
    <?= $content ?>

    <!-- ===== JAVASCRIPT ===== -->
    <!-- Page Specific JS - with module support -->
     <script src="/js/main.js"></script>
    <script src="/js/utils/toast.js"></script>
    <script src="/js/utils/Spinner.js"></script>
    <script src="/js/utils/Confirm.js"></script>

    <?php if (isset($js)): ?>
        <?php if (is_array($js)): ?>
            <?php foreach ($js as $file): ?>
                <?php
                // Check if file should be loaded as module
                $isModule = strpos($file, '-module') !== false || strpos($file, '.mjs') !== false;
                ?>
                <script <?= $isModule ? 'type="module"' : '' ?> src="/js/<?= $file ?>.js"></script>
            <?php endforeach; ?>
        <?php else: ?>
            <?php
            // Check if single file should be loaded as module
            $isModule = strpos($js, '-module') !== false || strpos($js, '.mjs') !== false;
            ?>
            <script <?= $isModule ? 'type="module"' : '' ?> src="/js/<?= $js ?>.js"></script>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php
if (isset($_SESSION['_flash'])) {
    $flash = $_SESSION['_flash'];
    
    if (!empty($flash['error'])) {
        echo '<script>Toast.error(' . json_encode($flash['error']) . ');</script>';
    }
    
    if (!empty($flash['success'])) {
        echo '<script>Toast.success(' . json_encode($flash['success']) . ');</script>';
    }
    
    if (!empty($flash['info'])) {
        echo '<script>Toast.info(' . json_encode($flash['info']) . ');</script>';
    }
    
    unset($_SESSION['_flash']);
}
?>
</body>

</html>