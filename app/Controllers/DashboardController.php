<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;

class DashboardController extends Controller {

    public function __construct() {
        parent::__construct();
        
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/auth/login');
        }
    }
    
    public function index() {
        $dashboardModel = $this->modelo('Dashboard');

        $citasHoy = $dashboardModel->obtenerCitasHoy();
        $estudiantesActivos = $dashboardModel->obtenerEstudiantesActivos();
        $consultasMes = $dashboardModel->obtenerConsultasMes();
        $proximasCitas = $dashboardModel->obtenerProximasCitas();

        Logger::debug('Dashboard accessed', ['usuario_id' => $_SESSION['usuario_id']]);

        $this->vista('dashboard/index', [
            'citas_hoy' => $citasHoy,
            'estudiantes_activos' => $estudiantesActivos,
            'consultas_mes' => $consultasMes,
            'proximas_citas' => $proximasCitas
        ]);
    }
}