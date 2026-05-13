<?php
// ============================================================
// PWA meta tags — include inside <head> on every page
// ============================================================
?>
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= SITE_URL ?>/manifest.json">

    <!-- iOS PWA support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Mrs B">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/img/icons/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?= SITE_URL ?>/assets/img/icons/apple-touch-icon-120.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= SITE_URL ?>/assets/img/icons/apple-touch-icon-152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="<?= SITE_URL ?>/assets/img/icons/apple-touch-icon-167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/assets/img/icons/apple-touch-icon-180.png">

    <!-- Theme colour (browser chrome + Android status bar) -->
    <meta name="theme-color" content="#d4136e">
    <meta name="msapplication-TileColor" content="#4a1942">
    <meta name="msapplication-TileImage" content="<?= SITE_URL ?>/assets/img/icons/icon-144.png">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/assets/img/icons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>/assets/img/icons/favicon-16.png">

    <!-- Service Worker registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?= SITE_URL ?>/sw.js', { scope: '/' })
                    .then(function(reg) { console.log('SW registered:', reg.scope); })
                    .catch(function(err) { console.log('SW failed:', err); });
            });
        }
    </script>
