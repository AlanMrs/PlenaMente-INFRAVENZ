<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">🔔 Recordatorios de Citas</h2>
    <a href="<?php echo BASE_URL; ?>/cita" class="btn btn-secondary">📅 Ir a la Agenda</a>
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

<p style="color: #555; margin-bottom: 16px;">
    Se muestran las citas <strong>Programadas</strong> que ocurrirán en las próximas <strong><?php echo (int)$horas; ?> horas</strong>.
    Envía un recordatorio por correo para reducir las ausencias.
</p>

<!-- Próximas citas -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 24px;">
    <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">⏳ Próximas citas</h3>

    <?php if (empty($proximas)): ?>
        <p style="text-align: center; color: #666; margin: 20px 0;">No hay citas programadas en las próximas <?php echo (int)$horas; ?> horas.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; color: #555;">
                    <th style="padding: 12px;">Fecha y Hora</th>
                    <th style="padding: 12px;">Paciente</th>
                    <th style="padding: 12px;">Correo</th>
                    <th style="padding: 12px;">Motivo</th>
                    <th style="padding: 12px;">Recordatorio</th>
                    <th style="padding: 12px; text-align: center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proximas as $cita): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <strong><?php echo date('d/m/Y', strtotime($cita['fecha_hora'])); ?></strong><br>
                            <span style="color: #777; font-size: 13px;">⏰ <?php echo date('g:i A', strtotime($cita['fecha_hora'])); ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos']); ?><br>
                            <small style="color: #888;">DUI/NIE: <?php echo htmlspecialchars($cita['nie_dui']); ?></small>
                        </td>
                        <td style="padding: 12px; font-size: 13px;">
                            <?php echo !empty($cita['correo_paciente']) ? htmlspecialchars($cita['correo_paciente']) : '<span style="color:#c00;">Sin correo</span>'; ?>
                        </td>
                        <td style="padding: 12px; color: #555; font-size: 14px; max-width: 220px;"><?php echo htmlspecialchars($cita['motivo_cita']); ?></td>
                        <td style="padding: 12px;">
                            <?php if (!empty($cita['ultimo_envio'])): ?>
                                <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #d4edda; color: #155724;">Enviado</span><br>
                                <small style="color: #777;"><?php echo date('d/m/Y g:i A', strtotime($cita['ultimo_envio'])); ?></small>
                            <?php else: ?>
                                <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #fff3cd; color: #856404;">No enviado</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if (!empty($cita['correo_paciente'])): ?>
                                <form method="POST" action="<?php echo BASE_URL; ?>/recordatorio/enviar/<?php echo $cita['id_cita']; ?>" style="display: inline;">
                                    <button type="submit" class="btn" style="background-color: var(--violeta-principal); border: none; color: white; padding: 6px 12px; font-size: 13px; border-radius: 4px; cursor: pointer;">
                                        🔔 <?php echo !empty($cita['ultimo_envio']) ? 'Reenviar' : 'Enviar'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Historial de envíos -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">🕓 Historial de envíos</h3>

    <?php if (empty($historial)): ?>
        <p style="text-align: center; color: #666; margin: 20px 0;">Aún no se han enviado recordatorios.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; color: #555;">
                    <th style="padding: 12px;">Fecha de envío</th>
                    <th style="padding: 12px;">Cita</th>
                    <th style="padding: 12px;">Paciente</th>
                    <th style="padding: 12px;">Canal</th>
                    <th style="padding: 12px;">Destinatario</th>
                    <th style="padding: 12px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $reg): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; font-size: 13px;"><?php echo date('d/m/Y g:i A', strtotime($reg['fecha_envio'])); ?></td>
                        <td style="padding: 12px; font-size: 13px;"><?php echo date('d/m/Y g:i A', strtotime($reg['fecha_hora'])); ?></td>
                        <td style="padding: 12px; font-size: 13px;"><?php echo htmlspecialchars($reg['nombres'] . ' ' . $reg['apellidos']); ?></td>
                        <td style="padding: 12px; font-size: 13px;"><?php echo htmlspecialchars($reg['canal']); ?></td>
                        <td style="padding: 12px; font-size: 13px;"><?php echo htmlspecialchars($reg['destinatario']); ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; background: <?php echo $reg['estado'] == 'Enviado' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;'; ?>">
                                <?php echo htmlspecialchars($reg['estado']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
