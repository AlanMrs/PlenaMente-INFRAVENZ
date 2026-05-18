<?php
namespace App\Controllers;

use App\Core\Controller;

class PacienteController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }
    
    // Método para listar los pacientes
    public function index() {
        $pacienteModel = $this->modelo('Paciente');
        
        $busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

        if (!empty($busqueda)) {
            // Si hay búsqueda, filtra los registros
            $pacientes = $pacienteModel->buscarPacientes($busqueda);
        } else {
            $pacientes = $pacienteModel->obtenerTodos();
        }

        // Envia los pacientes y la búsqueda actual a la vista
        $this->vista('pacientes/index', [
            'pacientes' => $pacientes,
            'busqueda' => $busqueda
        ]);
    }
}