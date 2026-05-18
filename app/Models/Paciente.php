<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Paciente {
    private $db;

    public function __construct() {
        // Conectamos a la base de datos 
        $this->db = (new Database())->getConnection();
    }

    // Obtener todos los registros de la tabla pacientes
    public function obtenerTodos() {
        $sql = "SELECT * FROM pacientes ORDER BY id_paciente DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar pacientes por NIE/DUI, nombres o apellidos 
    public function buscarPacientes($termino) {
        $sql = "SELECT * FROM pacientes 
                WHERE nie_dui LIKE :termino 
                OR nombres LIKE :termino 
                OR apellidos LIKE :termino 
                ORDER BY id_paciente DESC";
                
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':termino', '%' . $termino . '%');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>