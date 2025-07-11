<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAdmin();

$functions = new Functions();
$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rol = filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_STRING);
    $id_empresa = filter_input(INPUT_POST, 'id_empresa', FILTER_SANITIZE_NUMBER_INT);
    $id_area = filter_input(INPUT_POST, 'id_area', FILTER_SANITIZE_NUMBER_INT);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Procesar foto de perfil
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/users/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $targetPath)) {
            $foto_perfil = $filename;
        }
    }
    
    if ($action == 'create') {
        if ($password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
        } else {
            if ($functions->crearUsuario($id_empresa, $id_area, $nombre, $apellido, $email, $password, $rol, $foto_perfil)) {
                $_SESSION['success_message'] = "Usuario creado correctamente.";
                header("Location: usuarios.php");
                exit();
            } else {
                $error = "Error al crear el usuario. El correo electrónico ya puede estar en uso.";
            }
        }
    } elseif ($action == 'edit' && $id > 0) {
        $query = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email, rol = :rol, 
                 id_empresa = :id_empresa, id_area = :id_area";
        $params = [
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':rol' => $rol,
            ':id_empresa' => $id_empresa,
            ':id_area' => $id_area,
            ':id' => $id
        ];
        
        if ($foto_perfil) {
            $query .= ", foto_perfil = :foto_perfil";
            $params[':foto_perfil'] = $foto_perfil;
            
            // Eliminar foto anterior si existe
            $stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $oldPhoto = $stmt->fetchColumn();
            
            if ($oldPhoto && file_exists('../assets/img/users/' . $oldPhoto)) {
                unlink('../assets/img/users/' . $oldPhoto);
            }
        }
        
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                $error = "Las contraseñas no coinciden.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $query .= ", password = :password";
                $params[':password'] = $hashedPassword;
            }
        }
        
        if (!isset($error)) {
            $query .= " WHERE id = :id";
            $stmt = $conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Usuario actualizado correctamente.";
                header("Location: usuarios.php");
                exit();
            } else {
                $error = "Error al actualizar el usuario.";
            }
        }
    }
}

// Cambiar estado
if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE usuarios SET estado = NOT estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: usuarios.php");
    exit();
}

// Obtener datos para edición
if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header("Location: usuarios.php");
        exit();
    }
}

// Listar usuarios
$stmt = $conn->query("SELECT u.*, e.nombre as empresa_nombre, a.nombre as area_nombre 
                      FROM usuarios u 
                      LEFT JOIN empresas e ON u.id_empresa = e.id 
                      LEFT JOIN areas a ON u.id_area = a.id 
                      ORDER BY u.nombre, u.apellido");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener empresas y áreas para selects
$empresas = $conn->query("SELECT * FROM empresas WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$areas = $conn->query("SELECT a.*, e.nombre as empresa_nombre, u.nombre as unidad_nombre 
                       FROM areas a 
                       JOIN unidades u ON a.id_unidad = u.id 
                       JOIN empresas e ON u.id_empresa = e.id 
                       WHERE a.estado = 1 
                       ORDER BY e.nombre, u.nombre, a.nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Gestión de Usuarios</h1>
                <div class="actions">
                    <a href="usuarios.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </a>
                </div>
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
            
            <?php if ($action == 'list'): ?>
                <div class="card">
                    <div class="card-body">
                        <table id="usuariosTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Empresa</th>
                                    <th>Área</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usr): ?>
                                    <tr>
                                        <td><?php echo $usr['id']; ?></td>
                                        <td>
                                            <?php if ($usr['foto_perfil']): ?>
                                                <img src="../assets/img/users/<?php echo htmlspecialchars($usr['foto_perfil']); ?>" alt="Foto perfil" class="user-avatar-sm">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($usr['nombre'] . ' ' . $usr['apellido']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $usr['rol'] == 'admin' ? 'danger' : 
                                                    ($usr['rol'] == 'tecnico' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($usr['rol']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($usr['empresa_nombre'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($usr['area_nombre'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $usr['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $usr['estado'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="usuarios.php?action=edit&id=<?php echo $usr['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="usuarios.php?toggle=1&id=<?php echo $usr['id']; ?>" class="btn btn-sm btn-<?php echo $usr['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $usr['estado'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-<?php echo $usr['estado'] ? 'times' : 'check'; ?>"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($action == 'create' || $action == 'edit'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo $action == 'create' ? 'Crear Nuevo Usuario' : 'Editar Usuario'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo isset($usuario) ? htmlspecialchars($usuario['nombre']) : ''; ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el nombre del usuario.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellido">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" 
                                               value="<?php echo isset($usuario) ? htmlspecialchars($usuario['apellido']) : ''; ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el apellido del usuario.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($usuario) ? htmlspecialchars($usuario['email']) : ''; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese un correo electrónico válido.
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rol">Rol</label>
                                        <select class="form-control" id="rol" name="rol" required>
                                            <option value="">Seleccione un rol</option>
                                            <option value="admin" <?php echo (isset($usuario) && $usuario['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                            <option value="tecnico" <?php echo (isset($usuario) && $usuario['rol'] == 'tecnico') ? 'selected' : ''; ?>>Técnico</option>
                                            <option value="cliente" <?php echo (isset($usuario) && $usuario['rol'] == 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione un rol para el usuario.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_empresa">Empresa</label>
                                        <select class="form-control" id="id_empresa" name="id_empresa">
                                            <option value="">Seleccione una empresa</option>
                                            <?php foreach ($empresas as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" 
                                                    <?php echo (isset($usuario) && $usuario['id_empresa'] == $emp['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($emp['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_area">Área</label>
                                <select class="form-control" id="id_area" name="id_area">
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>" 
                                            data-empresa="<?php echo $area['id_empresa']; ?>"
                                            <?php echo (isset($usuario) && $usuario['id_area'] == $area['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($area['empresa_nombre'] . ' - ' . $area['unidad_nombre'] . ' - ' . $area['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="foto_perfil">Foto de Perfil</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="foto_perfil" name="foto_perfil" accept="image/*">
                                    <label class="custom-file-label" for="foto_perfil">Seleccionar archivo</label>
                                </div>
                                <?php if (isset($usuario) && $usuario['foto_perfil']): ?>
                                    <div class="mt-2">
                                        <img src="../assets/img/users/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto actual" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password"><?php echo $action == 'create' ? 'Contraseña' : 'Nueva Contraseña'; ?></label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               <?php echo $action == 'create' ? 'required' : ''; ?> minlength="6">
                                        <div class="invalid-feedback">
                                            Por favor ingrese una contraseña de al menos 6 caracteres.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirmar Contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               <?php echo $action == 'create' ? 'required' : ''; ?> minlength="6">
                                        <div class="invalid-feedback">
                                            Las contraseñas deben coincidir.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group text-right">
                                <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action == 'create' ? 'Crear Usuario' : 'Actualizar Usuario'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('#usuariosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: [7] }
                ]
            });
            
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
                            
                            // Validar que las contraseñas coincidan
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
            
            // Filtrar áreas por empresa seleccionada
            $('#id_empresa').change(function() {
                const empresaId = $(this).val();
                $('#id_area option').each(function() {
                    const areaEmpresaId = $(this).data('empresa');
                    if (!areaEmpresaId || areaEmpresaId == empresaId) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        if ($(this).prop('selected')) {
                            $(this).prop('selected', false);
                            $('#id_area').val('');
                        }
                    }
                });
            });
            
            // Mostrar nombre del archivo seleccionado
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });
            
            // Inicializar filtro de áreas según empresa seleccionada
            if ($('#id_empresa').val()) {
                $('#id_empresa').trigger('change');
            }
        });
    </script>
</body>
</html>