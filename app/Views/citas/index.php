<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">Agenda de Citas Activas</h2>
    <a href="<?php echo BASE_URL; ?>/cita/crear" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none;">📅 Programar Nueva Cita</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 'creada'): ?>
    <div style="padding: 12px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
        ✅ Cita agendada correctamente en el calendario.
    </div>
<?php endif; ?>

<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <?php if (empty($citas)): ?>
        <p style="text-align: center; color: #666; margin: 20px 0;">No hay citas pendientes programadas en la agenda.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; color: #555;">
                    <th style="padding: 12px;">Fecha y Hora</th>
                    <th style="padding: 12px;">Paciente</th>
                    <th style="padding: 12px;">Motivo de Consulta</th>
                    <th style="padding: 12px;">Estado</th>
                    <th style="padding: 12px; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($citas as $cita): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <strong><?php echo date('d/m/Y', strtotime($cita['fecha_hora'])); ?></strong><br>
                            <span style="color: #777; font-size: 13px;">⏰ <?php echo date('g:i A', strtotime($cita['fecha_hora'])); ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos']); ?><br>
                            <small style="color: #888;">DUI/NIE: <?php echo htmlspecialchars($cita['nie_dui']); ?></small>
                        </td>
                        <td style="padding: 12px; color: #555; font-size: 14px;"><?php echo htmlspecialchars($cita['motivo_cita']); ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; background: <?php echo $cita['estado_cita'] == 'Programada' ? '#fff3cd; color: #856404;' : '#d4edda; color: #155724;'; ?>">
                                <?php echo $cita['estado_cita']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($cita['estado_cita'] == 'Programada'): ?>
                                <a href="<?php echo BASE_URL; ?>/expediente/atender_cita/<?php echo $cita['id_cita']; ?>" class="btn btn-success" style="background-color: #28a745; border: none; padding: 6px 12px; font-size: 13px; text-decoration: none; color: white; border-radius: 4px; display: inline-block;">
                                    ▶️ Atender Cita
                                </a>
                            <?php else: ?>
                                <span style="color: #aaa; font-style: italic; font-size: 13px;">Atención Finalizada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>