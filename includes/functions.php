<?php
require_once 'db.php';

class Functions {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function crearEmpresa($nombre, $direccion, $telefono) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO empresas (nombre, direccion, telefono) VALUES (:nombre, :direccion, :telefono)");
        return $stmt->execute([':nombre' => $nombre, ':direccion' => $direccion, ':telefono' => $telefono]);
    }

    public function crearUsuario($idEmpresa, $idArea, $nombre, $apellido, $email, $password, $rol, $foto = null) {
        $conn = $this->db->getConnection();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO usuarios (id_empresa, id_area, nombre, apellido, email, password, rol, foto_perfil) VALUES (:id_empresa, :id_area, :nombre, :apellido, :email, :password, :rol, :foto)";
        
        try {
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':id_empresa' => empty($idEmpresa) ? null : $idEmpresa,
                ':id_area' => empty($idArea) ? null : $idArea,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':rol' => $rol,
                ':foto' => $foto
            ]);
        } catch (PDOException $e) {
            error_log("Error de Base de Datos al crear usuario: " . $e->getMessage());
            return false;
        }
    }

    // --- FUNCIÓN crearTicket CORREGIDA Y DEFINITIVA ---
    public function crearTicket($idCliente, $idEmpresa, $idArea, $titulo, $descripcion, $prioridad = 'media', $idCategoria = null) {
        $conn = $this->db->getConnection();
        
        // Se asegura de que si no hay empresa o área, se inserte NULL, lo cual ahora es permitido por la BD.
        $idEmpresa = empty($idEmpresa) ? null : $idEmpresa;
        $idArea = empty($idArea) ? null : $idArea;
        
        $sql = "INSERT INTO tickets (id_cliente, id_empresa, id_area, id_categoria, titulo, descripcion, prioridad) VALUES (:id_cliente, :id_empresa, :id_area, :id_categoria, :titulo, :descripcion, :prioridad)";
        
        try {
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':id_cliente' => $idCliente,
                ':id_empresa' => $idEmpresa,
                ':id_area' => $idArea,
                ':id_categoria' => $idCategoria,
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':prioridad' => $prioridad
            ]);
    
            return $result ? $conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error de BD al crear ticket: " . $e->getMessage());
            return false;
        }
    }

    // El resto de las funciones no necesitan cambios...
    public function responderTicket($idTicket, $idUsuario, $mensaje, $adjuntos = []) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO respuestas_tickets (id_ticket, id_usuario, mensaje) VALUES (:id_ticket, :id_usuario, :mensaje)");
        $stmt->execute([':id_ticket' => $idTicket, ':id_usuario' => $idUsuario, ':mensaje' => $mensaje]);
        $respuestaId = $conn->lastInsertId();

        if ($respuestaId && isset($adjuntos['name']) && !empty($adjuntos['name'][0])) {
            $this->guardarAdjuntos($respuestaId, $idTicket, $adjuntos);
        }
        return $respuestaId;
    }

    public function asignarTicket($ticketId, $tecnicoId, $comentario, $adminId) {
        $conn = $this->db->getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("UPDATE tickets SET id_tecnico = :id_tecnico WHERE id = :id_ticket");
            $stmt->execute([':id_tecnico' => $tecnicoId, ':id_ticket' => $ticketId]);

            if ($comentario) {
                $this->responderTicket($ticketId, $adminId, $comentario);
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function cambiarEstadoTicket($ticketId, $estado, $comentario = null, $userId = null) {
        $conn = $this->db->getConnection();
        $conn->beginTransaction();
        $userId = $userId ?? $_SESSION['user_id'];
        try {
            $query = "UPDATE tickets SET estado = :estado " . ($estado == 'cerrado' ? ", fecha_cierre = NOW()" : ", fecha_cierre = NULL") . " WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':estado' => $estado, ':id' => $ticketId]);

            if ($comentario) {
                $this->responderTicket($ticketId, $userId, $comentario);
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
    
    public function guardarAdjuntos($idRespuesta, $idTicket, $files) {
        $conn = $this->db->getConnection();
        $uploadDir = __DIR__ . '/../public/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] == UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($name);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($files['tmp_name'][$key], $targetPath)) {
                    $stmt = $conn->prepare("INSERT INTO adjuntos (id_respuesta, id_ticket, nombre_archivo, ruta_archivo, tipo_archivo, tamanio) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$idRespuesta, $idTicket, $name, $fileName, $files['type'][$key], $files['size'][$key]]);
                }
            }
        }
    }

    public function getEstadisticasTickets($userId = null, $rol = null) {
        $conn = $this->db->getConnection();
        $stats = ['total' => 0, 'abiertos' => 0, 'pendientes' => 0, 'cerrados' => 0, 'reabiertos' => 0];
        try {
            $query = "SELECT estado, COUNT(id) as count FROM tickets";
            $where = '';
            $params = [];
            if ($rol === 'cliente' && $userId) {
                $where = " WHERE id_cliente = :user_id";
                $params[':user_id'] = $userId;
            } elseif ($rol === 'tecnico' && $userId) {
                $where = " WHERE id_tecnico = :user_id";
                $params[':user_id'] = $userId;
            }
            $stmt = $conn->prepare($query . $where . " GROUP BY estado");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            if (!empty($results['abierto']))   $stats['abiertos'] = $results['abierto'];
            if (!empty($results['pendiente'])) $stats['pendientes'] = $results['pendiente'];
            if (!empty($results['cerrado']))   $stats['cerrados'] = $results['cerrado'];
            if (!empty($results['reabierto'])) $stats['reabiertos'] = $results['reabierto'];
            $stats['total'] = array_sum($stats);
            return $stats;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $stats;
        }
    }
    
    public function getTicketsCliente($userId, $limit = 5) {
        $conn = $this->db->getConnection();
        try {
            $sql = "SELECT id, titulo, estado FROM tickets WHERE id_cliente = :user_id ORDER BY fecha_creacion DESC LIMIT :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    public function getUltimosTickets($limit = 5) {
        $conn = $this->db->getConnection();
        try {
            $sql = "SELECT id, titulo, estado FROM tickets ORDER BY fecha_creacion DESC LIMIT :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }
}
?>

