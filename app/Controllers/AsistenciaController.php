<?php
namespace App\Controllers;

// 🟢 AGREGAR ESTA LÍNEA: Importa el Controlador base desde el Core de tu sistema
use App\Core\Controller;

class AsistenciaController extends Controller {

    public function index() {
        // Si viene una fecha por GET la usa, de lo contrario usa el día de hoy
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        $modeloAsistencia = $this->modelo('Asistencia');
        $pacientes = $modeloAsistencia->obtenerPacientesConAsistencia($fecha);

        // Envía los datos a tu vista
        $this->vista('asistencias/index', [
            'pacientes' => $pacientes,
            'fecha' => $fecha
        ]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha = $_POST['fecha_asistencia'];
            $asistencias = $_POST['asistencias'] ?? [];

            $modeloAsistencia = $this->modelo('Asistencia');

            // Recorremos el lote de asistencias enviado desde la tabla
            foreach ($asistencias as $id_expediente => $datos) {
                $estado = $datos['estado'] ?? 'Presente';
                $observaciones = $datos['observaciones'] ?? '';
                
                $modeloAsistencia->registrarAsistencia($id_expediente, $fecha, $estado, $observaciones);
            }

            // Redirecciona de vuelta con mensaje de éxito
            header('Location: ' . BASE_URL . '/asistencia?fecha=' . $fecha . '&success=1');
            exit();
        }
    }
}