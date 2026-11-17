<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;
use App\Core\Permisos;

class UsuarioController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requiere('usuarios');
    }
    
    // Método principal que lista los usuarios
    public function index() {
        $usuarioModel = $this->modelo('Usuario');
        
        $usuarios = $usuarioModel->obtenerTodos();
        
        $this->vista('usuarios/index', [
            'usuarios' => $usuarios,
            'flash' => Flash::all()
        ]);
    }

    // Muestra la pantalla del formulario
    public function crear() {
        $this->vista('usuarios/crear', [
            'roles' => $this->modelo('Rol')->obtenerTodos(),
            'flash' => Flash::all()
        ]);
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

            // El rol debe existir en el catalogo de roles predefinidos
            $rolModel = $this->modelo('Rol');
            if (!$rolModel->existe($id_rol)) {
                Flash::error('El rol seleccionado no es válido.');
                $this->redirect(BASE_URL . '/usuario/crear');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $usuarioModel = $this->modelo('Usuario');
            $exito = $usuarioModel->crearUsuario($id_rol, $nombre, $correo, $password_hash);

            if ($exito) {
                Logger::info('Usuario creado', ['correo' => $correo, 'id_rol' => $id_rol]);
                $this->auditar('CREAR', 'usuario', 0, 'Usuario creado: ' . $nombre . ' (' . $correo . ') con rol ' . $rolModel->obtenerPorId($id_rol)['nombre_rol'] . '.');
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

        $this->vista('usuarios/editar', [
            'usuario' => $usuario,
            'roles' => $this->modelo('Rol')->obtenerTodos(),
            'es_propio' => ((int)$_SESSION['usuario_id'] === (int)$usuario['id_usuario']),
            'flash' => Flash::all()
        ]);
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

            $usuarioModel = $this->modelo('Usuario');
            $rolModel = $this->modelo('Rol');

            // El rol debe existir en el catalogo de roles predefinidos
            if (!$rolModel->existe($id_rol)) {
                Flash::error('El rol seleccionado no es válido.');
                $this->redirect(BASE_URL . '/usuario/editar/' . $id);
            }

            $usuario = $usuarioModel->obtenerPorId($id);
            if (!$usuario) {
                Flash::error('Usuario no encontrado.');
                $this->redirect(BASE_URL . '/usuario');
            }

            $rolAnterior = (int)$usuario['id_rol'];
            $soyPropio = ((int)$_SESSION['usuario_id'] === (int)$id);

            // Un administrador no puede cambiar su propio rol
            if ($soyPropio && $id_rol !== $rolAnterior) {
                Flash::error('No puedes cambiar tu propio rol. Pide a otro administrador que lo haga.');
                $this->redirect(BASE_URL . '/usuario/editar/' . $id);
            }

            // Un administrador no puede desactivar su propia cuenta
            if ($soyPropio && $estado !== 'Activo' && $usuario['estado'] === 'Activo') {
                Flash::error('No puedes desactivar tu propia cuenta.');
                $this->redirect(BASE_URL . '/usuario/editar/' . $id);
            }

            // No se puede quedar el sistema sin ningun administrador activo
            $dejaDeSerAdmin = ($rolAnterior === Permisos::ROL_ADMINISTRADOR && $id_rol !== Permisos::ROL_ADMINISTRADOR);
            $seDesactiva = ($estado !== 'Activo' && $usuario['estado'] === 'Activo');
            if ($rolAnterior === Permisos::ROL_ADMINISTRADOR && ($dejaDeSerAdmin || $seDesactiva)) {
                if ($usuarioModel->contarActivosPorRol(Permisos::ROL_ADMINISTRADOR, $id) === 0) {
                    Flash::error('No puedes dejar el sistema sin ningún administrador activo. Activa otro administrador primero.');
                    $this->redirect(BASE_URL . '/usuario/editar/' . $id);
                }
            }

            $password_hash = null;
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    Flash::error('La contraseña debe tener al menos 8 caracteres.');
                    $this->redirect(BASE_URL . '/usuario/editar/' . $id);
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
            }

            $usuarioModel->actualizarUsuario($id, $id_rol, $nombre, $correo, $estado, $password_hash);

            $descripcion = 'Usuario actualizado: ' . $nombre . ' (' . $correo . ').';
            if ($id_rol !== $rolAnterior) {
                $descripcion .= ' Rol modificado de ' . $rolModel->obtenerPorId($rolAnterior)['nombre_rol']
                    . ' a ' . $rolModel->obtenerPorId($id_rol)['nombre_rol'] . '.';
            }
            if ($seDesactiva) {
                $descripcion .= ' Cuenta desactivada.';
            }
            $this->auditar('ACTUALIZAR', 'usuario', (int)$id, $descripcion);

            Logger::info('Usuario actualizado', [
                'id_usuario' => $id,
                'rol_anterior' => $rolAnterior,
                'rol_nuevo' => $id_rol,
                'estado' => $estado
            ]);
            Flash::success('Usuario actualizado correctamente. El cambio se aplica de inmediato.');
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
            $usuario = $usuarioModel->obtenerPorId($id);

            if (!$usuario) {
                Flash::error('Usuario no encontrado.');
                $this->redirect(BASE_URL . '/usuario');
            }

            // No se puede quedarse el sistema sin ningun administrador activo
            if ((int)$usuario['id_rol'] === Permisos::ROL_ADMINISTRADOR
                && $usuarioModel->contarActivosPorRol(Permisos::ROL_ADMINISTRADOR, $id) === 0) {
                Flash::error('No puedes desactivar al único administrador activo del sistema.');
                $this->redirect(BASE_URL . '/usuario');
            }
            
            $usuarioModel->eliminarLogico($id);

            $this->auditar('DESACTIVAR', 'usuario', (int)$id, 'Usuario desactivado: ' . $usuario['nombre_completo'] . ' (' . $usuario['correo'] . ').');
            Logger::info('Usuario desactivado', ['id_usuario' => $id, 'por' => $_SESSION['usuario_id']]);
            Flash::success('Usuario desactivado correctamente. El acceso se revoca de inmediato.');
        }

        $this->redirect(BASE_URL . '/usuario');
    }

    // Registrar en la tabla de auditoría (sin interrumpir la operación si falla)
}