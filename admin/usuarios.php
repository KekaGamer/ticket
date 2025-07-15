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
$error = ''; // Inicializar la variable de error

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rol = filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_STRING);
    $id_empresa = filter_input(INPUT_POST, 'id_empresa', FILTER_SANITIZE_NUMBER_INT) ?: null;
    $id_area = filter_input(INPUT_POST, 'id_area', FILTER_SANITIZE_NUMBER_INT) ?: null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/users/';
        if (!file_exists($uploadDir)) { mkdir($uploadDir, 0777, true); }
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
            // --- INICIO DE LA CORRECCIÓN: Lógica de creación robusta ---
            if ($functions->crearUsuario($id_empresa, $id_area, $nombre, $apellido, $email, $password, $rol, $foto_perfil)) {
                $_SESSION['success_message'] = "Usuario creado correctamente.";
                header("Location: usuarios.php");
                exit();
            } else {
                // Este error ahora se mostrará si la función devuelve false por CUALQUIER motivo
                $error = "No se pudo crear el usuario. Verifique que el correo no esté duplicado y que todos los campos requeridos estén completos.";
            }
            // --- FIN DE LA CORRECCIÓN ---
        }
    } elseif ($action == 'edit' && $id > 0) {
        // La lógica de edición no se modifica
        $query = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email, rol = :rol, id_empresa = :id_empresa, id_area = :id_area";
        $params = [
            ':nombre' => $nombre, ':apellido' => $apellido, ':email' => $email, ':rol' => $rol,
            ':id_empresa' => $id_empresa, ':id_area' => $id_area, ':id' => $id
        ];
        if ($foto_perfil) {
            $query .= ", foto_perfil = :foto_perfil";
            $params[':foto_perfil'] = $foto_perfil;
            $stmt_old = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
            $stmt_old->execute([':id' => $id]);
            $oldPhoto = $stmt_old->fetchColumn();
            if ($oldPhoto && file_exists('../assets/img/users/' . $oldPhoto)) {
                unlink('../assets/img/users/' . $oldPhoto);
            }
        }
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                $error = "Las contraseñas no coinciden.";
            } else {
                $query .= ", password = :password";
                $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
            }
        }
        if (empty($error)) {
            $query .= " WHERE id = :id";
            $stmt = $conn->prepare($query);
            if ($stmt->execute($params)) {
                $_SESSION['success_message'] = "Usuario actualizado correctamente.";
                header("Location: usuarios.php");
                exit();
            } else { $error = "Error al actualizar el usuario."; }
        }
    }
}

// El resto del código PHP para listar y obtener datos no cambia
if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE usuarios SET estado = NOT estado WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: usuarios.php");
    exit();
}
if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) { header("Location: usuarios.php"); exit(); }
}
$stmt = $conn->query("SELECT u.*, e.nombre as empresa_nombre, a.nombre as area_nombre 
                      FROM usuarios u 
                      LEFT JOIN empresas e ON u.id_empresa = e.id 
                      LEFT JOIN areas a ON u.id_area = a.id 
                      ORDER BY u.nombre, u.apellido");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$empresas = $conn->query("SELECT * FROM empresas WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$query_areas = "SELECT a.id, a.nombre, g.nombre as gerencia_nombre, e.nombre as empresa_nombre, e.id as id_empresa
                FROM areas a
                JOIN unidades u ON a.id_unidad = u.id
                JOIN gerencias g ON u.id_gerencia = g.id
                JOIN empresas e ON g.id_empresa = e.id
                WHERE a.estado = 1 
                ORDER BY e.nombre, g.nombre, u.nombre, a.nombre";
$areas = $conn->query($query_areas)->fetchAll(PDO::FETCH_ASSOC);
$page_title = "Gestión de Usuarios";
include_once '../includes/header.php';
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
    <link rel="stylesheet" href="../assets/css/profesional.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Gestión de Usuarios</h1>
                <div class="actions">
                     <?php if ($action != 'list'): ?>
                    <a href="usuarios.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    <?php else: ?>
                    <a href="usuarios.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Usuario</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
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
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><?php echo ucfirst($usuario['rol']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['empresa_nombre'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['area_nombre'] ?? 'N/A'); ?></td>
                                        <td><span class="badge <?php echo $usuario['estado'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $usuario['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                        <td>
                                            <a href="usuarios.php?action=edit&id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="usuarios.php?toggle=1&id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-<?php echo $usuario['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $usuario['estado'] ? 'Desactivar' : 'Activar'; ?>"><i class="fas fa-<?php echo $usuario['estado'] ? 'times' : 'check'; ?>"></i></a>
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
                        <form method="POST" action="usuarios.php?action=<?php echo $action; ?><?php echo $id ? '&id='.$id : ''; ?>" class="needs-validation" novalidate enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($usuario) ? htmlspecialchars($usuario['nombre']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellido">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo isset($usuario) ? htmlspecialchars($usuario['apellido']) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($usuario) ? htmlspecialchars($usuario['email']) : ''; ?>" required>
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
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_empresa">Empresa</label>
                                        <select class="form-control" id="id_empresa" name="id_empresa">
                                            <option value="">Seleccione una empresa</option>
                                            <?php foreach ($empresas as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" <?php echo (isset($usuario) && $usuario['id_empresa'] == $emp['id']) ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $area['id']; ?>" data-empresa="<?php echo $area['id_empresa']; ?>" <?php echo (isset($usuario) && $usuario['id_area'] == $area['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($area['empresa_nombre'] . ' - ' . $area['gerencia_nombre'] . ' - ' . $area['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Foto de Perfil</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="foto_perfil" name="foto_perfil" accept="image/*">
                                    <label class="custom-file-label" for="foto_perfil">Elegir archivo...</label>
                                </div>
                                <?php if (isset($usuario) && $usuario['foto_perfil']): ?>
                                    <div class="mt-2"><img src="../assets/img/users/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto actual" class="img-thumbnail" style="max-height: 100px;"></div>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password"><?php echo $action == 'create' ? 'Contraseña' : 'Nueva Contraseña (opcional)'; ?></label>
                                        <input type="password" class="form-control" id="password" name="password" <?php echo $action == 'create' ? 'required' : ''; ?> minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirmar Contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?php echo $action == 'create' ? 'required' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $action == 'create' ? 'Crear Usuario' : 'Actualizar Usuario'; ?></button>
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
            $('#usuariosTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' } });
        });
    </script>
</body>
</html>
