<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Recordatorio {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Citas Programadas dentro de las próximas $horas, con datos del paciente y estado de recordatorio
    public function obtenerProximas($horas = 24) {
        $horas = (int)$horas;
        $sql = "SELECT c.*, p.nombres, p.apellidos, p.nie_dui, p.correo_paciente,
                       p.telefono_contacto, p.nombre_responsable,
                       (SELECT COUNT(*) FROM recordatorios_citas r
                         WHERE r.id_cita = c.id_cita AND r.estado = 'Enviado') AS recordatorios_enviados,
                       (SELECT MAX(r2.fecha_envio) FROM recordatorios_citas r2
                         WHERE r2.id_cita = c.id_cita AND r2.estado = 'Enviado') AS ultimo_envio
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                WHERE c.estado_cita = 'Programada'
                  AND c.fecha_hora >= NOW()
                  AND c.fecha_hora <= DATE_ADD(NOW(), INTERVAL {$horas} HOUR)
                ORDER BY c.fecha_hora ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos completos de una cita (con paciente) para construir el recordatorio
    public function obtenerPorIdCita($id_cita) {
        $sql = "SELECT c.*, p.nombres, p.apellidos, p.nie_dui, p.correo_paciente,
                       p.telefono_contacto, p.nombre_responsable
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                WHERE c.id_cita = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id_cita);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ¿Ya se envió (con éxito) al menos un recordatorio para esta cita?
    public function yaEnviado($id_cita) {
        $sql = "SELECT COUNT(*) FROM recordatorios_citas WHERE id_cita = :id AND estado = 'Enviado'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id_cita);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    // Registrar el envío (exitoso o fallido) de un recordatorio
    public function registrarEnvio($id_cita, $id_usuario, $canal, $destinatario, $mensaje, $estado) {
        $sql = "INSERT INTO recordatorios_citas (id_cita, id_usuario, canal, destinatario, mensaje, estado, fecha_envio)
                VALUES (:id_cita, :id_usuario, :canal, :destinatario, :mensaje, :estado, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_cita', $id_cita);
        $stmt->bindValue(':id_usuario', $id_usuario);
        $stmt->bindValue(':canal', $canal);
        $stmt->bindValue(':destinatario', $destinatario);
        $stmt->bindValue(':mensaje', $mensaje);
        $stmt->bindValue(':estado', $estado);
        return $stmt->execute();
    }

    // Historial reciente de recordatorios enviados
    public function obtenerHistorial($limite = 30) {
        $limite = (int)$limite;
        $sql = "SELECT r.*, p.nombres, p.apellidos, c.fecha_hora
                FROM recordatorios_citas r
                INNER JOIN citas c ON r.id_cita = c.id_cita
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                ORDER BY r.fecha_envio DESC
                LIMIT {$limite}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Historial de recordatorios de una cita específica
    public function obtenerHistorialCita($id_cita) {
        $sql = "SELECT r.*, u.nombre_completo AS nombre_usuario
                FROM recordatorios_citas r
                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                WHERE r.id_cita = :id
                ORDER BY r.fecha_envio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id_cita);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
