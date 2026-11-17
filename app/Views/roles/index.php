<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">🔐 Roles y Permisos</h2>
    <a href="<?php echo BASE_URL; ?>/usuario" class="btn btn-secondary">⬅ Volver a Usuarios</a>
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

<div style="padding: 12px; background-color: #e8f0fe; color: #1a3d6d; border-radius: 4px; margin-bottom: 20px;">
    ℹ️ Los roles son <strong>predefinidos</strong> y no se pueden eliminar. Para asignar un rol a un usuario ve a
    <a href="<?php echo BASE_URL; ?>/usuario" style="color: #1a3d6d; font-weight: bold;">Gestión de Usuarios → Editar</a>.
    Los cambios de rol se aplican <strong>de inmediato</strong>, sin necesidad de volver a iniciar sesión.
</div>

<!-- Catalogo de roles -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">📋 Catálogo de Roles</h3>
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #eee; color: #555;">
                <th style="padding: 10px; text-align: center;">ID</th>
                <th style="padding: 10px;">Rol</th>
                <th style="padding: 10px;">Alcance del acceso</th>
                <th style="padding: 10px; text-align: center;">Usuarios activos</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $rol): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px; text-align: center;"><strong><?php echo (int)$rol['id_rol']; ?></strong></td>
                    <td style="padding: 10px;">
                        <strong><?php echo htmlspecialchars($rol['nombre_rol']); ?></strong>
                    </td>
                    <td style="padding: 10px; color: #555; font-size: 14px;">
                        <?php echo htmlspecialchars($descripciones[(int)$rol['id_rol']] ?? 'Sin descripcion.'); ?>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <span class="badge" style="background-color: var(--verde-menta); color: #333;">
                            <?php echo (int)$rol['usuarios_activos']; ?>
                        </span>
                        <small style="color: #888;">de <?php echo (int)$rol['usuarios_total']; ?></small>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Matriz de permisos -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">🛡️ Matriz de Permisos por Funcionalidad</h3>
    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; min-width: 620px;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; color: #555;">
                    <th style="padding: 10px;">Funcionalidad</th>
                    <?php foreach ($roles as $rol): ?>
                        <th style="padding: 10px; text-align: center;"><?php echo htmlspecialchars($rol['nombre_rol']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modulos as $clave => $etiqueta): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?php echo htmlspecialchars($etiqueta); ?></td>
                        <?php foreach ($roles as $rol): ?>
                            <?php $permitido = in_array((int)$rol['id_rol'], $matriz[$clave] ?? [], true); ?>
                            <td style="padding: 10px; text-align: center;">
                                <?php if ($permitido): ?>
                                    <span style="color: #28a745; font-weight: bold; font-size: 16px;" title="Permitido">✔</span>
                                <?php else: ?>
                                    <span style="color: #ccc; font-weight: bold; font-size: 16px;" title="No permitido">✘</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <small style="color: #888;">✔ Acceso permitido · ✘ Acceso restringido</small>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
