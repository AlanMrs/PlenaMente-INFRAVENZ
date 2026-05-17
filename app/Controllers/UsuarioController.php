<?php
namespace App\Controllers;

use App\Core\Controller;

class UsuarioController extends Controller {

    
    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
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
            // 1. Recibimos los datos limpios
            $nombre = trim($_POST['nombre_completo']);
            $correo = trim($_POST['correo']);
            $password = $_POST['password'];
            $id_rol = $_POST['id_rol'];

            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            
            $usuarioModel = $this->modelo('Usuario');
            $exito = $usuarioModel->crearUsuario($id_rol, $nombre, $correo, $password_hash);

            if ($exito) {
                
                header('Location: ' . BASE_URL . '/usuario');
                exit;
            } else {
                echo "Hubo un error al guardar el usuario en la base de datos.";
            }
        }
    }
}