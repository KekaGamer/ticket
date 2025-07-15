<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAdmin();
$db = new Database();
$conn = $db->getConnection();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action == 'create' || $action == 'edit')) {
    $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT);
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $plantilla = $_POST['plantilla'] ?? '';
    $icono_fa = filter_input(INPUT_POST, 'icono_fa', FILTER_SANITIZE_STRING);
    $parent_id = ($parent_id == 0) ? null : $parent_id;

    if ($action == 'create') {
        $stmt = $conn->prepare("INSERT INTO categorias_tickets (parent_id, nombre, descripcion, plantilla, icono_fa) VALUES (:parent_id, :nombre, :descripcion, :plantilla, :icono_fa)");
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE categorias_tickets SET parent_id = :parent_id, nombre = :nombre, descripcion = :descripcion, plantilla = :plantilla, icono_fa = :icono_fa WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    }
    
    if (isset($stmt)) {
        $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':plantilla', $plantilla);
        $stmt->bindParam(':icono_fa', $icono_fa);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Categoría guardada correctamente.";
        } else {
            $_SESSION['error_message'] = "Error al guardar la categoría.";
        }
        header("Location: categorias.php");
        exit();
    }
}

// --- INICIO DE LA LÓGICA DE ELIMINACIÓN AÑADIDA ---
if ($action == 'delete' && $id > 0) {
    // Se intenta eliminar la categoría.
    try {
        $stmt = $conn->prepare("DELETE FROM categorias_tickets WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success_message'] = "Categoría eliminada permanentemente.";
    } catch (PDOException $e) {
        // Esto previene un error fatal si la categoría está en uso.
        $_SESSION['error_message'] = "No se puede eliminar la categoría porque está en uso por tickets existentes o es padre de otras subcategorías.";
    }
    header("Location: categorias.php");
    exit();
}
// --- FIN DE LA LÓGICA DE ELIMINACIÓN ---

if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE categorias_tickets SET estado = NOT estado WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: categorias.php");
    exit();
}

if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM categorias_tickets WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
}

$parent_categories = $conn->query("SELECT id, nombre FROM categorias_tickets WHERE parent_id IS NULL ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->query("SELECT c.*, p.nombre as parent_nombre FROM categorias_tickets c LEFT JOIN categorias_tickets p ON c.parent_id = p.id ORDER BY c.nombre, p.nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestión de Categorías";
include '../includes/header.php';
?>
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            <div class="actions">
                <?php if ($action != 'list'): ?>
                <a href="categorias.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                <?php else: ?>
                <a href="categorias.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Categoría</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div class="card">
                <div class="card-body">
                    <table id="categoriasTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icono</th>
                                <th>Nombre</th>
                                <th>Categoría Padre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td><?php echo $cat['id']; ?></td>
                                    <td><i class="<?php echo htmlspecialchars($cat['icono_fa'] ?? 'fas fa-question-circle'); ?>"></i></td>
                                    <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cat['parent_nombre'] ?? '<strong>(Principal)</strong>'); ?></td>
                                    <td><span class="badge <?php echo $cat['estado'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $cat['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td>
                                        <a href="categorias.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="categorias.php?toggle=1&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-<?php echo $cat['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $cat['estado'] ? 'Desactivar' : 'Activar'; ?>"><i class="fas fa-<?php echo $cat['estado'] ? 'times' : 'check'; ?>"></i></a>
                                        <a href="categorias.php?action=delete&id=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Eliminar Permanentemente"
                                           onclick="return confirm('¡ADVERTENCIA!\n\n¿Estás seguro de que quieres eliminar esta categoría de forma permanente?\n\nEsta acción no se puede deshacer y puede afectar a tickets antiguos si está en uso.');">
                                            <i class="fas fa-trash"></i>
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
                            <label for="parent_id">Categoría Padre (Opcional)</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="0">-- Ninguna (Es una categoría principal) --</option>
                                <?php foreach ($parent_categories as $p_cat): ?>
                                    <?php if (!isset($categoria) || $p_cat['id'] != $categoria['id']): ?>
                                    <option value="<?php echo $p_cat['id']; ?>" <?php echo (isset($categoria) && $categoria['parent_id'] == $p_cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p_cat['nombre']); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Aplica solo para subcategorías.</small>
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre de la Categoría/Subcategoría</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($categoria) ? htmlspecialchars($categoria['nombre']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="icono_fa">Icono (Font Awesome)</label>
                            <input type="text" class="form-control" id="icono_fa" name="icono_fa" value="<?php echo isset($categoria) ? htmlspecialchars($categoria['icono_fa']) : 'fas fa-concierge-bell'; ?>">
                            <small class="form-text text-muted">
                                Ej: <code>fas fa-database</code>, <code>fas fa-user-plus</code>. 
                                <a href="https://fontawesome.com/v5/search?m=free" target="_blank">Busca iconos aquí</a>.
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($categoria) ? htmlspecialchars($categoria['descripcion']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="plantilla">Plantilla de Ticket (Opcional)</label>
                            <textarea class="form-control" id="plantilla" name="plantilla" rows="10"><?php echo isset($categoria) ? htmlspecialchars($categoria['plantilla']) : ''; ?></textarea>
                        </div>
                        <div class="form-actions">
                            <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Categoría</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script>
    $(document).ready(function() {
        $('#categoriasTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' } });
    });
</script>
</body>
</html>