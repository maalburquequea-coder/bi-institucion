# Product Backlog — BI Educativo I.E. N.° 14008 Piura 2026

## Información del proyecto

| Campo | Valor |
|---|---|
| Proyecto | Sistema de Business Intelligence Educativo |
| Institución | I.E. N.° 14008 — Piura |
| Objetivo | Prevención de deserción escolar mediante análisis predictivo |
| Metodología | Scrum |
| Velocidad estimada | 30 puntos por sprint |
| Duración del sprint | 2 semanas |

---

## Roles del sistema

| Rol | Descripción |
|---|---|
| Admin | Gestiona usuarios, aprueba cuentas, configura el sistema |
| Docente | Registra notas y asistencia, genera alertas por alumno |
| Padre | Visualiza el rendimiento y asistencia de su hijo/a |

---

## Épicas

| ID | Épica | Sprints |
|---|---|---|
| E1 | Autenticación y seguridad | Sprint 1 |
| E2 | Gestión de usuarios y roles | Sprint 2 |
| E3 | Portal del padre | Sprint 3 |
| E4 | Panel del docente | Sprint 3 |
| E5 | Sistema de alertas tempranas | Sprint 4 |
| E6 | Dashboard BI | Sprint 4 |
| E7 | Inteligencia Artificial (plan de mejora) | Sprint 5 |
| E8 | Auditoría y configuración | Sprint 5 |

---

## Historias de usuario

### Épica 1 — Autenticación y seguridad

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU01 | Como usuario registrado, quiero iniciar sesión con correo y contraseña para acceder al sistema según mi rol. | 5 | 1 | ✅ Completado |
| HU02 | Como nuevo usuario (Docente o Padre), quiero registrarme en el sistema para solicitar acceso. | 5 | 1 | ✅ Completado |
| HU03 | Como usuario registrado, quiero verificar mi correo electrónico mediante un enlace de confirmación. | 3 | 1 | ✅ Completado |
| HU04 | Como sistema, debo redirigir al usuario según su rol (Admin → admin.php, Docente → docente.php, Padre → padre.php). | 3 | 1 | ✅ Completado |
| HU05 | Como usuario, quiero cerrar sesión de forma segura. | 2 | 1 | ✅ Completado |

### Épica 2 — Gestión de usuarios y roles

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU06 | Como Admin, quiero ver el panel de administración con KPIs (usuarios activos, alertas, cargas del día). | 5 | 2 | ✅ Completado |
| HU07 | Como Admin, quiero aprobar o rechazar cuentas nuevas de Docentes y Padres. | 5 | 2 | ✅ Completado |
| HU08 | Como Admin, quiero editar datos de usuarios registrados (nombre, correo, rol, estado). | 3 | 2 | ✅ Completado |
| HU09 | Como Admin, quiero eliminar usuarios con limpieza completa de sus datos asociados. | 3 | 2 | ✅ Completado |
| HU10 | Como Padre, quiero vincular mi cuenta al DNI de mi hijo/a al registrarme. | 5 | 2 | ✅ Completado |
| HU11 | Como Admin, quiero aprobar o rechazar solicitudes de vinculación padre-estudiante. | 5 | 2 | ✅ Completado |
| HU12 | Como Admin, quiero gestionar periodos académicos (crear, activar, desactivar). | 3 | 2 | ✅ Completado |

### Épica 3 — Portal del padre

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU13 | Como Padre, quiero ver la lista de mis hijos vinculados con su resumen académico. | 5 | 3 | ✅ Completado |
| HU14 | Como Padre, quiero ver las notas de mi hijo por curso y periodo. | 5 | 3 | ✅ Completado |
| HU15 | Como Padre, quiero ver el historial de asistencia de mi hijo con filtro por mes. | 5 | 3 | ✅ Completado |
| HU16 | Como Padre, quiero ver el nivel de riesgo (Bajo / Medio / Alto) de mi hijo calculado automáticamente. | 5 | 3 | ✅ Completado |
| HU17 | Como Padre, quiero recibir notificaciones de alertas académicas de mi hijo. | 3 | 3 | ✅ Completado |
| HU18 | Como Padre, quiero ver gráficos de evolución de notas y asistencia por mes. | 3 | 3 | ✅ Completado |

### Épica 4 — Panel del docente

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU19 | Como Docente, quiero ver mi panel con KPIs (total estudiantes, alertas activas, promedio general). | 5 | 3 | ✅ Completado |
| HU20 | Como Docente, quiero ver el listado de mis estudiantes EPT con notas y asistencia. | 5 | 3 | ✅ Completado |
| HU21 | Como Docente, quiero subir un archivo Excel de asistencia para mi aula. | 8 | 3 | ✅ Completado |
| HU22 | Como Docente, quiero ver el historial de documentos de asistencia que subí. | 3 | 3 | ✅ Completado |
| HU23 | Como Docente, quiero ver qué estudiantes tienen riesgo Alto o Medio en mi aula. | 5 | 3 | ✅ Completado |

### Épica 5 — Sistema de alertas tempranas

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU24 | Como sistema, debo calcular el puntaje de riesgo de cada estudiante usando pesos configurables (notas, faltas, tardanzas). | 8 | 4 | ✅ Completado |
| HU25 | Como Admin, quiero ver la tabla de todos los estudiantes en riesgo con botón de acción por WhatsApp. | 5 | 4 | ✅ Completado |
| HU26 | Como sistema, debo generar notificaciones WhatsApp automáticas para padres de estudiantes en riesgo. | 5 | 4 | ✅ Completado |
| HU27 | Como sistema, debo enviar correos de alerta al padre cuando se detecta riesgo. | 5 | 4 | ✅ Completado |
| HU28 | Como Admin, quiero ver el estado de las notificaciones enviadas (Pendiente / Enviado / Fallido). | 3 | 4 | ✅ Completado |

### Épica 6 — Dashboard BI

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU29 | Como Admin, quiero ver KPIs generales: total estudiantes, padres, notas críticas, alertas generadas. | 5 | 4 | ✅ Completado |
| HU30 | Como Admin, quiero ver un gráfico de barras horizontales del rendimiento promedio por curso. | 5 | 4 | ✅ Completado |
| HU31 | Como Admin, quiero ver un gráfico de dona con la distribución de riesgo (Alto / Medio / Bajo). | 5 | 4 | ✅ Completado |
| HU32 | Como Admin, quiero ver un gráfico de barras de faltas por aula. | 5 | 4 | ✅ Completado |
| HU33 | Como Admin, quiero ver un gráfico de línea de evolución de notas por unidad. | 3 | 4 | ✅ Completado |

### Épica 7 — Inteligencia Artificial

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU34 | Como sistema, debo generar un plan de mejora personalizado con IA para estudiantes en riesgo. | 8 | 5 | ✅ Completado |
| HU35 | Como sistema, debo guardar en caché los planes generados para evitar llamadas repetidas a la IA. | 5 | 5 | ✅ Completado |
| HU36 | Como Padre, quiero ver el plan de mejora de mi hijo generado por IA. | 5 | 5 | ✅ Completado |
| HU37 | Como Docente, quiero generar y enviar un plan de mejora al padre de un estudiante en riesgo. | 5 | 5 | ✅ Completado |
| HU38 | Como Padre, quiero descargar el plan de mejora en formato PDF. | 3 | 5 | ✅ Completado |

### Épica 8 — Auditoría y configuración

| ID | Historia | Puntos | Sprint | Estado |
|---|---|---|---|---|
| HU39 | Como Admin, quiero ver el log de auditoría de todas las acciones del sistema. | 5 | 5 | ✅ Completado |
| HU40 | Como Admin, quiero ver el historial de accesos (exitosos y fallidos) al sistema. | 3 | 5 | ✅ Completado |
| HU41 | Como Admin, quiero configurar los parámetros del sistema (pesos de riesgo, umbrales). | 5 | 5 | ✅ Completado |
| HU42 | Como Admin, quiero ver el estado de registro de asistencia de todos los docentes por fecha. | 5 | 5 | ✅ Completado |

---

## Resumen de puntos

| Sprint | Puntos totales |
|---|---|
| Sprint 1 | 18 |
| Sprint 2 | 29 |
| Sprint 3 | 47 |
| Sprint 4 | 44 |
| Sprint 5 | 39 |
| **Total** | **177** |
