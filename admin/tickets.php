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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'assign' && $id > 0) {
        $id_tecnico = filter_input(INPUT_POST, 'id_tecnico', FILTER_SANITIZE_NUMBER_INT);
        $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_STRING);
        
        if ($functions->asignarTicket($id, $id_tecnico, $comentario, $_SESSION['user_id'])) {
            $_SESSION['success_message'] = "Ticket asignado correctamente.";
            header("Location: tickets.php?action=view&id=$id");
            exit();
        } else {
            $error = "Error al asignar el ticket.";
        }
    } elseif ($action == 'respond' && $id > 0) {
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
if (($action == 'view' || $action == 'assign' || $action == 'respond' || $action == 'change_status') && $id > 0) {
    $stmt = $conn->prepare("SELECT t.*, 
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.email as cliente_email,
                           tec.nombre as tecnico_nombre, tec.apellido as tecnico_apellido,
                           cat.nombre as categoria_nombre,
                           e.nombre as empresa_nombre,
                           a.nombre as area_nombre
                           FROM tickets t
                           JOIN usuarios c ON t.id_cliente = c.id
                           LEFT JOIN usuarios tec ON t.id_tecnico = tec.id
                           LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
                           JOIN empresas e ON t.id_empresa = e.id
                           JOIN areas a ON t.id_area = a.id
                           WHERE t.id = :id");
    $stmt->bindParam(':id', $id);
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

// Listar tickets
if ($action == 'list') {
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_prioridad = $_GET['prioridad'] ?? '';
    $filtro_tecnico = $_GET['tecnico'] ?? '';
    
    $query = "SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_creacion,
              c.nombre as cliente_nombre, c.apellido as cliente_apellido,
              tec.nombre as tecnico_nombre, tec.apellido as tecnico_apellido,
              cat.nombre as categoria_nombre
              FROM tickets t
              JOIN usuarios c ON t.id_cliente = c.id
              LEFT JOIN usuarios tec ON t.id_tecnico = tec.id
              LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
              WHERE 1=1";
    
    $params = [];
    
    if ($filtro_estado) {
        $query .= " AND t.estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
    
    if ($filtro_prioridad) {
        $query .= " AND t.prioridad = :prioridad";
        $params[':prioridad'] = $filtro_prioridad;
    }
    
    if ($filtro_tecnico) {
        $query .= " AND t.id_tecnico = :tecnico";
        $params[':tecnico'] = $filtro_tecnico;
    }
    
    $query .= " ORDER BY t.fecha_creacion DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener técnicos para asignación
$tecnicos = $conn->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'tecnico' AND estado = 1 ORDER BY nombre, apellido")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tickets - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tickets.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Gestión de Tickets</h1>
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
                                
                                <div class="form-group">
                                    <label for="tecnico">Técnico:</label>
                                    <select id="tecnico" name="tecnico" class="form-control">
                                        <option value="">Todos</option>
                                        <?php foreach ($tecnicos as $tec): ?>
                                            <option value="<?php echo $tec['id']; ?>" <?php echo $filtro_tecnico == $tec['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tec['nombre'] . ' ' . $tec['apellido']); ?>
                                            </option>
                                        <?php endforeach; ?>
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
                                    <th>Técnico</th>
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
                                        <td><?php echo $t['tecnico_nombre'] ? htmlspecialchars($t['tecnico_nombre'] . ' ' . $t['tecnico_apellido']) : 'No asignado'; ?></td>
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
                                        <td><?php echo date('d/m/Y H:i', strtotime($t['fecha_creacion'])); ?></td>
                                        <td>
                                            <a href="tickets.php?action=view&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($t['estado'] != 'cerrado'): ?>
                                                <a href="tickets.php?action=assign&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-warning" title="Asignar">
                                                    <i class="fas fa-user-tag"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($action == 'view'): ?>
                <div class="ticket-view">
                    <div class="ticket-header">
                        <div class="ticket-info">
                            <h2>Ticket #<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['titulo']); ?></h2>
                            <div class="ticket-meta">
                                <span class="badge status-<?php echo $ticket['estado']; ?>"><?php echo ucfirst($ticket['estado']); ?></span>
                                <span class="badge priority-<?php echo $ticket['prioridad']; ?>"><?php echo ucfirst($ticket['prioridad']); ?></span>
                                <span class="ticket-category"><?php echo $ticket['categoria_nombre'] ?? 'Sin categoría'; ?></span>
                            </div>
                        </div>
                        <div class="ticket-actions">
                            <?php if ($ticket['estado'] != 'cerrado'): ?>
                                <a href="tickets.php?action=assign&id=<?php echo $ticket['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-user-tag"></i> Asignar
                                </a>
                                <a href="tickets.php?action=change_status&id=<?php echo $ticket['id']; ?>" class="btn btn-info">
                                    <i class="fas fa-exchange-alt"></i> Cambiar Estado
                                </a>
                            <?php endif; ?>
                            <a href="tickets.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    
                    <div class="ticket-details">
                        <div class="detail-row">
                            <div class="detail-label">Cliente:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($ticket['cliente_nombre'] . ' ' . $ticket['cliente_apellido']); ?>
                                <small><?php echo htmlspecialchars($ticket['cliente_email']); ?></small>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Empresa/Área:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($ticket['empresa_nombre'] . ' - ' . $ticket['area_nombre']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Técnico Asignado:</div>
                            <div class="detail-value">
                                <?php echo $ticket['tecnico_nombre'] ? htmlspecialchars($ticket['tecnico_nombre'] . ' ' . $ticket['tecnico_apellido']) : 'No asignado'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Fecha Creación:</div>
                            <div class="detail-value">
                                <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($ticket['fecha_cierre']): ?>
                            <div class="detail-row">
                                <div class="detail-label">Fecha Cierre:</div>
                                <div class="detail-value">
                                    <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-row full-width">
                            <div class="detail-label">Descripción:</div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ticket-responses">
                        <h3>Historial del Ticket</h3>
                        
                        <?php foreach ($respuestas as $respuesta): ?>
                            <div class="response <?php echo $respuesta['rol'] == 'tecnico' ? 'response-technician' : ($respuesta['rol'] == 'admin' ? 'response-admin' : 'response-client'); ?>">
                                <div class="response-header">
                                    <div class="response-user">
                                        <?php if ($respuesta['foto_perfil']): ?>
                                            <img src="../assets/img/users/<?php echo htmlspecialchars($respuesta['foto_perfil']); ?>" alt="Foto perfil" class="user-avatar-sm">
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($respuesta['nombre'] . ' ' . $respuesta['apellido']); ?></strong>
                                        <small>(<?php echo ucfirst($respuesta['rol']); ?>)</small>
                                    </div>
                                    <div class="response-date">
                                        <?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?>
                                    </div>
                                </div>
                                <div class="response-body">
                                    <p><?php echo nl2br(htmlspecialchars($respuesta['mensaje'])); ?></p>
                                    
                                    <?php if (!empty($respuesta['adjuntos'])): ?>
                                        <div class="response-attachments">
                                            <strong>Adjuntos:</strong>
                                            <div class="attachments-list">
                                                <?php foreach ($respuesta['adjuntos'] as $adjunto): ?>
                                                    <div class="attachment-item">
                                                        <a href="../assets/uploads/<?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>" target="_blank" download>
                                                            <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($ticket['estado'] != 'cerrado'): ?>
                            <div class="response-form">
                                <h4>Responder Ticket</h4>
                                <form method="POST" action="tickets.php?action=respond&id=<?php echo $ticket['id']; ?>" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <textarea class="form-control" name="mensaje" rows="4" required placeholder="Escribe tu respuesta aquí..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="adjuntos">Adjuntar archivos:</label>
                                        <input type="file" id="adjuntos" name="adjuntos[]" multiple class="form-control-file">
                                        <small class="form-text text-muted">Puedes seleccionar múltiples archivos (máx. 10MB cada uno)</small>
                                    </div>
                                    <div class="form-group text-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Enviar Respuesta
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($action == 'assign'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Asignar Ticket #<?php echo $ticket['id']; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="id_tecnico">Seleccionar Técnico</label>
                                <select class="form-control" id="id_tecnico" name="id_tecnico" required>
                                    <option value="">Seleccione un técnico</option>
                                    <?php foreach ($tecnicos as $tec): ?>
                                        <option value="<?php echo $tec['id']; ?>">
                                            <?php echo htmlspecialchars($tec['nombre'] . ' ' . $tec['apellido']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un técnico para asignar el ticket.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comentario">Comentario (Opcional)</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="3" placeholder="Agregue un comentario para el técnico..."></textarea>
                            </div>
                            
                            <div class="form-group text-right">
                                <a href="tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-tag"></i> Asignar Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action == 'change_status'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Cambiar Estado del Ticket #<?php echo $ticket['id']; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="estado">Nuevo Estado</label>
                                <select class="form-control" id="estado" name="estado" required>
                                    <option value="">Seleccione un estado</option>
                                    <option value="abierto">Abierto</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="cerrado">Cerrado</option>
                                    <option value="reabierto">Reabierto</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un estado para el ticket.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comentario">Comentario (Opcional)</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="3" placeholder="Agregue un comentario sobre el cambio de estado..."></textarea>
                            </div>
                            
                            <div class="form-group text-right">
                                <a href="tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-exchange-alt"></i> Cambiar Estado
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
            $('#ticketsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                columnDefs: [
                    { orderable: false, targets: [8] }
                ],
                order: [[0, 'desc']]
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