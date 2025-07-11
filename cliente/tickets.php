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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create') {
        $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
        $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_SANITIZE_NUMBER_INT);
        $prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_SANITIZE_STRING);
        $adjuntos = $_FILES['adjuntos'] ?? [];
        
        // Obtener empresa y área del usuario
        $stmt = $conn->prepare("SELECT id_empresa, id_area FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userInfo) {
            $ticketId = $functions->crearTicket(
                $_SESSION['user_id'],
                $userInfo['id_empresa'],
                $userInfo['id_area'],
                $titulo,
                $descripcion,
                $prioridad,
                $id_categoria
            );
            
            if ($ticketId) {
                // Procesar adjuntos iniciales
                if (!empty($adjuntos)) {
                    foreach ($adjuntos['tmp_name'] as $key => $tmp_name) {
                        if ($adjuntos['error'][$key] == UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $adjuntos['name'][$key],
                                'tmp_name' => $adjuntos['tmp_name'][$key],
                                'type' => $adjuntos['type'][$key],
                                'size' => $adjuntos['size'][$key]
                            ];
                            $functions->guardarAdjunto(null, $ticketId, $file);
                        }
                    }
                }
                
                $_SESSION['success_message'] = "Ticket creado correctamente. Número de ticket: #$ticketId";
                header("Location: tickets.php?action=view&id=$ticketId");
                exit();
            } else {
                $error = "Error al crear el ticket. Por favor intente nuevamente.";
            }
        } else {
            $error = "No se pudo determinar su empresa/área. Contacte al administrador.";
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
    } elseif ($action == 'reopen' && $id > 0) {
        $mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING);
        
        if ($functions->cambiarEstadoTicket($id, 'reabierto', $mensaje)) {
            $_SESSION['success_message'] = "Ticket reabierto correctamente.";
            header("Location: tickets.php?action=view&id=$id");
            exit();
        } else {
            $error = "Error al reabrir el ticket.";
        }
    }
}

// Obtener datos del ticket para vista/edición
if (($action == 'view' || $action == 'respond' || $action == 'reopen') && $id > 0) {
    $stmt = $conn->prepare("SELECT t.*, 
                           tec.nombre as tecnico_nombre, tec.apellido as tecnico_apellido,
                           cat.nombre as categoria_nombre,
                           e.nombre as empresa_nombre,
                           a.nombre as area_nombre
                           FROM tickets t
                           LEFT JOIN usuarios tec ON t.id_tecnico = tec.id
                           LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
                           JOIN empresas e ON t.id_empresa = e.id
                           JOIN areas a ON t.id_area = a.id
                           WHERE t.id = :id AND t.id_cliente = :id_cliente");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':id_cliente', $_SESSION['user_id']);
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
    
    // Obtener adjuntos iniciales del ticket
    $stmt = $conn->prepare("SELECT * FROM adjuntos WHERE id_ticket = :id_ticket AND id_respuesta IS NULL");
    $stmt->bindParam(':id_ticket', $id);
    $stmt->execute();
    $adjuntosIniciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar tickets del cliente
if ($action == 'list') {
    $filtro_estado = $_GET['estado'] ?? '';
    
    $query = "SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_creacion, t.fecha_cierre,
              tec.nombre as tecnico_nombre, tec.apellido as tecnico_apellido,
              cat.nombre as categoria_nombre
              FROM tickets t
              LEFT JOIN usuarios tec ON t.id_tecnico = tec.id
              LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id
              WHERE t.id_cliente = :id_cliente";
    
    $params = [':id_cliente' => $_SESSION['user_id']];
    
    if ($filtro_estado) {
        $query .= " AND t.estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
    
    $query .= " ORDER BY t.fecha_creacion DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener categorías para nuevo ticket
$categorias = $conn->query("SELECT * FROM categorias_tickets WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
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
                <h1>Mis Tickets</h1>
                <div class="actions">
                    <a href="tickets.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Ticket
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
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($action == 'create'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Crear Nuevo Ticket</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="titulo">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required placeholder="Describa brevemente el problema">
                                <div class="invalid-feedback">
                                    Por favor ingrese un título para el ticket.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_categoria">Categoría</label>
                                <select class="form-control" id="id_categoria" name="id_categoria">
                                    <option value="">Seleccione una categoría (opcional)</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="prioridad">Prioridad</label>
                                <select class="form-control" id="prioridad" name="prioridad" required>
                                    <option value="">Seleccione una prioridad</option>
                                    <option value="baja">Baja</option>
                                    <option value="media" selected>Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione una prioridad para el ticket.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion">Descripción Detallada</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="6" required placeholder="Describa el problema con el mayor detalle posible..."></textarea>
                                <div class="invalid-feedback">
                                    Por favor ingrese una descripción detallada del problema.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="adjuntos">Adjuntar archivos:</label>
                                <input type="file" id="adjuntos" name="adjuntos[]" multiple class="form-control-file">
                                <small class="form-text text-muted">Puedes seleccionar múltiples archivos (máx. 10MB cada uno)</small>
                            </div>
                            
                            <div class="form-group text-right">
                                <a href="tickets.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Enviar Ticket
                                </button>
                            </div>
                        </form>
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
                            <a href="tickets.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    
                    <div class="ticket-details">
                        <div class="detail-row">
                            <div class="detail-label">Técnico Asignado:</div>
                            <div class="detail-value">
                                <?php echo $ticket['tecnico_nombre'] ? htmlspecialchars($ticket['tecnico_nombre'] . ' ' . $ticket['tecnico_apellido']) : 'No asignado'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Empresa/Área:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($ticket['empresa_nombre'] . ' - ' . $ticket['area_nombre']); ?>
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
                        
                        <?php if (!empty($adjuntosIniciales)): ?>
                            <div class="detail-row full-width">
                                <div class="detail-label">Archivos Adjuntos:</div>
                                <div class="detail-value">
                                    <div class="attachments-list">
                                        <?php foreach ($adjuntosIniciales as $adjunto): ?>
                                            <div class="attachment-item">
                                                <a href="../assets/uploads/<?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>" target="_blank" download>
                                                    <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($adjunto['nombre_archivo']); ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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
                        <?php elseif ($ticket['estado'] == 'cerrado'): ?>
                            <div class="response-form">
                                <h4>¿El problema no está resuelto?</h4>
                                <form method="POST" action="tickets.php?action=reopen&id=<?php echo $ticket['id']; ?>">
                                    <div class="form-group">
                                        <textarea class="form-control" name="mensaje" rows="4" required placeholder="Explica por qué el problema no está resuelto..."></textarea>
                                    </div>
                                    <div class="form-group text-right">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-undo"></i> Reabrir Ticket
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
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
                    { orderable: false, targets: [7] }
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