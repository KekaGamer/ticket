<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotCliente();

$functions = new Functions();
$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$error = '';
$success_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// --- INICIO FUNCIÓN CORREGIDA Y DEFINITIVA PARA FORMATEAR LA DESCRIPCIÓN ---
function formatTicketDescription($description) {
    $html = '<div class="ticket-description-details">';
    $lines = preg_split('/\\r\\n|\\r|\\n/', trim($description));
    
    $details = [];
    $current_label = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (preg_match('/^\*\*(.*?):\*\*$/s', $line, $matches)) {
            $current_label = htmlspecialchars(trim($matches[1]));
            $details[$current_label] = '';
        } elseif (strpos($line, '[x]') === 0 || strpos($line, '[ ]') === 0) {
            $current_label = null; // Reset for checkboxes
            $checked = (strpos($line, '[x]') === 0);
            $label = htmlspecialchars(trim(substr($line, 3)));
            $details[] = ['type' => 'checkbox', 'label' => $label, 'checked' => $checked];
        } elseif ($current_label !== null) {
            $details[$current_label] .= $line . "\n";
        }
    }

    foreach($details as $label => $value) {
        if (is_array($value) && isset($value['type']) && $value['type'] === 'checkbox') {
            $icon = $value['checked'] ? 'fa-check-square text-success' : 'fa-square text-muted';
            $html .= "<div class='detail-block full-width'><p class='detail-block-content'><i class='fas {$icon}'></i>&nbsp; {$value['label']}</p></div>";
        } else {
            $display_value = !empty(trim($value)) ? nl2br(htmlspecialchars(trim($value))) : '<em>No especificado</em>';
            $html .= "<div class='detail-block'><h5 class='detail-block-header'>{$label}</h5><p class='detail-block-content'>{$display_value}</p></div>";
        }
    }
    
    $html .= '</div>';
    return $html;
}
// --- FIN FUNCIÓN CORREGIDA ---

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'respond' && $id > 0) {
    if ($functions->responderTicket($id, $_SESSION['user_id'], $_POST['mensaje'], $_FILES['adjuntos'] ?? [])) {
        $_SESSION['success_message'] = "Respuesta enviada correctamente.";
    } else {
        $_SESSION['error_message'] = "Error al enviar la respuesta.";
    }
    header("Location: tickets.php?action=view&id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'reopen' && $id > 0) {
    if ($functions->cambiarEstadoTicket($id, 'reabierto', $_POST['mensaje'])) {
        $_SESSION['success_message'] = "Ticket reabierto correctamente.";
    } else { $_SESSION['error_message'] = "Error al reabrir el ticket."; }
    header("Location: tickets.php?action=view&id=$id"); exit();
}

$tickets = []; $ticket = null; $respuestas = [];

if ($action == 'list') {
    $stmt = $conn->prepare("SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_creacion, tec.nombre as tecnico_nombre 
                            FROM tickets t 
                            LEFT JOIN usuarios tec ON t.id_tecnico = tec.id 
                            WHERE t.id_cliente = :id_cliente 
                            ORDER BY t.fecha_creacion DESC");
    $stmt->execute([':id_cliente' => $_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action == 'view' && $id > 0) {
    $stmt = $conn->prepare("SELECT t.*, 
                           cat.nombre as categoria_nombre, 
                           tec.nombre as tecnico_nombre, tec.apellido as tecnico_apellido,
                           cli.nombre as cliente_nombre, cli.apellido as cliente_apellido,
                           emp.nombre as empresa_cliente
                           FROM tickets t
                           LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
                           LEFT JOIN usuarios tec ON t.id_tecnico = tec.id
                           JOIN usuarios cli ON t.id_cliente = cli.id
                           LEFT JOIN empresas emp ON cli.id_empresa = emp.id
                           WHERE t.id = :id AND t.id_cliente = :id_cliente");
    $stmt->execute([':id' => $id, ':id_cliente' => $_SESSION['user_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) { header("Location: tickets.php"); exit(); }
    
    $stmt_resp = $conn->prepare("SELECT r.*, u.nombre, u.apellido, u.rol, u.foto_perfil 
                               FROM respuestas_tickets r
                               JOIN usuarios u ON r.id_usuario = u.id
                               WHERE r.id_ticket = :id_ticket ORDER BY r.fecha_creacion ASC");
    $stmt_resp->execute([':id_ticket' => $id]);
    $respuestas = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Mis Tickets';
include_once '../includes/header.php';
?>
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Mis Tickets</h1>
            <div class="actions">
                <a href="catalogo.php" class="btn btn-primary"><i class="fas fa-plus"></i> Crear Nueva Solicitud</a>
                <?php if ($action == 'view'): ?>
                <a href="tickets.php" class="btn btn-secondary"><i class="fas fa-list"></i> Ver todos mis tickets</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?><div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div><?php endif; ?>
        
        <?php if ($action == 'list'): ?>
            <div class="card">
                <div class="card-body">
                    <table id="ticketsTable" class="display" style="width:100%">
                        <thead><tr><th>ID</th><th>Título</th><th>Técnico</th><th>Prioridad</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td>#<?php echo $t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($t['tecnico_nombre'] ?? 'No asignado'); ?></td>
                                    <td><span class="badge priority-<?php echo $t['prioridad']; ?>"><?php echo ucfirst($t['prioridad']); ?></span></td>
                                    <td><span class="badge status-<?php echo $t['estado']; ?>"><?php echo ucfirst($t['estado']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($t['fecha_creacion'])); ?></td>
                                    <td><a href="tickets.php?action=view&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary" title="Ver"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($action == 'view' && $ticket): ?>
            <div class="ticket-view-header">
                <div class="ticket-title-section">
                    <span class="ticket-id-badge">Ticket <?php echo $ticket['id']; ?></span>
                    <h2><?php echo htmlspecialchars($ticket['titulo']); ?></h2>
                </div>
            </div>
            
            <div class="ticket-summary-bar">
                <div class="summary-item"><span class="summary-label"><i class="fas fa-user"></i> Creado por</span><span class="summary-value"><?php echo htmlspecialchars($ticket['cliente_nombre'] . ' ' . $ticket['cliente_apellido']); ?></span></div>
                <div class="summary-item"><span class="summary-label"><i class="fas fa-building"></i> Empresa</span><span class="summary-value"><?php echo htmlspecialchars($ticket['empresa_cliente'] ?? 'No especificada'); ?></span></div>
                <div class="summary-item"><span class="summary-label"><i class="fas fa-calendar-alt"></i> Fecha de Creación</span><span class="summary-value"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></span></div>
                <div class="summary-item"><span class="summary-label"><i class="fas fa-flag"></i> Estado</span><span class="summary-value"><span class="badge status-<?php echo $ticket['estado']; ?>"><?php echo ucfirst($ticket['estado']); ?></span></span></div>
                <div class="summary-item"><span class="summary-label"><i class="fas fa-exclamation-circle"></i> Prioridad</span><span class="summary-value"><span class="badge priority-<?php echo $ticket['prioridad']; ?>"><?php echo ucfirst($ticket['prioridad']); ?></span></span></div>
            </div>

            <div class="ticket-view-grid">
                <div class="ticket-main-content">
                    <div class="card ticket-card">
                        <div class="card-header"><h4><i class="fas fa-history"></i> Historial de la Solicitud</h4></div>
                        <div class="card-body">
                            <div class="response response-client">
                                <div class="response-body"><?php echo formatTicketDescription($ticket['descripcion']); ?></div>
                            </div>
                            <?php foreach ($respuestas as $respuesta): ?>
                               <div class="response <?php echo ($respuesta['rol'] == 'tecnico' || $respuesta['rol'] == 'admin') ? 'response-technician' : 'response-client'; ?>">
                                   <div class="response-header">
                                        <strong><?php echo htmlspecialchars($respuesta['nombre'] . ' ' . $respuesta['apellido']); ?></strong><small>(<?php echo ucfirst($respuesta['rol']); ?>)</small>
                                        <span class="response-date"><?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?></span>
                                   </div>
                                   <div class="response-body"><?php echo nl2br(htmlspecialchars($respuesta['mensaje'])); ?></div>
                               </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($ticket['estado'] != 'cerrado'): ?>
                    <div class="card ticket-card">
                        <div class="card-header"><h3><i class="fas fa-reply"></i> Añadir una Respuesta</h3></div>
                        <div class="card-body">
                            <form method="POST" action="tickets.php?action=respond&id=<?php echo $ticket['id']; ?>" enctype="multipart/form-data">
                                <div class="form-group">
                                    <textarea class="form-control" name="mensaje" rows="10" required placeholder="Escribe tu mensaje aquí..."></textarea>
                                </div>
                                <div class="form-actions">
                                    <div class="file-upload-wrapper">
                                        <label for="adjuntos" class="file-upload-label"><i class="fas fa-paperclip"></i> Adjuntar Archivos</label>
                                        <input type="file" id="adjuntos" name="adjuntos[]" multiple>
                                        <span id="file-name-display" class="file-name-display"></span>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Respuesta</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ticket-sidebar">
                    <div class="card ticket-card">
                        <div class="card-header"><h4><i class="fas fa-clipboard-list"></i> Detalles del Ticket</h4></div>
                        <div class="card-body">
                            <div class="ticket-details">
                                <div class="detail-row"><div class="detail-label">Categoría</div><div class="detail-value"><?php echo htmlspecialchars($ticket['categoria_nombre'] ?? 'Sin categoría'); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Técnico Asignado</div><div class="detail-value"><?php echo $ticket['tecnico_nombre'] ? htmlspecialchars($ticket['tecnico_nombre'] . ' ' . $ticket['tecnico_apellido']) : 'No asignado'; ?></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#ticketsTable').DataTable({ 
            language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
            order: [[ 0, "desc" ]]
        });
        $('#adjuntos').change(function() {
            var files = $(this)[0].files;
            var fileNames = Array.from(files).map(f => f.name).join(', ');
            $('#file-name-display').text(fileNames || 'No hay archivos seleccionados');
        });
    });
</script>
</body>
</html>
