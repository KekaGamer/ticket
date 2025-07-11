<div class="sidebar">
    <div class="sidebar-header">
        <div class="user-profile">
            <img src="<?php echo $_SESSION['user_photo'] ?? '../assets/img/default-user.png'; ?>" alt="Foto de perfil" class="profile-img">
            <div class="profile-info">
                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="active">
                <a href="index.php">
                    <i class="icon-dashboard"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="empresas.php">
                    <i class="icon-company"></i> Empresas
                </a>
            </li>
            <li>
                <a href="gerencias.php">
                    <i class="icon-gerencia"></i> Gerencias
                </a>
            </li>
            <li>
                <a href="unidades.php">
                    <i class="icon-unidad"></i> Unidades
                </a>
            </li>
            <li>
                <a href="areas.php">
                    <i class="icon-area"></i> Áreas
                </a>
            </li>
            <li>
                <a href="usuarios.php">
                    <i class="icon-users"></i> Usuarios
                </a>
            </li>
            <li>
                <a href="tickets.php">
                    <i class="icon-tickets"></i> Tickets
                </a>
            </li>
            <li>
                <a href="categorias.php">
                    <i class="icon-categories"></i> Categorías
                </a>
            </li>
            <li class="divider"></li>
            <li>
                <a href="../logout.php">
                    <i class="icon-logout"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>
</div>