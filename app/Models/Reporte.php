<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Reporte {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Obtiene la lista de todos los expedientes activos para mostrar en la tabla principal
    public function obtenerExpedientesParaReporte() {
        $sql = "SELECT e.id_expediente, e.fecha_apertura, p.nie_dui, p.nombres, p.apellidos, p.tipo_paciente 
                FROM expedientes e
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                ORDER BY p.apellidos ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene toda la información del paciente vinculada a un expediente
    public function obtenerDatosPacientePorExpediente($id_expediente) {
        $sql = "SELECT e.*, p.* FROM expedientes e
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                WHERE e.id_expediente = :id_expediente LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtiene métricas clave: total de sesiones, fecha de la primera y la última
    public function obtenerResumenSesiones($id_expediente) {
        $sql = "SELECT 
                    COUNT(id_sesion) as total_sesiones,
                    MIN(fecha_registro) as primera_sesion,
                    MAX(fecha_registro) as ultima_sesion
                FROM sesiones_clinicas 
                WHERE id_expediente = :id_expediente";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtiene los apuntes de la última sesión clínica registrada para llenar el informe
    public function obtenerUltimaSesion($id_expediente) {
        $sql = "SELECT observaciones_generales, intervencion_realizada, notas_evolucion 
                FROM sesiones_clinicas 
                WHERE id_expediente = :id_expediente 
                ORDER BY fecha_registro DESC, id_sesion DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}