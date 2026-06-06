<?php
namespace App\Models;

// 🟢 Usamos la configuración de base de datos exacta de tu framework
use App\Config\Database;
use PDO;

class Asistencia {
    private $db;

    public function __construct() {
        // Conectamos a la base de datos exactamente igual que en Expediente.php
        $this->db = (new Database())->getConnection();
    }

    // Obtiene todos los expedientes haciendo INNER JOIN con pacientes para ver nombres,
    // y un LEFT JOIN con asistencias si ya fue registrada ese día
    public function obtenerPacientesConAsistencia($fecha) {
        $sql = "SELECT e.id_expediente, p.nombres, p.apellidos, p.tipo_paciente, p.nie_dui,
                       a.estado, a.observaciones
                FROM expedientes e
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                LEFT JOIN asistencias a ON e.id_expediente = a.id_expediente AND a.fecha_asistencia = :fecha
                ORDER BY p.apellidos ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Guarda o actualiza la asistencia de un paciente en una fecha específica
    public function registrarAsistencia($id_expediente, $fecha, $estado, $observaciones) {
        $sql = "INSERT INTO asistencias (id_expediente, fecha_asistencia, estado, observaciones)
                VALUES (:id_expediente, :fecha, :estado, :observaciones)
                ON DUPLICATE KEY UPDATE estado = :estado_update, observaciones = :observaciones_update";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente, PDO::PARAM_INT);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':observaciones', !empty($observaciones) ? trim($observaciones) : null);
        
        // Parámetros para la actualización si ya existe un registro para esa fecha
        $stmt->bindValue(':estado_update', $estado);
        $stmt->bindValue(':observaciones_update', !empty($observaciones) ? trim($observaciones) : null);
        
        return $stmt->execute();
    }
}