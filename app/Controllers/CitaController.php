<?php
namespace App\Controllers;

// Importamos la clase base que nos mostraste
use App\Core\Controller;

class CitaController extends Controller {

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] !== 1) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    // Listar todas las citas agendadas
    public function index() {
        $modeloCita = $this->modelo('Cita');
        $citas = $modeloCita->obtenerTodas();

        // Al pasar 'citas', la vista recibirá directamente la variable $citas
        $this->vista('citas/index', [
            'citas' => $citas
        ]);
    }

    // Mostrar el formulario para agendar una nueva cita
    public function crear() {
        $modeloPaciente = $this->modelo('Paciente');
        $pacientes = $modeloPaciente->obtenerTodos(); 

        // Al pasar 'pacientes', la vista recibirá directamente la variable $pacientes
        $this->vista('citas/crear', [
            'pacientes' => $pacientes
        ]);
    }

    // Procesar el formulario e insertar en la Base de Datos
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_paciente = $_POST['id_paciente'];
            $fecha       = $_POST['fecha_cita'];
            $hora        = $_POST['hora_cita'];
            $motivo      = trim($_POST['motivo']);

            // Fusionamos la fecha y la hora para tu campo DATETIME
            $fecha_hora = $fecha . ' ' . $hora . ':00';
            
            // Asignamos estáticamente el psicólogo 1 por ahora (luego puedes usar $_SESSION['id_usuario'])
            $id_usuario_psicologo = 1; 

            $modeloCita = $this->modelo('Cita');
            $exito = $modeloCita->crearCita($id_paciente, $id_usuario_psicologo, $fecha_hora, $motivo);

            if ($exito) {
                header('Location: ' . BASE_URL . '/cita?success=creada');
                exit();
            } else {
                echo "Error al programar la cita en el sistema.";
            }
        }
    }
}