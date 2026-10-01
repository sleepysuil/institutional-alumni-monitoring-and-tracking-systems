<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- No "user-scalable=no": it blocks pinch-zoom (an accessibility problem). iOS focus-zoom is prevented
         with 16px inputs in style.css instead. viewport-fit=cover lets the top bar respect phone notches. -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#388087">
    <title>USAT Alumni System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/assets/css/style.css">
</head>
<body>
    <!-- Top bar (phones and tablets only; hidden by CSS on desktop) -->
    <div class="mobile-topbar">
        <button class="mobile-toggle" id="mobileToggle" type="button" aria-label="Toggle menu" aria-controls="mainSidebar" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
        <span class="topbar-title">USAT Alumni System</span>
    </div>

    <!-- Dark overlay behind the open mobile menu -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>