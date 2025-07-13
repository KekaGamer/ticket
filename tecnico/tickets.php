<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotTecnico();
$functions = new Functions();
$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

if ($action == 'assign_me' && $id > 0) {
    if ($functions->asignarTicket($id, $userId, "El técnico {$_SESSION['user_name']} se ha asignado este ticket.", $userId)) {
        $_SESSION['success_message'] = "Te has asignado el ticket #$id correctamente.";
    } else {
        $_SESSION['error_message'] = "Error al intentar asignarte el ticket.";
    }
    header("Location: tickets.php?action=view&id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'respond' && $id > 0) {
        $mensaje = $_POST['mensaje'];
        $adjuntos = $_FILES['adjuntos'] ?? [];
        if ($functions->responderTicket($id, $userId, $mensaje, $adjuntos)) {
            $_SESSION['success_message'] = "Respuesta enviada correctamente.";
            header("Location: tickets.php?action=view&id=$id");
            exit();
        } else {
            $error = "Error al enviar la respuesta.";
        }
    } elseif ($action == 'change_status' && $id > 0) {
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
        $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_STRING);
        if ($functions->cambiarEstadoTicket($id, $estado, $comentario, $userId)) {
            $_SESSION['success_message'] = "Estado del ticket actualizado.";
            header("Location: tickets.php?action=view&id=$id");
            exit();
        } else {
            $error = "Error al actualizar el estado del ticket.";
        }
    }
}

if (($action == 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT t.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, cat.nombre as categoria_nombre FROM tickets t JOIN usuarios c ON t.id_cliente = c.id LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id WHERE t.id = :id AND (t.id_tecnico = :user_id OR t.id_area IN (SELECT id_area FROM usuarios WHERE id = :user_id))");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) { header("Location: tickets.php"); exit(); }

    $stmt_resp = $conn->prepare("SELECT r.*, u.nombre, u.apellido, u.rol FROM respuestas_tickets r JOIN usuarios u ON r.id_usuario = u.id WHERE r.id_ticket = :id_ticket ORDER BY r.fecha_creacion");
    $stmt_resp->execute([':id_ticket' => $id]);
    $respuestas = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
}

if ($action == 'list') {
    $filtro_estado = $_GET['estado'] ?? '';
    $query = "SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_creacion, c.nombre as cliente_nombre, c.apellido as cliente_apellido FROM tickets t JOIN usuarios c ON t.id_cliente = c.id WHERE t.id_area IN (SELECT id_area FROM usuarios WHERE id = :user_id)";
    $params = [':user_id' => $userId];
    if ($filtro_estado) { $query .= " AND t.estado = :estado"; $params[':estado'] = $filtro_estado; }
    $query .= " ORDER BY t.fecha_creacion DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <?php if ($action != 'list'): ?>
                <a href="tickets.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
                <div class="card-header filters">
                    <form method="GET" class="form-inline">
                         <div class="form-group">
                            <label for="estado">Estado:</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="">Todos</option>
                                <option value="abierto" <?php echo ($filtro_estado == 'abierto') ? 'selected' : ''; ?>>Abiertos</option>
                                <option value="pendiente" <?php echo ($filtro_estado == 'pendiente') ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="cerrado" <?php echo ($filtro_estado == 'cerrado') ? 'selected' : ''; ?>>Cerrados</option>
                                <option value="reabierto" <?php echo ($filtro_estado == 'reabierto') ? 'selected' : ''; ?>>Reabiertos</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                    </form>
                </div>
                <div class="card-body">
                    <table id="ticketsTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Cliente</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td>#<?php echo $t['id']; ?></td>
                                <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($t['cliente_nombre'] . ' ' . $t['cliente_apellido']); ?></td>
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
        <?php elseif ($action == 'view' && isset($ticket)): ?>
            <div class="ticket-view">
                <div class="ticket-header">
                    <div class="ticket-info">
                        <h2>Ticket #<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['titulo']); ?></h2>
                        <div class="ticket-meta">
                            <span class="badge status-<?php echo $ticket['estado']; ?>"><?php echo ucfirst($ticket['estado']); ?></span>
                            <span class="badge priority-<?php echo $ticket['prioridad']; ?>"><?php echo ucfirst($ticket['prioridad']); ?></span>
                        </div>
                    </div>
                    <div class="ticket-actions">
                        <?php if ($ticket['id_tecnico'] === null && $ticket['estado'] != 'cerrado'): ?>
                            <a href="tickets.php?action=assign_me&id=<?php echo $ticket['id']; ?>" class="btn btn-success"><i class="fas fa-user-check"></i> Asignarme Ticket</a>
                        <?php endif; ?>
                        <?php if ($ticket['estado'] != 'cerrado'): ?>
                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#changeStatusModal"><i class="fas fa-exchange-alt"></i> Cambiar Estado</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ticket-responses">
                    <h4>Historial del Ticket</h4>
                    <?php foreach ($respuestas as $respuesta): ?>
                        <div class="response <?php echo ($respuesta['rol'] == 'tecnico' || $respuesta['rol'] == 'admin') ? 'response-technician' : 'response-client'; ?>">
                            <div class="response-header"><strong><?php echo htmlspecialchars($respuesta['nombre'] . ' ' . $respuesta['apellido']); ?></strong></div>
                            <div class="response-body"><?php echo nl2br(htmlspecialchars($respuesta['mensaje'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($ticket['estado'] != 'cerrado' && $ticket['id_tecnico'] == $userId): ?>
                    <div class="response-form">
                        <h4>Responder</h4>
                        <form method="POST" action="tickets.php?action=respond&id=<?php echo $ticket['id']; ?>" enctype="multipart/form-data">
                            <div class="form-group"><textarea class="form-control" name="mensaje" rows="5" required></textarea></div>
                            <div class="form-group">
                                <div class="custom-file"><input type="file" name="adjuntos[]" multiple class="custom-file-input" id="adjuntos"><label class="custom-file-label" for="adjuntos">Adjuntar...</label></div>
                            </div>
                            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button></div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal fade" id="changeStatusModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="tickets.php?action=change_status&id=<?php echo $ticket['id']; ?>">
                            <div class="modal-header"><h5 class="modal-title">Cambiar Estado</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="estado">Nuevo Estado</label>
                                    <select class="form-control" name="estado" required>
                                        <option value="abierto" <?php if($ticket['estado'] == 'abierto') echo 'selected'; ?>>Abierto</option>
                                        <option value="pendiente" <?php if($ticket['estado'] == 'pendiente') echo 'selected'; ?>>Pendiente</option>
                                        <option value="cerrado">Cerrado</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="comentario">Comentario (opcional)</label>
                                    <textarea class="form-control" name="comentario" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script>
$(document).ready(function() {
    $('#ticketsTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' } });
});
</script>
</body>
</html>