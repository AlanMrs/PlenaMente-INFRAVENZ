<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class ExpedienteController extends Controller {

    public function __construct() {
        parent::__construct();
        
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/auth/login');
        }

        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] !== 1) {
            $this->redirect(BASE_URL . '/dashboard');
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
            Flash::warning('Este paciente ya tiene un expediente abierto.');
            $this->redirect(BASE_URL . '/expediente');
        }

        // Buscamos los datos básicos del paciente para mostrarlos en el formulario
        $paciente = $pacienteModel->obtenerPorId($id_paciente);

        if (!$paciente) {
            Flash::error('Paciente no encontrado.');
            $this->redirect(BASE_URL . '/paciente');
        }

        $this->vista('expedientes/crear', [
            'paciente' => $paciente
        ]);
    }

    // Procesar la inserción de los datos en la base de datos
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_paciente = (int)($_POST['id_paciente'] ?? 0);
            $fecha_apertura = $_POST['fecha_apertura'] ?? '';
            $antecedentes_familiares = !empty($_POST['antecedentes_familiares']) ? trim($_POST['antecedentes_familiares']) : null;
            $antecedentes_medicos = !empty($_POST['antecedentes_medicos']) ? trim($_POST['antecedentes_medicos']) : null;

            if (!$id_paciente || !$fecha_apertura) {
                Flash::error('Paciente y fecha de apertura son obligatorios.');
                $this->redirect(BASE_URL . '/expediente/crear/' . $id_paciente);
            }

            $expedienteModel = $this->modelo('Expediente');
            $exito = $expedienteModel->crearExpediente($id_paciente, $fecha_apertura, $antecedentes_familiares, $antecedentes_medicos);

            if ($exito) {
                Logger::info('Expediente creado', ['id_paciente' => $id_paciente]);
                Flash::success('Expediente abierto correctamente.');
                $this->redirect(BASE_URL . '/expediente');
            } else {
                Logger::error('Error al crear expediente', ['id_paciente' => $id_paciente]);
                Flash::error('Hubo un error al intentar abrir el expediente.');
                $this->redirect(BASE_URL . '/expediente/crear/' . $id_paciente);
            }
        }
    }

    public function ver($id) {
        $modeloExpediente = $this->modelo('Expediente');
        
        $expediente = $modeloExpediente->obtenerPorId($id);
        
        if (!$expediente) {
            Flash::error('Expediente no encontrado.');
            $this->redirect(BASE_URL . '/paciente');
        }
        
        $sesiones = $modeloExpediente->obtenerSesionesPorExpediente($id);
        
        $this->vista('expedientes/ver', [
            'expediente' => $expediente,
            'sesiones'   => $sesiones
        ]);
    }

    // Puente inteligente: Recibe el ID de la cita desde la agenda
    public function atender_cita($id_cita) {
        $modeloExpediente = $this->modelo('Expediente');
        
        $cita = $modeloExpediente->obtenerCitaPorId($id_cita);
        if (!$cita) {
            Flash::error('Cita no encontrada.');
            $this->redirect(BASE_URL . '/cita');
        }

        $expediente = $modeloExpediente->verificarSiExiste($cita['id_paciente']);
        
        if (!$expediente) {
            Flash::warning('El paciente no tiene expediente abierto. Debe crearlo primero.');
            $this->redirect(BASE_URL . '/paciente');
        }

        $this->redirect(BASE_URL . "/expediente/nueva_sesion/{$expediente['id_expediente']}?id_cita={$id_cita}");
    }

    // Mostrar formulario de nueva sesión
    public function nueva_sesion($id_expediente) {
        $modeloExpediente = $this->modelo('Expediente');
        $expediente = $modeloExpediente->obtenerPorId($id_expediente);
        
        if (!$expediente) {
            Flash::error('Expediente no encontrado.');
            $this->redirect(BASE_URL . '/expediente');
        }
        
        $id_cita = $_GET['id_cita'] ?? null;

        $this->vista('expedientes/nueva_sesion', [
            'expediente' => $expediente,
            'id_cita'    => $id_cita
        ]);
    }

    // Procesar el guardado de la sesión
    public function guardar_sesion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_expediente = (int)($_POST['id_expediente'] ?? 0);
            
            $id_cita = (!empty($_POST['id_cita'])) ? (int)$_POST['id_cita'] : null;
            
            $tipo_consulta = trim($_POST['tipo_consulta'] ?? '');
            $observaciones = trim($_POST['observaciones_generales'] ?? '');
            $intervencion  = trim($_POST['intervencion_realizada'] ?? '');
            $notas         = trim($_POST['notas_evolucion'] ?? '');

            if (!$id_expediente || !$tipo_consulta || !$observaciones || !$intervencion || !$notas) {
                Flash::error('Todos los campos son obligatorios.');
                $this->redirect(BASE_URL . "/expediente/nueva_sesion/{$id_expediente}" . ($id_cita ? "?id_cita={$id_cita}" : ''));
            }

            $modeloExpediente = $this->modelo('Expediente');
            
            $exito = $modeloExpediente->guardarSesion($id_expediente, $tipo_consulta, $observaciones, $intervencion, $notas, $id_cita);

            if ($exito) {
                if ($id_cita) {
                    $modeloExpediente->marcarCitaComoAtendida($id_cita);
                }
                Logger::info('Sesión clínica guardada', ['id_expediente' => $id_expediente, 'id_cita' => $id_cita]);
                Flash::success('Sesión clínica registrada correctamente.');
                $this->redirect(BASE_URL . '/expediente/ver/' . $id_expediente);
            } else {
                Logger::error('Error al guardar sesión clínica', ['id_expediente' => $id_expediente]);
                Flash::error('Error al guardar la sesión clínica.');
                $this->redirect(BASE_URL . "/expediente/nueva_sesion/{$id_expediente}" . ($id_cita ? "?id_cita={$id_cita}" : ''));
            }
        }
    }
}