<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Cita {
    private $db;

    public function __construct() {
        // Conexión estándar de tu sistema
        $this->db = (new Database())->getConnection();
    }

    // Traer las citas unidas con los nombres del paciente
    public function obtenerTodas() {
        $sql = "SELECT c.*, p.nombres, p.apellidos, p.nie_dui 
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                ORDER BY c.fecha_hora ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insertar la cita
    public function crearCita($id_paciente, $id_usuario_psicologo, $fecha_hora, $motivo_cita) {
        $sql = "INSERT INTO citas (id_paciente, id_usuario_psicologo, fecha_hora, motivo_cita, estado_cita) 
                VALUES (:id_paciente, :id_psicologo, :fecha_hora, :motivo, 'Programada')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_paciente', $id_paciente);
        $stmt->bindValue(':id_psicologo', $id_usuario_psicologo);
        $stmt->bindValue(':fecha_hora', $fecha_hora);
        $stmt->bindValue(':motivo', $motivo_cita);
        
        return $stmt->execute();
    }
}