<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Asegurarse de que BASE_URL esté definido
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="user-profile">
            <?php 
                $photo_url = BASE_URL . 'assets/img/default-user.png';
                if (isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo']) && file_exists(__DIR__ . '/../assets/img/users/' . $_SESSION['user_photo'])) {
                    $photo_url = BASE_URL . 'assets/img/users/' . $_SESSION['user_photo'];
                }
            ?>
            <img src="<?php echo $photo_url; ?>" alt="Foto de perfil" class="profile-img">
            <div class="profile-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Cliente'); ?></span>
                <span class="user-role">Cliente</span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'catalogo.php' || basename($_SERVER['PHP_SELF']) == 'solicitud.php' ? 'active' : ''; ?>">
                <a href="catalogo.php">
                    <i class="fas fa-book-open"></i> Catálogo de Solicitudes
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
                <a href="tickets.php">
                    <i class="fas fa-ticket-alt"></i> Mis Tickets
                </a>
            </li>
            <li class="divider"></li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <a href="perfil.php">
                    <i class="fas fa-user"></i> Mi Perfil
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>
</div>