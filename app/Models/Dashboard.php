<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Contar las citas del día actual
    public function obtenerCitasHoy() {
        // Usamos DATE(fecha_hora) para ignorar la hora y comparar solo el día
        $sql = "SELECT COUNT(*) as total FROM citas WHERE DATE(fecha_hora) = CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Contar solo a los pacientes que son Estudiantes
    public function obtenerEstudiantesActivos() {
        $sql = "SELECT COUNT(*) as total FROM pacientes WHERE tipo_paciente = 'Estudiante'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Contar las sesiones registradas en el mes actual
    public function obtenerConsultasMes() {
        $sql = "SELECT COUNT(*) as total FROM sesiones_clinicas WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Obtener la lista de citas de hoy con sus datos vinculados
    public function obtenerProximasCitas() {
        // Extraemos fecha_hora pero le ponemos el alias 'hora_cita' para que la vista lo reconozca
        $sql = "SELECT c.id_cita, c.fecha_hora as hora_cita, c.motivo_cita, c.estado_cita, p.nombres, p.apellidos, e.id_expediente
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                LEFT JOIN expedientes e ON p.id_paciente = e.id_paciente
                WHERE DATE(c.fecha_hora) = CURDATE()
                ORDER BY c.fecha_hora ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}