<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotCliente();

$db = new Database();
$conn = $db->getConnection();

// --- CONSULTA ACTUALIZADA: Ahora también obtenemos el campo del icono ---
$stmt = $conn->query("SELECT id, nombre, descripcion, icono_fa FROM categorias_tickets WHERE parent_id IS NULL AND estado = 1 ORDER BY nombre");
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Catálogo de Solicitudes";
include '../includes/header.php';
?>
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
        </div>

        <p class="text-muted">Bienvenido al portal de autoservicio. Seleccione una de las siguientes opciones para iniciar una solicitud.</p>

        <div class="service-catalog-grid">
            <?php if (empty($servicios)): ?>
                <div class="alert alert-info">No hay servicios disponibles en este momento. Por favor, contacte al administrador.</div>
            <?php else: ?>
                <?php foreach ($servicios as $servicio): ?>
                    <div class="service-card">
                        <div class="service-card-icon">
                            <i class="<?php echo htmlspecialchars($servicio['icono_fa'] ?? 'fas fa-concierge-bell'); ?>"></i>
                            </div>
                        <div class="service-card-body">
                            <h3 class="service-card-title"><?php echo htmlspecialchars($servicio['nombre']); ?></h3>
                            <p class="service-card-description"><?php echo htmlspecialchars($servicio['descripcion'] ?? 'Haga clic para iniciar esta solicitud.'); ?></p>
                        </div>
                        <div class="service-card-footer">
                            <a href="solicitud.php?servicio_id=<?php echo $servicio['id']; ?>" class="btn btn-primary btn-sm">Iniciar Solicitud</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
