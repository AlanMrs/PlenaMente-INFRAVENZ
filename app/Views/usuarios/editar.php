<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
// Nombre del rol actual del usuario editado (sin depender del indice del array)
$nombreRolActual = 'Sin rol';
foreach ($roles as $rol) {
    if ((int)$rol['id_rol'] === (int)$usuario['id_rol']) {
        $nombreRolActual = $rol['nombre_rol'];
        break;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">Editar Usuario</h2>
    <a href="<?php echo BASE_URL; ?>/usuario" class="btn btn-secondary">Cancelar</a>
</div>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $tipo => $mensajes): ?>
        <?php foreach ($mensajes as $mensaje): ?>
            <div style="padding: 12px; background-color: <?php echo $tipo == 'success' ? '#d4edda' : ($tipo == 'warning' ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $tipo == 'success' ? '#155724' : ($tipo == 'warning' ? '#856404' : '#721c24'); ?>; border-radius: 4px; margin-bottom: 16px; font-weight: bold;">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="<?php echo BASE_URL; ?>/usuario/actualizar/<?php echo $usuario['id_usuario']; ?>" method="POST">
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Nombre Completo:</label>
            <input type="text" name="nombre_completo" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Correo Electrónico:</label>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Rol del Usuario:</label>
            <?php if ($es_propio): ?>
                <input type="hidden" name="id_rol" value="<?php echo (int)$usuario['id_rol']; ?>">
                <input type="text" value="<?php echo htmlspecialchars($nombreRolActual); ?>" disabled
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f2f2f2; color: #666;">
                <small style="color: #856404;">No puedes cambiar tu propio rol. Pide a otro administrador que lo haga.</small>
            <?php else: ?>
                <select name="id_rol" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo (int)$rol['id_rol']; ?>" <?php echo ((int)$usuario['id_rol'] === (int)$rol['id_rol']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #888;">El cambio de rol se aplica de inmediato al usuario afectado.</small>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Estado:</label>
            <?php if ($es_propio): ?>
                <input type="hidden" name="estado" value="<?php echo htmlspecialchars($usuario['estado']); ?>">
                <input type="text" value="<?php echo htmlspecialchars($usuario['estado']); ?>" disabled
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f2f2f2; color: #666;">
                <small style="color: #856404;">No puedes desactivar tu propia cuenta.</small>
            <?php else: ?>
                <select name="estado" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="Activo" <?php echo ($usuario['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value="Inactivo" <?php echo ($usuario['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
                <small style="color: #888;">Al desactivar la cuenta, el usuario pierde el acceso de inmediato.</small>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 4px; border: 1px dashed #ccc;">
            <label style="display: block; margin-bottom: 5px;">Nueva Contraseña (Opcional):</label>
            <input type="password" name="password" placeholder="Dejar en blanco para no cambiarla" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <small style="color: #666;">Solo llena este campo si deseas cambiar la contraseña actual del usuario.</small>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Cambios</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
