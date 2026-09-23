<?php
// Ubicación: app/Config/Database.example.php
// Referencia de credenciales. NO subas este archivo a Hostinger.
// En su lugar, edita app/Config/Database.php con tus datos reales.

namespace App\Config;

use PDO;
use PDOException;

class Database {
    // IMPORTANTE Hostinger: usa 127.0.0.1 en vez de localhost
    // (localhost puede colgarse con IPv6 = error 504)
    private $host = "127.0.0.1";
    private $db_name = "plenamente_bd";
    private $username = "tu_usuario_aqui"; // En Hostinger: uXXXX_xxx
    private $password = "tu_password_aqui";
    public $conn;

    // Método para obtener la conexión a la base de datos
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $exception) {
            echo "Error de conexión a la Base de Datos: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>