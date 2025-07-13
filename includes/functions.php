<?php
require_once 'db.php';

class Functions {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // --- FUNCIONES QUE YA TENÍAS ---

    public function crearEmpresa($nombre, $direccion, $telefono) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO empresas (nombre, direccion, telefono) VALUES (:nombre, :direccion, :telefono)");
        return $stmt->execute([':nombre' => $nombre, ':direccion' => $direccion, ':telefono' => $telefono]);
    }

    public function crearUsuario($idEmpresa, $idArea, $nombre, $apellido, $email, $password, $rol, $foto = null) {
        $conn = $this->db->getConnection();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        // OJO: Tu tabla se llama 'usuarios', pero en la BD la creamos como 'tickets'. Asegúrate que coincida.
        // Asumiendo que la tabla es 'usuarios' y los campos de la BD son id_empresa, id_area, etc.
        $stmt = $conn->prepare("INSERT INTO usuarios (id_empresa, id_area, nombre, apellido, email, password, rol, foto_perfil) VALUES (:id_empresa, :id_area, :nombre, :apellido, :email, :password, :rol, :foto)");
        return $stmt->execute([
            ':id_empresa' => $idEmpresa, ':id_area' => $idArea, ':nombre' => $nombre, ':apellido' => $apellido,
            ':email' => $email, ':password' => $hashedPassword, ':rol' => $rol, ':foto' => $foto
        ]);
    }

    public function crearTicket($idCliente, $idEmpresa, $idArea, $titulo, $descripcion, $prioridad = 'media', $idCategoria = null) {
        $conn = $this->db->getConnection();
        // Corregido para usar 'asunto' como en la BD
        $stmt = $conn->prepare("INSERT INTO tickets (cliente_id, empresa_id, area_id, categoria_id, asunto, descripcion, prioridad) VALUES (:id_cliente, :id_empresa, :id_area, :id_categoria, :titulo, :descripcion, :prioridad)");
        $result = $stmt->execute([
            ':id_cliente' => $idCliente, ':id_empresa' => $idEmpresa, ':id_area' => $idArea, ':id_categoria' => $idCategoria,
            ':titulo' => $titulo, ':descripcion' => $descripcion, ':prioridad' => $prioridad
        ]);

        return $result ? $conn->lastInsertId() : false;
    }

    public function responderTicket($idTicket, $idUsuario, $mensaje, $adjuntos = []) {
        $conn = $this->db->getConnection();
        // El nombre de la tabla es respuestas_ticket
        $stmt = $conn->prepare("INSERT INTO respuestas_ticket (ticket_id, usuario_id, mensaje) VALUES (:id_ticket, :id_usuario, :mensaje)");
        $stmt->execute([':id_ticket' => $idTicket, ':id_usuario' => $idUsuario, ':mensaje' => $mensaje]);
        $respuestaId = $conn->lastInsertId();

        if ($respuestaId && !empty($adjuntos['name'][0])) {
            $this->guardarAdjuntos($respuestaId, $idTicket, $adjuntos);
        }
        return $respuestaId;
    }

    public function asignarTicket($ticketId, $tecnicoId, $comentario, $adminId) {
        $conn = $this->db->getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("UPDATE tickets SET tecnico_id = :id_tecnico WHERE id = :id_ticket");
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
        $uploadDir = __DIR__ . '/../public/uploads/'; // Corregido a la carpeta public
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] == UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($name);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($files['tmp_name'][$key], $targetPath)) {
                    // El nombre de la tabla es adjuntos_ticket
                    $stmt = $conn->prepare("INSERT INTO adjuntos_ticket (respuesta_id, ticket_id, nombre_archivo, ruta_archivo) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$idRespuesta, $idTicket, $name, $fileName]);
                }
            }
        }
    }

    // --- ✅ FUNCIONES FALTANTES AÑADIDAS AQUÍ ---

    /**
     * Obtiene las estadísticas de los tickets (abiertos, cerrados, etc.)
     * Esta es la función que faltaba.
     */
    public function getEstadisticasTickets() {
        $conn = $this->db->getConnection();
        $stats = [
            'total' => 0, 'abiertos' => 0, 'pendientes' => 0,
            'cerrados' => 0, 'reabiertos' => 0
        ];

        try {
            $query = "SELECT estado, COUNT(id) as count FROM tickets GROUP BY estado";
            $stmt = $conn->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Asignamos los valores, si no existen, se quedan en 0
            if (!empty($results['abierto']))   $stats['abiertos'] = $results['abierto'];
            if (!empty($results['pendiente'])) $stats['pendientes'] = $results['pendiente'];
            if (!empty($results['cerrado']))   $stats['cerrados'] = $results['cerrado'];
            if (!empty($results['reabierto'])) $stats['reabiertos'] = $results['reabierto'];
            
            // Calculamos el total
            $stats['total'] = array_sum($stats);

            return $stats;

        } catch (Exception $e) {
            // En caso de error, retornamos los valores en cero
            error_log($e->getMessage()); // Opcional: registrar el error
            return $stats;
        }
    }
    
    /**
     * Obtiene una lista de los últimos tickets.
     * Tu index.php también usa esta función.
     */
    public function getUltimosTickets($limit = 5) {
        $conn = $this->db->getConnection();
        try {
            // El index.php espera un campo 'titulo', por eso usamos "asunto as titulo"
            $sql = "SELECT id, asunto as titulo, estado FROM tickets ORDER BY fecha_creacion DESC LIMIT :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log($e->getMessage()); // Opcional: registrar el error
            return []; // Retorna un array vacío si hay un error
        }
    }
}