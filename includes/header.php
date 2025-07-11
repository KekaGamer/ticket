<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($css_files)): ?>
        <?php foreach ($css_files as $css_file): ?>
            <link rel="stylesheet" href="assets/css/<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php if (isset($show_header) && $show_header): ?>
    <header class="main-header">
        <div class="header-left">
            <button class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="logo-text"><?php echo SITE_NAME; ?></h1>
        </div>
        <div class="header-right">
            <div class="notifications">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </div>
            <div class="user-menu">
                <img src="<?php echo $_SESSION['user_photo'] ?? 'assets/img/default-user.png'; ?>" alt="User Photo" class="user-photo">
                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                <i class="fas fa-chevron-down"></i>
                <div class="dropdown-menu">
                    <a href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>