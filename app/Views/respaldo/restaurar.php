<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap;">
        <h2 style="color: var(--violeta-principal); margin: 0;">♻️ Restaurar desde un Respaldo</h2>
        <a href="<?php echo BASE_URL; ?>/respaldo" class="btn btn-secondary">⬅ Volver a Respaldos</a>
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

    <!-- Advertencia principal -->
    <div style="background: #f8d7da; border-left: 5px solid #dc3545; padding: 18px 22px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 10px 0; color: #721c24;">⚠️ Esta acción reemplaza TODOS los datos actuales</h3>
        <p style="margin: 0 0 8px 0; color: #721c24;">
            Al restaurar, el contenido de la base de datos será <strong>sustituido por el del respaldo</strong>:
            los pacientes, expedientes, citas, sesiones, asistencias e informes registrados <strong>después</strong> de ese respaldo se perderán.
        </p>
        <p style="margin: 0; color: #721c24;">
            Antes de restaurar se generará <strong>automáticamente un respaldo de seguridad</strong> con el estado actual,
            por si necesita revertir el cambio.
        </p>
    </div>

    <!-- Respaldo seleccionado -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">📦 Respaldo seleccionado</h3>
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #777; width: 30%;">Archivo</td>
                <td style="padding: 8px 0;"><code><?php echo htmlspecialchars($respaldo['archivo']); ?></code></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #777;">Generado</td>
                <td style="padding: 8px 0;"><?php echo date('d/m/Y H:i:s', $respaldo['fecha']); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #777;">Tamaño</td>
                <td style="padding: 8px 0;"><?php echo htmlspecialchars(App\Libraries\Respaldo::formatearTamano($respaldo['tamano'])); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #777;">Tablas contenidas</td>
                <td style="padding: 8px 0;"><strong><?php echo count($analisis['tablas']); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #777;">Registros totales</td>
                <td style="padding: 8px 0;"><strong><?php echo number_format($analisis['total_filas']); ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Contenido que se restaurará -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">🗃️ Contenido que se restaurará</h3>
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; color: #555;">
                    <th style="padding: 8px;">Tabla</th>
                    <th style="padding: 8px; text-align: center;">Registros</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analisis['tablas'] as $tabla => $filas): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px;"><code style="font-size: 13px;"><?php echo htmlspecialchars($tabla); ?></code></td>
                    <td style="padding: 8px; text-align: center;"><?php echo number_format($filas); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Verificaciones que se ejecutarán -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h3 style="margin-top: 0; margin-bottom: 12px; color: #333;">🔍 Verificaciones que se ejecutarán al restaurar</h3>
        <ul style="margin: 0; color: #555; font-size: 14px; line-height: 1.9;">
            <li>Que todas las tablas del respaldo existan y tengan el número de filas esperado.</li>
            <li>Que no existan registros huérfanos (citas sin paciente, sesiones sin expediente, etc.).</li>
            <li>Que los catálogos mínimos del sistema (roles y usuarios) estén presentes.</li>
        </ul>
        <p style="margin: 12px 0 0 0; font-size: 13px; color: #856404; background: #fff3cd; padding: 10px 12px; border-radius: 4px;">
            Si alguna verificación falla, el sistema <strong>revierte automáticamente</strong> la restauración con el respaldo de seguridad.
        </p>
    </div>

    <!-- Confirmación -->
    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form method="POST" action="<?php echo BASE_URL; ?>/respaldo/restaurar">
            <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($respaldo['archivo']); ?>">
            <div style="margin-bottom: 18px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">
                    Para confirmar, escribe el nombre del archivo (<code><?php echo htmlspecialchars($respaldo['archivo']); ?></code>):
                </label>
                <input type="text" name="confirmacion" required autocomplete="off"
                       placeholder="<?php echo htmlspecialchars($respaldo['archivo']); ?>"
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 14px; color: #721c24;">
                    <input type="checkbox" name="aceptar" value="1" required style="margin-top: 3px;">
                    <span>Entiendo que <strong>todos los datos actuales serán reemplazados</strong> por los del respaldo seleccionado y que se generará un respaldo de seguridad previo.</span>
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex: 1; padding: 12px; background-color: #dc3545; border: none; color: white; font-size: 16px; border-radius: 4px; cursor: pointer;">
                    ♻️ Restaurar ahora
                </button>
                <a href="<?php echo BASE_URL; ?>/respaldo" class="btn btn-secondary" style="flex: 1; padding: 12px; text-align: center; text-decoration: none;">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
