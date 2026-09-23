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

    // Citas del día actual, ordenadas cronológicamente
    public function obtenerCitasHoy() {
        $sql = "SELECT c.*, p.nombres, p.apellidos, p.nie_dui, p.tipo_paciente, p.grado_seccion,
                       p.telefono_contacto, e.id_expediente
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                LEFT JOIN expedientes e ON p.id_paciente = e.id_paciente
                WHERE DATE(c.fecha_hora) = CURDATE()
                ORDER BY c.fecha_hora ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalle completo de una cita con datos del paciente y expediente
    public function obtenerPorId($id) {
        $sql = "SELECT c.*, p.nombres, p.apellidos, p.nie_dui, p.tipo_paciente, p.grado_seccion,
                       p.telefono_contacto, p.correo_paciente, p.nombre_responsable, e.id_expediente
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                LEFT JOIN expedientes e ON p.id_paciente = e.id_paciente
                WHERE c.id_cita = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}