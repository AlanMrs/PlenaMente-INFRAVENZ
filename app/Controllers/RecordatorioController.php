<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;
use App\Libraries\Correo;

require_once __DIR__ . '/../Libraries/Correo.php';

class RecordatorioController extends Controller {

    private $horasVentana = 24;

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

    // Pantalla de recordatorios: próximas citas + historial de envíos
    public function index() {
        $modelo = $this->modelo('Recordatorio');

        $this->vista('recordatorios/index', [
            'proximas'  => $modelo->obtenerProximas($this->horasVentana),
            'historial' => $modelo->obtenerHistorial(30),
            'horas'     => $this->horasVentana,
            'flash'     => Flash::all()
        ]);
    }

    // Enviar el recordatorio de una cita (POST)
    public function enviar($id_cita) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Flash::warning('Acción no permitida.');
            $this->redirect(BASE_URL . '/recordatorio');
        }

        $modelo = $this->modelo('Recordatorio');
        $cita = $modelo->obtenerPorIdCita((int)$id_cita);

        if (!$cita) {
            Flash::error('Cita no encontrada.');
            $this->redirect(BASE_URL . '/recordatorio');
        }

        if ($cita['estado_cita'] !== 'Programada') {
            Flash::warning('Solo se envían recordatorios de citas en estado Programada.');
            $this->redirect(BASE_URL . '/recordatorio');
        }

        if (empty($cita['correo_paciente'])) {
            Flash::error('El paciente no tiene correo registrado; no se puede enviar el recordatorio.');
            $this->redirect(BASE_URL . '/recordatorio');
        }

        $destinatario = $cita['correo_paciente'];
        $asunto = 'Recordatorio de cita psicológica - ' . date('d/m/Y g:i A', strtotime($cita['fecha_hora']));
        $mensaje = $this->construirMensaje($cita);

        $enviado = Correo::enviar($destinatario, $asunto, $mensaje);
        $estado = $enviado ? 'Enviado' : 'Fallido';

        $modelo->registrarEnvio(
            (int)$cita['id_cita'],
            (int)$_SESSION['usuario_id'],
            'Correo',
            $destinatario,
            $mensaje,
            $estado
        );

        if ($enviado) {
            Logger::info('Recordatorio de cita enviado', [
                'id_cita' => $cita['id_cita'],
                'destinatario' => $destinatario,
                'usuario' => $_SESSION['usuario_id']
            ]);
            Flash::success('Recordatorio enviado a ' . $destinatario . '.');
        } else {
            Logger::error('No se pudo enviar el recordatorio de cita', [
                'id_cita' => $cita['id_cita'],
                'destinatario' => $destinatario
            ]);
            Flash::error('No se pudo enviar el recordatorio (revisa la configuración de correo). El intento quedó registrado.');
        }

        $this->redirect(BASE_URL . '/recordatorio');
    }

    // Construye el cuerpo HTML del recordatorio con fecha, hora y datos relevantes
    private function construirMensaje($cita) {
        $nombre = htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos']);
        $fecha = date('d/m/Y', strtotime($cita['fecha_hora']));
        $hora = date('g:i A', strtotime($cita['fecha_hora']));
        $motivo = htmlspecialchars($cita['motivo_cita']);

        $saludo = 'Hola <strong>';
        $saludo .= !empty($cita['nombre_responsable'])
            ? htmlspecialchars($cita['nombre_responsable'])
            : $nombre;
        $saludo .= '</strong>:';

        $html = "<h2>Recordatorio de cita psicológica - PlenaMente</h2>"
            . "<p>{$saludo}</p>"
            . "<p>Le recordamos la cita programada para el siguiente paciente:</p>"
            . "<table style=\"border-collapse:collapse; width:100%; max-width:480px;\">"
            . "<tr><td style=\"padding:6px 10px; background:#f4f4f4; font-weight:bold;\">Paciente</td>"
            . "<td style=\"padding:6px 10px;\">{$nombre}</td></tr>"
            . "<tr><td style=\"padding:6px 10px; background:#f4f4f4; font-weight:bold;\">Fecha</td>"
            . "<td style=\"padding:6px 10px;\">{$fecha}</td></tr>"
            . "<tr><td style=\"padding:6px 10px; background:#f4f4f4; font-weight:bold;\">Hora</td>"
            . "<td style=\"padding:6px 10px;\">{$hora}</td></tr>"
            . "<tr><td style=\"padding:6px 10px; background:#f4f4f4; font-weight:bold;\">Motivo</td>"
            . "<td style=\"padding:6px 10px;\">{$motivo}</td></tr>"
            . "</table>"
            . "<p>Por favor preséntese puntualmente. Si no puede asistir, comuníquese con anticipación con el área de psicología.</p>"
            . "<p style=\"color:#777; font-size:12px;\">Mensaje automático de PlenaMente - INFRAVENZ. No responda a este correo.</p>";

        return $html;
    }
}
