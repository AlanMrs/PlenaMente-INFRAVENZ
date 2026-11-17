<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* Acciones de la tabla de respaldos: separadas y diferenciadas por riesgo */
    .respaldo-acciones {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .respaldo-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 14px;
        margin: 0;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.2;
        color: #fff;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }

    .respaldo-btn:hover {
        opacity: .88;
        transform: translateY(-1px);
    }

    .respaldo-btn-restaurar {
        background-color: #17a2b8;
    }

    .respaldo-btn-descargar {
        background-color: #28a745;
    }

    .respaldo-btn-eliminar {
        background-color: #dc3545;
    }

    /* Separador vertical entre las acciones seguras y la destructiva */
    .respaldo-separador {
        width: 1px;
        height: 22px;
        margin: 0 4px;
        background-color: #d5d5d5;
    }

    .respaldo-form {
        display: inline;
        margin: 0;
    }
</style>
<?php require_once __DIR__ . '/../../Libraries/Respaldo.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap;">
    <div>
        <h2 style="color: var(--violeta-principal); margin: 0 0 4px 0;">💾 Respaldos del Sistema</h2>
        <p style="margin: 0; color: #666; font-size: 14px;">Copias de seguridad de la base de datos del sistema.</p>
    </div>
    <form method="POST" action="<?php echo BASE_URL; ?>/respaldo/generar" onsubmit="return confirm('¿Generar un nuevo respaldo de la base de datos?');">
        <button type="submit" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none; color: white; padding: 12px 18px; font-size: 15px;">
            💾 Generar respaldo
        </button>
    </form>
</div>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $tipo => $mensajes): ?>
        <?php foreach ($mensajes as $mensaje): ?>
            <div style="padding: 12px; background-color: <?php echo $tipo == 'success' ? '#d4edda' : ($tipo == 'warning' ? '#fff3cd' : ($tipo == 'info' ? '#e8f0fe' : '#f8d7da')); ?>; color: <?php echo $tipo == 'success' ? '#155724' : ($tipo == 'warning' ? '#856404' : ($tipo == 'info' ? '#1a3d6d' : '#721c24')); ?>; border-radius: 4px; margin-bottom: 16px; font-weight: bold;">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div style="background: #fff; border-left: 4px solid var(--violeta-principal); padding: 16px 18px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <div style="font-size: 12px; color: #777; text-transform: uppercase; letter-spacing: .5px; font-weight: bold;">Respaldos almacenados</div>
        <div style="font-size: 28px; font-weight: 700; color: var(--violeta-principal); margin: 6px 0 2px 0;"><?php echo count($respaldos); ?></div>
        <small style="color: #888;">Se conservan los <?php echo (int)$maximo; ?> más recientes; los antiguos se purgan automáticamente.</small>
    </div>
    <div style="background: #fff; border-left: 4px solid var(--verde-menta); padding: 16px 18px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <div style="font-size: 12px; color: #777; text-transform: uppercase; letter-spacing: .5px; font-weight: bold;">Último respaldo</div>
        <div style="font-size: 16px; font-weight: 700; color: #333; margin: 8px 0 2px 0;">
            <?php echo empty($respaldos) ? 'Ninguno' : htmlspecialchars($respaldos[0]['etiqueta']); ?>
        </div>
        <small style="color: #888;">Último archivo generado con éxito.</small>
    </div>
    <div style="background: #fff; border-left: 4px solid var(--azul-medio); padding: 16px 18px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <div style="font-size: 12px; color: #777; text-transform: uppercase; letter-spacing: .5px; font-weight: bold;">Ubicación segura</div>
        <div style="font-size: 13px; color: #333; margin: 8px 0 2px 0; word-break: break-all;"><?php echo htmlspecialchars($directorio); ?></div>
        <small style="color: #888;">Fuera del directorio público, con acceso bloqueado por <code>.htaccess</code>.</small>
    </div>
</div>

<div style="padding: 12px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 20px;">
    ⚠️ Los respaldos contienen <strong>información clínica sensible</strong>. Solo el Administrador puede descargarlos y deben custodiarse en un lugar seguro.
</div>

<div class="card">
    <div class="card-header">Respaldos generados</div>

    <?php if (empty($respaldos)): ?>
        <p style="text-align: center; color: #666; margin: 20px 0;">
            Todavía no hay respaldos generados. Pulsa <strong>💾 Generar respaldo</strong> para crear el primero.
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Fecha de generación</th>
                    <th>Tamaño</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($respaldos as $respaldo): ?>
                    <?php $tokenArchivo = App\Controllers\RespaldoController::tokenArchivo($respaldo['archivo']); ?>
                    <tr>
                    <td>
                        <code style="font-size: 13px;"><?php echo htmlspecialchars($respaldo['archivo']); ?></code>
                    </td>
                    <td><?php echo htmlspecialchars($respaldo['etiqueta']); ?></td>
                    <td><?php echo htmlspecialchars(App\Libraries\Respaldo::formatearTamano($respaldo['tamano'])); ?></td>
                    <td style="text-align: center;">
                        <div class="respaldo-acciones">
                            <a href="<?php echo BASE_URL; ?>/respaldo/restaurar?archivo=<?php echo $tokenArchivo; ?>"
                               class="btn respaldo-btn respaldo-btn-restaurar">
                                ♻️ Restaurar
                            </a>
                            <a href="<?php echo BASE_URL; ?>/respaldo/descargar?archivo=<?php echo $tokenArchivo; ?>"
                               class="btn respaldo-btn respaldo-btn-descargar">
                                ⬇ Descargar
                            </a>
                            <span class="respaldo-separador" aria-hidden="true"></span>
                            <form class="respaldo-form" method="POST"
                                  action="<?php echo BASE_URL; ?>/respaldo/eliminar"
                                  onsubmit="return confirm('¿Eliminar definitivamente este respaldo?');">
                                <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($respaldo['archivo']); ?>">
                                <button type="submit" class="btn respaldo-btn respaldo-btn-eliminar">
                                    🗑 Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p style="text-align:center; font-size:12px; color:#888; margin-top:20px;">
    El respaldo se genera en formato SQL comprimido (gzip) e incluye la estructura y los datos de todas las tablas del sistema.
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
