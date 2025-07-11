<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $id_unidad = filter_input(INPUT_POST, 'id_unidad', FILTER_SANITIZE_NUMBER_INT);
    
    if ($action == 'create') {
        $stmt = $conn->prepare("INSERT INTO areas (id_unidad, nombre, descripcion) VALUES (:id_unidad, :nombre, :descripcion)");
        $stmt->bindParam(':id_unidad', $id_unidad);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Área creada correctamente.";
            header("Location: areas.php");
            exit();
        } else {
            $error = "Error al crear el área.";
        }
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE areas SET id_unidad = :id_unidad, nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        $stmt->bindParam(':id_unidad', $id_unidad);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Área actualizada correctamente.";
            header("Location: areas.php");
            exit();
        } else {
            $error = "Error al actualizar el área.";
        }
    }
}

// Cambiar estado
if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE areas SET estado = NOT estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: areas.php");
    exit();
}

// Obtener datos para edición
if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM areas WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $area = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$area) {
        header("Location: areas.php");
        exit();
    }
}

// Listar áreas con información de unidad y gerencia
$query = "SELECT a.*, u.nombre as unidad_nombre, g.nombre as gerencia_nombre, e.nombre as empresa_nombre 
          FROM areas a
          JOIN unidades u ON a.id_unidad = u.id
          JOIN gerencias g ON u.id_gerencia = g.id
          JOIN empresas e ON g.id_empresa = e.id
          ORDER BY e.nombre, g.nombre, u.nombre, a.nombre";
$stmt = $conn->query($query);
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener unidades para select
$unidades = $conn->query("SELECT u.*, g.nombre as gerencia_nombre, e.nombre as empresa_nombre 
                          FROM unidades u
                          JOIN gerencias g ON u.id_gerencia = g.id
                          JOIN empresas e ON g.id_empresa = e.id
                          WHERE u.estado = 1
                          ORDER BY e.nombre, g.nombre, u.nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Áreas - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Gestión de Áreas</h1>
                <div class="actions">
                    <a href="areas.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Área
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
                        <table id="areasTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Unidad</th>
                                    <th>Gerencia</th>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($areas as $area): ?>
                                    <tr>
                                        <td><?php echo $area['id']; ?></td>
                                        <td><?php echo htmlspecialchars($area['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($area['unidad_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($area['gerencia_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($area['empresa_nombre']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $area['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $area['estado'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="areas.php?action=edit&id=<?php echo $area['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="areas.php?toggle=1&id=<?php echo $area['id']; ?>" class="btn btn-sm btn-<?php echo $area['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $area['estado'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-<?php echo $area['estado'] ? 'times' : 'check'; ?>"></i>
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
                        <h2><?php echo $action == 'create' ? 'Crear Nueva Área' : 'Editar Área'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="id_unidad">Unidad</label>
                                <select class="form-control" id="id_unidad" name="id_unidad" required>
                                    <option value="">Seleccione una unidad</option>
                                    <?php foreach ($unidades as $unidad): ?>
                                        <option value="<?php echo $unidad['id']; ?>" 
                                            <?php echo (isset($area) && $area['id_unidad'] == $unidad['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($unidad['empresa_nombre'] . ' - ' . $unidad['gerencia_nombre'] . ' - ' . $unidad['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione una unidad.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre">Nombre del Área</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo isset($area) ? htmlspecialchars($area['nombre']) : ''; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese el nombre del área.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($area) ? htmlspecialchars($area['descripcion']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group text-right">
                                <a href="areas.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action == 'create' ? 'Crear Área' : 'Actualizar Área'; ?>
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
            $('#areasTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: [6] }
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
                            form.classList.add('was-validated');
                        }, false);
                    });
                }, false);
            })();
        });
    </script>
</body>
</html>