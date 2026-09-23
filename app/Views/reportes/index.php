<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h2 style="color: var(--violeta-principal); margin-bottom: 24px;">
    📄 Informes Técnicos Psicológicos
</h2>
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 16px; flex-wrap: wrap;">
    <p style="margin: 0; color: #555;">Listado de informes generados. Puede filtrarlos por paciente o rango de fechas.</p>
    <a href="<?php echo BASE_URL; ?>/reportes/nuevo" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none; text-decoration: none; white-space: nowrap;">
        ➕ Nuevo Informe
    </a>
</div>

<!-- Barra de filtros -->
<div class="card" style="padding: 16px; margin-bottom: 20px;">
    <form action="<?php echo BASE_URL; ?>/reportes" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label for="buscar" style="font-size: 13px; font-weight: bold; display: block; margin-bottom: 4px;">Paciente (NIE/DUI, nombres, apellidos)</label>
            <input type="text" id="buscar" name="buscar" class="form-control" placeholder="Buscar paciente..." value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>
        <div>
            <label for="desde" style="font-size: 13px; font-weight: bold; display: block; margin-bottom: 4px;">Desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>">
        </div>
        <div>
            <label for="hasta" style="font-size: 13px; font-weight: bold; display: block; margin-bottom: 4px;">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none;">🔍 Filtrar</button>
            <a href="<?php echo BASE_URL; ?>/reportes" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>Nivel</th>
                <th>Fecha Generación</th>
                <th>Autor</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportes)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">
                        <div style="color: #888; margin-bottom: 12px;">No hay reportes guardados con esos criterios.</div>
                        <a href="<?php echo BASE_URL; ?>/reportes/nuevo" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none; text-decoration: none;">
                            ➕ Generar el primer informe
                        </a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($reportes as $r): ?>
                    <tr>
                        <td>#<?php echo str_pad($r['id_reporte'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r['apellidos'] . ', ' . $r['nombres']); ?></strong>
                            <br><small style="color: #777;"><?php echo htmlspecialchars($r['nie_dui']); ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars($r['tipo_paciente']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                                $colores = ['Bajo' => '#28a745', 'Medio' => '#ffc107', 'Alto' => '#dc3545'];
                                $color = $colores[$r['nivel_urgencia']] ?? '#6c757d';
                            ?>
                            <span class="badge" style="background-color: <?php echo $color; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars($r['nivel_urgencia']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha_generacion'])); ?></td>
                        <td><?php echo htmlspecialchars($r['autor_nombre'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/reportes/ver/<?php echo $r['id_reporte']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">
                                👁️ Ver Informe
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>