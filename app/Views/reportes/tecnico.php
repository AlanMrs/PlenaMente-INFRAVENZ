<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .informe-container {
        background: #fff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        max-width: 800px;
        margin: 0 auto;
        color: #333;
        font-family: 'Arial', sans-serif;
    }
    .informe-header {
        text-align: center;
        border-bottom: 2px solid var(--violeta-principal);
        padding-bottom: 15px;
        margin-bottom: 25px;
    }
    .informe-title {
        color: var(--violeta-principal);
        margin: 10px 0 5px 0;
        text-transform: uppercase;
        font-size: 22px;
    }
    .section-title {
        background-color: #f4f6f9;
        color: var(--azul-oscuro);
        padding: 8px 12px;
        font-size: 16px;
        font-weight: bold;
        margin-top: 25px;
        border-left: 4px solid var(--violeta-principal);
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 15px;
    }
    .info-item strong {
        color: #555;
    }
    .info-item {
        border-bottom: 1px dashed #eee;
        padding-bottom: 5px;
    }
    .text-box {
        border: 1px solid #ddd;
        min-height: 100px;
        padding: 15px;
        margin-top: 15px;
        border-radius: 4px;
        color: #666;
        font-style: italic;
    }
    .firmas-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        margin-top: 60px;
        text-align: center;
    }
    .firma-linea {
        border-top: 1px solid #333;
        padding-top: 10px;
        font-weight: bold;
    }
    
    /* REGLAS DE IMPRESIÓN */
    @media print {
        body * {
            visibility: hidden; /* Oculta todo por defecto */
        }
        .informe-container, .informe-container * {
            visibility: visible; /* Solo muestra el informe */
        }
        .informe-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0;
            box-shadow: none;
        }
        .no-print {
            display: none !important; /* Oculta botones al imprimir */
        }
    }
</style>

<div style="max-width: 800px; margin: 0 auto 20px auto; display: flex; justify-content: space-between;" class="no-print">
    <a href="<?php echo BASE_URL; ?>/reportes" class="btn btn-secondary">⬅ Volver a Informes</a>
    <button onclick="window.print()" class="btn btn-primary" style="background-color: #28a745; border: none;">🖨️ Imprimir / Guardar PDF</button>
</div>

<div class="informe-container">
    
    <div class="informe-header">
        <h2 class="informe-title">Informe Técnico Psicológico</h2>
        <p style="margin: 0; color: #666;">Sistema Institucional de Atención Psicológica - PlenaMente</p>
        <p style="margin: 5px 0 0 0; font-size: 14px;"><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y'); ?></p>
    </div>

    <div class="section-title">I. DATOS DE IDENTIFICACIÓN</div>
    <div class="info-grid">
        <div class="info-item"><strong>Expediente N°:</strong> #<?php echo str_pad($paciente['id_expediente'], 4, '0', STR_PAD_LEFT); ?></div>
        <div class="info-item"><strong>Tipo:</strong> <?php echo htmlspecialchars($paciente['tipo_paciente']); ?></div>
        
        <div class="info-item"><strong>Nombres:</strong> <?php echo htmlspecialchars($paciente['nombres']); ?></div>
        <div class="info-item"><strong>Apellidos:</strong> <?php echo htmlspecialchars($paciente['apellidos']); ?></div>
        
        <div class="info-item"><strong>Identificación (NIE/DUI):</strong> <?php echo htmlspecialchars($paciente['nie_dui']); ?></div>
        <div class="info-item"><strong>Fecha Nacimiento:</strong> <?php echo date('d/m/Y', strtotime($paciente['fecha_nacimiento'])); ?></div>
        
        <div class="info-item"><strong>Grado/Sección:</strong> <?php echo htmlspecialchars($paciente['grado_seccion'] ?? 'N/A'); ?></div>
        <div class="info-item"><strong>Género:</strong> <?php echo htmlspecialchars($paciente['genero']); ?></div>
        
        <div class="info-item" style="grid-column: span 2;"><strong>Responsable:</strong> <?php echo htmlspecialchars($paciente['nombre_responsable'] ?? 'N/A'); ?> (Tel: <?php echo htmlspecialchars($paciente['telefono_contacto'] ?? 'N/A'); ?>)</div>
    </div>

    <div class="section-title">II. ANTECEDENTES CLÍNICOS</div>
    <div style="margin-top: 15px;">
        <p><strong>Antecedentes Médicos:</strong> <?php echo htmlspecialchars($paciente['antecedentes_medicos'] ?? 'No refiere.'); ?></p>
        <p><strong>Antecedentes Familiares:</strong> <?php echo htmlspecialchars($paciente['antecedentes_familiares'] ?? 'No refiere.'); ?></p>
    </div>

    <div class="section-title">III. RESUMEN DE ATENCIÓN</div>
    <div class="info-grid">
        <div class="info-item"><strong>Total de Sesiones Realizadas:</strong> <?php echo $resumen['total_sesiones'] ?? 0; ?></div>
        <div class="info-item"><strong>Fecha de Apertura:</strong> <?php echo date('d/m/Y', strtotime($paciente['fecha_apertura'])); ?></div>
        
        <div class="info-item"><strong>Primera Sesión:</strong> <?php echo $resumen['primera_sesion'] ? date('d/m/Y', strtotime($resumen['primera_sesion'])) : 'Sin registros'; ?></div>
        <div class="info-item"><strong>Última Sesión:</strong> <?php echo $resumen['ultima_sesion'] ? date('d/m/Y', strtotime($resumen['ultima_sesion'])) : 'Sin registros'; ?></div>
    </div>

    <div class="section-title">IV. OBSERVACIONES CLÍNICAS (Última Sesión)</div>
    <div class="text-box" style="font-style: normal; color: #333;">
        <?php if (!empty($ultima_sesion['observaciones_generales'])): ?>
            <?php echo nl2br(htmlspecialchars($ultima_sesion['observaciones_generales'])); ?>
        <?php else: ?>
            <span style="color: #999; font-style: italic;">No hay observaciones registradas en la última sesión o el paciente aún no tiene sesiones clínicas.</span>
        <?php endif; ?>
    </div>

    <div class="section-title">V. NOTAS DE EVOLUCIÓN Y RECOMENDACIONES</div>
    <div class="text-box" style="font-style: normal; color: #333;">
        <?php if (!empty($ultima_sesion['notas_evolucion'])): ?>
            <?php echo nl2br(htmlspecialchars($ultima_sesion['notas_evolucion'])); ?>
        <?php else: ?>
            <span style="color: #999; font-style: italic;">No hay recomendaciones registradas en la última sesión.</span>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($ultima_sesion['intervencion_realizada'])): ?>
    <div class="section-title">VI. INTERVENCIÓN REALIZADA</div>
    <div class="text-box" style="font-style: normal; color: #333;">
        <?php echo nl2br(htmlspecialchars($ultima_sesion['intervencion_realizada'])); ?>
    </div>
    <?php endif; ?>

    <div class="firmas-grid">
        <div>
            <div class="firma-linea">Licda. Blanca Mirian Cruz</div>
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Psicológa - INFRAVENZ</p>
        </div>
        <div>
            <div class="firma-linea">Lic. Esteban Antonio Bonilla</div>
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Director - INFRAVENZ</p>
        </div>
    </div>

    <p style="text-align: center; font-size: 11px; color: #999; margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px;">
        Este documento es de carácter confidencial y de uso exclusivo institucional. Generado por el sistema PlenaMente el <?php echo date('d/m/Y h:i A'); ?>.
    </p>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>