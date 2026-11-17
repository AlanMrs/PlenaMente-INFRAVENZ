<?php
namespace App\Core;

use App\Config\Database;
use PDO;

/**
 * Sesion: mantiene sincronizada la sesion del usuario con la base de datos.
 *
 * Se ejecuta en cada peticion (ver App\Core\App) para que los cambios de rol o
 * de estado realizados por un administrador se apliquen de inmediato, sin
 * necesidad de que el usuario vuelva a iniciar sesion.
 */
class Sesion {

    /**
     * Relee el rol y el estado del usuario desde la BD y actualiza la sesion.
     * - Si la cuenta fue desactivada o eliminada, se cierra la sesion.
     * - Si el rol cambio, se actualiza en caliente y queda registrado en el log.
     *
     * @return array ['accion' => 'ok'|'logout', 'mensaje' => string]
     */
    public static function sincronizar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['usuario_id'])) {
            return ['accion' => 'ok', 'mensaje' => ''];
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT id_usuario, id_rol, estado FROM usuarios WHERE id_usuario = :id LIMIT 1");
            $stmt->bindValue(':id', (int)$_SESSION['usuario_id'], PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Si la BD no responde se mantiene la sesion actual (no se cierra por un fallo puntual)
            Logger::error('No se pudo sincronizar la sesion con la BD', ['error' => $e->getMessage()]);
            return ['accion' => 'ok', 'mensaje' => ''];
        }

        // El usuario ya no existe
        if (!$usuario) {
            self::cerrarSesion();
            Logger::warning('Sesion cerrada: el usuario ya no existe', ['usuario_id' => $_SESSION['usuario_id'] ?? null]);
            return ['accion' => 'logout', 'mensaje' => 'Tu cuenta ya no está disponible. Contacta al administrador.'];
        }

        // La cuenta fue desactivada: se cierra la sesion de inmediato
        if ($usuario['estado'] !== 'Activo') {
            self::cerrarSesion();
            Logger::warning('Sesion cerrada por cuenta desactivada', ['usuario_id' => $usuario['id_usuario']]);
            return ['accion' => 'logout', 'mensaje' => 'Tu cuenta fue desactivada. Contacta al administrador.'];
        }

        // El rol cambio: se aplica de inmediato
        $rolActual = (int)($_SESSION['id_rol'] ?? 0);
        $rolNuevo = (int)$usuario['id_rol'];
        if ($rolActual !== $rolNuevo) {
            Logger::info('Rol aplicado en caliente', [
                'usuario_id' => $usuario['id_usuario'],
                'rol_anterior' => $rolActual,
                'rol_nuevo' => $rolNuevo
            ]);
            $_SESSION['id_rol'] = $rolNuevo;
        }

        return ['accion' => 'ok', 'mensaje' => ''];
    }

    /**
     * Cierra la sesion conservando los mensajes flash.
     */
    private static function cerrarSesion() {
        $flash = $_SESSION['__flash_messages'] ?? [];
        $_SESSION = [];
        if (!empty($flash)) {
            $_SESSION['__flash_messages'] = $flash;
        }
    }
}
