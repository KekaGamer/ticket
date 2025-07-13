<?php
// Asegura que BASE_URL esté disponible
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$is_logged_in = isset($_SESSION['user_id']);
$show_header = $is_logged_in; // Por defecto, mostrar header si el usuario está logueado

// Construir la URL base de los assets de forma dinámica y robusta
$base_path = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
$assets_base_url = substr($base_path, 0, strpos($base_path, '/', 1)) . '/ticket/assets';
if (defined('BASE_URL')) {
    $assets_base_url = BASE_URL . 'assets';
}

$user_photo = 'default-user.png';
if (isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo'])) {
    $user_photo = 'users/' . $_SESSION['user_photo'];
}
?>
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tickets.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/profesional.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <header class="main-header">
        <div class="header-left">
            <button class="sidebar-toggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?php echo BASE_URL; ?>" class="logo-link">
                <h1 class="logo-text"><?php echo SITE_NAME; ?></h1>
            </a>
        </div>
        <div class="header-right">
            <div class="user-menu">
                <?php $user_photo_url = isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo']) ? BASE_URL . 'assets/img/users/' . $_SESSION['user_photo'] : BASE_URL . 'assets/img/default-user.png'; ?>
                <img src="<?php echo $user_photo_url; ?>" alt="Foto de Perfil" class="user-photo">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></span>
                <i class="fas fa-chevron-down"></i>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL . htmlspecialchars($_SESSION['user_role']); ?>/perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
                    <a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>