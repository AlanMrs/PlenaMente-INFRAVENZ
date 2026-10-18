<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class CitaController extends Controller {

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

    // Listar citas (por defecto las del día actual, ordenadas cronológicamente, paginadas)
    public function index() {
        $modeloCita = $this->modelo('Cita');
        $vista = $_GET['vista'] ?? 'hoy';
        if (!in_array($vista, ['hoy', 'todas'], true)) {
            $vista = 'hoy';
        }

        $porPagina = 10;
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));

        // Filtro por rango de fechas (solo aplica a "Todas las Citas")
        $patronFecha = '/^\d{4}-\d{2}-\d{2}$/';
        $desde = trim($_GET['desde'] ?? '');
        $hasta = trim($_GET['hasta'] ?? '');
        if ($vista !== 'todas' || !preg_match($patronFecha, $desde)) {
            $desde = '';
        }
        if ($vista !== 'todas' || !preg_match($patronFecha, $hasta)) {
            $hasta = '';
        }
        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            $tmp = $desde;
            $desde = $hasta;
            $hasta = $tmp;
        }

        if ($vista === 'todas') {
            $total = $modeloCita->contarTodas($desde ?: null, $hasta ?: null);
        } else {
            $total = $modeloCita->contarCitasHoy();
        }
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        $offset = ($pagina - 1) * $porPagina;

        $citas = ($vista === 'todas')
            ? $modeloCita->obtenerTodas($porPagina, $offset, $desde ?: null, $hasta ?: null)
            : $modeloCita->obtenerCitasHoy($porPagina, $offset);

        $this->vista('citas/index', [
            'citas'        => $citas,
            'vista'        => $vista,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
            'total'        => $total,
            'porPagina'    => $porPagina,
            'offset'       => $offset,
            'desde'        => $desde,
            'hasta'        => $hasta,
            'flash'        => Flash::all()
        ]);
    }

    // Mostrar el detalle completo de una cita
    public function ver($id_cita) {
        $modeloCita = $this->modelo('Cita');
        $cita = $modeloCita->obtenerPorId((int)$id_cita);

        if (!$cita) {
            Flash::error('Cita no encontrada.');
            $this->redirect(BASE_URL . '/cita');
        }

        Logger::info('Detalle de cita visualizado', ['id_cita' => $id_cita, 'usuario' => $_SESSION['usuario_id']]);

        $this->vista('citas/ver', [
            'cita' => $cita,
            'flash' => Flash::all()
        ]);
    }

    // Mostrar el formulario para agendar una nueva cita
    public function crear() {
        $modeloPaciente = $this->modelo('Paciente');
        $pacientes = $modeloPaciente->obtenerTodos(); 

        $this->vista('citas/crear', [
            'pacientes' => $pacientes,
            'flash' => Flash::all()
        ]);
    }

    // Procesar el formulario e insertar en la Base de Datos
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_paciente = (int)($_POST['id_paciente'] ?? 0);
            $fecha       = $_POST['fecha_cita'] ?? '';
            $hora        = $_POST['hora_cita'] ?? '';
            $motivo      = trim($_POST['motivo'] ?? '');

            if (!$id_paciente || !$fecha || !$hora || !$motivo) {
                Flash::error('Todos los campos son obligatorios.');
                header('Location: ' . BASE_URL . '/cita/crear');
                exit();
            }

            $fecha_hora = $fecha . ' ' . $hora . ':00';
            $id_usuario_psicologo = (int)$_SESSION['usuario_id'];

            $modeloCita = $this->modelo('Cita');
            $exito = $modeloCita->crearCita($id_paciente, $id_usuario_psicologo, $fecha_hora, $motivo);

            if ($exito) {
                Logger::info('Cita creada', ['id_paciente' => $id_paciente, 'psicologo' => $id_usuario_psicologo]);
                Flash::success('Cita programada correctamente.');
                header('Location: ' . BASE_URL . '/cita');
                exit();
            } else {
                Logger::error('Error al crear cita', ['id_paciente' => $id_paciente, 'psicologo' => $id_usuario_psicologo]);
                Flash::error('Error al programar la cita en el sistema.');
                header('Location: ' . BASE_URL . '/cita/crear');
                exit();
            }
        }
    }

    // Cancelar una cita: GET muestra el formulario con el motivo, POST lo registra
    public function cancelar($id_cita) {
        $modeloCita = $this->modelo('Cita');
        $cita = $modeloCita->obtenerPorId((int)$id_cita);

        if (!$cita) {
            Flash::error('Cita no encontrada.');
            $this->redirect(BASE_URL . '/cita');
        }

        if ($cita['estado_cita'] !== 'Programada') {
            Flash::warning('Solo se pueden cancelar citas en estado Programada.');
            $this->redirect(BASE_URL . '/cita/ver/' . $cita['id_cita']);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $motivo = trim($_POST['motivo_cancelacion'] ?? '');

            if (strlen($motivo) < 5) {
                Flash::error('Debes indicar el motivo de la cancelación (mínimo 5 caracteres).');
                $this->redirect(BASE_URL . '/cita/cancelar/' . $cita['id_cita']);
            }

            $id_usuario_cancela = (int)$_SESSION['usuario_id'];
            $exito = $modeloCita->cancelarCita((int)$cita['id_cita'], $motivo, $id_usuario_cancela);

            if ($exito) {
                Logger::info('Cita cancelada', [
                    'id_cita' => $cita['id_cita'],
                    'usuario' => $id_usuario_cancela,
                    'motivo'  => $motivo
                ]);
                Flash::success('La cita fue cancelada correctamente. El cambio se refleja en la agenda.');
                $this->redirect(BASE_URL . '/cita/ver/' . $cita['id_cita']);
            } else {
                Logger::error('No se pudo cancelar la cita', ['id_cita' => $cita['id_cita']]);
                Flash::error('No se pudo cancelar la cita. Intenta nuevamente.');
                $this->redirect(BASE_URL . '/cita/cancelar/' . $cita['id_cita']);
            }
        }

        $this->vista('citas/cancelar', [
            'cita'  => $cita,
            'flash' => Flash::all()
        ]);
    }
}