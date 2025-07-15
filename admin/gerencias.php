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
    $id_empresa = filter_input(INPUT_POST, 'id_empresa', FILTER_SANITIZE_NUMBER_INT);
    
    if ($action == 'create') {
        $stmt = $conn->prepare("INSERT INTO gerencias (id_empresa, nombre, descripcion) VALUES (:id_empresa, :nombre, :descripcion)");
        $stmt->bindParam(':id_empresa', $id_empresa);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Gerencia creada correctamente.";
            header("Location: gerencias.php");
            exit();
        } else {
            $error = "Error al crear la gerencia.";
        }
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE gerencias SET id_empresa = :id_empresa, nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        $stmt->bindParam(':id_empresa', $id_empresa);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Gerencia actualizada correctamente.";
            header("Location: gerencias.php");
            exit();
        } else {
            $error = "Error al actualizar la gerencia.";
        }
    }
}

// Cambiar estado
if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE gerencias SET estado = NOT estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: gerencias.php");
    exit();
}

// Obtener datos para edición
if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM gerencias WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $gerencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gerencia) {
        header("Location: gerencias.php");
        exit();
    }
}

// Listar gerencias con información de empresa
$query = "SELECT g.*, e.nombre as empresa_nombre 
          FROM gerencias g
          JOIN empresas e ON g.id_empresa = e.id
          ORDER BY e.nombre, g.nombre";
$stmt = $conn->query($query);
$gerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener empresas para select
$empresas = $conn->query("SELECT * FROM empresas WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$page_title = "Gestión de Gerencias";
include_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Gerencias - <?php echo SITE_NAME; ?></title>
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
                <h1>Gestión de Gerencias</h1>
                <div class="actions">
                    <?php if ($action != 'list'): ?>
                    <a href="gerencias.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                    <?php else: ?>
                    <a href="gerencias.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Gerencia</a>
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
                        <table id="gerenciasTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gerencias as $ger): ?>
                                    <tr>
                                        <td><?php echo $ger['id']; ?></td>
                                        <td><?php echo htmlspecialchars($ger['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($ger['empresa_nombre']); ?></td>
                                        <td><span class="badge <?php echo $ger['estado'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $ger['estado'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                        <td>
                                            <a href="gerencias.php?action=edit&id=<?php echo $ger['id']; ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="gerencias.php?toggle=1&id=<?php echo $ger['id']; ?>" class="btn btn-sm btn-<?php echo $ger['estado'] ? 'warning' : 'success'; ?>" title="<?php echo $ger['estado'] ? 'Desactivar' : 'Activar'; ?>"><i class="fas fa-<?php echo $ger['estado'] ? 'times' : 'check'; ?>"></i></a>
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
                        <h2><?php echo $action == 'create' ? 'Crear Nueva Gerencia' : 'Editar Gerencia'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="id_empresa">Empresa</label>
                                <select class="form-control" id="id_empresa" name="id_empresa" required>
                                    <option value="">Seleccione una empresa</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo (isset($gerencia) && $gerencia['id_empresa'] == $emp['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione una empresa.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre">Nombre de la Gerencia</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($gerencia) ? htmlspecialchars($gerencia['nombre']) : ''; ?>" required>
                                <div class="invalid-feedback">Por favor ingrese el nombre de la gerencia.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($gerencia) ? htmlspecialchars($gerencia['descripcion']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <a href="gerencias.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $action == 'create' ? 'Crear Gerencia' : 'Actualizar Gerencia'; ?>
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
            $('#gerenciasTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' } });
        });
    </script>
</body>
</html>