<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$error = '';
$success_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Obtener datos actuales del usuario para tener la foto anterior
$stmt_current = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt_current->bindParam(':id', $userId);
$stmt_current->execute();
$usuario = $stmt_current->fetch(PDO::FETCH_ASSOC);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $foto_perfil = $usuario['foto_perfil'];

    // --- LÓGICA AÑADIDA PARA MANEJAR LA FOTO DE PERFIL ---
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/users/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Eliminar foto anterior si no es la por defecto
        if ($foto_perfil && $foto_perfil != 'default-user.png' && file_exists($uploadDir . $foto_perfil)) {
            unlink($uploadDir . $foto_perfil);
        }
        
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $targetPath)) {
            $foto_perfil = $filename;
        }
    }

    if (!empty($password) && $password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $query = "UPDATE usuarios SET 
                 nombre = :nombre,
                 apellido = :apellido,
                 email = :email,
                 foto_perfil = :foto_perfil";
        
        $params = [
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':foto_perfil' => $foto_perfil,
            ':id' => $userId
        ];
        
        if (!empty($password)) {
            $query .= ", password = :password";
            $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($params)) {
            $_SESSION['success_message'] = "Perfil actualizado correctamente.";
            $_SESSION['user_name'] = $nombre . ' ' . $apellido;
            $_SESSION['user_photo'] = $foto_perfil;
            header("Location: perfil.php");
            exit();
        } else {
            $error = "Error al actualizar el perfil.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/profesional.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Mi Perfil de Administrador</h1>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="perfil.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <?php
                                $photo_url = '../assets/img/default-user.png';
                                if (!empty($usuario['foto_perfil']) && file_exists('../assets/img/users/' . $usuario['foto_perfil'])) {
                                    $photo_url = '../assets/img/users/' . $usuario['foto_perfil'];
                                }
                                ?>
                                <img src="<?php echo $photo_url; ?>" alt="Foto de perfil" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <div class="form-group">
                                    <label for="foto_perfil">Cambiar foto de perfil</label>
                                    <input type="file" class="form-control-file" id="foto_perfil" name="foto_perfil">
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" 
                                           value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                </div>
                                <hr>
                                <h5>Cambiar Contraseña</h5>
                                <p class="text-muted"><small>Dejar en blanco para no cambiar.</small></p>
                                <div class="form-group">
                                    <label for="password">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>