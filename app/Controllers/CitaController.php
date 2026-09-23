<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

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

    // Listar citas (por defecto las del día actual, ordenadas cronológicamente)
    public function index() {
        $modeloCita = $this->modelo('Cita');
        $vista = $_GET['vista'] ?? 'hoy';
        $citas = ($vista === 'todas') ? $modeloCita->obtenerTodas() : $modeloCita->obtenerCitasHoy();

        $this->vista('citas/index', [
            'citas' => $citas,
            'vista' => $vista,
            'flash' => Flash::all()
        ]);
    }

    // Mostrar el detalle completo de una cita
    public function ver($id_cita) {
        $modeloCita = $this->modelo('Cita');
        $cita = $modeloCita->obtenerPorId((int)$id_cita);

        if (!$cita) {
            Flash::error('Cita no encontrada.');
            $this->redirect(BASE_URL . '/cita');
        }

        Logger::info('Detalle de cita visualizado', ['id_cita' => $id_cita, 'usuario' => $_SESSION['usuario_id']]);

        $this->vista('citas/ver', [
            'cita' => $cita,
            'flash' => Flash::all()
        ]);
    }

    // Mostrar el formulario para agendar una nueva cita
    public function crear() {
        $modeloPaciente = $this->modelo('Paciente');
        $pacientes = $modeloPaciente->obtenerTodos(); 

        $this->vista('citas/crear', [
            'pacientes' => $pacientes,
            'flash' => Flash::all()
        ]);
    }

    // Procesar el formulario e insertar en la Base de Datos
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_paciente = (int)($_POST['id_paciente'] ?? 0);
            $fecha       = $_POST['fecha_cita'] ?? '';
            $hora        = $_POST['hora_cita'] ?? '';
            $motivo      = trim($_POST['motivo'] ?? '');

            if (!$id_paciente || !$fecha || !$hora || !$motivo) {
                Flash::error('Todos los campos son obligatorios.');
                header('Location: ' . BASE_URL . '/cita/crear');
                exit();
            }

            $fecha_hora = $fecha . ' ' . $hora . ':00';
            $id_usuario_psicologo = (int)$_SESSION['usuario_id'];

            $modeloCita = $this->modelo('Cita');
            $exito = $modeloCita->crearCita($id_paciente, $id_usuario_psicologo, $fecha_hora, $motivo);

            if ($exito) {
                Logger::info('Cita creada', ['id_paciente' => $id_paciente, 'psicologo' => $id_usuario_psicologo]);
                Flash::success('Cita programada correctamente.');
                header('Location: ' . BASE_URL . '/cita');
                exit();
            } else {
                Logger::error('Error al crear cita', ['id_paciente' => $id_paciente, 'psicologo' => $id_usuario_psicologo]);
                Flash::error('Error al programar la cita en el sistema.');
                header('Location: ' . BASE_URL . '/cita/crear');
                exit();
            }
        }
    }
}