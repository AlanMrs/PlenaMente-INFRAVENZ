<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">
        Expediente Clínico: EXP-<?php echo str_pad($expediente['id_expediente'], 5, "0", STR_PAD_LEFT); ?>
    </h2>
    <a href="<?php echo BASE_URL; ?>/paciente" class="btn btn-secondary">Volver a Pacientes</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 'sesion_guardada'): ?>
    <div style="padding: 12px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
        ✅ Sesión clínica registrada en el historial correctamente.
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start;">
    
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
        <p style="font-size: 13px; color: #666; background: #f9f9f9; padding: 8px; border-radius: 4px; margin: 0; max-height: 100px; overflow-y: auto;">
            <?php echo !empty($expediente['antecedentes_medicos']) ? nl2br(htmlspecialchars($expediente['antecedentes_medicos'])) : '<i>Ninguno registrado</i>'; ?>
        </p>
    </div>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333;">Historial de Sesiones Clínicas</h3>
            <a href="<?php echo BASE_URL; ?>/expediente/nueva_sesion/<?php echo $expediente['id_expediente']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px; background-color: var(--violeta-principal); border: none; border-radius: 4px; text-decoration: none; color: white;">
                ➕ Registrar Sesión
            </a>
        </div>

        <?php if (empty($sesiones)): ?>
            <div style="text-align: center; padding: 40px; color: #888; background: #fafafa; border: 2px dashed #eee; border-radius: 6px;">
                <p style="margin: 0 0 10px 0; font-size: 16px;">Este expediente aún no cuenta con sesiones clínicas.</p>
                <p style="margin: 0; font-size: 13px; color: #aaa;">Presione el botón "Registrar Sesión" para documentar la primera intervención.</p>
            </div>
        <?php else: ?>
            <div style="border-left: 3px solid #e0e0e0; padding-left: 20px; margin-left: 10px;">
                <?php foreach ($sesiones as $index => $sesion): ?>
                    <div style="position: relative; margin-bottom: 25px;">
                        <div style="position: absolute; width: 12px; height: 12px; background: var(--violeta-principal); border: 3px solid white; border-radius: 50%; left: -29px; top: 5px; box-shadow: 0 0 0 2px var(--violeta-principal);"></div>
                        
                        <div style="background: #fdfdfd; border: 1px solid #eaeaea; border-radius: 6px; padding: 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="font-weight: bold; color: #333;">Sesión Clínica #<?php echo count($sesiones) - $index; ?></span>
                                <span style="font-size: 13px; color: #777; font-weight: bold; background: #eee; padding: 2px 8px; border-radius: 4px;">
                                    📅 <?php echo date('d/m/Y', strtotime($sesion['fecha_registro'])); ?>
                                </span>
                            </div>
                            
                            <div style="font-size: 14px; color: #444; display: grid; gap: 10px;">
                                <p style="margin: 0;"><strong>Observaciones Generales:</strong><br>
                                    <span style="color: #666;"><?php echo nl2br(htmlspecialchars($sesion['observaciones_generales'])); ?></span>
                                </p>
                                <p style="margin: 0;"><strong>Intervención Realizada:</strong><br>
                                    <span style="color: #666;"><?php echo nl2br(htmlspecialchars($sesion['intervencion_realizada'])); ?></span>
                                </p>
                                <p style="margin: 0;"><strong>Notas de Evolución:</strong><br>
                                    <span style="color: #666;"><?php echo nl2br(htmlspecialchars($sesion['notas_evolucion'])); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>