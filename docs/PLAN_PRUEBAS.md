# Plan de Pruebas — PlenaMente-INFRAVENZ

**Sistema Institucional de Atención y Seguimiento Psicológico — INFRAVENZ**
Versión del documento: 1.0 · Última actualización: 2026-11-05

---

## 1. Objetivo

Garantizar que cada historia de usuario entregada cumple sus criterios de aceptación, que la **Definition of Done (DoD)** se cumple de forma verificable y que no se introducen regresiones en los módulos ya liberados.

Objetivos específicos:

1. Derivar de cada historia de usuario los casos de prueba que cubren sus criterios de aceptación.
2. Verificar que el código entregado cumple la DoD antes de considerarse terminado.
3. Documentar la evidencia de ejecución (casos ejecutados, defectos detectados, resultado).
4. Proteger la **integridad de la información clínica** (pacientes, expedientes, citas, reportes), que es el activo de mayor riesgo del sistema.

---

## 2. Alcance

### 2.1 Módulos cubiertos

| Módulo | Historias asociadas | Riesgo |
|---|---|---|
| Directorio de pacientes | Crear, Actualizar, Eliminar (desactivar) | **Alto** — datos maestro de todo el sistema |
| Agenda de citas | Crear, Modificar/Reprogramar, Cancelar, Paginación, Filtro por rango | **Alto** — agenda clínica y solapamientos |
| Recordatorios de citas | Envío de recordatorio por correo | Medio — dependencia de SMTP |
| Expedientes clínicos | Apertura, edición de antecedentes, sesiones, estado del caso | **Alto** — información sensible |
| Reportes técnicos | Generación, parametrización, exportación | Medio |
| Autenticación y roles | Login, recuperación de contraseña, RBAC | **Alto** — control de acceso |
| Auditoría | Registro de acciones sensibles | Medio |

### 2.2 Fuera de alcance

- Pruebas de carga/estrés y pruebas de penetración formally certificate (manuales).
- Pruebas de usabilidad con usuarios finales (se gestionan aparte, con el área solicitante).
- Compatibilidad con navegadores no soportados (el sistema es de uso interno en navegador de escritorio).

---

## 3. Marco de referencia

### 3.1 Roles de acceso (RBAC)

| `id_rol` | Rol | Permisos esperados en esta versión |
|---|---|---|
| 1 | **Psicólogo** | Gestión completa de pacientes, citas, expedientes, sesiones, reportes y recordatorios |
| 2 | **Director** | Dashboard, reportes y consulta; **sin** escritura clínica |
| 3 | **Administrador (IT)** | Usuarios y respaldos; **sin** acceso a datos clínicos de escritura |

> Todo módulo clínico (pacientes, citas, expedientes) debe proteger sus rutas de escritura con `$_SESSION['id_rol'] !== 1`.

### 3.2 Entorno

| Elemento | Definición |
|---|---|
| Stack | PHP >= 8.0 (nativo, MVC), MariaDB, HTML5/CSS3/JS |
| Servidor local | XAMPP (`C:\xampp\php\php.exe`), BD `plenamente_bd` en `127.0.0.1` |
| Servidor destino | Hostinger — `https://plenamente.dsoftin.net` (`public_html`) |
| Base de datos | Script maestro: `plenamente_bd.sql` |
| Logs | `logs/app-AAAA-MM-DD.log` (nivel mínimo: `error`) |
| Validación estática | `php -l <archivo>` sobre cada archivo PHP modificado |

### 3.3 Datos de prueba

| ID | Datos | Uso |
|---|---|---|
| `DT-01` | Paciente Estudiante, NIE `TEST-0001`, activo, con expediente y 2 citas | Paciente "poblado" (dependencias críticas) |
| `DT-02` | Paciente Estudiante, NIE `TEST-0002`, activo, sin dependencias | Paciente "limpio" |
| `DT-03` | Paciente Empleado, DUI `TEST-0003`, activo, `grado_seccion = NULL` | Validar reglas por tipo |
| `DT-04` | Paciente con NIE duplicado (copia de `DT-01`) | Caso negativo de unicidad |
| `DT-05` | Paciente inactivo (`estado_paciente = 'Inactivo'`) | Soft delete / reactivación |
| `DT-06` | Cita `Programada` del psicólogo A a las 09:00 | Pruebas de disponibilidad |
| `DT-07` | Cita `Cancelada` del psicólogo A a las 09:00 | No debe generar conflicto |

> Los datos `DT-*` deben crearse y eliminarse al terminar cada ciclo; nunca deben contener información clínica real de estudiantes.

---

## 4. Definition of Done (DoD)

Una historia **no se considera terminada** hasta que los tres bloques siguientes son verificables por el equipo de pruebas.

### 4.1 DoD — Funcional (criterios de aceptación)

| # | Criterio | Verificación |
|---|---|---|
| D1 | Todos los criterios de aceptación de la historia se cumplen | Casos de prueba asociados en estado *Pasado* |
| D2 | Los mensajes de éxito y de error se muestran al usuario | Evidencia en captura / caso de prueba |
| D3 | Las validaciones de entrada bloquean el guardado cuando el dato es inválido | Caso negativo aprobado |
| D4 | Los datos persistidos coinciden exactamente con los enviados (verificado en BD, no solo en pantalla) | Consulta directa a la tabla |
| D5 | La operación no altera datos ajenos a la historia (no hay efectos colaterales) | Comparación de registros antes/después |

### 4.2 DoD — Técnico

| # | Criterio | Verificación |
|---|---|---|
| D6 | `php -l` sin errores en **todos** los archivos PHP creados o modificados | Salida del linter |
| D7 | Todas las consultas SQL usan consultas preparadas (PDO) | Revisión de código |
| D8 | La vista escapa toda entrada del usuario (`htmlspecialchars`) | Revisión de código + CP de XSS |
| D9 | Las rutas respetan el RBAC (rol 1 en escritura clínica) | Casos de seguridad |
| D10 | Las operaciones sensibles quedan registradas en `auditoria_acciones` y en `logs/app-*.log` | Consulta a BD y lectura del log |
| D11 | El esquema SQL actualizado está en `plenamente_bd.sql` **y** se entrega el `ALTER`/`CREATE` equivalente para la BD existente | Revisión del `.sql` |
| D12 | No hay errores PHP (warning/notice/fatal) en la consola del navegador ni en el log | Revisión del log |

### 4.3 DoD — Pruebas

| # | Criterio | Verificación |
|---|---|---|
| D13 | Todos los casos de prueba de la historia están redactados y trazados a un criterio de aceptación | Matriz de trazabilidad |
| D14 | Casos críticos (prioridad *Alta*) ejecutados y aprobados | Registro de ejecución |
| D15 | Sin defectos abiertos de severidad *Crítica* o *Alta* | Registro de defectos |
| D16 | Pruebas de regresión de los módulos afectados superadas | Suite de regresión |
| D17 | La documentación de usuario / README está actualizada si cambió el flujo | Revisión del README |

---

## 5. Estrategia de pruebas

| Tipo | Alcance | Herramienta / técnica | Responsable |
|---|---|---|---|
| **Validación estática** | Sintaxis de todos los archivos PHP | `php -l` | Desarrollador |
| **Prueba unitaria (smoke)** | Modelos y reglas de validación | Script PHP CLI contra BD local (ver §9) | Desarrollador / QA |
| **Prueba funcional** | Flujos de usuario completos por rol | Manual, siguiendo los casos de §7 | QA |
| **Prueba de integración** | Controlador → Modelo → BD; correo (`mail()` → SMTP de Hostinger) | Manual | QA |
| **Prueba de validación** | Reglas de negocio (disponibilidad, unicidad, obligatoriedad) | Manual + smoke | QA |
| **Prueba de seguridad** | RBAC, sesión, inyección SQL, XSS | Manual | QA |
| **Prueba de regresión** | Módulos que comparten datos con el cambiado | Manual (Suite D) | QA |
| **Prueba de despliegue** | Script SQL en Hostinger + carga de archivos + verificación en producción | Manual | QA + Admin |

### 5.1 Niveles de prioridad de los casos

| Prioridad | Definición |
|---|---|
| **Alta** | Cubre un criterio de aceptación o un riesgo de integridad de datos. Su fallo bloquea la entrega. |
| **Media** | Cubre reglas de validación secundarias o variantes de la UI. |
| **Baja** | Verificación cosmética o de robustez no bloqueante. |

### 5.2 Severidad de defectos

| Severidad | Definición | Ejemplo |
|---|---|---|
| **Crítica** | Pérdida/corrupción de datos clínicos o brecha de seguridad total | Se puede eliminar el expediente de un paciente |
| **Alta** | Función principal inutilizable o dato guardado incorrectamente | El paciente se guarda con el teléfono de otro |
| **Media** | Funcionalidad con，力争 alterno o sin mensaje de error | No se muestra el mensaje de confirmación |
| **Baja** | Detalle visual o de redacción | Botón desalineado |

---

## 6. Ciclo de pruebas

### 6.1 Criterios de entrada

1. Historia de usuario con criterios de aceptación redactados.
2. Código integrado en la rama de trabajo y `php -l` sin errores.
3. Script SQL (`plenamente_bd.sql` + `ALTER` para BD existente) disponible.
4. Datos de prueba `DT-*` cargados.

### 6.2 Criterios de salida

1. 100 % de los casos de prioridad **Alta** ejecutados y aprobados.
2. ≥ 90 % de los casos de prioridad **Media** ejecutados y aprobados.
3. Cero defectos abiertos de severidad **Crítica** y **Alta**.
4. Matriz de trazabilidad completa (Historia → DoD → Caso → Resultado).
5. Pruebas de despliegue en Hostinger ejecutadas y registradas.

### 6.3 Registro de ejecución (plantilla)

| Caso | Fecha | Ejecutor | Resultado | Evidencia | Defecto |
|---|---|---|---|---|---|
| CP-A-01 | 2026-11-05 | QA | Pass / Fail / N/A | captura o log | ID |

---

## 7. Casos de prueba

### 7.1 Plantilla de caso de prueba

```
ID:            CP-<SUITE>-<NN>
Historia:      HU-<NN> — <nombre>
Prioridad:     Alta | Media | Baja
Tipo:          Funcional | Validación | Seguridad | Regresión
Precondición:  <estado del sistema y datos>
Datos:         <valores de entrada>
Pasos:         1. ... 2. ... 3. ...
Esperado:      <resultado observable en pantalla y/o en BD>
DoD cubierto:  D1, D3, D4, ...
```

---

### 7.2 Suite A — Actualizar información del paciente (HU-09)

> **Historia:** *Como psicólogo, necesito actualizar la información del paciente para mantener los datos correctos de acuerdo al tiempo.*
> 1. El sistema debe permitir modificar cualquier dato del paciente.
> 2. Debe validar los datos ingresados antes de guardar el registro.
> 3. Debe mostrar un mensaje de confirmación de la actualización.
>
> **Ruta:** `/paciente/editar/{id}` (GET) y `/paciente/actualizar/{id}` (POST)

#### A.1 Casos felices

| ID | Prioridad | Precondición | Datos | Pasos | Esperado | DoD |
|---|---|---|---|---|---|---|
| CP-A-01 | Alta | Sesión rol 1; existe `DT-01` | Todos los campos con valores válidos distintos a los actuales | 1. Ir al directorio de pacientes. 2. Pulsar **✏️ Editar** en `DT-01`. 3. Cambiar nombres, apellidos, fecha de nacimiento, género, tipo, grado, teléfono, correo y responsable. 4. Pulsar **💾 Guardar Cambios**. | Redirige al directorio; banner verde **"Los datos del paciente … se actualizaron correctamente."**; en BD los 10 campos quedan con los valores enviados; `estado_paciente` sin cambios | D1, D2, D4, D5 |
| CP-A-02 | Alta | `DT-02` | Solo se cambia el teléfono | Editar → cambiar únicamente `telefono_contacto` → Guardar | Se actualiza **solo** el teléfono; el resto de campos mantiene su valor original (comparación en BD) | D5 |
| CP-A-03 | Alta | `DT-02` | NIE/DUI sin modificar | Editar → guardar sin tocar el NIE/DUI | Se guarda correctamente (el NIE propio **no** se considera duplicado) | D1, D4 |
| CP-A-04 | Media | `DT-02` | Campos opcionales vacíos | Borrar teléfono, correo y responsable → Guardar | Se guardan como `NULL` en BD, sin error | D4 |
| CP-A-05 | Media | `DT-03` (Empleado) | Cambiar tipo a `Empleado` dejando grado/sección | Editar → seleccionar *Empleado* → Guardar | `grado_seccion` se guarda en `NULL` y el campo se deshabilita en el formulario | D4 |
| CP-A-06 | Alta | `DT-05` (inactivo) | Cualquier dato | Editar el paciente inactivo (visible con *Mostrar inactivos*) → Guardar | Se actualiza correctamente; el paciente **sigue inactivo** | D5 |
| CP-A-07 | Media | `DT-01` | Sin cambios reales | Editar → guardar sin modificar nada | Sin error; se registra `ACTUALIZAR` con campos modificados = *ninguno* | D10 |

#### A.2 Casos de validación (criterio 2)

| ID | Prioridad | Datos | Pasos | Esperado | DoD |
|---|---|---|---|---|---|
| CP-A-08 | Alta | NIE/DUI vacío | Editar → vaciar NIE/DUI → Guardar | No guarda. Mensaje rojo **"El NIE/DUI es obligatorio."**; BD sin cambios | D3, D15 |
| CP-A-09 | Alta | NIE/DUI con el valor de otro paciente | Editar → poner el NIE/DUI de `DT-01` en `DT-02` → Guardar | No guarda. Mensaje **"El NIE/DUI ya está registrado en otro paciente."** (no se produce error SQL 1062) | D3, D15 |
| CP-A-10 | Media | NIE/DUI de 21 caracteres | Guardar | No guarda. Mensaje **"El NIE/DUI no puede superar los 20 caracteres."** | D3 |
| CP-A-11 | Alta | Nombres vacíos | Guardar | No guarda. Mensaje **"Los nombres son obligatorios."** | D3 |
| CP-A-12 | Media | Nombres de 101 caracteres | Guardar | No guarda. Mensaje **"Los nombres no pueden superar los 100 caracteres."** | D3 |
| CP-A-13 | Alta | Apellidos vacíos | Guardar | No guarda. Mensaje **"Los apellidos son obligatorios."** | D3 |
| CP-A-14 | Alta | Fecha de nacimiento vacía | Guardar | No guarda. Mensaje **"La fecha de nacimiento es obligatoria."** | D3 |
| CP-A-15 | Alta | Fecha con formato `22/03/1999` | Guardar | No guarda. Mensaje **"La fecha de nacimiento no tiene un formato válido."** | D3 |
| CP-A-16 | Alta | Fecha inexistente `2024-02-31` | Guardar | No guarda. Mensaje **"La fecha de nacimiento no es una fecha válida."** | D3 |
| CP-A-17 | Alta | Fecha futura (año vigente + 1) | Guardar | No guarda. Mensaje **"La fecha de nacimiento no puede ser una fecha futura."** | D3 |
| CP-A-18 | Baja | Fecha `1890-01-01` | Guardar | No guarda. Mensaje **"La fecha de nacimiento es demasiado antigua."** | D3 |
| CP-A-19 | Alta | Género fuera de catálogo (manipulado por POST) | Guardar | No guarda. Mensaje **"Selecciona un género válido."** | D3 |
| CP-A-20 | Alta | Tipo de paciente fuera de catálogo | Guardar | No guarda. Mensaje **"Selecciona un tipo de paciente válido."** | D3 |
| CP-A-21 | Alta | Correo `correo@@roto` | Guardar | No guarda. Mensaje **"El correo electrónico no tiene un formato válido."** | D3 |
| CP-A-22 | Media | Teléfono `abc!!` | Guardar | No guarda. Mensaje **"El teléfono de contacto no tiene un formato válido."** | D3 |
| CP-A-23 | Media | Varios errores simultáneos | Guardar con 3 campos inválidos | No guarda. Se muestran **todos** los errores en un mismo mensaje | D2, D3 |

#### A.3 Mensaje de confirmación, auditoría y trazabilidad (criterio 3)

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-A-24 | Alta | Ejecutar CP-A-01 | Banner **verde** de confirmación visible en el directorio; el flash se consume una sola vez (no reaparece al recargar de nuevo) | D2 |
| CP-A-25 | Alta | Tras CP-A-01, consultar `auditoria_acciones` | Existe un registro `accion = 'ACTUALIZAR'`, `entidad = 'paciente'`, `id_entidad` = id del paciente, con la descripción de los campos modificados y `id_usuario` del psicólogo | D10 |
| CP-A-26 | Media | Tras CP-A-01, revisar `logs/app-AAAA-MM-DD.log` | Entrada `INFO` con `id_paciente`, `usuario` y campos modificados | D10, D12 |
| CP-A-27 | Alta | Tras un rechazo (CP-A-08) | **No** se crea registro en `auditoria_acciones` ni entrada `INFO` de actualización | D5, D10 |

#### A.4 Seguridad y robustez

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-A-28 | Alta | Cerrar sesión e ir a `/paciente/editar/1` | Redirige a `/auth/login`; no se muestra información del paciente | D9 |
| CP-A-29 | Alta | Iniciar sesión como Director (rol 2) o Admin (rol 3) e ir a `/paciente/editar/1` y a `/paciente/actualizar/1` | Redirige a `/dashboard`; **no** se modifica ningún registro | D9, D15 |
| CP-A-30 | Alta | `/paciente/actualizar/999999` (id inexistente) | Mensaje **"Paciente no encontrado."** y redirección al directorio | D9 |
| CP-A-31 | Alta | Nombre con `<script>alert(1)</script>` | Se guarda el texto literal y se **muestra escapado** en el directorio y en el formulario (no se ejecuta) | D8 |
| CP-A-32 | Alta | NIE/DUI con `' OR '1'='1` | Se trata como texto; no se listan registros ajenos ni se produce error 500 | D7, D12 |
| CP-A-33 | Media | Enviar el formulario por GET o sin sesión iniciada | Se rechaza y redirige al login | D9 |

#### A.5 Regresión

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-A-34 | Alta | Tras actualizar el nombre de `DT-01`, abrir su expediente, su cita y un reporte | Todos muestran el **nuevo** nombre (los datos se leen de `pacientes`, no están duplicados) | D5, D17 |
| CP-A-35 | Media | Tras actualizar `DT-01`, listar pacientes y usar el buscador por NIE/DUI | Aparece con los datos nuevos y el buscador lo encuentra por el NIE nuevo | D5 |
| CP-A-36 | Media | Verificar que el paciente sigue con su `estado_paciente` original y que la función de eliminar/reactivar no se rompió | Comportamiento idéntico al previo | D5, D16 |

---

### 7.3 Suite B — Modificar / reprogramar cita (HU-08)

> **Historia:** *Como psicólogo, necesito modificar cita para adaptarme a cambios.*
> 1. Cambiar fecha y hora. 2. Validar disponibilidad antes de confirmar. 3. Actualizar la cita.
> **Ruta:** `/cita/editar/{id}` (GET/POST)

| ID | Prioridad | Precondición / Datos | Pasos | Esperado | DoD |
|---|---|---|---|---|---|
| CP-B-01 | Alta | `DT-06` | Editar → cambiar fecha y hora a un horario libre → Guardar | Cita actualizada en BD (`fecha_hora`), banner verde de confirmación, redirección al detalle | D1, D2, D4 |
| CP-B-02 | Alta | `DT-06` | Editar → **misma** fecha y hora → Guardar | Sin cambios en BD; mensaje informativo; no se ejecuta un UPDATE efectivo | D5 |
| CP-B-03 | Alta | `DT-06` | Editar → mover la cita a la misma hora de **otra** cita `Programada` del mismo psicólogo | No guarda; mensaje rojo indicando el horario ocupado y el paciente con el que choca | D3 |
| CP-B-04 | Alta | `DT-06` | Editar → mover a +30 min respecto a otra cita del mismo psicólogo | No guarda (solapamiento dentro de la franja de 1 h) | D3 |
| CP-B-05 | Media | `DT-06` | Editar → mover a +90 min | Guarda (no hay solapamiento; el límite es 60 min) | D1 |
| CP-B-06 | Media | `DT-06` | Editar → mover a la hora de una cita de **otro** psicólogo | Guarda (el conflicto se evalúa solo por psicólogo) | D1 |
| CP-B-07 | Alta | `DT-07` (cita `Cancelada` a las 09:00) | Editar → mover a las 09:00 | Guarda (las citas canceladas no bloquean la agenda) | D1 |
| CP-B-08 | Media | `DT-06` | Editar → hora vacía o formato inválido | No guarda; mensaje **"Debes indicar una fecha y una hora válidas."** | D3 |
| CP-B-09 | Alta | Cita `Completada` | Intentar abrir `/cita/editar/{id}` | Redirige al detalle con aviso **"Solo se pueden reprogramar citas en estado Programada."** | D1, D9 |
| CP-B-10 | Alta | `DT-06` | Editar → guardar → consultar `logs/app-*.log` | Entrada `INFO` con `antes` y `despues` de `fecha_hora` | D10 |
| CP-B-11 | Media | `DT-06` | Ver el detalle y el listado | El botón **✏️ Reprogramar / Editar** solo aparece en citas `Programada` | D1 |
| CP-B-12 | Alta | Sesión rol 2/3 | `/cita/editar/{id}` | Redirige a `/dashboard` | D9 |

---

### 7.4 Suite C — Eliminar (desactivar) paciente + auditoría (HU-07)

| ID | Prioridad | Precondición / Datos | Pasos | Esperado | DoD |
|---|---|---|---|---|---|
| CP-C-01 | Alta | `DT-01` (con dependencias) | Eliminar → escribir un NIE/DUI **distinto** → Confirmar | No se desactiva; mensaje **"La confirmación no coincide…"** | D3 |
| CP-C-02 | Alta | `DT-01` | Eliminar → escribir el NIE/DUI exacto pero **sin** marcar la casilla | No se desactiva; mensaje exigiendo marcar la casilla | D3 |
| CP-C-03 | Alta | `DT-01` | Eliminar → escribir NIE/DUI exacto → marcar casilla → Confirmar | `estado_paciente = 'Inactivo'`; el paciente desaparece del listado activo; **no** se borran citas, expediente ni sesiones | D1, D5 |
| CP-C-04 | Alta | `DT-02` (sin dependencias) | Eliminar → Confirmar (sin reinforced validation) | Se desactiva correctamente | D1 |
| CP-C-05 | Alta | `DT-05` | Ver listado con *Mostrar inactivos* → Reactivar | Vuelve a `Activo` y reaparece en el listado activo | D1 |
| CP-C-06 | Alta | Tras CP-C-03 | Consultar `auditoria_acciones` | Registros `DESACTIVAR` y `REACTIVAR` con `id_usuario`, descripción y campos asociados | D10 |
| CP-C-07 | Alta | Rol 2/3 | `/paciente/eliminar/{id}` | Redirige a `/dashboard` | D9 |

---

### 7.5 Suite D — Matriz de cobertura de historias anteriores (regresión)

> Casos resumidos; se ejecutan en cada ciclo de entrega para detectar regresiones.

| ID | Historia / módulo | Verificación mínima | Prioridad |
|---|---|---|---|
| CP-D-01 | Crear paciente | Registro con todos los campos obligatorios; los opcionales en `NULL`; aparece en el listado | Alta |
| CP-D-02 | Crear paciente | NIE/DUI duplicado → error de BD controlado (no pantalla en blanco) | Alta |
| CP-D-03 | Crear cita | Se guarda como `Programada` y aparece en la agenda del día | Alta |
| CP-D-04 | Crear cita | Choque de horario (mismo psychologist, < 60 min) → mensaje de no disponibilidad | Alta |
| CP-D-05 | Cancelar cita | Pasa a `Cancelada`, guarda motivo, fecha y usuario; no puede cancelarse dos veces | Alta |
| CP-D-06 | Cancelar cita | Motivo < 5 caracteres → rechazado | Media |
| CP-D-07 | Paginación de citas | "Mostrando X–Y de N (página P de T)"; los enlaces respetan `vista`, `desde`, `hasta` | Media |
| CP-D-08 | Filtro por rango de fechas | Filtra correctamente; `desde > hasta` invierte el rango; fechas inválidas se ignoran | Media |
| CP-D-09 | Recordatorio | Envío de recordatorio: se registra `Enviado`/`Fallido` en `recordatorios_citas` y en el log | Media |
| CP-D-10 | Estado del caso | Cambio de estado en expediente, badge en listado y detalle, entrada en historial | Media |
| CP-D-11 | Historial de cambios | Editar antecedentes/sesiones genera entrada en `historial_expedientes`/`historial_sesiones` | Media |
| CP-D-12 | Autenticación | Login válido/inválido; ruta protegida redirige a login; cierre de sesión | Alta |
| CP-D-13 | Recuperación de contraseña | Solicitud genera token y correo; token caducado o inválido no permite restablecer | Media |
| CP-D-14 | Reportes | Filtros aplicados; exportación reflecte los datos vigentes (incluye datos actualizados de pacientes) | Media |
| CP-D-15 | Asistencias | Registro por expediente y fecha; duplicado bloqueado por índice único | Media |

---

### 7.6 Suite E — Seguridad y control de acceso (transversal)

| ID | Prioridad | Verificación | Resultado esperado |
|---|---|---|---|
| CP-E-01 | Alta | Cada ruta de escritura clínica (`paciente`, `cita`, `expediente`) probada con rol 2 y rol 3 | Redirección a `/dashboard` en todas |
| CP-E-02 | Alta | Acceso sin sesión a rutas de datos | Redirección a `/auth/login` |
| CP-E-03 | Alta | Manipulación de `id` en la URL (`/paciente/actualizar/0`, id inexistente) | Mensaje de no encontrado; sin error 500 |
| CP-E-04 | Alta | Intento de bypass de rol vía POST directo | Rechazado por el constructor del controlador |
| CP-E-05 | Alta | XSS en los campos de texto de todos los formularios | Salida escapada, sin ejecución de script |
| CP-E-06 | Alta | SQL injection en los campos de búsqueda y de los formularios | Consultas preparadas; sin error de sintaxis SQL ni fuga de datos |
| CP-E-07 | Media | Intento de respuesta a `success` forjado en la URL | No se muestra un mensaje falso (el flash solo proviene de la sesión) |

---

### 7.7 Suite G — Asignar roles y permisos (HU-10)

> **Historia:** *Como administrador necesito asignar roles y permisos para controlar el acceso a funcionalidades según tipo de rol.*
> 1. Asignar roles predefinidos (Administrador, Psicólogo, Director).
> 2. Restringir el acceso a funcionalidades según el rol asignado.
> 3. Los cambios deben aplicarse inmediatamente después de ser guardados.
>
> **Rutas:** `/usuario`, `/usuario/editar/{id}`, `/usuario/actualizar/{id}`, `/rol`
> **Matriz de referencia:** `app/Core/Permisos.php` (fuente única de verdad).

#### G.1 Asignación de roles predefinidos

| ID | Prioridad | Precondición / Datos | Pasos | Esperado | DoD |
|---|---|---|---|---|---|
| CP-G-01 | Alta | Sesión Administrador | Ir a *Gestión de Usuarios → Editar* | El selector de rol lista los 3 roles del catálogo `roles` (Psicólogo, Director, Administrador) con sus nombres reales | D1, D4 |
| CP-G-02 | Alta | Sesión Administrador | Ver la tabla de usuarios | Columna **Rol** con badge por rol en cada usuario | D2 |
| CP-G-03 | Alta | Sesión Administrador | Guardar un usuario con rol "Director" | BD: `id_rol = 2`; mensaje de confirmación | D1, D4 |
| CP-G-04 | Alta | POST manipulado con `id_rol = 99` | Guardar un usuario con rol inexistente | Se rechaza: **"El rol seleccionado no es válido."**; no se inserta ni se produce error SQL | D3, D7 |
| CP-G-05 | Alta | Sesión Administrador | Crear un usuario nuevo con un rol del catálogo | Usuario creado con el rol asignado y acceso coherente con el rol | D1 |
| CP-G-06 | Media | Sesión Administrador | Abrir `/rol` | Pantalla *Roles y Permisos*: catálogo de los 3 roles, alcance de cada uno y **matriz de permisos por funcionalidad** | D1, D2 |

#### G.2 Restricción de acceso según rol

| ID | Prioridad | Rol | Pasos | Esperado | DoD |
|---|---|---|---|---|---|
| CP-G-07 | Alta | Psicólogo | `/paciente`, `/cita`, `/expediente`, `/recordatorio` | HTTP 200; contenido del módulo | D9 |
| CP-G-08 | Alta | Psicólogo | `/usuario` y `/rol` | HTTP 302 → `/dashboard` con mensaje "No tienes permisos…" | D9 |
| CP-G-09 | Alta | Director | `/reportes`, `/asistencias` | HTTP 200 | D9 |
| CP-G-10 | Alta | Director | `/paciente`, `/cita`, `/expediente` | HTTP 302 → `/dashboard` | D9 |
| CP-G-11 | Alta | Director | `/reportes/nuevo` (generar informe) | HTTP 302 → `/dashboard` ("Solo el psicólogo puede crear informes.") | D9 |
| CP-G-12 | Alta | Director | Registrar asistencia (`/asistencia/guardar`) | HTTP 302 → `/dashboard` ("Solo el psicólogo puede registrar asistencias.") | D9 |
| CP-G-13 | Alta | Administrador | `/usuario` y `/rol` | HTTP 200 | D9 |
| CP-G-14 | Alta | Administrador | `/paciente` y `/expediente` | HTTP 302 → `/dashboard` | D9 |
| CP-G-15 | Alta | Todos | El menú lateral | Muestra **solo** los módulos permitidos para el rol (coherente con la matriz) | D1, D9 |
| CP-G-16 | Alta | Sin sesión | Cualquier ruta de datos | HTTP 302 → `/auth/login` | D9 |
| CP-G-17 | Media | Administrador | Intento de acceso directo a un módulo restringido por URL | Redirección + registro `WARNING` "Acceso denegado por rol" en el log | D9, D10 |

#### G.3 Aplicación inmediata de los cambios

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-G-18 | Alta | Psicólogo conectado → el Administrador cambia su rol a Director | En la **siguiente petición** del psicólogo (sin cerrar sesión) `/paciente` ya responde 302 y `/reportes` responde 200 | D1, D5 |
| CP-G-19 | Alta | Psicólogo conectado → el Administrador lo desactiva | La siguiente petición cierra la sesión y redirige a `/auth/login` con mensaje explicativo | D1, D5 |
| CP-G-20 | Alta | Administrador conectado → el Administrador lo desactiva o degrada su propio rol | Se rechaza: "No puedes desactivar tu propia cuenta." / "No puedes cambiar tu propio rol." | D3 |
| CP-G-21 | Alta | Único administrador activo → intentar degradarlo o desactivarlo | Se rechaza: "No puedes dejar el sistema sin ningún administrador activo." | D3 |
| CP-G-22 | Media | Tras un cambio de rol | Registro en `auditoria_acciones` (`ACTUALIZAR`, entidad `usuario`) con el rol anterior y el nuevo; entrada `INFO` en el log | D10 |
| CP-G-23 | Media | Tras un cambio de rol, revisar la sesión | `$_SESSION['id_rol']` refleja el rol nuevo sin reiniciar sesión | D5 |

#### G.4 Robustez

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-G-24 | Alta | Desactivar el último administrador por URL directa | Rechazado por el servidor | D3, D9 |
| CP-G-25 | Media | Enviar `id_rol` con valor no numérico o vacío | Mensaje de validación; sin error 500 | D3, D12 |
| CP-G-26 | Media | Revisar `logs/app-*.log` tras la suite completa | Sin errores PHP nuevos; los intentos denegados quedan como `WARNING` | D12 |

---

### 7.8 Suite H — Exportar reportes a PDF (HU-11)

> **Historia:** *Como psicólogo, necesito exportar reportes para compartirlos.*
> 1. Generar archivos PDF descargable. 2. Formato legible y profesional. 3. Incluir toda la información relevante.
> **Rutas:** `/reportes/tecnico/{id}` · `/reportes/imprimir/{id}` · `/reportes/ver/{id}` · `/reportes/imprimir_reporte/{id}`
> **Documento:** `app/Views/reportes/_documento.php` + `public/css/reporte-impresion.css`

> El PDF se genera con el motor de impresión del navegador (**Guardar como PDF**), sin librerías externas.
> La prueba en Chrome/Edge: *Imprimir → Destino: Guardar como PDF → Guardar*.

#### H.1 Generación y descarga

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-H-01 | Alta | `/reportes/tecnico/{id}` | Botón **📄 Descargar PDF** visible que abre `/reportes/imprimir/{id}` en una pestaña nueva | D1, D2 |
| CP-H-02 | Alta | `/reportes/imprimir/{id}` | Documento autónomo: 200, sin menú lateral, con su propia hoja de estilos | D1, D2 |
| CP-H-03 | Alta | `/reportes/imprimir/{id}` | La barra superior ofrece **🖨️ Imprimir / Guardar PDF** y `window.print()` se invoca al abrir | D1 |
| CP-H-04 | Alta | En el diálogo de impresión | Opción **Guardar como PDF** genera el archivo con el nombre del paciente | D1 |
| CP-H-05 | Alta | `/reportes/ver/{id}` (reporte guardado) | Botón **📄 Descargar PDF** que apunta a `/reportes/imprimir_reporte/{id}` | D1 |
| CP-H-06 | Alta | `/reportes/imprimir_reporte/{id}` | Imprime el reporte **guardado**, con su número, fecha de generación, autor y nivel de urgencia | D1, D3 |

#### H.2 Formato legible y profesional

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-H-07 | Alta | Hoja `reporte-impresion.css` | Define `@page` (tamaño carta), tipografía corporativa y paleta institucional | D2 |
| CP-H-08 | Alta | Imprimir el documento | La barra de herramientas NO aparece en el PDF; el documento ocupa toda la hoja | D2 |
| CP-H-09 | Media | Documento largo (historial de sesiones) | La tabla de sesiones no se parte a la mitad; el encabezado se repite en cada página | D2 |
| CP-H-10 | Media | Firmas y pie de página | Las firmas y el aviso de confidencialidad no se separan del resto | D2 |
| CP-H-11 | Media | Con acentos y ñ | Los textos se muestran correctamente (á é í ó ú ñ ¿ ¡) | D2 |
| CP-H-12 | Media | Pantalla (vista previa) | El documento se ve igual en pantalla que en el PDF (mismo partial y mismo CSS) | D2, D5 |

#### H.3 Contenido relevante (criterio 3)

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-H-13 | Alta | Documento del informe técnico | Incluye identificación completa: NIE/DUI, nombres, apellidos, fecha de nacimiento, **edad calculada**, género, grado/sección, teléfono, correo y responsable | D3 |
| CP-H-14 | Alta | Documento del informe técnico | Incluye número de expediente, fecha de apertura y **estado del caso** | D3 |
| CP-H-15 | Alta | Documento del informe técnico | Incluye antecedentes médicos y familiares | D3 |
| CP-H-16 | Alta | Documento del informe técnico | Incluye el resumen de atención: total de sesiones, primera y última sesión | D3 |
| CP-H-17 | Alta | Documento del informe técnico | Incluye el resumen de citas: total, atendidas, canceladas y próxima cita programada | D3 |
| CP-H-18 | Alta | Documento del informe técnico | Incluye las tres secciones clínicas: observaciones, intervención realizada y notas de evolución | D3 |
| CP-H-19 | Alta | Documento del informe técnico | Incluye la tabla con el **historial completo de sesiones** (fecha, tipo de consulta, observaciones) | D3 |
| CP-H-20 | Alta | Documento del informe técnico | Incluye fecha de emisión, autor (psicólogo de la sesión) y firmas | D3 |
| CP-H-21 | Alta | Documento del reporte guardado | El contenido almacenado se descompone en sus 3 secciones (no en un bloque de texto plano) | D2, D3 |
| CP-H-22 | Media | Paciente sin sesiones registradas | El documento se genera con los mensajes "No hay observaciones…" y "Historial de sesiones" omitido | D3 |
| CP-H-23 | Media | Documento | Incluye el aviso de "carácter confidencial y de uso exclusivo institucional" | D3 |

#### H.4 Permisos y robustez

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-H-24 | Alta | Director en `/reportes/imprimir/{id}` | HTTP 302 → dashboard (mismo permiso que la vista en pantalla) | D9 |
| CP-H-25 | Alta | Director en `/reportes/imprimir_reporte/{id}` | HTTP 200 (puede exportar lo que ya puede consultar) | D9 |
| CP-H-26 | Alta | Sin sesión | Redirección a `/auth/login` | D9 |
| CP-H-27 | Alta | Expediente o reporte inexistente | Mensaje "no encontrado" y redirección; sin error 500 | D9, D12 |
| CP-H-28 | Media | Texto con `<script>` en las notas | Se escapa en el documento (no se ejecuta) | D8 |
| CP-H-29 | Media | Revisar `logs/app-*.log` | Entrada `INFO` "Informe exportado para impresion"; sin errores PHP | D10, D12 |

---

### 7.9 Suite I — Métricas institucionales (HU-12)

> **Historia:** *Como director, necesito visualizar métricas para un análisis general.*
> 1. Mostrar indicadores claves. 2. Actualizar información dinámicamente. 3. Presentar los datos de forma clara.
> **Ruta canónica:** `/dashboard` (permiso `metricas`: Director y Administrador) · Alias: `/metricas` → redirige a `/dashboard`
> **Piezas:** `app/Core/Metricas.php` · `app/Models/Dashboard::obtenerMetricas()` · `app/Libraries/Graficos.php` · `app/Views/metricas/index.php` · `public/css/metricas.css`

> **El dashboard se adapta al rol:** el Psicólogo (rol 1) mantiene su vista operativa
> (citas de hoy y agenda del día); el Director (rol 2) y el Administrador (rol 3)
> ven el **panel de métricas** en `/dashboard`.

#### I.1 Indicadores clave

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-I-01 | Alta | Panel del Director | Muestra 8 tarjetas de indicadores (pacientes activos, expedientes en atención, citas del período, cumplimiento, inasistencia, sesiones, informes emitidos, casos de alta urgencia) | D1, D2 |
| CP-I-02 | Alta | Tarjeta "Pacientes activos" | Total de pacientes `Activo`, desglose estudiantes/empleados e inactivos | D1, D4 |
| CP-I-03 | Alta | Tarjeta "Expedientes en atención" | Suma de `Activo` + `Seguimiento`, desglose de seguimientos, cerrados y abiertos en el período | D1, D4 |
| CP-I-04 | Alta | Tarjeta "Citas del período" | Total de citas del rango, completadas y programadas | D1, D4 |
| CP-I-05 | Alta | Tarjeta "Cumplimiento de citas" | Porcentaje `completadas / total` calculado correctamente | D1, D4 |
| CP-I-06 | Alta | Tarjeta "Tasa de inasistencia" | Porcentaje `inasistencias / total` calculado correctamente | D1, D4 |
| CP-I-07 | Alta | Tarjeta "Sesiones clínicas" | Total, expedientes atendidos y promedio de sesiones por expediente | D1, D4 |
| CP-I-08 | Alta | Tarjeta "Informes emitidos" | Total y número + porcentaje de informes de urgencia alta | D1, D4 |
| CP-I-09 | Alta | Contraste contra la BD | Cada cifra del panel coincide con la consulta SQL directa (citas, pacientes activos, completadas, informes de alta urgencia) | D4, D15 |
| CP-I-10 | Media | Paciente con muchas sesiones | El promedio por expediente se calcula sobre los expedientes atendidos, no sobre el total de pacientes | D4 |

#### I.2 Actualización dinámica

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-I-11 | Alta | Esperar 60 s sin interactuar | La página se recarga sola y los indicadores se actualizan | D2 |
| CP-I-12 | Alta | Botón "🔄 Actualizar" | Recarga inmediata conservando el período seleccionado | D2 |
| CP-I-13 | Alta | Interruptor "Auto" desmarcado | No hay recarga automática; el estado se conserva entre recargas | D2 |
| CP-I-14 | Media | Marca de tiempo | Se muestra la fecha y hora de la última actualización | D2 |
| CP-I-15 | Media | Cronómetro | Cuenta regresiva visible hasta la próxima actualización | D2 |
| CP-I-16 | Media | Cursor sobre la página | La actualización se pausa para no interrumpir al usuario | D2 |
| CP-I-17 | Alta | Filtro de período | "Este mes / 3 meses / 6 meses / 12 meses / Todo" recalcula los indicadores y muestra el rango de fechas | D2, D4 |
| CP-I-18 | Media | Período inválido en la URL | Cae en el período predeterminado (6 meses) sin error | D3, D12 |

#### I.3 Presentación clara

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-I-19 | Alta | Gráfico "Evolución mensual" | Barras agrupadas de citas y sesiones de los últimos 12 meses, con leyenda | D3 |
| CP-I-20 | Alta | Gráfico "Citas por día de la semana" | Barras horizontales con los 7 días | D3 |
| CP-I-21 | Alta | Gráficos de estado del caso, nivel de urgencia y tipo de paciente | Barras horizontales con etiqueta, color por categoría y valor | D3 |
| CP-I-22 | Alta | Tabla "Resumen de la agenda" | Estado, cantidad y participación porcentual con barra de progreso | D3 |
| CP-I-23 | Media | Gráficos SVG | Responsivos (`viewBox`), con `<title>` en cada barra (accesibilidad) | D3 |
| CP-I-24 | Media | Panel sin datos | No hay errores ni división por cero; los gráficos quedan vacíos | D3, D12 |
| CP-I-25 | Media | Pantalla pequeña | Las tarjetas y gráficos se reorganizan en una columna | D3 |
| CP-I-26 | Media | Nota al pie | Aclara que las métricas son agregadas y no exponen información clínica individual | D3 |

#### I.4 Permisos

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-I-27 | Alta | Psicólogo en `/dashboard` | Ve **su** dashboard operativo (agenda del día), sin tarjetas KPI | D9 |
| CP-I-28 | Alta | Director en `/dashboard` | HTTP 200 con el panel de métricas completo | D9 |
| CP-I-29 | Alta | Administrador en `/dashboard` | HTTP 200 con el panel de métricas completo | D9 |
| CP-I-30 | Media | Menú lateral | Una sola entrada "📊 Dashboard"; no hay entradas duplicadas de métricas | D9 |
| CP-I-31 | Alta | Director en `/metricas` | HTTP 302 → `/dashboard` (alias conservado), conservando el período | D9 |
| CP-I-32 | Alta | Psicólogo en `/metricas` | HTTP 302 → `/dashboard` (no ve las métricas) | D9 |

---

### 7.10 Suite J — Respaldos del sistema (HU-13)

> **Historia:** *Como administrador, necesito generar respaldo para proteger la información del sistema.*
> 1. Generar backups manuales. 2. Almacenar de forma rápida y segura. 3. Confirmar la generación exitosa.
> **Rutas:** `/respaldo` · `/respaldo/generar` · `/respaldo/descargar/{archivo}` · `/respaldo/eliminar/{archivo}`
> **Permiso:** `backups` (solo Administrador)
> **Piezas:** `app/Libraries/Respaldo.php` · `app/Controllers/RespaldoController.php` · `backups/` (fuera del docroot)

#### J.1 Backups manuales

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-J-01 | Alta | Administrador en `/respaldo` | Ve la pantalla con botón **💾 Generar respaldo** y el listado de archivos | D1, D2 |
| CP-J-02 | Alta | Pulsar "Generar respaldo" | Se genera un archivo `respaldo_AAAAMMDD_HHMMSS.sql.gz` en la carpeta de respaldos | D1 |
| CP-J-03 | Alta | Tras generar | Mensaje de confirmación con **nombre del archivo, tamaño y duración** | D3 |
| CP-J-04 | Alta | Tras generar | Registro `CREAR` en `auditoria_acciones` (entidad `respaldo`) y entrada `INFO` en el log | D10 |
| CP-J-05 | Media | Generar dos respaldos en el mismo segundo | El segundo **no** sobrescribe el primero (sufijo numérico) | D5 |
| CP-J-06 | Alta | `GET /respaldo/generar` (sin POST) | No genera nada; redirige al listado | D3 |

#### J.2 Almacenamiento rápido y seguro

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-J-07 | Alta | Contenido del archivo | Es un dump SQL gzip con la estructura (`CREATE TABLE`) y los datos (`INSERT`) de **todas** las tablas | D1, D4 |
| CP-J-08 | Alta | **Restauración real** del dump | Se restaura en una base de datos temporal sin errores y el número de registros coincide con el original | D4, D15 |
| CP-J-09 | Alta | Orden de las tablas | Las tablas referenciadas se crean antes que las que las referencian (el respaldo restaura sin claves foráneas) | D4 |
| CP-J-10 | Alta | Ubicación | La carpeta `backups/` está **fuera** de `public/` (docroot) | D9 |
| CP-J-11 | Alta | Acceso directo por URL | `backups/.htaccess` bloquea el acceso (Apache 2.2 y 2.4) y el `.htaccess` raíz lista `backups` entre las carpetas prohibidas | D9 |
| CP-J-12 | Alta | Descarga | La descarga se hace por la ruta autenticada del Administrador, nunca por URL directa del archivo | D9 |
| CP-J-13 | Alta | Intento de path traversal en la descarga (`../../app/Config/Database.php`, rutas absolutas) | Rechazado; no se entrega ningún archivo | D9, D15 |
| CP-J-14 | Media | Versionamiento | Los `*.sql.gz` están en `.gitignore` (no se suben al repositorio) | D10 |
| CP-J-15 | Media | Retención | Se conservan los 10 respaldos más recientes; al superarlos, los antiguos se purgan solos | D5 |
| CP-J-16 | Media | Rendimiento | La generación de una base de prueba se completa en menos de 10 segundos | D5 |

#### J.3 Confirmación y gestión

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-J-17 | Alta | Listado tras generar | Muestra nombre, fecha, tamaño y acciones de cada respaldo | D2 |
| CP-J-18 | Alta | Eliminar un respaldo | Se borra del disco, se confirma con mensaje y queda registrado en la auditoría | D3, D10 |
| CP-J-19 | Alta | Descargar un respaldo | HTTP 200 con `Content-Type: application/gzip` y `Content-Disposition: attachment`, con el tamaño exacto del archivo | D1 |
| CP-J-20 | Media | Pantalla sin respaldos | Mensaje claro indicando que aún no hay respaldos | D2 |
| CP-J-21 | Media | Pantalla | Advierte que los respaldos contienen información clínica sensible y que deben custodiarse | D2 |
| CP-J-22 | Media | Revisar `logs/app-*.log` | Entradas `INFO` de generación, descarga y eliminación; sin errores PHP | D10, D12 |

#### J.4 Permisos

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-J-23 | Alta | Psicólogo en `/respaldo` | HTTP 302 → `/dashboard` | D9 |
| CP-J-24 | Alta | Psicólogo en `/respaldo/generar` | HTTP 302; no se genera ningún archivo | D9, D15 |
| CP-J-25 | Alta | Sin sesión en `/respaldo` | HTTP 302 → `/auth/login` | D9 |
| CP-J-26 | Media | Menú lateral | El enlace "💾 Respaldos" solo aparece para el Administrador | D9 |

---

### 7.11 Suite K — Restaurar datos desde un respaldo (HU-14)

> **Historia:** *Como administrador, necesito restaurar datos desde un respaldo.*
> 1. Permitir seleccionar un respaldo existente. 2. Restaurar los datos correctamente. 3. Validar la integridad de la información restaurada.
> **Rutas:** `/respaldo/restaurar/{archivo}` (GET = confirmación, POST = ejecución) · `/respaldo` (acción ♻️ Restaurar)
> **Permiso:** `backups` (solo Administrador)

#### K.1 Selección del respaldo

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-K-01 | Alta | Administrador en `/respaldo` | Cada respaldo listado ofrece la acción **♻️ Restaurar** | D1, D2 |
| CP-K-02 | Alta | Pulsar "Restaurar" | Se abre la pantalla de confirmación con el respaldo seleccionado | D1 |
| CP-K-03 | Alta | Pantalla de confirmación | Muestra archivo, fecha, tamaño, tablas y total de registros a restaurar | D1, D3 |
| CP-K-04 | Alta | Pantalla de confirmación | Muestra el detalle tabla por tabla con su número de registros | D3 |
| CP-K-05 | Alta | Analizar un respaldo | `Respaldo::analizar()` detecta tablas y filas **sin modificar la base** | D5, D10 |
| CP-K-06 | Alta | Analizar un archivo que no es respaldo o está corrupto | Se rechaza con un mensaje claro y no se toca la base | D3, D12 |
| CP-K-07 | Alta | Seleccionar un nombre con path traversal | Se rechaza; no se lee ningún archivo fuera de la carpeta | D9, D15 |

#### K.2 Confirmación y salvaguardas

| ID | Prioridad | Pasos | Esperado | DoD |
|---|---|---|---|---|
| CP-K-08 | Alta | Restaurar sin escribir el nombre del archivo | Se rechaza con el mensaje "debes escribir exactamente el nombre del archivo" | D3 |
| CP-K-09 | Alta | Restaurar sin marcar la casilla | Se rechaza con el mensaje "debes marcar la casilla" | D3 |
| CP-K-10 | Alta | Confirmar correctamente | Se genera **automáticamente un respaldo de seguridad** del estado actual antes de tocar nada | D5 |
| CP-K-11 | Alta | Si no se puede crear el respaldo de seguridad | La restauración se cancela y se informa (el sistema nunca queda sin protección) | D5, D15 |
| CP-K-12 | Media | Pantalla de confirmación | Advierte de forma expresa que se reemplazan todos los datos actuales | D2 |

#### K.3 Restauración correcta

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-K-13 | Alta | Restaurar sobre la base viva | La restauración se ejecuta sin errores (sentencias aplicadas > 0) | D1, D4 |
| CP-K-14 | Alta | Dato registrado **después** del respaldo | Desaparece tras restaurar | D4 |
| CP-K-15 | Alta | Dato contenido **en** el respaldo | Sigue presente tras restaurar | D4 |
| CP-K-16 | Alta | Número de registros por tabla | Vuelve exactamente al número del respaldo | D4 |
| CP-K-17 | Alta | Restauración repetida | Es idempotente: repetirla no altera el resultado | D5 |
| CP-K-18 | Alta | Usuario que realiza la restauración | Permanece en el sistema (su cuenta no desaparece) | D5 |
| CP-K-19 | Media | Rendimiento | La restauración se completa en menos de 30 segundos | D5 |
| CP-K-20 | Alta | Historial | Se registra `RESTAURAR` en `auditoria_acciones` con el nombre del archivo y el respaldo de seguridad usado | D10 |

#### K.4 Validación de integridad

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-K-21 | Alta | Tras restaurar | Todas las tablas del respaldo existen en la base de datos | D3, D4 |
| CP-K-22 | Alta | Conteo por tabla | Cada tabla tiene exactamente el número de filas declarado en el archivo | D3 |
| CP-K-23 | Alta | Integridad referencial | Sin citas sin paciente, sesiones/expedientes/asistencias/informes sin expediente, ni acciones de auditoría sin usuario | D3 |
| CP-K-24 | Alta | Catálogos mínimos | El catálogo de roles queda completo (3 roles) | D3 |
| CP-K-25 | Media | Sin administradores activos | Se genera una **advertencia** visible para el administrador | D2, D3 |
| CP-K-26 | Media | Tablas que no están en el respaldo | Se advierte que conservan sus datos actuales | D2, D3 |
| CP-K-27 | Alta | Detectar una corrupción (eliminar filas) | La validación falla y **describe** la tabla discrepante | D3 |
| CP-K-28 | Alta | Restauración que no supera la validación | El sistema **se revierte automáticamente** con el respaldo de seguridad y lo informa | D5, D15 |
| CP-K-29 | Alta | Reversión automática fallida | Se informa del fallo y se indica qué respaldo restaurar manualmente | D15 |

#### K.5 Permisos

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-K-30 | Alta | Director en `/respaldo/restaurar/{archivo}` | HTTP 302 → `/dashboard` | D9 |
| CP-K-31 | Alta | Psicólogo en `/respaldo/restaurar/{archivo}` | HTTP 302 → `/dashboard` | D9 |
| CP-K-32 | Alta | Sin sesión | HTTP 302 → `/auth/login` | D9 |

---

### 7.12 Suite L — Auditoría de acciones (HU-15)

> **Historia:** *Como administrador, necesito auditar acciones para el control del sistema.*
> 1. Registrar acciones críticas (crear, editar, eliminar). 2. Consultar registros por usuario o fecha. 3. Mostrar detalles claros de cada acción.
> **Rutas:** `/auditoria` (listado con filtros) · `/auditoria/ver/{id}` (detalle)
> **Permiso:** `auditoria` (solo Administrador) · **Registro:** tabla `auditoria_acciones`

#### L.1 Registro de acciones críticas

| ID | Prioridad | Acción | Verificación | DoD |
|---|---|---|---|---|
| CP-L-01 | Alta | Crear paciente | Registro `CREAR` / `paciente` con el usuario, la fecha y la descripción | D1, D10 |
| CP-L-02 | Alta | Actualizar paciente | Registro `ACTUALIZAR` / `paciente` con los campos modificados | D1, D10 |
| CP-L-03 | Alta | Eliminar paciente | Registro `ELIMINAR` / `paciente` | D1, D10 |
| CP-L-04 | Alta | Reactivar paciente | Registro `REACTIVAR` / `paciente` | D1, D10 |
| CP-L-05 | Alta | Crear cita | Registro `CREAR` / `cita` con el `id_entidad` de la cita | D1, D10 |
| CP-L-06 | Alta | Reprogramar cita | Registro `ACTUALIZAR` / `cita` con el horario anterior y el nuevo | D1, D10 |
| CP-L-07 | Alta | Cancelar cita | Registro `ELIMINAR` / `cita` con el motivo de cancelación | D1, D10 |
| CP-L-08 | Alta | Abrir expediente | Registro `CREAR` / `expediente` | D1, D10 |
| CP-L-09 | Alta | Editar antecedentes | Registro `ACTUALIZAR` / `expediente` con los campos modificados | D1, D10 |
| CP-L-10 | Alta | Cambiar estado del caso | Registro `ACTUALIZAR` / `expediente` con el estado anterior y el nuevo | D1, D10 |
| CP-L-11 | Alta | Registrar sesión clínica | Registro `CREAR` / `sesion` | D1, D10 |
| CP-L-12 | Alta | Generar informe | Registro `CREAR` / `informe` con el nivel de urgencia | D1, D10 |
| CP-L-13 | Alta | Registrar asistencia | Registro `CREAR` / `asistencia` con la fecha y el estado | D1, D10 |
| CP-L-14 | Alta | Crear usuario / cambiar rol / desactivar | Registro `CREAR` / `ACTUALIZAR` / `DESACTIVAR` sobre `usuario` | D1, D10 |
| CP-L-15 | Alta | Generar / eliminar / restaurar respaldo | Registro `CREAR` / `ELIMINAR` / `RESTAURAR` sobre `respaldo` | D1, D10 |
| CP-L-16 | Alta | Fallo de la auditoría | La operación de negocio **no** se interrumpe; queda constancia en el log | D10, D12 |
| CP-L-17 | Media | Acciones de solo lectura | Consultar un listado **no** genera registro (el registro es de acciones críticas) | D5 |

#### L.2 Consulta por usuario o fecha

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-L-18 | Alta | Filtro por usuario | Solo se muestran los registros de ese usuario (los demás quedan fuera de la tabla) | D1 |
| CP-L-19 | Alta | Filtro por rango de fechas (hoy) | Solo los registros del día | D1 |
| CP-L-20 | Alta | Filtro por rango de fechas (futuro) | "No hay registros de auditoría que coincidan" | D1, D3 |
| CP-L-21 | Alta | Filtro por tipo de acción | Solo los registros de esa acción | D1 |
| CP-L-22 | Alta | Filtro por entidad | Solo los registros de esa entidad | D1 |
| CP-L-23 | Alta | Búsqueda de texto en la descripción | Encuentra el registro por el dato buscado | D1 |
| CP-L-24 | Alta | Combinación de filtros | Usuario + acción + entidad + fecha se aplican **together** | D1 |
| CP-L-25 | Media | Rango invertido (`desde` > `hasta`) | El sistema lo corrige intercambiando las fechas | D3 |
| CP-L-26 | Media | Fecha con formato inválido | Se ignora el filtro en lugar de romper la consulta | D3, D12 |
| CP-L-27 | Media | Listado largo | Paginación de 20 en 20 con el total y los filtros preservados | D2 |

#### L.3 Detalle claro de cada acción

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-L-28 | Alta | Vista de detalle | Muestra fecha y hora, usuario responsable (con rol y correo), acción, entidad e ID, y la descripción completa | D3 |
| CP-L-29 | Alta | Detalle de un registro inexistente | Mensaje "Registro de auditoría no encontrado" y redirección | D3 |
| CP-L-30 | Media | Listado | Cada fila muestra fecha, usuario, acción (con color por riesgo), entidad y descripción | D3 |
| CP-L-31 | Media | Acciones en el listado | Código de color por tipo de acción (verde=crear, turquesa=actualizar, rojo=eliminar, naranja=desactivar…) | D3 |
| CP-L-32 | Media | Usuario eliminado o sin rol | La vista indica "Usuario eliminado" en lugar de fallar | D3 |
| CP-L-33 | Alta | Solo lectura | El registro no puede modificarse ni eliminarse desde el sistema | D5 |

#### L.4 Resumen y permisos

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-L-34 | Media | Resumen superior | Total de acciones, tipos de acción, usuarios con registro y eliminaciones del período | D2 |
| CP-L-35 | Alta | Psicólogo en `/auditoria` | HTTP 302 → `/dashboard` | D9 |
| CP-L-36 | Alta | Psicólogo en `/auditoria/ver/{id}` | HTTP 302 → `/dashboard` | D9 |
| CP-L-37 | Media | Menú lateral | El enlace "📋 Auditoría" solo aparece para el Administrador | D9 |
| CP-L-38 | Alta | Sin sesión | HTTP 302 → `/auth/login` | D9 |

---

### 7.13 Suite F — Despliegue en Hostinger

| ID | Prioridad | Verificación | Resultado esperado |
|---|---|---|---|
| CP-F-01 | Alta | Ejecutar los `ALTER`/`CREATE TABLE` pendientes sobre la BD existente | Sin errores; los datos previos se conservan |
| CP-F-02 | Alta | Subir los archivos modificados a `public_html` | Archivos en la ruta correcta; la aplicación responde 200 |
| CP-F-03 | Alta | Recorrer las Suite A, B y C en producción | Todos los casos *Alta* en estado *Pasado* |
| CP-F-04 | Media | Envío de recordatorio en producción | Correo recibido; si no llega, revisar `logs/app-*.log` |
| CP-F-05 | Media | Revisar `logs/app-*.log` en producción tras la prueba | Sin errores PHP nuevos |
| CP-F-06 | Alta | En Hostinger: descargar y restaurar un respaldo | **Sin 403 Forbidden** (el nombre del archivo viaja en el query string, no en la ruta, para no exponer la extensión `.sql` que algunos servidores bloquean) | D1, D15 |

---

## 8. Matriz de trazabilidad

| Historia | Criterios de aceptación | DoD clave | Casos de prueba | Estado |
|---|---|---|---|---|
| HU-07 Eliminar paciente | Soft delete · validación reforzada · auditoría | D1, D3, D4, D9, D10 | CP-C-01…CP-C-07 | En ejecución |
| HU-08 Modificar cita | Cambiar fecha/hora · validar disponibilidad · actualizar | D1, D3, D4, D9, D10 | CP-B-01…CP-B-12 | En ejecución |
| HU-09 Actualizar paciente | Modificar cualquier dato · validar antes de guardar · mensaje de confirmación | D1, D2, D3, D4, D9, D10 | CP-A-01…CP-A-36 | En ejecución |
| HU-10 Roles y permisos | Asignar roles predefinidos · restringir acceso por rol · aplicación inmediata | D1, D2, D3, D4, D5, D9, D10 | CP-G-01…CP-G-26, Suite E | En ejecución |
| HU-11 Exportar reportes | PDF descargable · formato legible y profesional · información completa | D1, D2, D3, D8, D9, D10 | CP-H-01…CP-H-29 | En ejecución |
| HU-12 Métricas | Indicadores clave · actualización dinámica · presentación clara | D1, D2, D3, D4, D9, D12 | CP-I-01…CP-I-32 | En ejecución |
| HU-13 Respaldos | Backups manuales · almacenamiento rápido y seguro · confirmación exitosa | D1, D2, D3, D4, D5, D9, D10, D12 | CP-J-01…CP-J-26 | En ejecución |
| HU-14 Restaurar | Seleccionar un respaldo · restaurar correctamente · validar integridad | D1, D2, D3, D4, D5, D9, D10, D12, D15 | CP-K-01…CP-K-32 | En ejecución |
| HU-15 Auditoría | Registrar acciones críticas · consultar por usuario o fecha · detalles claros | D1, D2, D3, D5, D9, D10, D12 | CP-L-01…CP-L-38 | En ejecución |

---

### 7.15 Suite N — Entrega real del correo de recordatorios (HU-16)

> Revisión solicitada: el recordatorio se registraba como "Enviado" pero el correo no llegaba.
> Causa: `mail()` devuelve `true` al entregar el mensaje al proceso local, sin confirmar la
> entrega; en hosting el remitente inexistente hace que el mensaje se descarte después.
> Solución: envío por **SMTP nativo** con respuesta real del servidor y registro fiel del estado.

#### N.1 Cliente SMTP

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-N-01 | Alta | Envío contra servidor SMTP simulado | Conexión, `EHLO`, `AUTH LOGIN`, `MAIL FROM`, `RCPT TO`, `DATA` y `QUIT` completos | D1, D4 |
| CP-N-02 | Alta | Servidor responde `250` al mensaje | El envío se considera aceptado | D1 |
| CP-N-03 | Alta | Credenciales incorrectas (`535`) | El envío **falla** y se informa del rechazo de credenciales | D3, D12 |
| CP-N-04 | Alta | Servidor rechaza el mensaje (`550`) | El envío **falla** y se informa de la respuesta del servidor | D3, D12 |
| CP-N-05 | Alta | Servidor SMTP inaccesible | El envío **falla** con el error de conexión | D3, D12 |
| CP-N-06 | Media | El diálogo con el servidor | Queda disponible para diagnóstico (EHLO, AUTH, MAIL, RCPT, DATA) | D3 |
| CP-N-07 | Media | Destinatario con formato inválido | Se rechaza antes de conectar | D3 |

#### N.2 Estado fiel del recordatorio

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-N-08 | Alta | El servidor acepta el mensaje | `recordatorios_citas.estado = 'Enviado'` | D1, D4 |
| CP-N-09 | Alta | El servidor rechaza el mensaje | `recordatorios_citas.estado = 'Fallido'` (**nunca "Enviado"**) | D3, D4 |
| CP-N-10 | Alta | El envío falla | El mensaje al usuario explica el motivo y remite al diagnóstico | D2, D3 |
| CP-N-11 | Alta | Paciente sin correo registrado | No se intenta el envío; mensaje explicativo | D3 |
| CP-N-12 | Alta | Paciente con correo inválido | No se registra ningún envío; se pide corregir la ficha | D3 |
| CP-N-13 | Media | El paciente tiene correo válido | Se registra el destinatario en el historial | D4 |

#### N.3 Diagnóstico y configuración

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-N-14 | Alta | Pantalla `/recordatorio/diagnostico` | Muestra remitente, servidor, puerto, cifrado, usuario y si la clave está configurada | D2, D3 |
| CP-N-15 | Alta | Envío de correo de prueba | Muestra si el servidor aceptó el mensaje y el diálogo SMTP literal | D3 |
| CP-N-16 | Media | Configuración sin clave | La pantalla indica que falta configurar el buzón y guía los pasos | D2 |
| CP-N-17 | Media | Enlace desde Recordatorios | El acceso al diagnóstico está a un clic | D2 |
| CP-N-18 | Alta | Archivo `Correo.local.php` | No se versiona (está en `.gitignore`); la clave nunca se sube al repositorio | D10 |
| CP-N-19 | Media | `mail()` como alternativa | Si SMTP falla y está habilitado, se registra la advertencia del método usado | D12 |

---

### 7.14 Suite M — Sugerencias de interfaz (revisión de UX)

> Revisión puntual solicitada sobre la terminología del proceso de eliminación de pacientes,
> el aviso de citas con fecha vencida y la información mostrada en el inicio de sesión.

#### M.1 Terminología "eliminar paciente"

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-M-01 | Alta | Pantalla de eliminación | El proceso se denomina "Eliminar paciente"; no aparece la palabra "desactivación" | D2 |
| CP-M-02 | Alta | Casilla de confirmación | Indica que el paciente "será eliminado" y que su información clínica se conservará | D2, D3 |
| CP-M-03 | Alta | Mensaje de resultado | "fue eliminado del directorio. Su historial clínico se conserva" | D2 |
| CP-M-04 | Alta | Auditoría | La acción registrada es `ELIMINAR` y la descripción usa "eliminación", no "desactivación" | D3, D10 |
| CP-M-05 | Media | Listado de pacientes | El badge del paciente eliminado dice "Eliminado"; el conmutador dice "Mostrar eliminados" | D2 |
| CP-M-06 | Media | Métricas | La tarjeta de pacientes indica "eliminados" | D2 |

#### M.2 Aviso de citas con fecha vencida

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-M-07 | Alta | Consulta principal de citas | Banner con el número de citas con fecha vencida y la indicación de actualizar su estado | D2, D3 |
| CP-M-08 | Alta | Fila de la cita vencida | Etiqueta "⚠️ Vencida" y resaltado de la fila | D3 |
| CP-M-09 | Alta | Fila de la cita vigente | No se marca como vencida | D5 |
| CP-M-10 | Alta | Acciones de la cita vencida | Permite cancelar o marcar como inasistencia directamente desde el listado | D1 |
| CP-M-11 | Alta | Detalle de la cita vencida | Panel de advertencia con las acciones de cancelar y marcar inasistencia | D2, D3 |
| CP-M-12 | Alta | Marcar inasistencia | La cita pasa a estado `Inasistencia` y el cambio queda auditado | D1, D10 |
| CP-M-13 | Alta | Marcar inasistencia dos veces | Se rechaza: solo aplica a citas `Programada` | D3 |
| CP-M-14 | Media | Cita ya atendida o cancelada | No se considera vencida (el aviso aplica solo a `Programada`) | D5 |

#### M.3 Inicio de sesión sin datos de usuarios

| ID | Prioridad | Verificación | Esperado | DoD |
|---|---|---|---|---|
| CP-M-15 | Alta | Formulario de login | No expone ningún correo de administrador ni de usuario real | D9 |
| CP-M-16 | Alta | Hint del correo | Placeholder genérico y nota "use el correo institucional con el que fue registrado" | D2 |
| CP-M-17 | Media | Hint de la contraseña | Indica el criterio de contraseña (mayúscula, minúscula, número y símbolo) | D2 |
| CP-M-18 | Media | Aviso institucional | Indica que el acceso es exclusivo para personal autorizado y a quién acudir | D2 |
| CP-M-19 | Media | Recuperación de contraseña | Se conserva el enlace "¿Olvidaste tu contraseña?" | D1 |
| HU-01 Crear paciente | Registro con validaciones | D1, D3, D4 | CP-D-01, CP-D-02 | Pendiente |
| HU-02 Crear cita | Agenda + disponibilidad | D1, D3 | CP-D-03, CP-D-04 | Pendiente |
| HU-03 Cancelar cita | Motivo obligatorio + estado | D1, D3 | CP-D-05, CP-D-06 | Pendiente |
| HU-04 Paginación y filtro | Paginación + rango de fechas | D1 | CP-D-07, CP-D-08 | Pendiente |
| HU-05 Recordatorios | Envío y registro | D1, D10 | CP-D-09 | Pendiente |
| HU-06 Estado del caso | Cambio de estado | D1, D4 | CP-D-10 | Pendiente |
| HU-10 Autenticación/RBAC | Acceso por roles | D9 | CP-D-12, CP-D-13, Suite E | Pendiente |

---

## 9. Pruebas automatizadas de humo (smoke test)

Además de las pruebas manuales, el repositorio incluye arneses de prueba ejecutables en CLI contra la BD local:

```bash
# Modelos, reglas de validación y disponibilidad de citas
C:\xampp\php\php.exe tests\smoke_paciente.php

# Catálogo de roles, matriz de permisos y aplicación inmediata de cambios
C:\xampp\php\php.exe tests\smoke_roles.php

# Flujo completo por HTTP (requiere el servidor local levantado)
C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
C:\xampp\php\php.exe tests\http_roles.php
```

| Arnés | Qué valida |
|---|---|
| `smoke_paciente.php` | Bloques 1–6: unicidad de NIE/DUI · `actualizar()` · 15 rechazos y 4 aceptaciones de validación · detección de campos modificados · disponibilidad de citas · limpieza |
| `smoke_roles.php` | Bloques 1–8: catálogo de roles · matriz de permisos por rol (24 aserciones) · permisos según sesión · conteo de administradores · cambio de rol aplicado en caliente · cierre de sesión por cuenta desactivada · FK de `id_rol` · limpieza |
| `http_roles.php` | 26 aserciones end-to-end: login real por HTTP, matriz visible para el admin, restricciones por rol (302), **aplicación inmediata sin re-login**, cierre de sesión por desactivación, y limpieza de los datos de prueba |
| `http_reportes_pdf.php` | 50 aserciones end-to-end: CSS de impresión (@page, ocultamiento de la barra, cortes de página) · botón de descarga · documento autónomo sin menú · presencia de las 7 secciones y de todo el contenido clínico · guardado y exportación del reporte guardado · permisos por rol · limpieza |
| `http_metricas.php` | 65 aserciones end-to-end: contraste de cada KPI contra la BD · generación de SVG (barras, valores, escapado, sin división por cero) · **dashboard del Director = panel de métricas** (8 tarjetas, 5 gráficos, controles) · dashboard del Psicólogo = vista operativa · alias `/metricas` → `/dashboard` · filtro de período · permisos por rol · limpieza |
| `smoke_respaldo.php` | 41 aserciones: generación real (13 tablas, gzip, < 10 s) · contenido del dump · **restauración en una BD temporal con contraste de datos** · orden por dependencias · purga y eliminación · nombres únicos · protección contra path traversal · carpeta fuera del docroot con `.htaccess` · limpieza |
| `http_respaldo.php` | 38 aserciones end-to-end: pantalla de respaldos · generación manual con confirmación (nombre, tamaño, duración) · registro en auditoría · descarga gzip íntegra · rechazo de path traversal · eliminación con confirmación · permisos del Psicólogo y del anónimo · limpieza |
| `smoke_restauracion.php` | 35 aserciones: análisis previo sin modificar la BD · **restauración real sobre la base viva** (lo posterior al respaldo desaparece, lo anterior permanece) · validación de integridad (conteos por tabla, huérfanos, catálogos) · detección de corrupción · rechazo de archivos inválidos y corruptos · path traversal · limpieza |
| `http_restauracion.php` | 37 aserciones end-to-end: selección del respaldo · pantalla de confirmación con el análisis y las verificaciones · confirmaciones obligatorias (nombre y casilla) · restauración real con datos que deben desaparecer · confirmación con validación de integridad · respaldo de seguridad automático · archivo inexistente y path traversal · permisos del Director · limpieza |
| `http_auditoria.php` | 63 aserciones end-to-end: registro de las 5 acciones críticas de paciente y cita (CREAR, ACTUALIZAR, ELIMINAR, REACTIVAR) con usuario, entidad, ID y descripción · filtros por usuario, fecha, acción, entidad, texto y su combinación · detalle de una acción · permisos del Psicólogo · limpieza |
| `http_sugerencias.php` | 40 aserciones end-to-end: login sin datos de usuarios (hints genéricos) · terminología "eliminar paciente" (mensajes, casilla, badge, auditoría) · aviso de citas vencidas en la consulta principal y en el detalle · registro de inasistencia con auditoría · limpieza |
| `smoke_correo.php` | 18 aserciones: cliente SMTP nativo contra un servidor simulado (envío aceptado, credenciales inválidas, mensaje rechazado, servidor inaccesible, destinatario inválido) y carga de la configuración · se apoya en `smtp_falso.php` (servidor SMTP simulado) |
| `http_recordatorios.php` | 24 aserciones end-to-end: envío aceptado → `Enviado`; envío rechazado → `Fallido` (nunca "Enviado"); destinatario inválido → no se registra envío; pantalla de diagnóstico con configuración, prueba de correo y diálogo SMTP |

**Regla:** los arneses crean y destruyen datos `SMOKE-TEST-*` / `smoke-http-*`; nunca deben ejecutarse contra la BD de producción.

---

## 10. Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Envío de correo no probado localmente (`mail()` sin SMTP en XAMPP) | Falsa impresión de que el recordatorio funciona | Prueba obligatoria en Hostinger (CP-F-04) + revisión de `logs/app-*.log` |
| El hosting bloquea con 403 las URLs que incluyen `.sql` en la ruta | Descarga y restauración de respaldos inaccesibles | El nombre del archivo viaja en el query string (`?archivo=...`, con los puntos codificados); nunca en el path (CP-F-06) |
| `mail()` no confirma la entrega (devuelve `true` y el hosting descarta el mensaje) | Recordatorios marcados como "Enviado" que nunca llegan | Envío por SMTP nativo con la respuesta real del servidor; el estado se registra según esa respuesta (Suite N) |
| El buzón remitente no existe en el hosting | El servidor rechaza el mensaje después de aceptarlo localmente | Remitente configurable y buzón real obligatorio; pantalla de diagnóstico con envío de prueba (CP-N-14, CP-N-15) |
| Divergencia entre `plenamente_bd.sql` y la BD de Hostinger | Errores fatales al desplegar | Checklist de `ALTER` pendientes antes de cada despliegue (CP-F-01) |
| Datos clínicos reales mezclados con datos de prueba | Contaminación de la BD | Uso exclusivo de identificadores `TEST-*` / `SMOKE-TEST-*` y limpieza al cierre |
| Modificación de datos maestros sin trazabilidad | Imposible reconstruir el historial | Registro obligatorio en `auditoria_acciones` (D10) |
| Regresión al actualizar un paciente (expediente, reportes) | Inconsistencia entre módulos | Suite D y casos CP-A-34…CP-A-36 |
