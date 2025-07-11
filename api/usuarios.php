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

// Solo administradores pueden acceder a esta API
if ($auth->getUserRole() != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$resource = array_shift($request);

switch ($method) {
    case 'GET':
        // Listar usuarios
        if ($resource == 'usuarios') {
            $filters = [
                'rol' => $_GET['rol'] ?? null,
                'estado' => $_GET['estado'] ?? null,
                'empresa' => $_GET['empresa'] ?? null,
                'area' => $_GET['area'] ?? null
            ];
            
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 10;
            $offset = ($page - 1) * $perPage;
            
            $query = "SELECT SQL_CALC_FOUND_ROWS u.*, e.nombre as empresa_nombre, a.nombre as area_nombre 
                      FROM usuarios u
                      LEFT JOIN empresas e ON u.id_empresa = e.id
                      LEFT JOIN areas a ON u.id_area = a.id
                      WHERE 1=1";
            
            $params = [];
            
            foreach ($filters as $key => $value) {
                if ($value) {
                    $query .= " AND u.$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            $query .= " ORDER BY u.nombre, u.apellido LIMIT :offset, :per_page";
            $params[':offset'] = $offset;
            $params[':per_page'] = $perPage;
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total de registros
            $total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
            
            echo json_encode([
                'data' => $usuarios,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        }
        // Obtener un usuario específico
        elseif ($resource == 'usuario' && isset($request[0])) {
            $userId = $request[0];
            
            $stmt = $conn->prepare("SELECT u.*, e.nombre as empresa_nombre, a.nombre as area_nombre 
                                   FROM usuarios u
                                   LEFT JOIN empresas e ON u.id_empresa = e.id
                                   LEFT JOIN areas a ON u.id_area = a.id
                                   WHERE u.id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                echo json_encode($usuario);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado']);
            }
        }
        // Obtener técnicos para asignación
        elseif ($resource == 'tecnicos') {
            $stmt = $conn->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'tecnico' AND estado = 1 ORDER BY nombre, apellido");
            $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tecnicos);
        }
        break;
        
    case 'POST':
        // Crear nuevo usuario
        if ($resource == 'usuarios') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($functions->crearUsuario(
                $data['id_empresa'] ?? null,
                $data['id_area'] ?? null,
                $data['nombre'],
                $data['apellido'],
                $data['email'],
                $data['password'],
                $data['rol'],
                null // Foto de perfil se manejaría por separado
            )) {
                http_response_code(201);
                echo json_encode(['message' => 'Usuario creado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear el usuario']);
            }
        }
        break;
        
    case 'PUT':
        // Actualizar usuario
        if ($resource == 'usuarios' && isset($request[0])) {
            $userId = $request[0];
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = "UPDATE usuarios SET 
                      nombre = :nombre,
                      apellido = :apellido,
                      email = :email,
                      rol = :rol,
                      id_empresa = :id_empresa,
                      id_area = :id_area";
            
            $params = [
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':email' => $data['email'],
                ':rol' => $data['rol'],
                ':id_empresa' => $data['id_empresa'] ?? null,
                ':id_area' => $data['id_area'] ?? null,
                ':id' => $userId
            ];
            
            if (!empty($data['password'])) {
                $query .= ", password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            
            $query .= " WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Usuario actualizado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar el usuario']);
            }
        }
        break;
        
    case 'DELETE':
        // Eliminar usuario (cambiar estado)
        if ($resource == 'usuarios' && isset($request[0])) {
            $userId = $request[0];
            
            $stmt = $conn->prepare("UPDATE usuarios SET estado = 0 WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Usuario desactivado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al desactivar el usuario']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>