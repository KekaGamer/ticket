<?php
require_once 'db.php';

class Functions {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Funciones para empresas
    public function crearEmpresa($nombre, $direccion, $telefono) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO empresas (nombre, direccion, telefono) VALUES (:nombre, :direccion, :telefono)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':telefono', $telefono);
        return $stmt->execute();
    }

    // Funciones para gerencias
    public function crearGerencia($idEmpresa, $nombre, $descripcion) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO gerencias (id_empresa, nombre, descripcion) VALUES (:id_empresa, :nombre, :descripcion)");
        $stmt->bindParam(':id_empresa', $idEmpresa);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        return $stmt->execute();
    }

    // Funciones para unidades
    public function crearUnidad($idGerencia, $nombre, $descripcion) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO unidades (id_gerencia, nombre, descripcion) VALUES (:id_gerencia, :nombre, :descripcion)");
        $stmt->bindParam(':id_gerencia', $idGerencia);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        return $stmt->execute();
    }

    // Funciones para áreas
    public function crearArea($idUnidad, $nombre, $descripcion) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO areas (id_unidad, nombre, descripcion) VALUES (:id_unidad, :nombre, :descripcion)");
        $stmt->bindParam(':id_unidad', $idUnidad);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        return $stmt->execute();
    }

    // Funciones para usuarios
    public function crearUsuario($idEmpresa, $idArea, $nombre, $apellido, $email, $password, $rol, $foto = null) {
        $conn = $this->db->getConnection();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO usuarios (id_empresa, id_area, nombre, apellido, email, password, rol, foto_perfil) VALUES (:id_empresa, :id_area, :nombre, :apellido, :email, :password, :rol, :foto)");
        $stmt->bindParam(':id_empresa', $idEmpresa);
        $stmt->bindParam(':id_area', $idArea);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':foto', $foto);
        return $stmt->execute();
    }

    // Funciones para tickets
    public function crearTicket($idCliente, $idEmpresa, $idArea, $titulo, $descripcion, $prioridad = 'media', $idCategoria = null) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO tickets (id_cliente, id_empresa, id_area, id_categoria, titulo, descripcion, prioridad) VALUES (:id_cliente, :id_empresa, :id_area, :id_categoria, :titulo, :descripcion, :prioridad)");
        $stmt->bindParam(':id_cliente', $idCliente);
        $stmt->bindParam(':id_empresa', $idEmpresa);
        $stmt->bindParam(':id_area', $idArea);
        $stmt->bindParam(':id_categoria', $idCategoria);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':prioridad', $prioridad);
        $result = $stmt->execute();
        
        if ($result) {
            $ticketId = $conn->lastInsertId();
            $this->enviarNotificacionNuevoTicket($ticketId);
            return $ticketId;
        }
        return false;
    }

    // Funciones para respuestas de tickets
    public function responderTicket($idTicket, $idUsuario, $mensaje, $adjuntos = []) {
        $conn = $this->db->getConnection();
        $conn->beginTransaction();
        
        try {
            // Insertar respuesta
            $stmt = $conn->prepare("INSERT INTO respuestas_tickets (id_ticket, id_usuario, mensaje) VALUES (:id_ticket, :id_usuario, :mensaje)");
            $stmt->bindParam(':id_ticket', $idTicket);
            $stmt->bindParam(':id_usuario', $idUsuario);
            $stmt->bindParam(':mensaje', $mensaje);
            $stmt->execute();
            $respuestaId = $conn->lastInsertId();
            
            // Procesar adjuntos
            if (!empty($adjuntos)) {
                foreach ($adjuntos as $adjunto) {
                    $this->guardarAdjunto($respuestaId, $idTicket, $adjunto);
                }
            }
            
            $conn->commit();
            $this->enviarNotificacionRespuesta($idTicket, $respuestaId);
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error al responder ticket: " . $e->getMessage());
            return false;
        }
    }

    // Funciones para adjuntos
    private function guardarAdjunto($idRespuesta, $idTicket, $file) {
        $uploadDir = '../assets/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("INSERT INTO adjuntos (id_respuesta, id_ticket, nombre_archivo, ruta_archivo, tipo_archivo, tamanio) VALUES (:id_respuesta, :id_ticket, :nombre, :ruta, :tipo, :tamanio)");
            $stmt->bindParam(':id_respuesta', $idRespuesta);
            $stmt->bindParam(':id_ticket', $idTicket);
            $stmt->bindParam(':nombre', $file['name']);
            $stmt->bindParam(':ruta', $targetPath);
            $stmt->bindParam(':tipo', $file['type']);
            $stmt->bindParam(':tamanio', $file['size']);
            return $stmt->execute();
        }
        return false;
    }

    // Funciones para notificaciones por correo
    private function enviarNotificacionNuevoTicket($ticketId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT t.*, u.nombre, u.apellido, u.email FROM tickets t JOIN usuarios u ON t.id_cliente = u.id WHERE t.id = :id");
        $stmt->bindParam(':id', $ticketId);
        $stmt->execute();
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $to = ADMIN_EMAIL;
            $subject = "Nuevo Ticket #" . $ticketId . " - " . $ticket['titulo'];
            $message = $this->getEmailTemplate('nuevo_ticket', $ticket);
            $headers = "From: " . MAIL_FROM . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            mail($to, $subject, $message, $headers);
        }
    }

    private function enviarNotificacionRespuesta($ticketId, $respuestaId) {
        $conn = $this->db->getConnection();
        
        // Obtener información del ticket y la respuesta
        $stmt = $conn->prepare("SELECT t.*, r.mensaje, u.nombre, u.apellido, u.email FROM tickets t JOIN respuestas_tickets r ON t.id = r.id_ticket JOIN usuarios u ON r.id_usuario = u.id WHERE r.id = :respuesta_id");
        $stmt->bindParam(':respuesta_id', $respuestaId);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            // Determinar a quién enviar la notificación
            $to = ($data['id_usuario'] == $data['id_cliente']) ? $this->getTecnicoEmail($ticketId) : $data['email'];
            
            $subject = "Respuesta al Ticket #" . $ticketId . " - " . $data['titulo'];
            $message = $this->getEmailTemplate('respuesta_ticket', $data);
            $headers = "From: " . MAIL_FROM . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            mail($to, $subject, $message, $headers);
        }
    }

    private function getTecnicoEmail($ticketId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT u.email FROM tickets t JOIN usuarios u ON t.id_tecnico = u.id WHERE t.id = :ticket_id");
        $stmt->bindParam(':ticket_id', $ticketId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['email'] : ADMIN_EMAIL;
    }

    private function getEmailTemplate($template, $data) {
        ob_start();
        include "../templates/email_" . $template . ".php";
        return ob_get_clean();
    }

    // Funciones para estadísticas
    public function getEstadisticasTickets($idUsuario = null, $rol = null, $idArea = null) {
        $conn = $this->db->getConnection();
        
        $where = "";
        $params = [];
        
        if ($idUsuario && $rol == 'tecnico') {
            $where = "WHERE id_area IN (SELECT id_area FROM usuarios WHERE id = :id_usuario)";
            $params[':id_usuario'] = $idUsuario;
        } elseif ($idUsuario && $rol == 'cliente') {
            $where = "WHERE id_cliente = :id_usuario";
            $params[':id_usuario'] = $idUsuario;
        } elseif ($idArea) {
            $where = "WHERE id_area = :id_area";
            $params[':id_area'] = $idArea;
        }
        
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'abierto' THEN 1 ELSE 0 END) as abiertos,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados,
                    SUM(CASE WHEN estado = 'reabierto' THEN 1 ELSE 0 END) as reabiertos
                  FROM tickets $where";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Otras funciones útiles
    public function cambiarEstadoTicket($ticketId, $estado, $comentario = null, $idTecnico = null) {
        $conn = $this->db->getConnection();
        $conn->beginTransaction();
        
        try {
            // Actualizar estado del ticket
            $query = "UPDATE tickets SET estado = :estado";
            $params = [':estado' => $estado, ':id' => $ticketId];
            
            if ($idTecnico) {
                $query .= ", id_tecnico = :id_tecnico";
                $params[':id_tecnico'] = $idTecnico;
            }
            
            if ($estado == 'cerrado') {
                $query .= ", fecha_cierre = NOW()";
            }
            
            $query .= " WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            $stmt->execute();
            
            // Registrar comentario si existe
            if ($comentario) {
                $this->responderTicket($ticketId, $_SESSION['user_id'], $comentario);
            }
            
            $conn->commit();
            $this->enviarNotificacionCambioEstado($ticketId, $estado, $comentario);
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error al cambiar estado del ticket: " . $e->getMessage());
            return false;
        }
    }

    private function enviarNotificacionCambioEstado($ticketId, $estado, $comentario = null) {
        $conn = $this->db->getConnection();
        
        // Obtener información del ticket
        $stmt = $conn->prepare("SELECT t.*, u.nombre, u.apellido, u.email FROM tickets t JOIN usuarios u ON t.id_cliente = u.id WHERE t.id = :ticket_id");
        $stmt->bindParam(':ticket_id', $ticketId);
        $stmt->execute();
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $data = [
                'ticket' => $ticket,
                'estado' => $estado,
                'comentario' => $comentario,
                'usuario' => $_SESSION['user_name']
            ];
            
            $to = $ticket['email'];
            $subject = "Ticket #" . $ticketId . " - Estado actualizado a " . ucfirst($estado);
            $message = $this->getEmailTemplate('cambio_estado', $data);
            $headers = "From: " . MAIL_FROM . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            mail($to, $subject, $message, $headers);
        }
    }
}
?>