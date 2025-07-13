<?php
require_once 'db.php';

class Auth {
    private $db;
    // private $ad; // Comentado para deshabilitar AD

    public function __construct() {
        $this->db = new Database();
        // $this->ad = new ADConnection(); // Comentado para deshabilitar AD
    }

    public function login($email, $password, $useAD = false) {
        // La lógica de Active Directory ha sido removida.
        // Solo se utiliza la autenticación local.
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
                
                // Actualizar último login
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

    public function redirectIfNotLoggedIn() {
        if (!$this->isLoggedIn()) {
            header("Location: ../login.php");
            exit();
        }
    }

    public function redirectIfNotAdmin() {
        $this->redirectIfNotLoggedIn();
        if ($this->getUserRole() != 'admin') {
            header("Location: ../index.php");
            exit();
        }
    }

    public function redirectIfNotTecnico() {
        $this->redirectIfNotLoggedIn();
        if ($this->getUserRole() != 'tecnico') {
            header("Location: ../index.php");
            exit();
        }
    }

    public function redirectIfNotCliente() {
        $this->redirectIfNotLoggedIn();
        if ($this->getUserRole() != 'cliente') {
            header("Location: ../index.php");
            exit();
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}
?>