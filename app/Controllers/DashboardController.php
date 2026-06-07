<?php
namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller {

    public function __construct() {
        // Aseguramos que la sesión esté activa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }
    
    public function index() {
        // 1. Instanciamos el nuevo modelo de Dashboard
        $dashboardModel = $this->modelo('Dashboard');

        // 2. Extraemos las estadísticas
        $citasHoy = $dashboardModel->obtenerCitasHoy();
        $estudiantesActivos = $dashboardModel->obtenerEstudiantesActivos();
        $consultasMes = $dashboardModel->obtenerConsultasMes();
        $proximasCitas = $dashboardModel->obtenerProximasCitas();

        // 3. Enviamos todas las variables a la vista
        $this->vista('dashboard/index', [
            'citas_hoy' => $citasHoy,
            'estudiantes_activos' => $estudiantesActivos,
            'consultas_mes' => $consultasMes,
            'proximas_citas' => $proximasCitas
        ]);
    }
}