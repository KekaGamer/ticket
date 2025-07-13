<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAdmin();

$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $id_gerencia = filter_input(INPUT_POST, 'id_gerencia', FILTER_SANITIZE_NUMBER_INT);
    
    if ($action == 'create') {
        $stmt = $conn->prepare("INSERT INTO unidades (id_gerencia, nombre, descripcion) VALUES (:id_gerencia, :nombre, :descripcion)");
        $stmt->bindParam(':id_gerencia', $id_gerencia);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Unidad creada correctamente.";
            header("Location: unidades.php");
            exit();
        } else {
            $error = "Error al crear la unidad.";
        }
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE unidades SET id_gerencia = :id_gerencia, nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        $stmt->bindParam(':id_gerencia', $id_gerencia);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Unidad actualizada correctamente.";
            header("Location: unidades.php");
            exit();
        } else {
            $error = "Error al actualizar la unidad.";
        }
    }
}

if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE unidades SET estado = NOT estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: unidades.php");
    exit();
}

if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM unidades WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$unidad) {
        header("Location: unidades.php");
        exit();
    }
}

$query = "SELECT u.*, g.nombre as gerencia_nombre, e.nombre as empresa_nombre 
          FROM unidades u
          JOIN gerencias g ON u.id_gerencia = g.id
          JOIN empresas e ON g.id_empresa = e.id
          ORDER BY e.nombre, g.nombre, u.nombre";
$stmt = $conn->query($query);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gerencias = $conn->query("SELECT g.*, e.nombre as empresa_nombre 
                           FROM gerencias g
                           JOIN empresas e ON g.id_empresa = e.id
                           WHERE g.estado = 1
                           ORDER BY e.nombre, g.nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Unidades - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/profesional.css">
</head>
<body>
    <?php $show_header = true; include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Gestión de Unidades</h1>
                <div class="actions">
                    <?php if ($action != 'list'): ?>
                    <a href="unidades.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                    <?php else: ?>
                    <a href="unidades.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Unidad</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <div class="card">
                    <div class="card-body">
                        <table id="unidadesTable" class="display" style="width:100%">
                           <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Gerencia</th>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unidades as $uni): ?>
                                    <tr>
                                        <td><?php echo $uni['id']; ?></td>
                                        <td><?php echo htmlspecialchars($uni['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($uni['gerencia_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($uni['empresa_nombre']); ?></td>
                                        <td><span class="badge <?php echo $uni['estado'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $uni['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                        <td>
                                            <a href="unidades.php?action=edit&id=<?php echo $uni['id']; ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="unidades.php?toggle=1&id=<?php echo $uni['id']; ?>" class="btn btn-sm btn-<?php echo $uni['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $uni['estado'] ? 'Desactivar' : 'Activar'; ?>"><i class="fas fa-<?php echo $uni['estado'] ? 'times' : 'check'; ?>"></i></a>
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
                        <h2><?php echo $action == 'create' ? 'Crear Nueva Unidad' : 'Editar Unidad'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="id_gerencia">Gerencia</label>
                                <select class="form-control" id="id_gerencia" name="id_gerencia" required>
                                    <option value="">Seleccione una gerencia</option>
                                    <?php foreach ($gerencias as $ger): ?>
                                        <option value="<?php echo $ger['id']; ?>" <?php echo (isset($unidad) && $unidad['id_gerencia'] == $ger['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ger['empresa_nombre'] . ' - ' . $ger['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione una gerencia.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre">Nombre de la Unidad</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($unidad) ? htmlspecialchars($unidad['nombre']) : ''; ?>" required>
                                <div class="invalid-feedback">Por favor ingrese el nombre de la unidad.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($unidad) ? htmlspecialchars($unidad['descripcion']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <a href="unidades.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $action == 'create' ? 'Crear Unidad' : 'Actualizar Unidad'; ?></button>
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
            $('#unidadesTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
                columnDefs: [ { orderable: false, targets: [5] } ]
            });
        });
    </script>
</body>
</html>