<?php
namespace App\Controllers;

use App\Core\Controller;

class AsistenciaController extends Controller {

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Permitimos entrar a Psicólogo (1) y Director (2)
        if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 2])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index() {
        // Obtenemos la fecha seleccionada (por defecto hoy)
        $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
        
        $asistenciaModel = $this->modelo('Asistencia');
        
        // Obtenemos los datos para la vista
        $asistencias = $asistenciaModel->obtenerAsistenciasPorFecha($fecha);
        $expedientes = $asistenciaModel->obtenerExpedientesDisponibles();

        $this->vista('asistencias/index', [
            'fecha' => $fecha,
            'asistencias' => $asistencias,
            'expedientes' => $expedientes
        ]);
    }

    public function guardar() {
        // Solo el Psicólogo puede guardar
        if ($_SESSION['id_rol'] != 1) {
            header('Location: ' . BASE_URL . '/asistencia');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha = $_POST['fecha_asistencia'];
            $id_expediente = $_POST['id_expediente'];
            $estado = $_POST['estado'];
            $observaciones = $_POST['observaciones'];

            $asistenciaModel = $this->modelo('Asistencia');
            
            // Registramos a este paciente individual
            if(!empty($id_expediente) && !empty($estado)) {
                $asistenciaModel->registrarAsistencia($id_expediente, $fecha, $estado, $observaciones);
            }

            // Redirigimos a la misma fecha
            header('Location: ' . BASE_URL . '/asistencia?fecha=' . $fecha . '&success=1');
            exit;
        }
    }
}