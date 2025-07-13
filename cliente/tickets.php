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
        $descripcion = $_POST['descripcion']; // Permitir HTML de plantillas
        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_SANITIZE_NUMBER_INT);
        $prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_SANITIZE_STRING);
        $adjuntos = $_FILES['adjuntos'] ?? [];
        
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
                if (!empty($adjuntos['name'][0])) {
                    $functions->guardarAdjuntos(null, $ticketId, $adjuntos);
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
        $mensaje = $_POST['mensaje'];
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
    
    $stmt_resp = $conn->prepare("SELECT r.*, u.nombre, u.apellido, u.rol, u.foto_perfil 
                               FROM respuestas_tickets r
                               JOIN usuarios u ON r.id_usuario = u.id
                               WHERE r.id_ticket = :id_ticket ORDER BY r.fecha_creacion");
    $stmt_resp->bindParam(':id_ticket', $id);
    $stmt_resp->execute();
    $respuestas = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($respuestas as &$respuesta) {
        $stmt_adj = $conn->prepare("SELECT * FROM adjuntos WHERE id_respuesta = :id_respuesta");
        $stmt_adj->bindParam(':id_respuesta', $respuesta['id']);
        $stmt_adj->execute();
        $respuesta['adjuntos'] = $stmt_adj->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $stmt_adj_ini = $conn->prepare("SELECT * FROM adjuntos WHERE id_ticket = :id_ticket AND id_respuesta IS NULL");
    $stmt_adj_ini->bindParam(':id_ticket', $id);
    $stmt_adj_ini->execute();
    $adjuntosIniciales = $stmt_adj_ini->fetchAll(PDO::FETCH_ASSOC);
}

// Listar tickets del cliente
if ($action == 'list') {
    $filtro_estado = $_GET['estado'] ?? '';
    $query = "SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_creacion, tec.nombre as tecnico_nombre, tec.apellido as tecnico_apellido, cat.nombre as categoria_nombre FROM tickets t LEFT JOIN usuarios tec ON t.id_tecnico = tec.id LEFT JOIN categorias_tickets cat ON t.id_categoria = cat.id WHERE t.id_cliente = :id_cliente";
    $params = [':id_cliente' => $_SESSION['user_id']];
    if ($filtro_estado) {
        $query .= " AND t.estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
    $query .= " ORDER BY t.fecha_creacion DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener categorías jerárquicas para el formulario
$categorias_stmt = $conn->query("SELECT id, parent_id, nombre, plantilla FROM categorias_tickets WHERE estado = 1 ORDER BY nombre");
$all_categorias = $categorias_stmt->fetchAll(PDO::FETCH_ASSOC);
$categorias_jerarquicas = [];
$subcategorias_map = [];
foreach ($all_categorias as $categoria) {
    if ($categoria['parent_id'] === null) {
        $categorias_jerarquicas[$categoria['id']] = $categoria;
    } else {
        if (!isset($subcategorias_map[$categoria['parent_id']])) $subcategorias_map[$categoria['parent_id']] = [];
        $subcategorias_map[$categoria['parent_id']][] = $categoria;
    }
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
                <?php if ($action == 'list'): ?>
                <a href="tickets.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Ticket</a>
                <?php else: ?>
                <a href="tickets.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
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
                                    <td>#<?php echo $t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                                    <td><?php echo $t['tecnico_nombre'] ? htmlspecialchars($t['tecnico_nombre'] . ' ' . $t['tecnico_apellido']) : 'No asignado'; ?></td>
                                    <td><?php echo htmlspecialchars($t['categoria_nombre'] ?? 'N/A'); ?></td>
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
        <?php elseif ($action == 'create'): ?>
            <div class="card">
                <div class="card-header"><h2>Crear Nuevo Ticket</h2></div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_categoria_padre">Categoría Principal</label>
                                    <select class="form-control" id="id_categoria_padre" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($categorias_jerarquicas as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_categoria">Subcategoría (Aplicativo)</label>
                                    <select class="form-control" id="id_categoria" name="id_categoria" required>
                                        <option value="">Primero elija una categoría principal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="titulo">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="form-group">
                            <label for="prioridad">Prioridad</label>
                            <select class="form-control" id="prioridad" name="prioridad" required>
                                <option value="baja">Baja</option>
                                <option value="media" selected>Media</option>
                                <option value="alta">Alta</option>
                                <option value="critica">Crítica</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="8" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Adjuntar archivos (opcional)</label>
                            <div class="custom-file">
                                <input type="file" id="adjuntos" name="adjuntos[]" multiple class="custom-file-input">
                                <label class="custom-file-label" for="adjuntos">Elegir archivos...</label>
                            </div>
                        </div>
                        <div class="form-actions">
                            <a href="tickets.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Ticket</button>
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
                        </div>
                    </div>
                </div>
                <div class="ticket-responses">
                    <h4>Historial del Ticket</h4>
                    <div class="response response-client">
                        <div class="response-header"><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Cliente)</div>
                        <div class="response-body"><?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?></div>
                    </div>
                    <?php foreach ($respuestas as $respuesta): ?>
                       <div class="response <?php echo ($respuesta['rol'] == 'tecnico' || $respuesta['rol'] == 'admin') ? 'response-technician' : 'response-client'; ?>">
                           <div class="response-header">
                                <strong><?php echo htmlspecialchars($respuesta['nombre'] . ' ' . $respuesta['apellido']); ?></strong>
                                <small>(<?php echo ucfirst($respuesta['rol']); ?>)</small>
                                <span class="response-date"><?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?></span>
                           </div>
                           <div class="response-body"><?php echo nl2br(htmlspecialchars($respuesta['mensaje'])); ?></div>
                       </div>
                    <?php endforeach; ?>

                    <?php if ($ticket['estado'] != 'cerrado'): ?>
                    <div class="response-form">
                        <h4>Responder</h4>
                        <form method="POST" action="tickets.php?action=respond&id=<?php echo $ticket['id']; ?>" enctype="multipart/form-data">
                            <div class="form-group">
                                <textarea class="form-control" name="mensaje" rows="5" required></textarea>
                            </div>
                            <div class="form-group">
                                <div class="custom-file"><input type="file" name="adjuntos[]" multiple class="custom-file-input" id="adjuntos"><label class="custom-file-label" for="adjuntos">Adjuntar...</label></div>
                            </div>
                            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button></div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="response-form">
                        <h4>¿El problema no está resuelto?</h4>
                        <form method="POST" action="tickets.php?action=reopen&id=<?php echo $ticket['id']; ?>">
                            <div class="form-group">
                                <textarea class="form-control" name="mensaje" rows="4" required placeholder="Explica por qué el problema no está resuelto..."></textarea>
                            </div>
                            <div class="form-actions"><button type="submit" class="btn btn-warning"><i class="fas fa-undo"></i> Reabrir Ticket</button></div>
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
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script>
$(document).ready(function() {
    $('#ticketsTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' } });

    var subcategoriasMap = <?php echo json_encode($subcategorias_map); ?>;
    
    $('#id_categoria_padre').change(function() {
        var parentId = $(this).val();
        var subcatSelect = $('#id_categoria');
        subcatSelect.html('<option value="">Seleccione...</option>');
        $('#descripcion').val('');
        if (parentId && subcategoriasMap[parentId]) {
            subcategoriasMap[parentId].forEach(function(subcat) {
                var option = $('<option>', { value: subcat.id, text: subcat.nombre });
                option.data('plantilla', subcat.plantilla);
                subcatSelect.append(option);
            });
        }
    });

    $('#id_categoria').change(function() {
        var plantilla = $(this).find('option:selected').data('plantilla') || '';
        $('#descripcion').val(plantilla);
    });

    $('.custom-file-input').on('change', function() {
        var files = Array.from(this.files).map(f => f.name).join(', ');
        $(this).next('.custom-file-label').html(files || 'Elegir archivos...');
    });
});
</script>
</body>
</html>