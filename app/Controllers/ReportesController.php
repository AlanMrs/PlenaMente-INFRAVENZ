<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class ReportesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requiere('reportes');
    }

    // Muestra la lista de reportes guardados con filtros por paciente y fechas
    public function index() {
        $reporteModel = $this->modelo('Reporte');

        $busqueda = trim($_GET['buscar'] ?? '');
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';

        $reportes = $reporteModel->obtenerReportes($busqueda, $desde, $hasta);

        $this->vista('reportes/index', [
            'reportes' => $reportes,
            'busqueda' => $busqueda,
            'desde' => $desde,
            'hasta' => $hasta
        ]);
    }

    // Solo el Psicólogo (rol 1) puede crear, generar o guardar informes.
    // El Director (rol 2) solo consulta: index() y ver().
    protected function soloPsicologo(): void {
        $this->requiere('reportes.crear', 'Solo el psicólogo puede crear informes.');
    }

    // Muestra la lista de expedientes para elegir de quién generar un informe
    public function nuevo() {
        $this->soloPsicologo();

        $reporteModel = $this->modelo('Reporte');
        $expedientes = $reporteModel->obtenerExpedientesParaReporte();

        $this->vista('reportes/nuevo', [
            'expedientes' => $expedientes
        ]);
    }

    // Genera el Informe Técnico de un paciente en específico (sin guardar aún)
    public function tecnico($id_expediente) {
        $this->soloPsicologo();

        $reporteModel = $this->modelo('Reporte');
        
        $paciente = $reporteModel->obtenerDatosPacientePorExpediente($id_expediente);
        $resumen_sesiones = $reporteModel->obtenerResumenSesiones($id_expediente);
        
        $ultima_sesion = $reporteModel->obtenerUltimaSesion($id_expediente);

        if (!$paciente) {
            Flash::error('Expediente no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        Logger::info('Informe técnico generado', ['id_expediente' => $id_expediente, 'usuario' => $_SESSION['usuario_id']]);

        $documento = $this->construirDocumentoTecnico(
            $paciente,
            $resumen_sesiones,
            $ultima_sesion,
            $this->modelo('Expediente')->obtenerSesionesPorExpediente($id_expediente, 'ASC'),
            $reporteModel->obtenerResumenCitas($id_expediente)
        );

        $this->vista('reportes/tecnico', [
            'paciente' => $paciente,
            'resumen' => $resumen_sesiones,
            'ultima_sesion' => $ultima_sesion,
            'documento' => $documento
        ]);
    }

    // Guarda el informe técnico generado en la tabla reportes
    public function guardar() {
        $this->soloPsicologo();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/reportes');
        }

        $id_expediente = (int)($_POST['id_expediente'] ?? 0);
        $id_usuario_autor = (int)$_SESSION['usuario_id'];
        $nivel_urgencia = $_POST['nivel_urgencia'] ?? 'Bajo';

        if (!in_array($nivel_urgencia, ['Bajo', 'Medio', 'Alto'])) {
            $nivel_urgencia = 'Bajo';
        }

        if (!$id_expediente) {
            Flash::error('No se pudo guardar el informe: expediente inválido.');
            $this->redirect(BASE_URL . '/reportes');
        }

        // Se arma el contenido clínico del informe con la última sesión
        $observaciones = trim($_POST['observaciones_generales'] ?? '');
        $intervencion = trim($_POST['intervencion_realizada'] ?? '');
        $notas = trim($_POST['notas_evolucion'] ?? '');

        $contenido_tecnico = "OBSERVACIONES CLÍNICAS:" . PHP_EOL . ($observaciones ?: 'No refiere.') . PHP_EOL . PHP_EOL
            . "INTERVENCIÓN REALIZADA:" . PHP_EOL . ($intervencion ?: 'No registrada.') . PHP_EOL . PHP_EOL
            . "NOTAS DE EVOLUCIÓN Y RECOMENDACIONES:" . PHP_EOL . ($notas ?: 'No registradas.') . PHP_EOL;

        $reporteModel = $this->modelo('Reporte');
        $nuevoId = $reporteModel->guardarReporte($id_expediente, $id_usuario_autor, $contenido_tecnico, $nivel_urgencia);

        if ($nuevoId) {
            Logger::info('Informe guardado', ['id_reporte' => $nuevoId, 'id_expediente' => $id_expediente, 'usuario' => $id_usuario_autor]);
            $this->auditar('CREAR', 'informe', (int)$nuevoId, 'Informe técnico generado para el expediente #'
                . $id_expediente . ' con nivel de urgencia ' . $nivel_urgencia . '.');
            Flash::success('Informe guardado correctamente.');
            $this->redirect(BASE_URL . '/reportes/ver/' . $nuevoId);
        }

        Logger::error('Error al guardar informe', ['id_expediente' => $id_expediente, 'usuario' => $id_usuario_autor]);
        Flash::error('Error al guardar el informe.');
        $this->redirect(BASE_URL . '/reportes/tecnico/' . $id_expediente);
    }

    // Muestra el contenido de un reporte guardado
    public function ver($id_reporte) {
        $reporteModel = $this->modelo('Reporte');
        $reporte = $reporteModel->obtenerReportePorId((int)$id_reporte);

        if (!$reporte) {
            Flash::error('Reporte no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        Logger::info('Reporte visualizado', ['id_reporte' => $id_reporte, 'usuario' => $_SESSION['usuario_id']]);

        $this->vista('reportes/ver', [
            'reporte' => $reporte,
            'documento' => $this->construirDocumentoDesdeReporte($reporte),
            'flash' => Flash::all()
        ]);
    }

    // Documento imprimible del informe tecnico de un expediente (exportacion a PDF)
    public function imprimir($id_expediente) {
        // Mismo permiso que la vista en pantalla del informe tecnico
        $this->requiere('reportes.crear', 'Solo el psicólogo puede generar el informe técnico.');

        $id_expediente = (int)$id_expediente;
        $reporteModel = $this->modelo('Reporte');
        $expedienteModel = $this->modelo('Expediente');

        $paciente = $reporteModel->obtenerDatosPacientePorExpediente($id_expediente);
        if (!$paciente) {
            Flash::error('Expediente no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        $documento = $this->construirDocumentoTecnico(
            $paciente,
            $reporteModel->obtenerResumenSesiones($id_expediente),
            $reporteModel->obtenerUltimaSesion($id_expediente),
            $expedienteModel->obtenerSesionesPorExpediente($id_expediente, 'ASC'),
            $reporteModel->obtenerResumenCitas($id_expediente)
        );

        Logger::info('Informe tecnico exportado para impresion', [
            'id_expediente' => $id_expediente,
            'usuario' => $_SESSION['usuario_id']
        ]);

        $this->vista('reportes/impresion', [
            'documento' => $documento,
            'modo' => 'tecnico',
            'volver_url' => BASE_URL . '/reportes/tecnico/' . $id_expediente
        ]);
    }

    // Documento imprimible de un reporte ya guardado (exportacion a PDF)
    public function imprimirReporte($id_reporte) {
        $id_reporte = (int)$id_reporte;
        $reporteModel = $this->modelo('Reporte');
        $reporte = $reporteModel->obtenerReportePorId($id_reporte);

        if (!$reporte) {
            Flash::error('Reporte no encontrado.');
            $this->redirect(BASE_URL . '/reportes');
        }

        Logger::info('Reporte exportado para impresion', [
            'id_reporte' => $id_reporte,
            'usuario' => $_SESSION['usuario_id']
        ]);

        $this->vista('reportes/impresion', [
            'documento' => $this->construirDocumentoDesdeReporte($reporte),
            'modo' => 'reporte',
            'volver_url' => BASE_URL . '/reportes/ver/' . $id_reporte
        ]);
    }

    // Alias de ruta: el enrutador resuelve el metodo por su nombre exacto en la
    // URL, por lo que "imprimir_reporte" (con guion bajo) no encuentra
    // "imprimirReporte". Este alias mantiene la convencion camelCase en el codigo.
    public function imprimir_reporte($id_reporte) {
        $this->imprimirReporte($id_reporte);
    }

    /**
     * Arma la estructura normalizada del documento imprimible a partir del
     * expediente, el resumen de sesiones, la ultima sesion, el historial y las citas.
     */
    private function construirDocumentoTecnico($paciente, $resumen, $ultima_sesion, $sesiones, $citas) {
        $fechaNacimiento = $paciente['fecha_nacimiento'] ?? null;
        $edad = null;
        if ($fechaNacimiento) {
            $nacimiento = new \DateTime($fechaNacimiento);
            $edad = $nacimiento->diff(new \DateTime())->y;
        }

        return [
            'titulo' => 'Informe Técnico Psicológico',
            'subtitulo' => 'Departamento de Psicología — INFRAVENZ',
            'paciente_nombre' => trim($paciente['nombres'] . ' ' . $paciente['apellidos']),
            'fecha_emision' => date('d/m/Y'),
            'autor' => $_SESSION['usuario_nombre'] ?? 'Psicólogo(a) del sistema',
            'expediente_n' => str_pad($paciente['id_expediente'], 4, '0', STR_PAD_LEFT),
            'reporte_n' => null,
            'urgencia' => null,
            'identificacion' => [
                ['Expediente N°', '#' . str_pad($paciente['id_expediente'], 4, '0', STR_PAD_LEFT)],
                ['Tipo de paciente', $paciente['tipo_paciente']],
                ['Nombres', $paciente['nombres']],
                ['Apellidos', $paciente['apellidos']],
                ['Identificación (NIE/DUI)', $paciente['nie_dui']],
                ['Fecha de nacimiento', $this->formatearFecha($fechaNacimiento)],
                ['Edad', $edad !== null ? $edad . ' años' : 'N/A'],
                ['Género', $paciente['genero']],
                ['Grado / Sección', $paciente['grado_seccion'] ?: 'N/A'],
                ['Estado del caso', $paciente['estado_caso'] ?? 'Activo'],
                ['Teléfono de contacto', $paciente['telefono_contacto'] ?: 'No registrado'],
                ['Correo electrónico', $paciente['correo_paciente'] ?: 'No registrado'],
                ['Responsable', $paciente['nombre_responsable'] ?: 'No registrado']
            ],
            'antecedentes' => [
                'medicos' => $paciente['antecedentes_medicos'] ?: 'No refiere antecedentes médicos.',
                'familiares' => $paciente['antecedentes_familiares'] ?: 'No refiere antecedentes familiares.'
            ],
            'resumen' => [
                ['Fecha de apertura del expediente', $this->formatearFecha($paciente['fecha_apertura'] ?? null)],
                ['Total de sesiones realizadas', (string)($resumen['total_sesiones'] ?? 0)],
                ['Primera sesión', $this->formatearFecha($resumen['primera_sesion'] ?? null)],
                ['Última sesión', $this->formatearFecha($resumen['ultima_sesion'] ?? null)],
                ['Total de citas registradas', (string)($citas['total_citas'] ?? 0)],
                ['Citas atendidas', (string)($citas['citas_atendidas'] ?? 0)],
                ['Citas canceladas', (string)($citas['citas_canceladas'] ?? 0)],
                ['Próxima cita programada', $this->formatearFechaHora($citas['proxima_cita'] ?? null)]
            ],
            'observaciones' => $ultima_sesion['observaciones_generales'] ?? '',
            'intervencion' => $ultima_sesion['intervencion_realizada'] ?? '',
            'evolucion' => $ultima_sesion['notas_evolucion'] ?? '',
            'sesiones' => $sesiones,
            'citas' => $citas
        ];
    }

    /**
     * Arma la estructura normalizada del documento a partir de un reporte guardado.
     */
    private function construirDocumentoDesdeReporte($reporte) {
        $secciones = $this->descomponerContenidoTecnico($reporte['contenido_tecnico'] ?? '');

        $fechaNacimiento = $reporte['fecha_nacimiento'] ?? null;
        $edad = null;
        if ($fechaNacimiento) {
            $nacimiento = new \DateTime($fechaNacimiento);
            $edad = $nacimiento->diff(new \DateTime())->y;
        }

        $reporteModel = $this->modelo('Reporte');

        return [
            'titulo' => 'Informe Técnico Psicológico',
            'subtitulo' => 'Departamento de Psicología — INFRAVENZ',
            'paciente_nombre' => trim($reporte['nombres'] . ' ' . $reporte['apellidos']),
            'fecha_emision' => $this->formatearFecha($reporte['fecha_generacion'] ?? null),
            'autor' => $reporte['autor_nombre'] ?? 'N/A',
            'expediente_n' => str_pad($reporte['id_expediente'], 4, '0', STR_PAD_LEFT),
            'reporte_n' => str_pad($reporte['id_reporte'], 4, '0', STR_PAD_LEFT),
            'urgencia' => $reporte['nivel_urgencia'] ?? 'Bajo',
            'identificacion' => [
                ['Expediente N°', '#' . str_pad($reporte['id_expediente'], 4, '0', STR_PAD_LEFT)],
                ['Reporte N°', '#' . str_pad($reporte['id_reporte'], 4, '0', STR_PAD_LEFT)],
                ['Tipo de paciente', $reporte['tipo_paciente']],
                ['Nombres', $reporte['nombres']],
                ['Apellidos', $reporte['apellidos']],
                ['Identificación (NIE/DUI)', $reporte['nie_dui']],
                ['Fecha de nacimiento', $this->formatearFecha($fechaNacimiento)],
                ['Edad', $edad !== null ? $edad . ' años' : 'N/A'],
                ['Género', $reporte['genero']],
                ['Grado / Sección', $reporte['grado_seccion'] ?: 'N/A'],
                ['Teléfono de contacto', $reporte['telefono_contacto'] ?: 'No registrado'],
                ['Correo electrónico', $reporte['correo_paciente'] ?? 'No registrado'],
                ['Responsable', $reporte['nombre_responsable'] ?: 'No registrado']
            ],
            'antecedentes' => [
                'medicos' => $reporte['antecedentes_medicos'] ?: 'No refiere antecedentes médicos.',
                'familiares' => $reporte['antecedentes_familiares'] ?: 'No refiere antecedentes familiares.'
            ],
            'resumen' => [
                ['Fecha de apertura del expediente', $this->formatearFecha($reporte['fecha_apertura'] ?? null)],
                ['Total de sesiones realizadas', (string)($reporteModel->obtenerResumenSesiones($reporte['id_expediente'])['total_sesiones'] ?? 0)],
                ['Fecha de generación del informe', $this->formatearFecha($reporte['fecha_generacion'] ?? null)],
                ['Nivel de urgencia', $reporte['nivel_urgencia'] ?? 'Bajo']
            ],
            'observaciones' => $secciones['observaciones'],
            'intervencion' => $secciones['intervencion'],
            'evolucion' => $secciones['evolucion'],
            'sesiones' => [],
            'citas' => []
        ];
    }

    /**
     * Separa el contenido almacenado del informe en sus tres secciones clinicas.
     */
    private function descomponerContenidoTecnico($contenido) {
        $secciones = ['observaciones' => '', 'intervencion' => '', 'evolucion' => ''];

        $patrones = [
            'observaciones' => 'OBSERVACIONES CLÍNICAS:',
            'intervencion' => 'INTERVENCIÓN REALIZADA:',
            'evolucion' => 'NOTAS DE EVOLUCIÓN Y RECOMENDACIONES:'
        ];

        $texto = str_replace("\r\n", "\n", (string)$contenido);

        foreach ($patrones as $clave => $etiqueta) {
            if (stripos($texto, $etiqueta) !== false) {
                $posicion = stripos($texto, $etiqueta) + strlen($etiqueta);
                $resto = substr($texto, $posicion);
                // Se corta en la siguiente etiqueta conocida
                $siguiente = null;
                foreach ($patrones as $otraEtiqueta) {
                    if ($otraEtiqueta === $etiqueta) {
                        continue;
                    }
                    $pos = stripos($resto, $otraEtiqueta);
                    if ($pos !== false && ($siguiente === null || $pos < $siguiente)) {
                        $siguiente = $pos;
                    }
                }
                $valor = $siguiente !== null ? substr($resto, 0, $siguiente) : $resto;
                $secciones[$clave] = trim($valor);
            }
        }

        // Si no se pudo descomponer, todo el contenido va a observaciones
        if ($secciones['observaciones'] === '' && $secciones['intervencion'] === '' && $secciones['evolucion'] === '') {
            $secciones['observaciones'] = trim($texto);
        }

        return $secciones;
    }

    private function formatearFecha($fecha) {
        if (empty($fecha)) {
            return 'Sin registro';
        }
        $timestamp = strtotime($fecha);
        return $timestamp ? date('d/m/Y', $timestamp) : 'Sin registro';
    }

    private function formatearFechaHora($fecha) {
        if (empty($fecha)) {
            return 'Sin registro';
        }
        $timestamp = strtotime($fecha);
        return $timestamp ? date('d/m/Y', $timestamp) . ' — ' . date('g:i A', $timestamp) : 'Sin registro';
    }
}