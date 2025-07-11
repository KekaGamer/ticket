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
    $plantilla = filter_input(INPUT_POST, 'plantilla', FILTER_SANITIZE_STRING);
    
    if ($action == 'create') {
        $stmt = $conn->prepare("INSERT INTO categorias_tickets (nombre, descripcion, plantilla) VALUES (:nombre, :descripcion, :plantilla)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':plantilla', $plantilla);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Categoría creada correctamente.";
            header("Location: categorias.php");
            exit();
        } else {
            $error = "Error al crear la categoría.";
        }
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE categorias_tickets SET nombre = :nombre, descripcion = :descripcion, plantilla = :plantilla WHERE id = :id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':plantilla', $plantilla);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Categoría actualizada correctamente.";
            header("Location: categorias.php");
            exit();
        } else {
            $error = "Error al actualizar la categoría.";
        }
    }
}

// Cambiar estado
if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE categorias_tickets SET estado = NOT estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: categorias.php");
    exit();
}

// Obtener datos para edición
if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM categorias_tickets WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoria) {
        header("Location: categorias.php");
        exit();
    }
}

// Listar categorías
$stmt = $conn->query("SELECT * FROM categorias_tickets ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - <?php echo SITE_NAME; ?></title>
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
                <h1>Gestión de Categorías de Tickets</h1>
                <div class="actions">
                    <a href="categorias.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Categoría
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
                        <table id="categoriasTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($cat['descripcion']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $cat['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $cat['estado'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="categorias.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="categorias.php?toggle=1&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-<?php echo $cat['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $cat['estado'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-<?php echo $cat['estado'] ? 'times' : 'check'; ?>"></i>
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
                        <h2><?php echo $action == 'create' ? 'Crear Nueva Categoría' : 'Editar Categoría'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo isset($categoria) ? htmlspecialchars($categoria['nombre']) : ''; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese el nombre de la categoría.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($categoria) ? htmlspecialchars($categoria['descripcion']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="plantilla">Plantilla (Opcional)</label>
                                <textarea class="form-control" id="plantilla" name="plantilla" rows="6" placeholder="Puede incluir una plantilla para guiar al usuario al crear tickets de esta categoría..."><?php echo isset($categoria) ? htmlspecialchars($categoria['plantilla']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group text-right">
                                <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action == 'create' ? 'Crear Categoría' : 'Actualizar Categoría'; ?>
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
            $('#categoriasTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: [4] }
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