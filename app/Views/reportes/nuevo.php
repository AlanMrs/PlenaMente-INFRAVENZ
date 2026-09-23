<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h2 style="color: var(--violeta-principal); margin-bottom: 24px;">➕ Nuevo Informe Técnico</h2>
<p style="margin-bottom: 20px; color: #555;">Seleccione el expediente del paciente para el cual desea generar el informe.</p>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>ID Exp.</th>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>Fecha Apertura</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($expedientes)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">No hay expedientes registrados. Cree primero un expediente desde <a href="<?php echo BASE_URL; ?>/paciente">Pacientes</a>.</td>
                </tr>
            <?php else: ?>
                <?php foreach($expedientes as $exp): ?>
                    <tr>
                        <td>#<?php echo str_pad($exp['id_expediente'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($exp['apellidos'] . ', ' . $exp['nombres']); ?></strong>
                            <br><small style="color: #777;"><?php echo htmlspecialchars($exp['nie_dui']); ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars($exp['tipo_paciente']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($exp['fecha_apertura'])); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/reportes/tecnico/<?php echo $exp['id_expediente']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">
                                📄 Generar Informe
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="margin-top: 16px;">
    <a href="<?php echo BASE_URL; ?>/reportes" class="btn btn-secondary">⬅ Volver a Informes</a>
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>