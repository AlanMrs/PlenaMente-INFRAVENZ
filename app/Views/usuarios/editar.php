<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">Editar Usuario</h2>
    <a href="<?php echo BASE_URL; ?>/usuario" class="btn btn-secondary">Cancelar</a>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="<?php echo BASE_URL; ?>/usuario/actualizar/<?php echo $usuario['id_usuario']; ?>" method="POST">
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Nombre Completo:</label>
            <input type="text" name="nombre_completo" value="<?php echo $usuario['nombre_completo']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Correo Electrónico:</label>
            <input type="email" name="correo" value="<?php echo $usuario['correo']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Rol del Usuario:</label>
            <select name="id_rol" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="1" <?php echo ($usuario['id_rol'] == 1) ? 'selected' : ''; ?>>Administrador</option>
                <option value="2" <?php echo ($usuario['id_rol'] == 2) ? 'selected' : ''; ?>>Psicólogo/a</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Estado:</label>
            <select name="estado" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="Activo" <?php echo ($usuario['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                <option value="Inactivo" <?php echo ($usuario['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
            </select>
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