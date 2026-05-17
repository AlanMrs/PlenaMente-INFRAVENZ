<?php require_once __DIR__ . '/../layouts/header.php'; ?>

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
                <option value="1">Administrador</option>
                <option value="2">Psicólogo/a</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Usuario</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>