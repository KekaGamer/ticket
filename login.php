<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $useAD = isset($_POST['use_ad']) ? true : false;
    
    if ($auth->login($email, $password, $useAD)) {
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
            default:
                header("Location: index.php");
        }
        exit();
    } else {
        $error = "Credenciales incorrectas. Por favor intente nuevamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="assets/img/logo.png" alt="MASERCOM Logo" class="logo">
                <h1>Sistema de Tickets</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required class="form-control">
                </div>
                
                <div class="form-group form-check">
                    <input type="checkbox" id="use_ad" name="use_ad" class="form-check-input">
                    <label for="use_ad" class="form-check-label">Usar Active Directory</label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                </div>
            </form>
            
            <div class="login-footer">
                <p>¿Necesitas ayuda? <a href="mailto:<?php echo ADMIN_EMAIL; ?>">Contacta al administrador</a></p>
            </div>
        </div>
    </div>
</body>
</html>