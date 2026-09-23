<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class UsuarioController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] !== 3) {
            $this->redirect(BASE_URL . '/dashboard');
        }
    }
    
    // Método principal que lista los usuarios
    public function index() {
        $usuarioModel = $this->modelo('Usuario');
        
        $usuarios = $usuarioModel->obtenerTodos();
        
        $this->vista('usuarios/index', [
            'usuarios' => $usuarios
        ]);
    }

    // Muestra la pantalla del formulario
    public function crear() {
        $this->vista('usuarios/crear');
    }

    // Recibe los datos del formulario, encripta y guarda
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nombre = trim($_POST['nombre_completo'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $password = $_POST['password'] ?? '';
            $id_rol = (int)($_POST['id_rol'] ?? 0);

            if (!$nombre || !$correo || !$password || !$id_rol) {
                Flash::error('Todos los campos son obligatorios.');
                $this->redirect(BASE_URL . '/usuario/crear');
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                Flash::error('El correo no tiene un formato válido.');
                $this->redirect(BASE_URL . '/usuario/crear');
            }

            if (strlen($password) < 8) {
                Flash::error('La contraseña debe tener al menos 8 caracteres.');
                $this->redirect(BASE_URL . '/usuario/crear');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $usuarioModel = $this->modelo('Usuario');
            $exito = $usuarioModel->crearUsuario($id_rol, $nombre, $correo, $password_hash);

            if ($exito) {
                Logger::info('Usuario creado', ['correo' => $correo, 'id_rol' => $id_rol]);
                Flash::success('Usuario registrado correctamente.');
                $this->redirect(BASE_URL . '/usuario');
            } else {
                Logger::error('Error al crear usuario', ['correo' => $correo]);
                Flash::error('Hubo un error al guardar el usuario (posible correo duplicado).');
                $this->redirect(BASE_URL . '/usuario/crear');
            }
        }
    }

    // Muestra el formulario con los datos actuales
    public function editar($id = null) {
        if (!$id) {
            $this->redirect(BASE_URL . '/usuario');
        }

        $usuarioModel = $this->modelo('Usuario');
        $usuario = $usuarioModel->obtenerPorId($id);

        if (!$usuario) {
            Flash::error('Usuario no encontrado.');
            $this->redirect(BASE_URL . '/usuario');
        }

        $this->vista('usuarios/editar', ['usuario' => $usuario]);
    }

    // Procesa los cambios
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nombre = trim($_POST['nombre_completo'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $id_rol = (int)($_POST['id_rol'] ?? 0);
            $estado = $_POST['estado'] ?? 'Activo';
            $password = $_POST['password'] ?? '';

            if (!$nombre || !$correo || !$id_rol) {
                Flash::error('Todos los campos obligatorios deben ser completados.');
                $this->redirect(BASE_URL . '/usuario/editar/' . $id);
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                Flash::error('El correo no tiene un formato válido.');
                $this->redirect(BASE_URL . '/usuario/editar/' . $id);
            }

            $password_hash = null;
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    Flash::error('La contraseña debe tener al menos 8 caracteres.');
                    $this->redirect(BASE_URL . '/usuario/editar/' . $id);
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
            }

            $usuarioModel = $this->modelo('Usuario');
            $usuarioModel->actualizarUsuario($id, $id_rol, $nombre, $correo, $estado, $password_hash);

            Logger::info('Usuario actualizado', ['id_usuario' => $id]);
            Flash::success('Usuario actualizado correctamente.');
            $this->redirect(BASE_URL . '/usuario');
        }
    }

    // Procesa la eliminación lógica
    public function eliminar($id = null) {
        if ($id) {
            if ((int)$id === (int)$_SESSION['usuario_id']) {
                Flash::error('No puede desactivar su propia cuenta.');
                $this->redirect(BASE_URL . '/usuario');
            }
            
            $usuarioModel = $this->modelo('Usuario');
            $usuarioModel->eliminarLogico($id);
            
            Logger::info('Usuario desactivado', ['id_usuario' => $id, 'por' => $_SESSION['usuario_id']]);
            Flash::success('Usuario desactivado correctamente.');
        }

        $this->redirect(BASE_URL . '/usuario');
    }
}