<?php
namespace App\Models;

// Usamos la configuración de base de datos exacta de tu framework
use App\Config\Database;
use PDO;

class Expediente {
    private $db;

    public function __construct() {
        // Conectamos a la base de datos exactamente igual que en Paciente.php
        $this->db = (new Database())->getConnection();
    }

    // 1. Obtener todos los expedientes haciendo un JOIN con pacientes para ver sus nombres
    public function obtenerTodos() {
        $sql = "SELECT e.*, p.nie_dui, p.nombres, p.apellidos, p.tipo_paciente 
                FROM expedientes e
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                ORDER BY e.id_expediente DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Verificar si un paciente ya cuenta con un expediente abierto (Evita duplicados)
    public function verificarSiExiste($id_paciente) {
        $sql = "SELECT id_expediente FROM expedientes WHERE id_paciente = :id_paciente LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_paciente', $id_paciente);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Crear un nuevo expediente médico/psicológico
    public function crearExpediente($id_paciente, $fecha_apertura, $antecedentes_familiares, $antecedentes_medicos) {
        $sql = "INSERT INTO expedientes (id_paciente, fecha_apertura, antecedentes_familiares, antecedentes_medicos) 
                VALUES (:id_paciente, :fecha_apertura, :antecedentes_familiares, :antecedentes_medicos)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_paciente', $id_paciente);
        $stmt->bindValue(':fecha_apertura', $fecha_apertura);
        $stmt->bindValue(':antecedentes_familiares', $antecedentes_familiares);
        $stmt->bindValue(':antecedentes_medicos', $antecedentes_medicos);
        
        return $stmt->execute();
    }

    // 4. Obtener un expediente específico por su ID con todos los datos detallados del paciente
    public function obtenerPorId($id_expediente) {
        $sql = "SELECT e.*, p.nie_dui, p.nombres, p.apellidos, p.tipo_paciente, 
                       p.grado_seccion, p.telefono_contacto, p.correo_paciente, 
                       p.genero, p.fecha_nacimiento, p.nombre_responsable
                FROM expedientes e
                INNER JOIN pacientes p ON e.id_paciente = p.id_paciente
                WHERE e.id_expediente = :id_expediente LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 5. Obtener todas las sesiones clínicas asociadas a este expediente
    public function obtenerSesionesPorExpediente($id_expediente) {
        $sql = "SELECT * FROM sesiones_clinicas 
                WHERE id_expediente = :id_expediente 
                ORDER BY fecha_registro DESC, id_sesion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_expediente', $id_expediente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. Guardar una nueva sesión clínica (Soportando de forma opcional el id_cita)
    public function guardarSesion($id_expediente, $observaciones, $intervencion, $notas, $id_cita = null) {
        $sql = "INSERT INTO sesiones_clinicas (id_cita, id_expediente, observaciones_generales, intervencion_realizada, notas_evolucion, fecha_registro) 
                VALUES (:id_cita, :id_expediente, :observaciones, :intervencion, :notas, NOW())";
        
        $stmt = $this->db->prepare($sql);
        // Si id_cita es vacío o null, guardamos un NULL real de base de datos
        $stmt->bindValue(':id_cita', !empty($id_cita) ? $id_cita : null, !empty($id_cita) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id_expediente', $id_expediente);
        $stmt->bindValue(':observaciones', $observaciones);
        $stmt->bindValue(':intervencion', $intervencion);
        $stmt->bindValue(':notas', $notas);
        
        return $stmt->execute();
    }

    // 7. Obtener los datos básicos de una cita (para saber qué paciente la agendó)
    public function obtenerCitaPorId($id_cita) {
        $sql = "SELECT * FROM citas WHERE id_cita = :id_cita LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_cita', $id_cita);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 8. Actualizar el estado de la cita a 'Atendida'
    public function marcarCitaComoAtendida($id_cita) {
        $sql = "UPDATE citas SET estado = 'Atendida' WHERE id_cita = :id_cita";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_cita', $id_cita);
        return $stmt->execute();
    }
}