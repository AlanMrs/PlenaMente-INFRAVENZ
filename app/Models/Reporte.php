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

    // Guardar un informe técnico en la tabla reportes
    public function guardarReporte($id_expediente, $id_usuario_autor, $contenido_tecnico, $nivel_urgencia = 'Bajo') {
        $sql = "INSERT INTO reportes (id_expediente, id_usuario_autor, fecha_generacion, contenido_tecnico, nivel_urgencia) 
                VALUES (:id_expediente, :id_usuario_autor, CURDATE(), :contenido_tecnico, :nivel_urgencia)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario_autor', $id_usuario_autor, PDO::PARAM_INT);
        $stmt->bindValue(':contenido_tecnico', $contenido_tecnico);
        $stmt->bindValue(':nivel_urgencia', $nivel_urgencia);
        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    // Listar reportes guardados con filtros opcionales (paciente y rango de fechas)
    public function obtenerReportes($busqueda = '', $desde = '', $hasta = '') {
        $sql = "SELECT r.id_reporte, r.fecha_generacion, r.nivel_urgencia,
                       u.nombre_completo AS autor_nombre,
                       p.nombres, p.apellidos, p.nie_dui, p.tipo_paciente
                FROM reportes r
                INNER JOIN expedientes e ON r.id_expediente = e.id_expediente
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                LEFT JOIN usuarios u ON r.id_usuario_autor = u.id_usuario
                WHERE 1=1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (p.nie_dui LIKE :busqueda OR p.nombres LIKE :busqueda OR p.apellidos LIKE :busqueda)";
            $params[':busqueda'] = '%' . $busqueda . '%';
        }
        if (!empty($desde)) {
            $sql .= " AND r.fecha_generacion >= :desde";
            $params[':desde'] = $desde;
        }
        if (!empty($hasta)) {
            $sql .= " AND r.fecha_generacion <= :hasta";
            $params[':hasta'] = $hasta;
        }

        $sql .= " ORDER BY r.fecha_generacion DESC, r.id_reporte DESC";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un reporte guardado con los datos del paciente y del autor
    public function obtenerReportePorId($id_reporte) {
        $sql = "SELECT r.*, u.nombre_completo AS autor_nombre,
                       e.fecha_apertura,
                       p.nie_dui, p.nombres, p.apellidos, p.tipo_paciente, p.genero,
                       p.fecha_nacimiento, p.grado_seccion, p.nombre_responsable,
                       p.telefono_contacto, e.antecedentes_medicos, e.antecedentes_familiares
                FROM reportes r
                INNER JOIN expedientes e ON r.id_expediente = e.id_expediente
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                LEFT JOIN usuarios u ON r.id_usuario_autor = u.id_usuario
                WHERE r.id_reporte = :id_reporte LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_reporte', $id_reporte, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}