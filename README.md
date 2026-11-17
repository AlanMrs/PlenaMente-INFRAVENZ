# PlenaMente - INFRAVENZ 🧠📚

**Sistema Institucional de Atención y Seguimiento Psicológico** Desarrollado para el Instituto Nacional Profesor Francisco Ventura Zelaya (INFRAVENZ).

---

## 📖 Descripción del Proyecto
**PlenaMente-INFRAVENZ** es una aplicación web diseñada para centralizar, estructurar y automatizar la gestión clínica y administrativa del departamento de psicología de INFRAVENZ. 

El sistema mitiga la carga operativa crítica que representa manejar físicamente los expedientes de más de 700 usuarios, eliminando el riesgo de pérdida de información sensible, optimizando el agendamiento de citas mediante alertas, y permitiendo a la administración generar reportes técnicos de forma inmediata para la atención oportuna de casos críticos.

## 🚀 Características Principales
* **Gestión de Expedientes Clínicos Digitales:** Creación y consulta ágil del historial de estudiantes y empleados.
* **Control de Agenda:** Calendario interactivo para evitar cruces de citas.
* **Sistema de Alertas Automatizado:** Envío de recordatorios vía correo electrónico a pacientes y notificaciones internas al psicólogo.
* **Roles de Acceso (RBAC):** * *Operativo (Psicólogo):* Gestión completa de pacientes y expedientes.
  * *Directivo (Administración):* Visualización de dashboards y reportes.
  * *Administrador (IT):* Mantenimiento de usuarios y respaldos.
* **Reportes Parametrizables:** Generación de métricas e historiales exportables.

## 🛠️ Stack Tecnológico y Arquitectura
El sistema está construido bajo el paradigma de **Programación Orientada a Objetos (POO)** utilizando el patrón de diseño **MVC (Modelo-Vista-Controlador)**.

* **Backend:** PHP >= 8.0 (Nativo)
* **Base de Datos:** MariaDB (SQL)
* **Frontend:** HTML5, CSS3, JavaScript (Diseño Propio UI/UX)
* **Despliegue Objetivo:** Cloud Hosting (Hostinger)

## 📂 Estructura del Proyecto (MVC)
```text
plenamente/
├── app/                    # Lógica central del patrón MVC
│   ├── Config/             # Configuración del sistema y conexión segura (PDO) a la base de datos
│   ├── Core/               # Núcleo del mini-framework (Enrutador principal y clase Controller base)
│   ├── Controllers/        # Controladores (Manejan el flujo de la app y la seguridad por roles - RBAC)
│   │   ├── AsistenciaController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ReportesController.php
│   │   └── ...
│   ├── Models/             # Modelos (Consultas preparadas de MariaDB para evitar inyección SQL)
│   │   ├── Asistencia.php
│   │   ├── Dashboard.php
│   │   ├── Reporte.php
│   │   ├── Usuario.php
│   │   └── ...
│   └── Views/              # Interfaces gráficas (HTML nativo integrado con PHP)
│       ├── asistencia/     # Módulo de bitácora de asistencias y verificación
│       ├── auth/           # Pantallas de autenticación y login
│       ├── dashboard/      # Panel de control principal con métricas (KPIs) dinámicas
│       ├── layouts/        # Componentes reutilizables (header.php, sidebar, footer.php)
│       ├── reportes/       # Generación de informes técnicos optimizados para impresión
│       └── ...
├── public/                 # Directorio público (Raíz segura expuesta al servidor web)
│   ├── css/                # Hojas de estilo puras utilizando variables CSS nativas
│   ├── img/                # Logotipos y recursos gráficos institucionales
│   ├── js/                 # Scripts interactivos del lado del cliente
│   └── index.php           # Front Controller (Punto único de entrada de toda la aplicación)
└── plenamente_bd.sql       # Script de creación y estructura de la base de datos (MariaDB)

## 🛠️ Standares de codificacion

* **PascalCase** para Clases y Archivos base (ej. AsistenciaController, Dashboard.php).
* **camelCase** para Métodos y Funciones (ej. obtenerAsistenciasPorFecha(), registrarAsistencia()).
* **snake_case** para Variables, Arrays y columnas de Base de Datos (ej. id_expediente, fecha_registro).

---

## 🧪 Pruebas

| Recurso | Ubicación | Descripción |
|---|---|---|
| **Plan de pruebas** | [`docs/PLAN_PRUEBAS.md`](docs/PLAN_PRUEBAS.md) | DoD, estrategia, casos de prueba (Suites A–H) y matriz de trazabilidad Historia → Caso |
| **Smoke test (modelos)** | [`tests/smoke_paciente.php`](tests/smoke_paciente.php) | Validación automática de modelos y reglas de negocio contra la BD local |
| **Smoke test (roles)** | [`tests/smoke_roles.php`](tests/smoke_roles.php) | Catálogo de roles, matriz de permisos y aplicación inmediata de cambios |
| **Prueba end-to-end HTTP** | [`tests/http_roles.php`](tests/http_roles.php) | Login real por HTTP, restricciones por rol y aplicación inmediata sin re-login |
| **Prueba end-to-end (PDF)** | [`tests/http_reportes_pdf.php`](tests/http_reportes_pdf.php) | Exportación de informes: documento imprimible, contenido clínico completo y permisos |
| **Prueba end-to-end (métricas)** | [`tests/http_metricas.php`](tests/http_metricas.php) | Panel de métricas: contraste de KPIs contra la BD, gráficos SVG, filtro de período y permisos |
| **Smoke test (respaldos)** | [`tests/smoke_respaldo.php`](tests/smoke_respaldo.php) | Genera un respaldo real, lo **restaura** en una BD temporal y verifica la gestión de archivos |
| **Prueba end-to-end (respaldos)** | [`tests/http_respaldo.php`](tests/http_respaldo.php) | Generación manual, confirmación, descarga, eliminación y permisos del Administrador |
| **Smoke test (restauración)** | [`tests/smoke_restauracion.php`](tests/smoke_restauracion.php) | Restaura **sobre la base viva** y valida conteos, integridad referencial y reversión |
| **Prueba end-to-end (restauración)** | [`tests/http_restauracion.php`](tests/http_restauracion.php) | Selección, confirmación explícita, restauración real y validación de integridad |
| **Prueba end-to-end (auditoría)** | [`tests/http_auditoria.php`](tests/http_auditoria.php) | Registro de acciones críticas, filtros por usuario/fecha/entidad y detalle |
| **Prueba end-to-end (sugerencias)** | [`tests/http_sugerencias.php`](tests/http_sugerencias.php) | Terminología "eliminar paciente", aviso de citas vencidas y login sin datos de usuarios |
| **Smoke test (correo/SMTP)** | [`tests/smoke_correo.php`](tests/smoke_correo.php) | Cliente SMTP nativo contra un servidor simulado: envío, credenciales, rechazo y destino inválido |
| **Prueba end-to-end (recordatorios)** | [`tests/http_recordatorios.php`](tests/http_recordatorios.php) | Estado real del envío (Enviado/Fallido), destinatario inválido y pantalla de diagnóstico |

```bash
# Validación de sintaxis de un archivo
C:\xampp\php\php.exe -l app\Models\Paciente.php

# Ejecución de los arneses (crean y destruyen datos de prueba)
C:\xampp\php\php.exe tests\smoke_paciente.php
C:\xampp\php\php.exe tests\smoke_roles.php
C:\xampp\php\php.exe tests\smoke_respaldo.php
C:\xampp\php\php.exe tests\smoke_restauracion.php
C:\xampp\php\php.exe tests\smoke_correo.php

# Las pruebas end-to-end requieren el servidor local levantado
C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
C:\xampp\php\php.exe tests\http_roles.php
C:\xampp\php\php.exe tests\http_reportes_pdf.php
C:\xampp\php\php.exe tests\http_metricas.php
C:\xampp\php\php.exe tests\http_respaldo.php
C:\xampp\php\php.exe tests\http_restauracion.php
C:\xampp\php\php.exe tests\http_auditoria.php
C:\xampp\php\php.exe tests\http_sugerencias.php
C:\xampp\php\php.exe tests\http_recordatorios.php
```

> Los arneses **nunca** deben ejecutarse contra la base de datos de producción.

---

## 💾 Respaldos y restauración del sistema

Copias de seguridad de la base de datos, disponibles solo para el **Administrador**
(ruta `/respaldo`, permiso `backups`).

| Pieza | Ubicación | Función |
|---|---|---|
| Librería | [`app/Libraries/Respaldo.php`](app/Libraries/Respaldo.php) | Generación, análisis, restauración y validación de integridad |
| Controlador | [`app/Controllers/RespaldoController.php`](app/Controllers/RespaldoController.php) | Generar, listar, descargar, eliminar y **restaurar** |
| Vista | [`app/Views/respaldo/index.php`](app/Views/respaldo/index.php) | Pantalla de respaldos con confirmación y tabla de archivos |
| Vista | [`app/Views/respaldo/restaurar.php`](app/Views/respaldo/restaurar.php) | Confirmación de restauración con el análisis previo del archivo |
| Almacenamiento | [`backups/`](backups/) | Carpeta **fuera del docroot**, con `.htaccess` que bloquea el acceso web |

**Generar un respaldo**

- Volcado SQL **en PHP puro** (no requiere `mysqldump` ni que `exec()` esté habilitado),
  comprimido con gzip y ordenado por dependencias para que sea restaurable.
- Se conservan los **10 respaldos más recientes** (los antiguos se purgan solos).
- Confirmación con nombre, tamaño y duración; registro en `auditoria_acciones` y en el log.

**Restaurar un respaldo**

1. Se **analiza** el archivo elegido (tablas y número de registros) **sin modificar nada**.
2. El administrador debe **escribir el nombre del archivo** y marcar la casilla de confirmación.
3. Se genera **automáticamente un respaldo de seguridad** del estado actual.
4. Se aplica el respaldo sobre la base viva.
5. Se **valida la integridad**: existencia de las tablas, coincidencia exacta de los
   conteos por tabla, ausencia de registros huérfanos y presencia de los catálogos mínimos.
6. Si alguna verificación falla, el sistema **se revierte automáticamente** con el respaldo
   de seguridad y lo informa.

> **Nota de despliegue (Hostinger):** el nombre del archivo se envía en el *query string*
> (`/respaldo/descargar?archivo=...`) y no en la ruta. Algunos servidores devuelven
> **403 Forbidden** a cualquier URL que incluya la extensión `.sql` en su camino, por lo que
> de esta forma la descarga y la restauración funcionan sin bloqueos del hosting.
> Los enlaces antiguos con el nombre en la ruta siguen siendo compatibles.

> **Recommendation operativa:** genere un respaldo antes de cualquier cambio importante y
> descargue el archivo a un almacenamiento externo (el sistema conserva solo los 10 últimos).


---

## 📋 Registro de Auditoría

Traza de las **acciones críticas** del sistema, disponible solo para el **Administrador**
(ruta `/auditoria`, permiso `auditoria`). El registro es de **solo lectura**.

| Pieza | Ubicación | Función |
|---|---|---|
| Registro | `app/Core/Controller.php` → `auditar()` | Método centralizado: todo controlador lo invoca tras una acción crítica |
| Modelo | [`app/Models/Auditoria.php`](app/Models/Auditoria.php) | Registro y consulta filtrada (usuario, acción, entidad, fechas, texto) |
| Controlador | [`app/Controllers/AuditoriaController.php`](app/Controllers/AuditoriaController.php) | Listado con filtros y paginación, y detalle de una acción |
| Vistas | `app/Views/auditoria/` | Listado con badges por acción y detalle de la acción |

**Acciones que se registran**

| Entidad | Acciones auditadas |
|---|---|
| `paciente` | CREAR · ACTUALIZAR · DESACTIVAR · REACTIVAR |
| `cita` | CREAR · ACTUALIZAR (reprogramar) · ELIMINAR (cancelar) |
| `expediente` | CREAR · ACTUALIZAR (antecedentes y estado del caso) |
| `sesion` | CREAR |
| `informe` | CREAR |
| `asistencia` | CREAR |
| `usuario` | CREAR · ACTUALIZAR · DESACTIVAR |
| `respaldo` | CREAR · ELIMINAR · RESTAURAR · REVERTIR |

**Consulta:** filtros combinables por **usuario**, **tipo de acción**, **entidad**,
**rango de fechas** y **texto libre**; paginación de 20 en 20 y resumen estadístico
(total, tipos de acción, usuarios con registro y eliminaciones del período).

> La auditoría nunca interrumpe la operación de negocio: si el registro falla, la acción
> se completa igual y queda constancia del error en `logs/app-*.log`.

---

## 📧 Envío de correo (recordatorios)

El envío usa **SMTP nativo** (cliente propio en PHP, sin librerías) porque `mail()`
devuelve `true` en cuanto el mensaje pasa al proceso local de envío: **no confirma la
entrega**. Con SMTP se conoce la respuesta real del servidor (250 aceptado, 535
credenciales, 550 bloqueado) y el recordatorio se registra como `Enviado` **solo si el
servidor aceptó el mensaje**.

| Pieza | Ubicación | Función |
|---|---|---|
| Cliente SMTP | [`app/Libraries/Smtp.php`](app/Libraries/Smtp.php) | EHLO, STARTTLS, AUTH LOGIN, MAIL FROM, RCPT TO, DATA (con diagnóstico del diálogo) |
| Envío | [`app/Libraries/Correo.php`](app/Libraries/Correo.php) | Configuración, SMTP con alternativa `mail()`, y diagnóstico del envío |
| Configuración | [`app/Config/Correo.php`](app/Config/Correo.php) | Remitente y servidor SMTP (versionado, sin secretos) |
| Configuración local | `app/Config/Correo.local.php` | Clave del buzón (**no se versiona**; plantilla en `Correo.local.ejemplo.php`) |
| Diagnóstico | `/recordatorio/diagnostico` | Muestra la configuración y permite enviar un correo de prueba con el diálogo SMTP visible |

### ⚙️ Configuración en el hosting (Hostinger)

1. **Crear el buzón de salida** en el panel: *Correo electrónico → Buzones*.
   En esta cuenta el buzón queda como **`psicologia@dsoftin.net`** (el dominio de la
   cuenta; la aplicación puede vivir en el subdominio `plenamente.dsoftin.net` sin que
   eso afecte al envío). **Debe existir como buzón real**: si el remitente no existe,
   el hosting descarta el mensaje aunque `mail()` devuelva `true`.
2. Copiar `app/Config/Correo.local.ejemplo.php` como `app/Config/Correo.local.php` y
   colocar la clave del buzón (o definir la variable de entorno `PLENAMENTE_SMTP_CLAVE`).
3. Subir el archivo `app/Config/Correo.local.php` a `public_html/app/Config/`.
4. Verificar con **📧 Diagnóstico** (menú *Recordatorios*): la prueba de correo debe
   responder *250 accepted* y el correo debe llegar.

> Si en el panel el buzón aparece con otro dominio (por ejemplo `psicologia@otrodominio.com`),
> ajusta `remitente` y `smtp.usuario` en `app/Config/Correo.local.php` con esa dirección exacta.

### ⚠️ Causas frecuentes de "registra Enviado pero no llega"

| Causa | Cómo se detecta | Solución |
|---|---|---|
| El buzón remitente no existe en el hosting | Diagnóstico: el servidor responde `550` o `553` | Crear el buzón y usarlo como remitente |
| Credenciales incorrectas | Diagnóstico: `535 Authentication credentials invalid` | Revisar usuario y clave en `Correo.local.php` |
| Cifrado incorrecto (587 vs 465) | Diagnóstico: error de conexión o de TLS | Ajustar `cifrado` (`tls` / `ssl`) y `puerto` |
| El mensaje llegó a spam | El diagnóstico muestra `250` y aun así no se ve | Revisar spam y(add) SPF/DKIM del dominio |
| Correo del paciente mal escrito | El sistema lo rechaza antes de enviar | Corregir el correo en la ficha del paciente |

---

## 📈 Dashboard de Métricas

Panel de análisis general para la **Dirección** (rol 2) y el **Administrador** (rol 3).
Todas las cifras son agregadas: no expone información clínica individual.

| Pieza | Ubicación | Función |
|---|---|---|
| Construcción | [`app/Core/Metricas.php`](app/Core/Metricas.php) | Períodos disponibles, resolución de rangos y armado de los datos |
| Modelo | [`app/Models/Dashboard.php`](app/Models/Dashboard.php) | `obtenerMetricas($desde, $hasta)`: KPIs, serie mensual de 12 meses y distribuciones |
| Librería | [`app/Libraries/Graficos.php`](app/Libraries/Graficos.php) | Gráficos SVG nativos (barras verticales agrupadas y horizontales), sin dependencias |
| Vista | [`app/Views/metricas/index.php`](app/Views/metricas/index.php) | 8 tarjetas KPI, 5 gráficos y tabla de resumen de la agenda |
| Estilos | [`public/css/metricas.css`](public/css/metricas.css) | Tarjetas, chips de período y controles de actualización |

**El dashboard se adapta al rol:** el Psicólogo (rol 1) mantiene su vista operativa
(citas de hoy y agenda del día); el Director y el Administrador ven las métricas.
La URL canónica es `/dashboard`; `/metricas` queda como alias que redirige.

**Indicadores:** pacientes activos · expedientes en atención · citas del período · cumplimiento
de citas (%) · tasa de inasistencia (%) · sesiones clínicas · informes emitidos · casos de alta urgencia.

**Actualización dinámica:** auto-refresco cada 60 s con interruptor (pausado si el usuario
interactúa), botón *Actualizar*, marca de tiempo y filtro de período
(*Este mes / 3 meses / 6 meses / 12 meses / Todo*).

---

## 📄 Exportación de informes a PDF

Los informes técnicos se exportan **sin librerías externas**: el documento se genera
como HTML autónomo y el navegador lo convierte en PDF con *Imprimir → Guardar como PDF*.

| Pieza | Ubicación | Función |
|---|---|---|
| Documento | [`app/Views/reportes/_documento.php`](app/Views/reportes/_documento.php) | Partial único con las 7 secciones clínicas (lo usan la vista en pantalla y la imprimible) |
| Hoja de estilos | [`public/css/reporte-impresion.css`](public/css/reporte-impresion.css) | Estilos de pantalla e impresión (`@page`, cortes de página, ocultamiento de la barra de herramientas) |
| Vista imprimible | [`app/Views/reportes/impresion.php`](app/Views/reportes/impresion.php) | Documento autónomo, sin menú lateral, con impresión automática |

| Ruta | Permiso | Resultado |
|---|---|---|
| `/reportes/imprimir/{id_expediente}` | Psicólogo | Documento imprimible del informe técnico |
| `/reportes/imprimir_reporte/{id_reporte}` | Psicólogo y Director | Documento imprimible del reporte ya guardado |

> **Nota sobre el enrutador:** las rutas se resuelven por el nombre exacto del método.
> Un método con guion bajo (`imprimir_reporte`) necesita un alias explícito; por eso
> conviven `imprimirReporte()` (convención camelCase) y `imprimir_reporte()` (ruta).


---

## 🔐 Roles y Permisos

La matriz de acceso es la fuente única de verdad y vive en [`app/Core/Permisos.php`](app/Core/Permisos.php):

| Módulo | Psicólogo | Director | Administrador |
|---|:--:|:--:|:--:|
| Dashboard | ✔ | ✔ | ✔ |
| Pacientes · Citas · Recordatorios · Expedientes | ✔ | ✘ | ✘ |
| Asistencias (consulta) | ✔ | ✔ | ✘ |
| Asistencias (registro) · Informes (generar) | ✔ | ✘ | ✘ |
| Informes (consulta) | ✔ | ✔ | ✘ |
| Usuarios · Roles y Permisos | ✘ | ✘ | ✔ |

* Los controladores aplican los permisos con `Controller::requiere('modulo')`.
* `Core\Sesion::sincronizar()` relee el rol y el estado del usuario en cada petición, por lo que un cambio de rol o una desactivación **se aplican de inmediato**, sin reiniciar sesión.
* La pantalla `/rol` muestra el catálogo de roles y la matriz de permisos vigente.

