<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Usuario {
    private $db;

    public function __construct() {
        // Conectamos a la base de datos al instanciar el modelo
        $this->db = (new Database())->getConnection();
    }

    // Obtener todos los usuarios 
    public function obtenerTodos() {
        $sql = "SELECT * FROM usuarios ORDER BY id_usuario DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // función para Registrar Usuario (PBI-05)
    public function crearUsuario($id_rol, $nombre_completo, $correo, $password_hash) {
        $sql = "INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado) 
                VALUES (:id_rol, :nombre_completo, :correo, :password_hash, 'Activo')";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':id_rol', $id_rol);
        $stmt->bindValue(':nombre_completo', $nombre_completo);
        $stmt->bindValue(':correo', $correo);
        $stmt->bindValue(':password_hash', $password_hash);
        
        return $stmt->execute();
    }
}
?>