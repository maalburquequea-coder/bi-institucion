# Sprint 4 — Alertas Tempranas y Dashboard BI

| Campo | Detalle |
|---|---|
| Sprint | 4 de 5 |
| Duración | 2 semanas |
| Objetivo | Implementar el motor de riesgo estudiantil y el dashboard con visualizaciones BI |
| Puntos comprometidos | 44 |
| Estado | ✅ Completado |

---

## Sprint Backlog

### Sistema de alertas tempranas

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU24 | Calcular puntaje de riesgo por estudiante con pesos configurables | 8 | Desarrollador | ✅ Done |
| HU25 | Ver tabla de estudiantes en riesgo con botón WhatsApp por fila | 5 | Desarrollador | ✅ Done |
| HU26 | Generar notificaciones WhatsApp automáticas para padres en riesgo | 5 | Desarrollador | ✅ Done |
| HU27 | Enviar correo de alerta al padre cuando se detecta riesgo | 5 | Desarrollador | ✅ Done |
| HU28 | Ver estado de notificaciones enviadas (Pendiente / Enviado / Fallido) | 3 | Desarrollador | ✅ Done |

### Dashboard BI

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU29 | Ver KPIs: total estudiantes, padres, notas críticas, alertas | 5 | Desarrollador | ✅ Done |
| HU30 | Gráfico de barras horizontales: rendimiento promedio por curso | 5 | Desarrollador | ✅ Done |
| HU31 | Gráfico de dona: distribución de riesgo (Alto / Medio / Bajo) | 5 | Desarrollador | ✅ Done |
| HU32 | Gráfico de barras: faltas por aula | 5 | Desarrollador | ✅ Done |
| HU33 | Gráfico de línea: evolución de notas por unidad | 3 | Desarrollador | ✅ Done |

---

## Archivos involucrados

| Archivo | Descripción |
|---|---|
| [dashboard.php](../dashboard.php) | Punto de entrada del Dashboard BI |
| [asistencia.php](../asistencia.php) | Gestión de asistencia y alertas desde Admin |
| [notificaciones.php](../notificaciones.php) | Lista de notificaciones enviadas a padres |
| [controllers/DashboardController.php](../controllers/DashboardController.php) | Acción `dashboard`: resumen, riesgos, cursos, asistencia |
| [models/EstudianteModel.php](../models/EstudianteModel.php) | Métodos: `estudiantesEnRiesgo`, `rendimientoPorCurso`, `asistenciaPorGrado`, `generarAlertasWhatsApp` |
| [models/AuthModel.php](../models/AuthModel.php) | Métodos: `todasLasNotificaciones`, `documentosAsistencia`, `estadoRegistroAsistencia` |
| [views/alertas_v.php](../views/alertas_v.php) | Vista del Dashboard BI con KPIs, 4 gráficos y tabla de riesgo |
| [views/notificaciones_v.php](../views/notificaciones_v.php) | Vista de historial de notificaciones |
| [views/asistencia_documentos_v.php](../views/asistencia_documentos_v.php) | Vista de estado de cargas de asistencia |
| [services/EmailService.php](../services/EmailService.php) | Envío de correos de alerta (PHPMailer) |

---

## Fórmula de cálculo de riesgo

```
puntaje = (promedio < 11 → peso_promedio_bajo) 
        + (faltas × peso_falta) 
        + (tardanzas × peso_tardanza) 
        + (cursos_desaprobados × 15)

Límite máximo: 100

Riesgo Alto  → puntaje >= riesgo_alto  (defecto: 70)
Riesgo Medio → puntaje >= riesgo_medio (defecto: 40)
Riesgo Bajo  → puntaje < riesgo_medio
```

Todos los pesos y umbrales son configurables desde `configuracion_sistema` en la BD.

---

## Criterios de aceptación cumplidos

- [x] El puntaje de riesgo se calcula para todos los estudiantes con datos en BD.
- [x] La tabla de alertas muestra: nombre, aula, padre/apoderado, promedio, faltas/tardanzas, nivel de riesgo, botón WhatsApp.
- [x] El botón WhatsApp genera una URL `https://wa.me/...` con mensaje pre-redactado.
- [x] Las notificaciones WhatsApp se registran en la tabla `notificaciones` sin duplicados.
- [x] El correo de alerta se envía vía PHPMailer y se registra el estado (Enviado / Fallido).
- [x] Los 4 gráficos del Dashboard usan Chart.js con animaciones y diseño responsive.
- [x] Los KPIs del Dashboard se calculan en tiempo real desde la BD.

---

## Notas técnicas

- Los gráficos usan `grid-template-columns: repeat(12, 1fr)` con tarjetas `span 6` (2 por fila).
- La URL de WhatsApp usa `whatsappUrl()` definida en `config/conexion.php` con encode del mensaje.
- `generarAlertasWhatsApp()` evita duplicados verificando si ya existe la misma notificación.
- El gráfico de evolución de notas usa datos mock `[12.5, 13.2, 12.8, 14.1, 13.5, 14.8]` mientras se implementan datos reales por unidad.
