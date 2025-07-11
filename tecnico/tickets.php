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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'respond' && $id > 0) {
        $mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING);
        $adjuntos = $_FILES['adjuntos'] ?? [];
        
        if ($functions->responderTicket($id, $_SESSION['user_id'], $mensaje, $adjuntos)) {
            $_SESSION['success_message'] = "Respuesta enviada correctamente.";
            header("Location: tickets.php?action=view&id=$id");
            exit();
        } else {
            $error = "Error al enviar la respuesta.";
        }
    } elseif ($action == 'change_status' && $id > 0) {
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
        $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_STRING);
        
        if ($functions->cambiarEstadoTicket($id, $estado, $comentario)) {
            $_SESSION['success_message'] = "Estado del ticket actualizado correctamente.";
            header("Location: tickets.php?action=view&id=$id");
            exit();
        } else {
            $error = "Error al actualizar el estado del ticket.";
        }
    }
}

// Obtener datos del ticket para vista/edición
if (($action == 'view' || $action == 'respond' || $action == 'change_status') && $id > 0) {
    $stmt = $conn->prepare("SELECT t.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.email as cliente_email,
                           cat.nombre as categoria_nombre,
                           e.nombre as empresa_nombre,
                           a.nombre as area_nombre
                           FROM tickets t
                           JOIN usuarios c ON t.id_cliente = c.id
                           LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
                           JOIN empresas e ON t.id_empresa = e.id
                           JOIN areas a ON t.id_area = a.id
                           WHERE t.id = :id AND (t.id_tecnico = :user_id OR t.id_tecnico IS NULL) AND t.id_area IN (SELECT id_area FROM usuarios WHERE id = :user_id)");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        header("Location: tickets.php");
        exit();
    }
    
    // Obtener respuestas del ticket
    $stmt = $conn->prepare("SELECT r.*, u.nombre, u.apellido, u.rol, u.foto_perfil 
                           FROM respuestas_tickets r
                           JOIN usuarios u ON r.id_usuario = u.id
                           WHERE r.id_ticket = :id_ticket
                           ORDER BY r.fecha_creacion");
    $stmt->bindParam(':id_ticket', $id);
    $stmt->execute();
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener adjuntos para cada respuesta
    foreach ($respuestas as &$respuesta) {
        $stmt = $conn->prepare("SELECT * FROM adjuntos WHERE id_respuesta = :id_respuesta");
        $stmt->bindParam(':id_respuesta', $respuesta['id']);
        $stmt->execute();
        $respuesta['adjuntos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Listar tickets asignados al técnico o de su área
if ($action == 'list') {
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_prioridad = $_GET['prioridad'] ?? '';
    
    $query = "SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_creacion, t.fecha_cierre,
              c.nombre as cliente_nombre, c.apellido as cliente_apellido,
              cat.nombre as categoria_nombre
              FROM tickets t
              JOIN usuarios c ON t.id_cliente = c.id
              LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
              WHERE (t.id_tecnico = :user_id OR t.id_tecnico IS NULL) AND t.id_area IN (SELECT id_area FROM usuarios WHERE id = :user_id)";
    
    $params = [':user_id' => $_SESSION['user_id']];
    
    if ($filtro_estado) {
        $query .= " AND t.estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
    
    if ($filtro_prioridad) {
        $query .= " AND t.prioridad = :prioridad";
        $params[':prioridad'] = $filtro_prioridad;
    }
    
    $query .= " ORDER BY t.fecha_creacion DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tickets.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Mis Tickets</h1>
                <div class="actions">
                    <a href="tickets.php?action=list" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Ver Todos
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
                    <div class="card-header">
                        <h2>Listado de Tickets</h2>
                        <div class="filters">
                            <form method="GET" class="form-inline">
                                <input type="hidden" name="action" value="list">
                                
                                <div class="form-group">
                                    <label for="estado">Estado:</label>
                                    <select id="estado" name="estado" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="abierto" <?php echo $filtro_estado == 'abierto' ? 'selected' : ''; ?>>Abiertos</option>
                                        <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                        <option value="cerrado" <?php echo $filtro_estado == 'cerrado' ? 'selected' : ''; ?>>Cerrados</option>
                                        <option value="reabierto" <?php echo $filtro_estado == 'reabierto' ? 'selected' : ''; ?>>Reabiertos</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="prioridad">Prioridad:</label>
                                    <select id="prioridad" name="prioridad" class="form-control">
                                        <option value="">Todas</option>
                                        <option value="baja" <?php echo $filtro_prioridad == 'baja' ? 'selected' : ''; ?>>Baja</option>
                                        <option value="media" <?php echo $filtro_prioridad == 'media' ? 'selected' : ''; ?>>Media</option>
                                        <option value="alta" <?php echo $filtro_prioridad == 'alta' ? 'selected' : ''; ?>>Alta</option>
                                        <option value="critica" <?php echo $filtro_prioridad == 'critica' ? 'selected' : ''; ?>>Crítica</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="ticketsTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Cliente</th>
                                    <th>Categoría</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $t): ?>
                                    <tr>
                                        <td><?php echo $t['id']; ?></td>
                                        <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($t['cliente_nombre'] . ' ' . $t['cliente_apellido']); ?></td>
                                        <td><?php echo $t['categoria_nombre'] ?? 'N/A'; ?></td>
                                        <td>
                                            <span class="badge priority-<?php echo $t['prioridad']; ?>">
                                                                                                <?php echo ucfirst($t['prioridad']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $t['estado']; ?>">
                                                <?php echo ucfirst($t['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $functions->formatDate($t['fecha_creacion']); ?></td>
                                        <td>
                                            <a href="tickets.php?action=view&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($t['estado'] != 'cerrado'): ?>
                                                <a href="tickets.php?action=respond&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-success" title="Responder">
                                                    <i class="fas fa-reply"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($action == 'view' && isset($ticket)): ?>
                <div class="ticket-view">
                    <div class="ticket-header">
                        <div class="ticket-title">
                            <h2>Ticket #<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['titulo']); ?></h2>
                            <div class="ticket-meta">
                                <span class="badge priority-<?php echo $ticket['prioridad']; ?>">
                                    <?php echo ucfirst($ticket['prioridad']); ?>
                                </span>
                                <span class="badge status-<?php echo $ticket['estado']; ?>">
                                    <?php echo ucfirst($ticket['estado']); ?>
                                </span>
                                <span class="ticket-date">
                                    <i class="far fa-calendar-alt"></i> <?php echo $functions->formatDate($ticket['fecha_creacion']); ?>
                                </span>
                                <?php if ($ticket['fecha_cierre']): ?>
                                    <span class="ticket-date">
                                        <i class="far fa-calendar-times"></i> Cerrado: <?php echo $functions->formatDate($ticket['fecha_cierre']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="ticket-actions">
                            <?php if ($ticket['estado'] != 'cerrado'): ?>
                                <a href="tickets.php?action=respond&id=<?php echo $ticket['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-reply"></i> Responder
                                </a>
                                <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#changeStatusModal">
                                    <i class="fas fa-exchange-alt"></i> Cambiar Estado
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#reopenModal">
                                    <i class="fas fa-door-open"></i> Reabrir
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="ticket-details">
                        <div class="ticket-client">
                            <div class="client-info">
                                <h3>Cliente</h3>
                                <p><?php echo htmlspecialchars($ticket['cliente_nombre'] . ' ' . $ticket['cliente_apellido']); ?></p>
                                <p><a href="mailto:<?php echo $ticket['cliente_email']; ?>"><?php echo $ticket['cliente_email']; ?></a></p>
                            </div>
                            <div class="ticket-meta-info">
                                <h3>Información Adicional</h3>
                                <p><strong>Empresa:</strong> <?php echo $ticket['empresa_nombre']; ?></p>
                                <p><strong>Área:</strong> <?php echo $ticket['area_nombre']; ?></p>
                                <p><strong>Categoría:</strong> <?php echo $ticket['categoria_nombre'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                        
                        <div class="ticket-description">
                            <h3>Descripción</h3>
                            <div class="description-content">
                                <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($ticket['adjuntos'])): ?>
                            <div class="ticket-attachments">
                                <h3>Archivos Adjuntos</h3>
                                <div class="attachments-list">
                                    <?php foreach ($ticket['adjuntos'] as $adjunto): ?>
                                        <div class="attachment-item">
                                            <a href="../uploads/<?php echo $adjunto['nombre_archivo']; ?>" target="_blank" download>
                                                <i class="fas fa-paperclip"></i> <?php echo $adjunto['nombre_original']; ?>
                                            </a>
                                            <span>(<?php echo $functions->formatFileSize($adjunto['tamanio']); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ticket-responses">
                        <h3>Conversación</h3>
                        <?php if (empty($respuestas)): ?>
                            <div class="no-responses">
                                <p>No hay respuestas aún.</p>
                            </div>
                        <?php else: ?>
                            <div class="responses-list">
                                <?php foreach ($respuestas as $respuesta): ?>
                                    <div class="response-item <?php echo $respuesta['rol'] == 'tecnico' ? 'response-technician' : 'response-client'; ?>">
                                        <div class="response-header">
                                            <div class="response-user">
                                                <img src="../uploads/avatars/<?php echo $respuesta['foto_perfil'] ?? 'default.png'; ?>" alt="Avatar" class="user-avatar">
                                                <div class="user-info">
                                                    <strong><?php echo htmlspecialchars($respuesta['nombre'] . ' ' . $respuesta['apellido']); ?></strong>
                                                    <span class="user-role"><?php echo ucfirst($respuesta['rol']); ?></span>
                                                </div>
                                            </div>
                                            <div class="response-date">
                                                <?php echo $functions->formatDateTime($respuesta['fecha_creacion']); ?>
                                            </div>
                                        </div>
                                        <div class="response-content">
                                            <?php echo nl2br(htmlspecialchars($respuesta['mensaje'])); ?>
                                        </div>
                                        <?php if (!empty($respuesta['adjuntos'])): ?>
                                            <div class="response-attachments">
                                                <strong>Adjuntos:</strong>
                                                <?php foreach ($respuesta['adjuntos'] as $adjunto): ?>
                                                    <div class="attachment-item">
                                                        <a href="../uploads/<?php echo $adjunto['nombre_archivo']; ?>" target="_blank" download>
                                                            <i class="fas fa-paperclip"></i> <?php echo $adjunto['nombre_original']; ?>
                                                        </a>
                                                        <span>(<?php echo $functions->formatFileSize($adjunto['tamanio']); ?>)</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Modal para cambiar estado -->
                <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="tickets.php?action=change_status&id=<?php echo $ticket['id']; ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeStatusModalLabel">Cambiar Estado del Ticket</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="estado">Nuevo Estado:</label>
                                        <select class="form-control" id="estado" name="estado" required>
                                            <option value="pendiente" <?php echo $ticket['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="en_proceso" <?php echo $ticket['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                            <option value="cerrado" <?php echo $ticket['estado'] == 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="comentario">Comentario (Opcional):</label>
                                        <textarea class="form-control" id="comentario" name="comentario" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Modal para reabrir ticket -->
                <div class="modal fade" id="reopenModal" tabindex="-1" role="dialog" aria-labelledby="reopenModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="tickets.php?action=change_status&id=<?php echo $ticket['id']; ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="reopenModalLabel">Reabrir Ticket</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="estado" value="reabierto">
                                    <div class="form-group">
                                        <label for="comentario">Razón para reabrir:</label>
                                        <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-warning">Reabrir Ticket</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action == 'respond' && isset($ticket)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Responder Ticket #<?php echo $ticket['id']; ?></h2>
                        <a href="tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al ticket
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="mensaje">Mensaje:</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="6" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="adjuntos">Adjuntar archivos:</label>
                                <input type="file" class="form-control-file" id="adjuntos" name="adjuntos[]" multiple>
                                <small class="form-text text-muted">Puedes seleccionar múltiples archivos (máximo 10MB cada uno).</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Enviar Respuesta
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#ticketsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                "order": [[0, "desc"]]
            });
            
            $('.alert').on('click', '.close', function() {
                $(this).parent().fadeOut();
            });
        });
    </script>
</body>
</html>