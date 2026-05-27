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
}