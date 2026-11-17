<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap;">
    <div>
        <h2 style="color: var(--violeta-principal); margin: 0 0 4px 0;">📧 Diagnóstico de Correo</h2>
        <p style="margin: 0; color: #666; font-size: 14px;">Verifica la configuración de salida y comprueba la entrega con un correo de prueba.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/recordatorio" class="btn btn-secondary">⬅ Volver a Recordatorios</a>
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

<!-- Configuracion vigente -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">⚙️ Configuración vigente</h3>
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 30%;">Remitente</td>
            <td style="padding: 8px 0;"><strong><?php echo htmlspecialchars($estado['remitente']); ?></strong> (<?php echo htmlspecialchars($estado['nombre']); ?>)</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Servidor SMTP</td>
            <td style="padding: 8px 0;">
                <?php echo htmlspecialchars($estado['servidor']); ?>:<?php echo htmlspecialchars((string)$estado['puerto']); ?>
                (<?php echo htmlspecialchars((string)$estado['cifrado']); ?>)
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Usuario SMTP</td>
            <td style="padding: 8px 0;"><?php echo htmlspecialchars($estado['usuario']); ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Clave del buzón</td>
            <td style="padding: 8px 0;">
                <span class="badge" style="background-color: <?php echo $estado['smtp_posible'] ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;'; ?>; padding: 4px 10px; border-radius: 4px; font-size: 12px;">
                    <?php echo htmlspecialchars($estado['clave']); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Envío disponible</td>
            <td style="padding: 8px 0;">
                <?php if ($estado['smtp_posible']): ?>
                    <span style="color:#155724; font-weight:bold;">SMTP (recomendado)</span> — se conocerá la respuesta real del servidor.
                <?php else: ?>
                    <span style="color:#856404; font-weight:bold;">Sin SMTP configurado</span> — se usará <code>mail()</code>, que no confirma la entrega.
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Servidor web</td>
            <td style="padding: 8px 0;"><?php echo htmlspecialchars($estado['dominio']); ?></td>
        </tr>
    </table>

    <?php if (!$estado['smtp_posible']): ?>
        <div style="padding: 12px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-top: 14px; font-size: 13px; line-height: 1.6;">
            <strong>⚠️ Configuración incompleta</strong><br>
            Para que el hosting entregue los correos se necesita:
            <ol style="margin: 8px 0 0 18px; padding: 0;">
                <li>Crear el buzón <code><?php echo htmlspecialchars($estado['remitente']); ?></code> en el panel de Hostinger (Correo electrónico → Buzones).</li>
                <li>Copiar <code>app/Config/Correo.local.ejemplo.php</code> como <code>app/Config/Correo.local.php</code> y colocar la clave del buzón.</li>
                <li>Verificar que ese buzón exista también como remitente permitido por el hosting.</li>
            </ol>
        </div>
    <?php endif; ?>
</div>

<!-- Envio de prueba -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">✉️ Enviar correo de prueba</h3>
    <form method="POST" action="<?php echo BASE_URL; ?>/recordatorio/diagnostico" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 260px;">
            <label style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: bold;">Destinatario</label>
            <input type="email" name="destinatario" required placeholder="correo@destino.com"
                   style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <button type="submit" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none; color: white; padding: 10px 18px; border-radius: 4px; cursor: pointer;">
            📨 Enviar prueba
        </button>
    </form>
</div>

<?php if ($resultado): ?>
    <!-- Resultado de la prueba -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 5px solid <?php echo $resultado['enviado'] ? '#28a745' : '#dc3545'; ?>;">
        <h3 style="margin-top: 0; margin-bottom: 12px; color: #333;">
            <?php echo $resultado['enviado'] ? '✅ El servidor aceptó el mensaje' : '❌ El envío no se completó'; ?>
        </h3>
        <p style="margin: 0 0 12px 0; color: #555; font-size: 14px;">
            Destino: <strong><?php echo htmlspecialchars($resultado['destinatario']); ?></strong> ·
            Método: <strong><?php echo htmlspecialchars($resultado['diagnostico']['metodo'] ?? '-'); ?></strong> ·
            Remitente: <strong><?php echo htmlspecialchars($resultado['diagnostico']['remitente'] ?? '-'); ?></strong>
        </p>

        <?php if (!empty($resultado['diagnostico']['errores'])): ?>
            <div style="padding: 12px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
                <strong>Errores:</strong><br>
                <?php foreach ($resultado['diagnostico']['errores'] as $error): ?>
                    • <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($resultado['diagnostico']['advertencia'])): ?>
            <div style="padding: 12px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
                <strong>Advertencia:</strong> <?php echo htmlspecialchars($resultado['diagnostico']['advertencia']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($resultado['diagnostico']['dialogo'])): ?>
            <details open>
                <summary style="cursor: pointer; font-weight: bold; color: #333; font-size: 14px;">🗒️ Diálogo con el servidor de correo</summary>
                <pre style="background: #263238; color: #c8e6c9; padding: 14px; border-radius: 6px; overflow-x: auto; font-size: 12px; line-height: 1.6; margin-top: 10px;"><?php echo htmlspecialchars(implode("\n", $resultado['diagnostico']['dialogo'])); ?></pre>
            </details>
        <?php endif; ?>
    </div>
<?php endif; ?>

<p style="text-align:center; font-size:12px; color:#888; margin-top:20px;">
    Si el mensaje se acepta pero no llega, revise la carpeta de spam del destinatario y que el buzón remitente exista en el hosting.
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
