<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class AsistenciaController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requiere('asistencias');
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
        $this->requiere('asistencias.registrar', 'Solo el psicólogo puede registrar asistencias.');

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
                $this->auditar('CREAR', 'asistencia', $id_expediente, 'Asistencia registrada el '
                    . date('d/m/Y', strtotime($fecha)) . ' con estado "' . $estado . '" en el expediente #'
                    . $id_expediente . '.');
                Flash::success('Asistencia registrada correctamente.');
            } else {
                Logger::error('Error al registrar asistencia', ['id_expediente' => $id_expediente, 'fecha' => $fecha]);
                Flash::error('Error al registrar la asistencia.');
            }

            $this->redirect(BASE_URL . "/asistencia?fecha={$fecha}");
        }
    }
}