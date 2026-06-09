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
        // Instanciamos el nuevo modelo de Dashboard
        $dashboardModel = $this->modelo('Dashboard');

        // Extraemos las estadísticas
        $citasHoy = $dashboardModel->obtenerCitasHoy();
        $estudiantesActivos = $dashboardModel->obtenerEstudiantesActivos();
        $consultasMes = $dashboardModel->obtenerConsultasMes();
        $proximasCitas = $dashboardModel->obtenerProximasCitas();

        // Enviamos todas las variables a la vista
        $this->vista('dashboard/index', [
            'citas_hoy' => $citasHoy,
            'estudiantes_activos' => $estudiantesActivos,
            'consultas_mes' => $consultasMes,
            'proximas_citas' => $proximasCitas
        ]);
    }
}