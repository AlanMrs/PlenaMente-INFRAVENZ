<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class PacienteController extends Controller {

    public function __construct() {
        parent::__construct();
        
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/auth/login');
        }

        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] !== 1) {
            $this->redirect(BASE_URL . '/dashboard');
        }
    }
    
    // Método para listar los pacientes
    public function index() {
        $pacienteModel = $this->modelo('Paciente');
        
        $busqueda = trim($_GET['buscar'] ?? '');

        if (!empty($busqueda)) {
            $pacientes = $pacienteModel->buscarPacientes($busqueda);
        } else {
            $pacientes = $pacienteModel->obtenerTodos();
        }

        $this->vista('pacientes/index', [
            'pacientes' => $pacientes,
            'busqueda' => $busqueda
        ]);
    }

    // Muestra la pantalla del formulario
    public function crear() {
        $this->vista('pacientes/crear');
    }

    // Recibe los datos y los guarda en la base de datos
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Datos obligatorios
            $nie_dui = trim($_POST['nie_dui'] ?? '');
            $nombres = trim($_POST['nombres'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
            $genero = $_POST['genero'] ?? '';
            $tipo_paciente = $_POST['tipo_paciente'] ?? '';
            
            // Validación básica
            if (!$nie_dui || !$nombres || !$apellidos || !$fecha_nacimiento || !$genero || !$tipo_paciente) {
                Flash::error('Todos los campos obligatorios deben ser completados.');
                $this->redirect(BASE_URL . '/paciente/crear');
            }

            // Datos opcionales
            $grado_seccion = !empty($_POST['grado_seccion']) ? trim($_POST['grado_seccion']) : null;
            $telefono_contacto = !empty($_POST['telefono_contacto']) ? trim($_POST['telefono_contacto']) : null;
            $correo_paciente = !empty($_POST['correo_paciente']) ? trim($_POST['correo_paciente']) : null;
            $nombre_responsable = !empty($_POST['nombre_responsable']) ? trim($_POST['nombre_responsable']) : null;

            $pacienteModel = $this->modelo('Paciente');
            
            $exito = $pacienteModel->crearPaciente($nie_dui, $nombres, $apellidos, $fecha_nacimiento, $genero, $tipo_paciente, $grado_seccion, $telefono_contacto, $correo_paciente, $nombre_responsable);

            if ($exito) {
                Logger::info('Paciente creado', ['nie_dui' => $nie_dui, 'nombres' => $nombres, 'apellidos' => $apellidos]);
                Flash::success('Paciente registrado correctamente.');
                $this->redirect(BASE_URL . '/paciente');
            } else {
                Logger::error('Error al crear paciente', ['nie_dui' => $nie_dui]);
                Flash::error('Hubo un error al guardar el paciente.');
                $this->redirect(BASE_URL . '/paciente/crear');
            }
        }
    }
}