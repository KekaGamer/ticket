<?php
require_once 'config.php';

class Database {
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'utf8mb4'");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die("Error al conectar con la base de datos. Por favor intente más tarde.");
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}

/*
// Conexión a Active Directory (CLASE COMPLETA COMENTADA)
class ADConnection {
    private $conn;

    public function __construct() {
        $this->conn = ldap_connect(AD_SERVER);
        if (!$this->conn) {
            error_log("Error al conectar con Active Directory");
            return false;
        }
        
        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);
        
        return $this->conn;
    }

    public function authenticate($username, $password) {
        $bind = @ldap_bind($this->conn, $username . '@' . AD_DOMAIN, $password);
        if ($bind) {
            $filter = "(sAMAccountName=" . $username . ")";
            $search = ldap_search($this->conn, AD_BASEDN, $filter);
            $info = ldap_get_entries($this->conn, $search);
            
            if ($info['count'] > 0) {
                return $info[0];
            }
        }
        return false;
    }

    public function close() {
        ldap_close($this->conn);
    }
}
*/
?>