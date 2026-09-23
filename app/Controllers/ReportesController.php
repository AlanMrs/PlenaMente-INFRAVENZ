<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class ReportesController extends Controller {

    public function __construct() {
        parent::__construct();

        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/auth/login');
        }

        if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 2])) {
            $this->redirect(BASE_URL . '/dashboard');
        }
    }

    // Muestra la lista de pacientes/expedientes para elegir a quién hacerle el informe
    public function index() {
        $reporteModel = $this->modelo('Reporte');
        $expedientes = $reporteModel->obtenerExpedientesParaReporte();

        $this->vista('reportes/index', [
            'expedientes' => $expedientes
        ]);
    }

    // Genera el Informe Técnico de un paciente en específico
    public function tecnico($id_expediente) {
        $reporteModel = $this->modelo('Reporte');
        
        $paciente = $reporteModel->obtenerDatosPacientePorExpediente($id_expediente);
        $resumen_sesiones = $reporteModel->obtenerResumenSesiones($id_expediente);
        
        $ultima_sesion = $reporteModel->obtenerUltimaSesion($id_expediente);

        if (!$paciente) {
            Flash::error('Expediente no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        Logger::info('Reporte técnico generado', ['id_expediente' => $id_expediente, 'usuario' => $_SESSION['usuario_id']]);

        $this->vista('reportes/tecnico', [
            'paciente' => $paciente,
            'resumen' => $resumen_sesiones,
            'ultima_sesion' => $ultima_sesion
        ]);
    }
}