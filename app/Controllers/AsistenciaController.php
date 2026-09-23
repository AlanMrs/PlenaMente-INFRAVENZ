<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class AsistenciaController extends Controller {

    public function __construct() {
        parent::__construct();

        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/auth/login');
        }

        if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 2])) {
            $this->redirect(BASE_URL . '/dashboard');
        }
    }

    public function index() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        $asistenciaModel = $this->modelo('Asistencia');
        
        $asistencias = $asistenciaModel->obtenerAsistenciasPorFecha($fecha);
        $expedientes = $asistenciaModel->obtenerExpedientesDisponibles();

        $this->vista('asistencias/index', [
            'fecha' => $fecha,
            'asistencias' => $asistencias,
            'expedientes' => $expedientes
        ]);
    }

    public function guardar() {
        if ($_SESSION['id_rol'] != 1) {
            Flash::error('Solo el psicólogo puede registrar asistencias.');
            $this->redirect(BASE_URL . '/asistencia');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha = $_POST['fecha_asistencia'] ?? '';
            $id_expediente = (int)($_POST['id_expediente'] ?? 0);
            $estado = $_POST['estado'] ?? '';
            $observaciones = trim($_POST['observaciones'] ?? '');

            if (!$fecha || !$id_expediente || !$estado) {
                Flash::error('Fecha, paciente y estado son obligatorios.');
                $this->redirect(BASE_URL . "/asistencia?fecha={$fecha}");
            }

            $asistenciaModel = $this->modelo('Asistencia');
            
            $exito = $asistenciaModel->registrarAsistencia($id_expediente, $fecha, $estado, $observaciones);

            if ($exito) {
                Logger::info('Asistencia registrada', ['id_expediente' => $id_expediente, 'fecha' => $fecha, 'estado' => $estado]);
                Flash::success('Asistencia registrada correctamente.');
            } else {
                Logger::error('Error al registrar asistencia', ['id_expediente' => $id_expediente, 'fecha' => $fecha]);
                Flash::error('Error al registrar la asistencia.');
            }

            $this->redirect(BASE_URL . "/asistencia?fecha={$fecha}");
        }
    }
}