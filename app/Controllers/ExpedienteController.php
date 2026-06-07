<?php
namespace App\Controllers;

use App\Core\Controller;

class ExpedienteController extends Controller {

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] !== 1) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

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

    // Puente inteligente: Recibe el ID de la cita desde la agenda
    public function atender_cita($id_cita) {
        $modeloExpediente = $this->modelo('Expediente');
        
        // 1. Buscamos la cita para saber quién es el paciente
        $cita = $modeloExpediente->obtenerCitaPorId($id_cita);
        if (!$cita) {
            header("Location: " . BASE_URL . "/cita?error=cita_no_existe");
            exit();
        }

        // 2. Verificamos si ese paciente ya tiene un expediente abierto
        $expediente = $modeloExpediente->verificarSiExiste($cita['id_paciente']);
        
        if (!$expediente) {
            // Si no tiene expediente, lo mandamos al listado de pacientes con una advertencia
            header("Location: " . BASE_URL . "/paciente?error=deye_abrir_expediente_primero");
            exit();
        }

        // 3. Si ya tiene expediente, lo mandamos directo a registrar la sesión pasando el id_cita por la URL
        header("Location: " . BASE_URL . "/expediente/nueva_sesion/" . $expediente['id_expediente'] . "?id_cita=" . $id_cita);
        exit();
    }

    // Mostrar formulario de nueva sesión
    public function nueva_sesion($id_expediente) {
        $modeloExpediente = $this->modelo('Expediente');
        $expediente = $modeloExpediente->obtenerPorId($id_expediente);
        
        // Capturamos si viene un id_cita por la URL, si no, queda como null
        $id_cita = $_GET['id_cita'] ?? null;

        $this->vista('expedientes/nueva_sesion', [
            'expediente' => $expediente,
            'id_cita'    => $id_cita
        ]);
    }

    // Procesar el guardado de la sesión
    public function guardar_sesion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_expediente = $_POST['id_expediente'];
            
            // BLINDAJE: Si id_cita viene vacío de la vista, lo convertimos en un NULL real de PHP
            $id_cita = (!empty($_POST['id_cita'])) ? $_POST['id_cita'] : null;
            
            $tipo_consulta = $_POST['tipo_consulta']; 
            $observaciones = trim($_POST['observaciones_generales']);
            $intervencion  = trim($_POST['intervencion_realizada']);
            $notas         = trim($_POST['notas_evolucion']);

            $modeloExpediente = $this->modelo('Expediente');
            
            // Enviamos las variables limpias al modelo
            $exito = $modeloExpediente->guardarSesion($id_expediente, $tipo_consulta, $observaciones, $intervencion, $notas, $id_cita);

            if ($exito) {
                if (!empty($id_cita)) {
                    $modeloExpediente->marcarCitaComoAtendida($id_cita);
                }
                header('Location: ' . BASE_URL . '/expediente/ver/' . $id_expediente . '?success=sesion_guardada');
                exit();
            } else {
                echo "Error al guardar la sesión clínica.";
            }
        }
    }
}