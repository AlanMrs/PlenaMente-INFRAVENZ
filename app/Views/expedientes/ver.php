<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">
        Expediente Clínico: EXP-<?php echo str_pad($expediente['id_expediente'], 5, "0", STR_PAD_LEFT); ?>
    </h2>
    <div style="display: flex; gap: 8px; align-items: center;">
        <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1): ?>
            <a href="<?php echo BASE_URL; ?>/reportes/tecnico/<?php echo $expediente['id_expediente']; ?>" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none; text-decoration: none; color: white; padding: 8px 14px;">
                📄 Generar Informe
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/paciente" class="btn btn-secondary">Volver a Pacientes</a>
    </div>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 'sesion_guardada'): ?>
    <div style="padding: 12px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
        ✅ Sesión clínica registrada en el historial correctamente.
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: 25px; align-items: start;">
    
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid var(--violeta-principal);">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 70px; height: 70px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; color: #666; font-size: 28px; font-weight: bold;">
                <?php echo strtoupper(substr($expediente['nombres'], 0, 1) . substr($expediente['apellidos'], 0, 1)); ?>
            </div>
            <h3 style="margin: 0; color: #333; font-size: 18px;"><?php echo htmlspecialchars($expediente['nombres'] . ' ' . $expediente['apellidos']); ?></h3>
            <span style="display: inline-block; margin-top: 5px; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: <?php echo $expediente['tipo_paciente'] == 'Estudiante' ? '#e3f2fd; color: #0d47a1;' : '#e8f5e9; color: #1b5e20;'; ?>">
                <?php echo $expediente['tipo_paciente']; ?>
            </span>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

        <div style="font-size: 14px; color: #555; line-height: 1.6;">
            <p><strong>NIE / DUI:</strong> <?php echo htmlspecialchars($expediente['nie_dui']); ?></p>
            <p><strong>Género:</strong> <?php echo htmlspecialchars($expediente['genero'] ?? 'No especificado'); ?></p>
            <p><strong>Edad:</strong> 
                <?php 
                    $cumpleanos = new DateTime($expediente['fecha_nacimiento']);
                    $hoy = new DateTime();
                    $edad = $hoy->diff($cumpleanos);
                    echo $edad->y . " años";
                ?>
            </p>
            <?php if($expediente['tipo_paciente'] == 'Estudiante'): ?>
                <p><strong>Grado/Sección:</strong> <?php echo htmlspecialchars($expediente['grado_seccion'] ?? 'N/A'); ?></p>
                <p><strong>Responsable:</strong> <?php echo htmlspecialchars($expediente['nombre_responsable'] ?? 'N/A'); ?></p>
            <?php endif; ?>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($expediente['telefono_contacto'] ?? 'N/A'); ?></p>
            <p><strong>Correo:</strong> <?php echo htmlspecialchars($expediente['correo_paciente'] ?? 'N/A'); ?></p>
            <p><strong>Apertura:</strong> <?php echo date('d/m/Y', strtotime($expediente['fecha_apertura'])); ?></p>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

        <h4 style="margin: 0 0 5px 0; color: var(--violeta-principal); font-size: 14px;">Antecedentes Familiares</h4>
        <p style="font-size: 13px; color: #666; background: #f9f9f9; padding: 8px; border-radius: 4px; margin: 0 0 15px 0; max-height: 100px; overflow-y: auto;">
            <?php echo !empty($expediente['antecedentes_familiares']) ? nl2br(htmlspecialchars($expediente['antecedentes_familiares'])) : '<i>Ninguno registrado</i>'; ?>
        </p>

        <h4 style="margin: 0 0 5px 0; color: var(--violeta-principal); font-size: 14px;">Antecedentes Médicos / Personales</h4>
        <p style="font-size: 13px; color: #666; background: #f9f9f9; padding: 8px; border-radius: 4px; margin: 0 0 15px 0; max-height: 100px; overflow-y: auto;">
            <?php echo !empty($expediente['antecedentes_medicos']) ? nl2br(htmlspecialchars($expediente['antecedentes_medicos'])) : '<i>Ninguno registrado</i>'; ?>
        </p>

        <a href="<?php echo BASE_URL; ?>/expediente/editar/<?php echo $expediente['id_expediente']; ?>" class="btn" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid var(--violeta-principal); color: var(--violeta-principal); background: white; text-decoration: none; border-radius: 4px; text-align: center;">
            ✏️ Editar Antecedentes
        </a>
    </div>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 10px; flex-wrap: wrap;">
            <div>
                <h3 style="margin: 0; color: #333;">Historial de Sesiones Clínicas</h3>
                <span style="font-size: 12px; color: #888;"><?php echo $total_sesiones; ?> sesión(es) registrada(s)</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $expediente['id_expediente']; ?>?orden=desc" class="btn" style="<?php echo ($orden === 'DESC') ? 'background-color: var(--violeta-principal); color: white; border: none;' : 'background-color: #e9ecef; color: #333; text-decoration: none;'; ?> padding: 8px 12px; font-size: 13px;">
                    🕒 Más recientes
                </a>
                <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $expediente['id_expediente']; ?>?orden=asc" class="btn" style="<?php echo ($orden === 'ASC') ? 'background-color: var(--violeta-principal); color: white; border: none;' : 'background-color: #e9ecef; color: #333; text-decoration: none;'; ?> padding: 8px 12px; font-size: 13px;">
                    🕒 Más antiguas
                </a>
                <a href="<?php echo BASE_URL; ?>/expediente/nueva_sesion/<?php echo $expediente['id_expediente']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px; background-color: var(--violeta-principal); border: none; border-radius: 4px; text-decoration: none; color: white;">
                    ➕ Registrar Sesión
                </a>
            </div>
        </div>

        <?php if (empty($sesiones)): ?>
            <div style="text-align: center; padding: 40px; color: #888; background: #fafafa; border: 2px dashed #eee; border-radius: 6px;">
                <p style="margin: 0 0 10px 0; font-size: 16px;">Este expediente aún no cuenta con sesiones clínicas.</p>
                <p style="margin: 0; font-size: 13px; color: #aaa;">Presione el botón "Registrar Sesión" para documentar la primera intervención.</p>
            </div>
        <?php else: ?>
            <div style="border-left: 3px solid #e0e0e0; padding-left: 20px; margin-left: 10px;">
                <?php foreach ($sesiones as $index => $sesion): ?>
                    <?php $num_sesion = ($orden === 'ASC') ? ($index + 1) : ($total_sesiones - $index); ?>
                    <div style="position: relative; margin-bottom: 15px;">
                        <div style="position: absolute; width: 12px; height: 12px; background: var(--violeta-principal); border: 3px solid white; border-radius: 50%; left: -29px; top: 14px; box-shadow: 0 0 0 2px var(--violeta-principal);"></div>

                        <details <?php echo ($index === 0) ? 'open' : ''; ?> style="background: #fdfdfd; border: 1px solid #eaeaea; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            <summary style="padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
<div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-weight: bold; color: #333; word-break: break-word;">Sesión Clínica #<?php echo $num_sesion; ?></span>
                                        <span style="background: #e3f2fd; color: #0d47a1; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; border: 1px solid #bbdefb; white-space: nowrap;">
                                            💼 <?php echo htmlspecialchars($sesion['tipo_consulta'] ?? 'Seguimiento'); ?>
                                        </span>
                                    </div>
                                    <span style="font-size: 13px; color: #777; font-weight: bold; background: #eee; padding: 2px 8px; border-radius: 4px; white-space: nowrap;">
                                        📅 <?php echo date('d/m/Y h:i A', strtotime($sesion['fecha_registro'])); ?>
                                    </span>
                                    <a href="<?php echo BASE_URL; ?>/expediente/editar_sesion/<?php echo $sesion['id_sesion']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; text-decoration: none; color: white; background-color: var(--violeta-principal); border: none; border-radius: 4px; white-space: nowrap;">
                                        ✏️ Editar
                                    </a>
                                </summary>

                            <div style="padding: 10px 15px 15px 15px; font-size: 14px; color: #444; display: grid; gap: 10px; border-top: 1px dashed #eee; min-width: 0;">
                                <p style="margin: 0; word-break: break-word; overflow-wrap: anywhere;"><strong>Observaciones Generales:</strong><br>
                                    <span style="color: #666;"><?php echo nl2br(htmlspecialchars($sesion['observaciones_generales'])); ?></span>
                                </p>
                                <p style="margin: 0; word-break: break-word; overflow-wrap: anywhere;"><strong>Intervención Realizada:</strong><br>
                                    <span style="color: #666;"><?php echo nl2br(htmlspecialchars($sesion['intervencion_realizada'])); ?></span>
                                </p>
                                <p style="margin: 0; word-break: break-word; overflow-wrap: anywhere;"><strong>Notas de Evolución:</strong><br>
                                    <span style="color: #666;"><?php echo nl2br(htmlspecialchars($sesion['notas_evolucion'])); ?></span>
                                </p>

                                <?php $histSesion = isset($historial_sesiones) ? ($historial_sesiones[$sesion['id_sesion']] ?? []) : []; ?>
                                <?php if (!empty($histSesion)): ?>
                                    <details style="margin-top: 10px; font-size: 13px;">
                                        <summary style="cursor: pointer; color: var(--violeta-principal); font-weight: bold;">
                                            🕓 Historial de cambios de esta sesión (<?php echo count($histSesion); ?>)
                                        </summary>
                                        <div style="overflow-x: auto; margin-top: 10px; max-width: 100%;">
                                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                                <thead>
                                                    <tr style="border-bottom: 2px solid #eee; color: #555; font-size: 12px;">
                                                        <th style="padding: 8px; width: 22%;">Fecha</th>
                                                        <th style="padding: 8px; width: 18%;">Campo</th>
                                                        <th style="padding: 8px; width: 20%;">Psicólogo</th>
                                                        <th style="padding: 8px; width: 20%;">Anterior</th>
                                                        <th style="padding: 8px; width: 20%;">Nuevo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($histSesion as $h): ?>
                                                        <tr style="border-bottom: 1px solid #eee;">
                                                            <td style="padding: 8px; white-space: nowrap;"><?php echo date('d/m/Y h:i A', strtotime($h['fecha_modificacion'])); ?></td>
                                                            <td style="padding: 8px; font-weight: bold; word-break: break-word;"><?php echo htmlspecialchars($h['campo']); ?></td>
                                                            <td style="padding: 8px; word-break: break-word;"><?php echo htmlspecialchars($h['nombre_completo']); ?></td>
                                                            <td style="padding: 8px; color: #856404; word-break: break-word; overflow-wrap: anywhere;"><?php echo $h['valor_anterior'] ? nl2br(htmlspecialchars($h['valor_anterior'])) : '<i style="color:#aaa;">Vacío</i>'; ?></td>
                                                            <td style="padding: 8px; color: #155724; word-break: break-word; overflow-wrap: anywhere;"><?php echo $h['valor_nuevo'] ? nl2br(htmlspecialchars($h['valor_nuevo'])) : '<i style="color:#aaa;">Vacío</i>'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </details>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($historial)): ?>
<!-- Historial de cambios de antecedentes -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-top: 20px;">
    <details>
        <summary style="cursor: pointer; font-weight: bold; color: var(--violeta-principal);">
            🕓 Historial de Cambios de Antecedentes (<?php echo count($historial); ?>)
        </summary>
        <div style="margin-top: 15px; overflow-x: auto; max-width: 100%;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; table-layout: fixed;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; color: #555;">
                        <th style="padding: 10px; width: 20%;">Fecha</th>
                        <th style="padding: 10px; width: 16%;">Campo</th>
                        <th style="padding: 10px; width: 18%;">Psicólogo</th>
                        <th style="padding: 10px; width: 23%;">Valor Anterior</th>
                        <th style="padding: 10px; width: 23%;">Valor Nuevo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px; white-space: nowrap;"><?php echo date('d/m/Y h:i A', strtotime($h['fecha_modificacion'])); ?></td>
                            <td style="padding: 10px; font-weight: bold; word-break: break-word;"><?php echo htmlspecialchars($h['tipo_cambio']); ?></td>
                            <td style="padding: 10px; word-break: break-word;"><?php echo htmlspecialchars($h['nombre_completo']); ?></td>
                            <td style="padding: 10px; color: #856404; word-break: break-word; overflow-wrap: anywhere;"><?php echo $h['valor_anterior'] ? nl2br(htmlspecialchars($h['valor_anterior'])) : '<i style="color:#aaa;">Vacío</i>'; ?></td>
                            <td style="padding: 10px; color: #155724; word-break: break-word; overflow-wrap: anywhere;"><?php echo $h['valor_nuevo'] ? nl2br(htmlspecialchars($h['valor_nuevo'])) : '<i style="color:#aaa;">Vacío</i>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
</div>
<?php endif; ?>

<!-- Navegación entre expedientes (anterior / siguiente) -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee;">
    <?php if (!empty($vecinos['anterior'])): ?>
        <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $vecinos['anterior']; ?><?php echo ($orden === 'ASC') ? '?orden=asc' : ''; ?>" class="btn btn-secondary" style="text-decoration: none;">
            ← Anterior (EXP-<?php echo str_pad($vecinos['anterior'], 5, '0', STR_PAD_LEFT); ?>)
        </a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>

    <?php if (!empty($vecinos['siguiente'])): ?>
        <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $vecinos['siguiente']; ?><?php echo ($orden === 'ASC') ? '?orden=asc' : ''; ?>" class="btn btn-secondary" style="text-decoration: none;">
            Siguiente (EXP-<?php echo str_pad($vecinos['siguiente'], 5, '0', STR_PAD_LEFT); ?>) →
        </a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>