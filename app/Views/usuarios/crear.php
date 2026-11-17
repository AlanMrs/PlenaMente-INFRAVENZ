<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $tipo => $mensajes): ?>
        <?php foreach ($mensajes as $mensaje): ?>
            <div style="padding: 12px; background-color: <?php echo $tipo == 'success' ? '#d4edda' : ($tipo == 'warning' ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $tipo == 'success' ? '#155724' : ($tipo == 'warning' ? '#856404' : '#721c24'); ?>; border-radius: 4px; margin-bottom: 16px; font-weight: bold;">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">Crear Nuevo Usuario</h2>
    <a href="<?php echo BASE_URL; ?>/usuario" class="btn btn-secondary">Volver a la lista</a>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="<?php echo BASE_URL; ?>/usuario/guardar" method="POST">
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Nombre Completo:</label>
            <input type="text" name="nombre_completo" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Correo Electrónico:</label>
            <input type="email" name="correo" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Contraseña:</label>
            <input type="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;">Rol del Usuario:</label>
            <select name="id_rol" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">Seleccione un rol...</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?php echo (int)$rol['id_rol']; ?>">
                        <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #888;">El rol determina a qué módulos tendrá acceso el usuario.</small>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Usuario</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>