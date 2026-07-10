# Sprint 3 — Portal del Padre y Panel del Docente

| Campo | Detalle |
|---|---|
| Sprint | 3 de 5 |
| Duración | 2 semanas |
| Objetivo | Que el padre pueda monitorear a su hijo y el docente pueda registrar notas y asistencia |
| Puntos comprometidos | 47 |
| Estado | ✅ Completado |

---

## Sprint Backlog

### Portal del Padre

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU13 | Ver lista de hijos vinculados con resumen académico | 5 | Desarrollador | ✅ Done |
| HU14 | Ver notas del hijo por curso y periodo | 5 | Desarrollador | ✅ Done |
| HU15 | Ver historial de asistencia con filtro por mes | 5 | Desarrollador | ✅ Done |
| HU16 | Ver nivel de riesgo del hijo (Bajo / Medio / Alto) | 5 | Desarrollador | ✅ Done |
| HU17 | Recibir notificaciones de alertas académicas | 3 | Desarrollador | ✅ Done |
| HU18 | Ver gráficos de evolución de notas y asistencia mensual | 3 | Desarrollador | ✅ Done |

### Panel del Docente

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU19 | Panel del Docente con KPIs (estudiantes, alertas, promedio) | 5 | Desarrollador | ✅ Done |
| HU20 | Ver estudiantes EPT con notas y porcentaje de asistencia | 5 | Desarrollador | ✅ Done |
| HU21 | Subir archivo Excel de asistencia para el aula | 8 | Desarrollador | ✅ Done |
| HU22 | Ver historial de documentos de asistencia subidos | 3 | Desarrollador | ✅ Done |
| HU23 | Ver estudiantes con riesgo Alto o Medio en el aula | 5 | Desarrollador | ✅ Done |

---

## Archivos involucrados

| Archivo | Descripción |
|---|---|
| [padre.php](../padre.php) | Punto de entrada del portal del padre |
| [docente.php](../docente.php) | Punto de entrada del panel del docente |
| [portal.php](../portal.php) | Portal general (selección de hijo) |
| [controllers/DashboardController.php](../controllers/DashboardController.php) | Controlador central: renderiza vistas según rol y acción |
| [models/AuthModel.php](../models/AuthModel.php) | Métodos: `panelPadre`, `hijosDelPadre`, `hijosDetallePadre`, `marcarNotificacionesPadreLeidas` |
| [models/EstudianteModel.php](../models/EstudianteModel.php) | Métodos: `docentePanelCompleto`, `estudiantesEptPorGrado`, `resumenDocenteEpt` |
| [views/padre_v.php](../views/padre_v.php) | Vista del portal del padre (notas, asistencia, riesgo, notificaciones) |
| [views/docente_v.php](../views/docente_v.php) | Vista del panel del docente (KPIs, tabla estudiantes, carga de asistencia) |
| [views/portal_v.php](../views/portal_v.php) | Vista de selección de hijo del Padre |
| [views/asistencia_documentos_v.php](../views/asistencia_documentos_v.php) | Vista de estado de documentos de asistencia |

---

## Criterios de aceptación cumplidos

- [x] El Padre ve únicamente los hijos vinculados a su cuenta.
- [x] Las notas se muestran por curso y periodo en tabla ordenada.
- [x] El historial de asistencia se puede filtrar por mes.
- [x] El puntaje de riesgo se calcula dinámicamente usando configuración de la BD.
- [x] Las notificaciones se marcan como "Leído" al acceder al detalle del hijo.
- [x] Los gráficos (evolución de notas y asistencia por mes) usan Chart.js.
- [x] El Docente ve KPIs y tabla de sus estudiantes EPT.
- [x] El Docente puede subir archivos Excel (.xlsx) de asistencia.
- [x] El historial de cargas muestra fecha, aula y estado (Cargado / Pendiente / Parcial).

---

## Notas técnicas

- El cálculo de riesgo en `panelPadre` usa `configuracion_sistema` para obtener pesos dinámicos.
- Los archivos Excel se guardan en `uploads/asistencia/` con nombre único (`timestamp_hash.xlsx`).
- El panel del Docente solo muestra cursos EPT (filtro por `nombre_curso LIKE '%EPT%'`).
- Los datos de demostración (estudiantes 900, 901) están hardcodeados en `panelPadre` para presentaciones.
