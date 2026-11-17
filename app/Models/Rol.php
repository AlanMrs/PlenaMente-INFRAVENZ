<?php
namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Rol: catalogo de roles predefinidos del sistema (tabla `roles`).
 */
class Rol {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Listar los roles predefinidos con la cantidad de usuarios asignados
    public function obtenerTodos() {
        $sql = "SELECT r.id_rol, r.nombre_rol,
                       (SELECT COUNT(*) FROM usuarios u WHERE u.id_rol = r.id_rol AND u.estado = 'Activo') AS usuarios_activos,
                       (SELECT COUNT(*) FROM usuarios u WHERE u.id_rol = r.id_rol) AS usuarios_total
                FROM roles r
                ORDER BY r.id_rol";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un rol por su identificador
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id_rol = :id LIMIT 1");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ¿El identificador corresponde a un rol predefinido existente?
    public function existe($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE id_rol = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
