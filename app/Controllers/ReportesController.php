<?php
namespace App\Controllers;

use App\Core\Controller;

class ReportesController extends Controller {

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Validar si hay sesión
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // 2. BLINDAJE: Solo Psicólogo (1) y Director (2) pueden entrar
        if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 2])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
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
        
        // Obtenemos los datos del paciente y un resumen de sus sesiones
        $paciente = $reporteModel->obtenerDatosPacientePorExpediente($id_expediente);
        $resumen_sesiones = $reporteModel->obtenerResumenSesiones($id_expediente);
        
        // NUEVO: Obtenemos el texto de la última sesión para autollenar el informe
        $ultima_sesion = $reporteModel->obtenerUltimaSesion($id_expediente);

        if (!$paciente) {
            header('Location: ' . BASE_URL . '/reportes');
            exit;
        }

        $this->vista('reportes/tecnico', [
            'paciente' => $paciente,
            'resumen' => $resumen_sesiones,
            'ultima_sesion' => $ultima_sesion // Pasamos la variable a la vista
        ]);
    }
}