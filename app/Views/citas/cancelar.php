<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="max-width: 640px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="color: var(--violeta-principal); margin: 0;">🚫 Cancelar Cita</h2>
        <a href="<?php echo BASE_URL; ?>/cita/ver/<?php echo $cita['id_cita']; ?>" class="btn btn-secondary">⬅ Volver al Detalle</a>
    </div>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $tipo => $mensajes): ?>
            <?php foreach ($mensajes as $mensaje): ?>
                <div style="padding: 12px; background-color: <?php echo $tipo == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $tipo == 'success' ? '#155724' : '#721c24'; ?>; border-radius: 4px; margin-bottom: 16px; font-weight: bold;">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Resumen de la cita a cancelar -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">📅 Cita a cancelar</h3>
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #777; width: 40%;">Paciente</td>
                <td style="padding: 8px 0;"><strong><?php echo htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos']); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #777;">Fecha y Hora</td>
                <td style="padding: 8px 0;"><strong><?php echo date('d/m/Y', strtotime($cita['fecha_hora'])); ?> — ⏰ <?php echo date('g:i A', strtotime($cita['fecha_hora'])); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #777;">Motivo de Consulta</td>
                <td style="padding: 8px 0;"><?php echo htmlspecialchars($cita['motivo_cita']); ?></td>
            </tr>
        </table>
    </div>

    <!-- Formulario que solicita el motivo de cancelación -->
    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div style="padding: 12px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 20px;">
            ⚠️ Esta acción cambiará el estado de la cita a <strong>Cancelada</strong> y no se puede deshacer.
        </div>

        <form action="<?php echo BASE_URL; ?>/cita/cancelar/<?php echo $cita['id_cita']; ?>" method="POST" onsubmit="return confirm('¿Confirmas la cancelación de esta cita?');">
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Motivo de la Cancelación: <span style="color: #dc3545;">*</span></label>
                <textarea name="motivo_cancelacion" required minlength="5" maxlength="255" rows="4" placeholder="Ej. El paciente no puede asistir, reprogramación solicitada por el responsable, caso derivado..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; resize: vertical;"></textarea>
                <small style="color: #888;">Obligatorio. Mínimo 5 caracteres (máximo 255).</small>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex: 1; padding: 12px; background-color: #dc3545; border: none; color: white; font-size: 16px; border-radius: 4px; cursor: pointer;">
                    🚫 Confirmar Cancelación
                </button>
                <a href="<?php echo BASE_URL; ?>/cita/ver/<?php echo $cita['id_cita']; ?>" class="btn btn-secondary" style="flex: 1; padding: 12px; text-align: center; text-decoration: none;">
                    No cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
