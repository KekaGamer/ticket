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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    
    if ($action == 'create') {
        if ($functions->crearEmpresa($nombre, $direccion, $telefono)) {
            $_SESSION['success_message'] = "Empresa creada correctamente.";
            header("Location: empresas.php");
            exit();
        } else {
            $error = "Error al crear la empresa.";
        }
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE empresas SET nombre = :nombre, direccion = :direccion, telefono = :telefono WHERE id = :id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Empresa actualizada correctamente.";
            header("Location: empresas.php");
            exit();
        } else {
            $error = "Error al actualizar la empresa.";
        }
    }
}

if (isset($_GET['toggle']) && $id > 0) {
    $stmt = $conn->prepare("UPDATE empresas SET estado = NOT estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header("Location: empresas.php");
    exit();
}

if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM empresas WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empresa) {
        header("Location: empresas.php");
        exit();
    }
}

$stmt = $conn->query("SELECT * FROM empresas ORDER BY nombre");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - <?php echo SITE_NAME; ?></title>
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
                <h1>Gestión de Empresas</h1>
                <div class="actions">
                    <?php if ($action != 'list'): ?>
                    <a href="empresas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                    <?php else: ?>
                    <a href="empresas.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Empresa</a>
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
                        <table id="empresasTable" class="display" style="width:100%">
                            </table>
                    </div>
                </div>
            <?php elseif ($action == 'create' || $action == 'edit'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo $action == 'create' ? 'Crear Nueva Empresa' : 'Editar Empresa'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="nombre">Nombre de la Empresa</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($empresa) ? htmlspecialchars($empresa['nombre']) : ''; ?>" required>
                                <div class="invalid-feedback">Por favor ingrese el nombre de la empresa.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="3"><?php echo isset($empresa) ? htmlspecialchars($empresa['direccion']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo isset($empresa) ? htmlspecialchars($empresa['telefono']) : ''; ?>">
                            </div>
                            
                            <div class="form-actions">
                                <a href="empresas.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $action == 'create' ? 'Crear Empresa' : 'Actualizar Empresa'; ?>
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
    </body>
</html>