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

    // Muestra la lista de reportes guardados con filtros por paciente y fechas
    public function index() {
        $reporteModel = $this->modelo('Reporte');

        $busqueda = trim($_GET['buscar'] ?? '');
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';

        $reportes = $reporteModel->obtenerReportes($busqueda, $desde, $hasta);

        $this->vista('reportes/index', [
            'reportes' => $reportes,
            'busqueda' => $busqueda,
            'desde' => $desde,
            'hasta' => $hasta
        ]);
    }

    // Muestra la lista de expedientes para elegir de quién generar un informe
    public function nuevo() {
        $reporteModel = $this->modelo('Reporte');
        $expedientes = $reporteModel->obtenerExpedientesParaReporte();

        $this->vista('reportes/nuevo', [
            'expedientes' => $expedientes
        ]);
    }

    // Genera el Informe Técnico de un paciente en específico (sin guardar aún)
    public function tecnico($id_expediente) {
        $reporteModel = $this->modelo('Reporte');
        
        $paciente = $reporteModel->obtenerDatosPacientePorExpediente($id_expediente);
        $resumen_sesiones = $reporteModel->obtenerResumenSesiones($id_expediente);
        
        $ultima_sesion = $reporteModel->obtenerUltimaSesion($id_expediente);

        if (!$paciente) {
            Flash::error('Expediente no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        Logger::info('Informe técnico generado', ['id_expediente' => $id_expediente, 'usuario' => $_SESSION['usuario_id']]);

        $this->vista('reportes/tecnico', [
            'paciente' => $paciente,
            'resumen' => $resumen_sesiones,
            'ultima_sesion' => $ultima_sesion
        ]);
    }

    // Guarda el informe técnico generado en la tabla reportes
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/reportes');
        }

        $id_expediente = (int)($_POST['id_expediente'] ?? 0);
        $id_usuario_autor = (int)$_SESSION['usuario_id'];
        $nivel_urgencia = $_POST['nivel_urgencia'] ?? 'Bajo';

        if (!in_array($nivel_urgencia, ['Bajo', 'Medio', 'Alto'])) {
            $nivel_urgencia = 'Bajo';
        }

        if (!$id_expediente) {
            Flash::error('No se pudo guardar el informe: expediente inválido.');
            $this->redirect(BASE_URL . '/reportes');
        }

        // Se arma el contenido clínico del informe con la última sesión
        $observaciones = trim($_POST['observaciones_generales'] ?? '');
        $intervencion = trim($_POST['intervencion_realizada'] ?? '');
        $notas = trim($_POST['notas_evolucion'] ?? '');

        $contenido_tecnico = "OBSERVACIONES CLÍNICAS:" . PHP_EOL . ($observaciones ?: 'No refiere.') . PHP_EOL . PHP_EOL
            . "INTERVENCIÓN REALIZADA:" . PHP_EOL . ($intervencion ?: 'No registrada.') . PHP_EOL . PHP_EOL
            . "NOTAS DE EVOLUCIÓN Y RECOMENDACIONES:" . PHP_EOL . ($notas ?: 'No registradas.') . PHP_EOL;

        $reporteModel = $this->modelo('Reporte');
        $nuevoId = $reporteModel->guardarReporte($id_expediente, $id_usuario_autor, $contenido_tecnico, $nivel_urgencia);

        if ($nuevoId) {
            Logger::info('Informe guardado', ['id_reporte' => $nuevoId, 'id_expediente' => $id_expediente, 'usuario' => $id_usuario_autor]);
            Flash::success('Informe guardado correctamente.');
            $this->redirect(BASE_URL . '/reportes/ver/' . $nuevoId);
        }

        Logger::error('Error al guardar informe', ['id_expediente' => $id_expediente, 'usuario' => $id_usuario_autor]);
        Flash::error('Error al guardar el informe.');
        $this->redirect(BASE_URL . '/reportes/tecnico/' . $id_expediente);
    }

    // Muestra el contenido de un reporte guardado
    public function ver($id_reporte) {
        $reporteModel = $this->modelo('Reporte');
        $reporte = $reporteModel->obtenerReportePorId((int)$id_reporte);

        if (!$reporte) {
            Flash::error('Reporte no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        Logger::info('Reporte visualizado', ['id_reporte' => $id_reporte, 'usuario' => $_SESSION['usuario_id']]);

        $this->vista('reportes/ver', [
            'reporte' => $reporte
        ]);
    }
}