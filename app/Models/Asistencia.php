<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Asistencia {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // 1. Cargar expedientes para el menú desplegable del formulario
    public function obtenerExpedientesDisponibles() {
        $sql = "SELECT e.id_expediente, p.nombres, p.apellidos, p.nie_dui, p.tipo_paciente
                FROM expedientes e
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                ORDER BY p.apellidos ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Obtener SOLO las asistencias ya registradas en una fecha específica
    public function obtenerAsistenciasPorFecha($fecha) {
        $sql = "SELECT a.estado, a.observaciones, e.id_expediente, p.nombres, p.apellidos, p.nie_dui, p.tipo_paciente
                FROM asistencias a
                INNER JOIN expedientes e ON a.id_expediente = e.id_expediente
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                WHERE a.fecha_asistencia = :fecha
                ORDER BY a.fecha_registro DESC"; // Suponiendo que tengas fecha_registro, o quita el order by
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Guardar o actualizar la asistencia individual
    public function registrarAsistencia($id_expediente, $fecha, $estado, $observaciones) {
        $sql = "INSERT INTO asistencias (id_expediente, fecha_asistencia, estado, observaciones)
                VALUES (:id_expediente, :fecha, :estado, :observaciones)
                ON DUPLICATE KEY UPDATE estado = :estado_update, observaciones = :observaciones_update";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente, PDO::PARAM_INT);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':observaciones', !empty($observaciones) ? trim($observaciones) : null);
        
        // Para actualización
        $stmt->bindValue(':estado_update', $estado);
        $stmt->bindValue(':observaciones_update', !empty($observaciones) ? trim($observaciones) : null);
        
        return $stmt->execute();
    }
}