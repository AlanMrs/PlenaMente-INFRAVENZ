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
        parent::__construct();
        $this->requiere('recordatorios');
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

        $destinatario = trim($cita['correo_paciente']);

        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            Logger::warning('Recordatorio no enviado: correo de destino invalido', [
                'id_cita' => $cita['id_cita'],
                'destinatario' => $destinatario
            ]);
            Flash::error('El correo registrado del paciente no es una dirección válida (' . $destinatario . '). Corríjelo en la ficha del paciente.');
            $this->redirect(BASE_URL . '/recordatorio');
        }

        $asunto = 'Recordatorio de cita psicológica - ' . date('d/m/Y g:i A', strtotime($cita['fecha_hora']));
        $mensaje = $this->construirMensaje($cita);

        $diagnostico = null;
        $enviado = Correo::enviar($destinatario, $asunto, $mensaje, $diagnostico);

        // El estado se registra segun lo que realmente respondio el transporte
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
                'metodo' => $diagnostico['metodo'] ?? null,
                'remitente' => $diagnostico['remitente'] ?? null,
                'usuario' => $_SESSION['usuario_id']
            ]);
            Flash::success('Recordatorio enviado a ' . $destinatario . ' (método: ' . ($diagnostico['metodo'] ?? '-') . ').');
        } else {
            $motivo = !empty($diagnostico['errores'])
                ? implode(' | ', $diagnostico['errores'])
                : 'el servidor de correo no confirmó la entrega';
            Logger::error('No se pudo enviar el recordatorio de cita', [
                'id_cita' => $cita['id_cita'],
                'destinatario' => $destinatario,
                'remitente' => $diagnostico['remitente'] ?? null,
                'metodo' => $diagnostico['metodo'] ?? null,
                'errores' => $diagnostico['errores'] ?? [],
                'dialogo' => $diagnostico['dialogo'] ?? []
            ]);
            Flash::error('No se pudo enviar el recordatorio: ' . $motivo
                . ' Revise el diagnóstico de correo (📧 Diagnóstico) para más detalle.');
        }

        $this->redirect(BASE_URL . '/recordatorio');
    }

    /**
     * Diagnostico de correo: muestra la configuracion vigente y permite enviar
     * un correo de prueba para verificar la entrega en el hosting.
     */
    public function diagnostico() {
        $config = Correo::config();
        $smtp = $config['smtp'] ?? [];

        $estado = [
            'metodo'      => null,
            'remitente'   => $config['remitente'] ?? '',
            'nombre'      => $config['nombre_remitente'] ?? '',
            'servidor'    => $smtp['host'] ?? '',
            'puerto'      => $smtp['puerto'] ?? '',
            'cifrado'     => $smtp['cifrado'] ?? '',
            'usuario'     => $smtp['usuario'] ?? '',
            'clave'       => !empty($smtp['clave']) ? 'Configurada' : 'NO configurada',
            'smtp_posible'=> !empty($smtp['host']) && !empty($smtp['usuario']) && !empty($smtp['clave']),
            'dominio'     => $_SERVER['SERVER_NAME'] ?? 'localhost',
            'envio_local' => function_exists('mail')
        ];

        $resultado = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $destinatario = trim($_POST['destinatario'] ?? '');

            if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
                Flash::error('Escribe una dirección de correo válida para la prueba.');
                $this->redirect(BASE_URL . '/recordatorio/diagnostico');
            }

            $diagnostico = null;
            $enviado = Correo::enviar(
                $destinatario,
                'Prueba de correo - PlenaMente INFRAVENZ',
                '<h2>Prueba de envío</h2><p>Si recibes este mensaje, el envío de correo del sistema '
                . '<strong>PlenaMente</strong> está funcionando correctamente.</p>'
                . '<p>Fecha de la prueba: ' . date('d/m/Y H:i:s') . '</p>',
                $diagnostico
            );

            Logger::info('Prueba de correo ejecutada', [
                'destinatario' => $destinatario,
                'resultado' => $enviado ? 'enviado' : 'fallido',
                'metodo' => $diagnostico['metodo'] ?? null,
                'errores' => $diagnostico['errores'] ?? []
            ]);

            $resultado = [
                'enviado' => $enviado,
                'destinatario' => $destinatario,
                'diagnostico' => $diagnostico
            ];
        }

        $this->vista('recordatorios/diagnostico', [
            'estado'   => $estado,
            'resultado'=> $resultado,
            'flash'    => Flash::all()
        ]);
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
