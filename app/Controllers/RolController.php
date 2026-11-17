<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;
use App\Core\Permisos;

/**
 * RolController: consulta del catalogo de roles predefinidos y de la matriz
 * de permisos asociada a cada rol.
 */
class RolController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requiere('roles');
    }

    // Pantalla de roles y permisos
    public function index() {
        $roles = $this->modelo('Rol')->obtenerTodos();
        $modulos = Permisos::modulos();
        $matriz = Permisos::matriz();
        $descripciones = Permisos::descripcionRoles();

        $this->vista('roles/index', [
            'roles' => $roles,
            'modulos' => $modulos,
            'matriz' => $matriz,
            'descripciones' => $descripciones,
            'flash' => Flash::all()
        ]);
    }
}
