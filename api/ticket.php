<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
$functions = new Functions();
$db = new Database();
$conn = $db->getConnection();

// Verificar autenticación
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$resource = array_shift($request);

switch ($method) {
    case 'GET':
        // Obtener tickets según rol
        if ($resource == 'tickets') {
            $filters = [
                'estado' => $_GET['estado'] ?? null,
                'prioridad' => $_GET['prioridad'] ?? null,
                'tecnico' => $_GET['tecnico'] ?? null,
                'cliente' => $_GET['cliente'] ?? null,
                'area' => $_GET['area'] ?? null
            ];
            
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 10;
            $offset = ($page - 1) * $perPage;
            
            $query = "SELECT SQL_CALC_FOUND_ROWS t.*, 
                      c.nombre as cliente_nombre, c.apellido as cliente_apellido,
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
                      WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros según rol
            if ($auth->getUserRole() == 'tecnico') {
                $query .= " AND t.id_area IN (SELECT id_area FROM usuarios WHERE id = :user_id)";
                $params[':user_id'] = $_SESSION['user_id'];
            } elseif ($auth->getUserRole() == 'cliente') {
                $query .= " AND t.id_cliente = :user_id";
                $params[':user_id'] = $_SESSION['user_id'];
            }
            
            foreach ($filters as $key => $value) {
                if ($value) {
                    $query .= " AND t.$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            $query .= " ORDER BY t.fecha_creacion DESC LIMIT :offset, :per_page";
            $params[':offset'] = $offset;
            $params[':per_page'] = $perPage;
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            $stmt->execute();
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total de registros
            $total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
            
            echo json_encode([
                'data' => $tickets,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        }
        // Obtener un ticket específico
        elseif ($resource == 'ticket' && isset($request[0])) {
            $ticketId = $request[0];
            
            $query = "SELECT t.*, 
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
                      WHERE t.id = :id";
            
            // Verificar permisos
            if ($auth->getUserRole() == 'tecnico') {
                $query .= " AND t.id_area IN (SELECT id_area FROM usuarios WHERE id = :user_id)";
            } elseif ($auth->getUserRole() == 'cliente') {
                $query .= " AND t.id_cliente = :user_id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $ticketId);
            
            if ($auth->getUserRole() != 'admin') {
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
            }
            
            $stmt->execute();
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ticket) {
                // Obtener respuestas
                $stmt = $conn->prepare("SELECT r.*, u.nombre, u.apellido, u.rol, u.foto_perfil 
                                       FROM respuestas_tickets r
                                       JOIN usuarios u ON r.id_usuario = u.id
                                       WHERE r.id_ticket = :id_ticket
                                       ORDER BY r.fecha_creacion");
                $stmt->bindParam(':id_ticket', $ticketId);
                $stmt->execute();
                $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Obtener adjuntos para cada respuesta
                foreach ($respuestas as &$respuesta) {
                    $stmt = $conn->prepare("SELECT * FROM adjuntos WHERE id_respuesta = :id_respuesta");
                    $stmt->bindParam(':id_respuesta', $respuesta['id']);
                    $stmt->execute();
                    $respuesta['adjuntos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                $ticket['respuestas'] = $respuestas;
                
                echo json_encode($ticket);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket no encontrado']);
            }
        }
        break;
        
    case 'POST':
        // Crear nuevo ticket
        if ($resource == 'tickets') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($auth->getUserRole() == 'cliente') {
                // Obtener empresa y área del cliente
                $stmt = $conn->prepare("SELECT id_empresa, id_area FROM usuarios WHERE id = :id");
                $stmt->bindParam(':id', $_SESSION['user_id']);
                $stmt->execute();
                $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userInfo) {
                    $ticketId = $functions->crearTicket(
                        $_SESSION['user_id'],
                        $userInfo['id_empresa'],
                        $userInfo['id_area'],
                        $data['titulo'],
                        $data['descripcion'],
                        $data['prioridad'] ?? 'media',
                        $data['id_categoria'] ?? null
                    );
                    
                    if ($ticketId) {
                        http_response_code(201);
                        echo json_encode([
                            'id' => $ticketId,
                            'message' => 'Ticket creado correctamente'
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al crear el ticket']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'No se pudo determinar la empresa/área del cliente']);
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Solo los clientes pueden crear tickets']);
            }
        }
        // Responder ticket
        elseif ($resource == 'tickets' && isset($request[0]) && $request[1] == 'respond') {
            $ticketId = $request[0];
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Verificar permisos
            $stmt = $conn->prepare("SELECT id FROM tickets WHERE id = :id AND 
                                   (id_cliente = :user_id OR id_tecnico = :user_id OR :is_admin = 1)");
            $stmt->bindParam(':id', $ticketId);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $isAdmin = $auth->getUserRole() == 'admin' ? 1 : 0;
            $stmt->bindParam(':is_admin', $isAdmin);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                if ($functions->responderTicket($ticketId, $_SESSION['user_id'], $data['mensaje'])) {
                    echo json_encode(['message' => 'Respuesta enviada correctamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al enviar la respuesta']);
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permiso para responder este ticket']);
            }
        }
        break;
        
    case 'PUT':
        // Actualizar ticket (asignar, cambiar estado)
        if ($resource == 'tickets' && isset($request[0])) {
            $ticketId = $request[0];
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Verificar permisos (solo admin o técnico asignado)
            $stmt = $conn->prepare("SELECT id FROM tickets WHERE id = :id AND 
                                   (id_tecnico = :user_id OR :is_admin = 1)");
            $stmt->bindParam(':id', $ticketId);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $isAdmin = $auth->getUserRole() == 'admin' ? 1 : 0;
            $stmt->bindParam(':is_admin', $isAdmin);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                if (isset($data['id_tecnico'])) {
                    // Asignar técnico
                    if ($functions->asignarTicket($ticketId, $data['id_tecnico'], $data['comentario'] ?? null, $_SESSION['user_id'])) {
                        echo json_encode(['message' => 'Ticket asignado correctamente']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al asignar el ticket']);
                    }
                } elseif (isset($data['estado'])) {
                    // Cambiar estado
                    if ($functions->cambiarEstadoTicket($ticketId, $data['estado'], $data['comentario'] ?? null)) {
                        echo json_encode(['message' => 'Estado del ticket actualizado']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al actualizar el estado']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Acción no válida']);
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permiso para modificar este ticket']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>