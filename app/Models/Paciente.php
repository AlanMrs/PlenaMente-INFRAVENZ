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

    // Obtener todos los pacientes detectando si ya tienen expediente
    public function obtenerTodos() {
        $sql = "SELECT p.*, e.id_expediente 
                FROM pacientes p 
                LEFT JOIN expedientes e ON p.id_paciente = e.id_paciente 
                ORDER BY p.id_paciente DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear un nuevo paciente/estudiante
    public function crearPaciente($nie_dui, $nombres, $apellidos, $fecha_nacimiento, $genero, $tipo_paciente, $grado_seccion, $telefono_contacto, $correo_paciente, $nombre_responsable) {
        
        $sql = "INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, grado_seccion, telefono_contacto, correo_paciente, nombre_responsable) 
                VALUES (:nie_dui, :nombres, :apellidos, :fecha_nacimiento, :genero, :tipo_paciente, :grado_seccion, :telefono_contacto, :correo_paciente, :nombre_responsable)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':nie_dui', $nie_dui);
        $stmt->bindValue(':nombres', $nombres);
        $stmt->bindValue(':apellidos', $apellidos);
        $stmt->bindValue(':fecha_nacimiento', $fecha_nacimiento);
        $stmt->bindValue(':genero', $genero);
        $stmt->bindValue(':tipo_paciente', $tipo_paciente);
        
        // Campos opcionales (pueden ir nulos)
        $stmt->bindValue(':grado_seccion', $grado_seccion);
        $stmt->bindValue(':telefono_contacto', $telefono_contacto);
        $stmt->bindValue(':correo_paciente', $correo_paciente);
        $stmt->bindValue(':nombre_responsable', $nombre_responsable);
        
        return $stmt->execute();
    }

    // Buscar pacientes detectando si ya tienen expediente
    public function buscarPacientes($termino) {
        $sql = "SELECT p.*, e.id_expediente 
                FROM pacientes p 
                LEFT JOIN expedientes e ON p.id_paciente = e.id_paciente 
                WHERE p.nie_dui LIKE :termino 
                OR p.nombres LIKE :termino 
                OR p.apellidos LIKE :termino 
                ORDER BY p.id_paciente DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':termino', '%' . $termino . '%');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM pacientes WHERE id_paciente = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
?>