<?php
require_once 'db.php';
require_once 'config.php'; // Se añade para tener acceso a BASE_URL

class Auth {
    private $db;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = new Database();
    }

    public function login($email, $password, $useAD = false) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email AND estado = 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
                
                $this->updateLastLogin($user['id']);
                return true;
            }
        }
        return false;
    }

    private function updateLastLogin($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }

    // --- SECCIÓN DE REDIRECCIONES CORREGIDA ---

    public function redirectIfNotLoggedIn() {
        if (!$this->isLoggedIn()) {
            // Usa la URL absoluta para evitar errores
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
    }

    public function redirectIfNotAdmin() {
        $this->redirectIfNotLoggedIn();
        if ($this->getUserRole() != 'admin') {
            header("Location: " . BASE_URL . "index.php");
            exit();
        }
    }

    public function redirectIfNotTecnico() {
        $this->redirectIfNotLoggedIn();
        if ($this->getUserRole() != 'tecnico') {
            header("Location: " . BASE_URL . "index.php");
            exit();
        }
    }

    public function redirectIfNotCliente() {
        $this->redirectIfNotLoggedIn();
        if ($this->getUserRole() != 'cliente') {
            header("Location: " . BASE_URL . "index.php");
            exit();
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        // --- LÍNEA CORREGIDA ---
        // Se utiliza BASE_URL para una redirección absoluta y sin errores 404.
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}
?>