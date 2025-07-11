<?php
require_once 'includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    // Redirigir según el rol del usuario
    switch ($auth->getUserRole()) {
        case 'admin':
            header("Location: admin/");
            break;
        case 'tecnico':
            header("Location: tecnico/");
            break;
        case 'cliente':
            header("Location: cliente/");
            break;
    }
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>