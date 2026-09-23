<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;

class AuthController extends Controller {
    
    // 1. Mostrar la pantalla de Login
    public function login() {
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/dashboard');
        }
        $this->vista('auth/login');
    }

    // 2. Procesar los datos que vienen del formulario
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $correo = trim($_POST['correo'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$correo || !$password) {
                Flash::error('Correo y contraseña son obligatorios.');
                $this->redirect(BASE_URL . '/auth/login');
            }

            $usuarioModel = $this->modelo('Usuario');
            $usuario = $usuarioModel->obtenerPorCorreo($correo);

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                if ($usuario['estado'] !== 'Activo') {
                    Logger::warning('Intento de login con cuenta inactiva', ['correo' => $correo]);
                    Flash::error('Esta cuenta ha sido desactivada. Consulte con el administrador.');
                    $this->redirect(BASE_URL . '/auth/login');
                }

                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                
                Logger::info('Login exitoso', ['usuario_id' => $usuario['id_usuario'], 'rol' => $usuario['id_rol']]);
                Flash::success('Bienvenido, ' . htmlspecialchars($usuario['nombre_completo']));
                $this->redirect(BASE_URL . '/dashboard');
            } else {
                Logger::warning('Intento de login fallido', ['correo' => $correo]);
                Flash::error('Correo o contraseña incorrectos.');
                $this->redirect(BASE_URL . '/auth/login');
            }
        }
    }

    // 3. Cerrar sesión
    public function logout() {
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        session_unset();
        session_destroy();
        
        if ($usuarioId) {
            Logger::info('Logout', ['usuario_id' => $usuarioId]);
        }
        Flash::success('Sesión cerrada correctamente.');
        $this->redirect(BASE_URL . '/auth/login');
    }
}
?>