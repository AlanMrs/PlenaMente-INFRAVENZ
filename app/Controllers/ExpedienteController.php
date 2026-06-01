<?php
namespace App\Controllers;

use App\Core\Controller;

class ExpedienteController extends Controller {

    // Pantalla principal del listado de expedientes
    public function index() {
        $expedienteModel = $this->modelo('Expediente');
        $expedientes = $expedienteModel->obtenerTodos();

        $this->vista('expedientes/index', [
            'expedientes' => $expedientes
        ]);
    }

    // Mostrar el formulario para abrir un expediente (pasa el id del paciente seleccionado)
    public function crear($id_paciente) {
        $pacienteModel = $this->modelo('Paciente');
        $expedienteModel = $this->modelo('Expediente');

        // Validamos si ya existe un expediente para este paciente
        if ($expedienteModel->verificarSiExiste($id_paciente)) {
            // Si ya existe, lo redirigimos al listado con un mensaje de aviso
            header('Location: ' . BASE_URL . '/expediente?error=ya_existe');
            exit;
        }

        // Buscamos los datos básicos del paciente para mostrarlos en el formulario
        $paciente = $pacienteModel->obtenerPorId($id_paciente); // Asegúrate de tener este método en tu Paciente Model

        $this->vista('expedientes/crear', [
            'paciente' => $paciente
        ]);
    }

    // Procesar la inserción de los datos en la base de datos
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_paciente = $_POST['id_paciente'];
            $fecha_apertura = $_POST['fecha_apertura'];
            $antecedentes_familiares = !empty($_POST['antecedentes_familiares']) ? trim($_POST['antecedentes_familiares']) : null;
            $antecedentes_medicos = !empty($_POST['antecedentes_medicos']) ? trim($_POST['antecedentes_medicos']) : null;

            $expedienteModel = $this->modelo('Expediente');
            $exito = $expedienteModel->crearExpediente($id_paciente, $fecha_apertura, $antecedentes_familiares, $antecedentes_medicos);

            if ($exito) {
                header('Location: ' . BASE_URL . '/expediente?success=creado');
                exit;
            } else {
                echo "Hubo un error al intentar abrir el expediente.";
            }
        }
    }

    public function ver($id) {
        // Inicializamos el modelo directamente usando el método nativo de tu framework
        $modeloExpediente = $this->modelo('Expediente');
        
        // 1. Buscamos el expediente con los datos del paciente
        $expediente = $modeloExpediente->obtenerPorId($id);
        
        // Si el expediente no existe, redirigimos al listado de pacientes con un aviso
        if (!$expediente) {
            header("Location: " . BASE_URL . "/paciente?error=no_encontrado");
            exit();
        }
        
        // 2. Buscamos todas las sesiones clínicas de este expediente
        $sesiones = $modeloExpediente->obtenerSesionesPorExpediente($id);
        
        // 3. Cargamos la vista enviando ambos flujos de datos de forma directa
        $this->vista('expedientes/ver', [
            'expediente' => $expediente,
            'sesiones'   => $sesiones
        ]);
    }

    // Mostrar formulario de nueva sesión
    public function nueva_sesion($id_expediente) {
        $modeloExpediente = $this->modelo('Expediente');
        $expediente = $modeloExpediente->obtenerPorId($id_expediente);

        $this->vista('expedientes/nueva_sesion', [
            'expediente' => $expediente
        ]);
    }

    // Procesar el guardado de la sesión
    public function guardar_sesion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_expediente = $_POST['id_expediente'];
            $observaciones = trim($_POST['observaciones_generales']);
            $intervencion  = trim($_POST['intervencion_realizada']);
            $notas         = trim($_POST['notas_evolucion']);

            $modeloExpediente = $this->modelo('Expediente');
            $exito = $modeloExpediente->guardarSesion($id_expediente, $observaciones, $intervencion, $notas);

            if ($exito) {
                header('Location: ' . BASE_URL . '/expediente/ver/' . $id_expediente . '?success=sesion_guardada');
            } else {
                echo "Error al guardar la sesión.";
            }
        }
    }
}