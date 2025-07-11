<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotTecnico();

$db = new Database();
$conn = $db->getConnection();

$userId = $_SESSION['user_id'];

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT u.*, e.nombre as empresa_nombre, a.nombre as area_nombre 
                       FROM usuarios u
                       LEFT JOIN empresas e ON u.id_empresa = e.id
                       LEFT JOIN areas a ON u.id_area = a.id
                       WHERE u.id = :id");
$stmt->bindParam(':id', $userId);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Procesar foto de perfil
    $foto_perfil = $usuario['foto_perfil'];
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/users/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Eliminar foto anterior si existe
        if ($foto_perfil && file_exists($uploadDir . $foto_perfil)) {
            unlink($uploadDir . $foto_perfil);
        }
        
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $targetPath)) {
            $foto_perfil = $filename;
        }
    }
    
    // Validar contraseña si se proporcionó
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
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Perfil actualizado correctamente.";
            $_SESSION['user_name'] = $nombre . ' ' . $apellido;
            
            // Recargar datos del usuario
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Mi Perfil</h1>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="profile-picture-container">
                                    <div class="profile-picture">
                                        <?php if ($usuario['foto_perfil']): ?>
                                            <img src="../assets/img/users/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil" id="profileImage">
                                        <?php else: ?>
                                            <div class="profile-initials">
                                                <?php echo substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="foto_perfil">Cambiar foto</label>
                                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="form-control-file">
                                        <small class="form-text text-muted">Formatos: JPG, PNG (max. 2MB)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese su nombre.
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" 
                                           value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese su apellido.
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese un correo electrónico válido.
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="empresa">Empresa</label>
                                    <input type="text" class="form-control" id="empresa" 
                                           value="<?php echo htmlspecialchars($usuario['empresa_nombre'] ?? 'No asignada'); ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="area">Área</label>
                                    <input type="text" class="form-control" id="area" 
                                           value="<?php echo htmlspecialchars($usuario['area_nombre'] ?? 'No asignada'); ?>" readonly>
                                </div>
                                
                                <hr>
                                
                                <h5>Cambiar Contraseña</h5>
                                <div class="form-group">
                                    <label for="password">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted">Dejar en blanco para no cambiar</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                                
                                <div class="form-group text-right">
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
    <script>
        // Validación de formulario
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        // Validar que las contraseñas coincidan si se proporcionaron
                        if ($('#password').val() !== $('#confirm_password').val()) {
                            $('#confirm_password').addClass('is-invalid');
                            event.preventDefault();
                            event.stopPropagation();
                        } else {
                            $('#confirm_password').removeClass('is-invalid');
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Mostrar vista previa de la imagen seleccionada
        $('#foto_perfil').change(function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    if ($('#profileImage').length) {
                        $('#profileImage').attr('src', e.target.result);
                    } else {
                        $('.profile-initials').replaceWith('<img src="' + e.target.result + '" alt="Foto de perfil" id="profileImage">');
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>