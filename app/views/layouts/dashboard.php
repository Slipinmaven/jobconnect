<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['user_theme'] ?? 'dark' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png?v=2">
    <title>
        <?= htmlspecialchars($title ?? 'JobConnect') ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <!-- Main CSS -->
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/loader.css">
    <?php
        $sidebarCssPath = dirname(__DIR__, 3) . '/public/css/sidebar.css';
        $sidebarCssVersion = is_file($sidebarCssPath) ? filemtime($sidebarCssPath) : false;
        $sidebarCssUrl = '/css/sidebar.css' . ($sidebarCssVersion !== false ? '?v=' . $sidebarCssVersion : '');
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($sidebarCssUrl, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Page Specific CSS -->
    <?php if (isset($css)): ?>
        <?php if (is_array($css)): ?>
            <?php foreach ($css as $file): ?>
                <link rel="stylesheet" href="/css/<?= $file ?>.css">
            <?php endforeach; ?>
        <?php else: ?>
            <link rel="stylesheet" href="/css/<?= $css ?>.css">
        <?php endif; ?>
    <?php endif; ?>
</head>

<body>
    <!-- Your content here -->
     <div class="app-wrapper">
        <?php
            if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employer'){
                include __DIR__ . '/../partials/employer_sidebar.php';
            } elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'jobseeker'){
                include __DIR__ . '/../partials/jobseeker_sidebar.php';
            }
        ?>
        <?= $content ?>
     </div>
    

    <!-- ===== JAVASCRIPT ===== -->
    <!-- Page Specific JS - with module support -->
    <script src="/js/main.js"></script>
    <?php
        $sidebarJsPath = dirname(__DIR__, 3) . '/public/js/sidebar.js';
        $sidebarJsVersion = is_file($sidebarJsPath) ? filemtime($sidebarJsPath) : false;
        $sidebarJsUrl = '/js/sidebar.js' . ($sidebarJsVersion !== false ? '?v=' . $sidebarJsVersion : '');
    ?>
    <script src="<?= htmlspecialchars($sidebarJsUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="/js/utils/toast.js"></script>
    <script src="/js/utils/Confirm.js"></script>

    <?php if (isset($js)): ?>
        <?php if (is_array($js)): ?>
            <?php foreach ($js as $file): ?>
                <?php
                // Check if file should be loaded as module
                $isModule = strpos($file, '-module') !== false || strpos($file, '.mjs') !== false;
                $scriptPath = dirname(__DIR__, 3) . '/public/js/' . $file . '.js';
                $scriptVersion = is_file($scriptPath) ? filemtime($scriptPath) : false;
                $scriptUrl = '/js/' . $file . '.js' . ($scriptVersion !== false ? '?v=' . $scriptVersion : '');
                ?>
                <script <?= $isModule ? 'type="module"' : '' ?> src="<?= htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
            <?php endforeach; ?>
        <?php else: ?>
            <?php
            // Check if single file should be loaded as module
            $isModule = strpos($js, '-module') !== false || strpos($js, '.mjs') !== false;
            $scriptPath = dirname(__DIR__, 3) . '/public/js/' . $js . '.js';
            $scriptVersion = is_file($scriptPath) ? filemtime($scriptPath) : false;
            $scriptUrl = '/js/' . $js . '.js' . ($scriptVersion !== false ? '?v=' . $scriptVersion : '');
            ?>
            <script <?= $isModule ? 'type="module"' : '' ?> src="<?= htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
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