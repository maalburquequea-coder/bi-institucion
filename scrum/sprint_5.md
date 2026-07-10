# Sprint 5 — Inteligencia Artificial, Auditoría y Configuración

| Campo | Detalle |
|---|---|
| Sprint | 5 de 5 |
| Duración | 2 semanas |
| Objetivo | Integrar generación automática de planes de mejora con IA y completar auditoría y configuración del sistema |
| Puntos comprometidos | 39 |
| Estado | ✅ Completado |

---

## Sprint Backlog

### Inteligencia Artificial

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU34 | Generar plan de mejora personalizado con IA para estudiantes en riesgo | 8 | Desarrollador | ✅ Done |
| HU35 | Guardar planes en caché para evitar llamadas repetidas a la API | 5 | Desarrollador | ✅ Done |
| HU36 | El Padre puede ver el plan de mejora de su hijo generado por IA | 5 | Desarrollador | ✅ Done |
| HU37 | El Docente puede generar y enviar un plan de mejora al padre | 5 | Desarrollador | ✅ Done |
| HU38 | El Padre puede descargar el plan de mejora en PDF | 3 | Desarrollador | ✅ Done |

### Auditoría y configuración

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU39 | Ver log de auditoría de todas las acciones del sistema | 5 | Desarrollador | ✅ Done |
| HU40 | Ver historial de accesos (exitosos y fallidos) al sistema | 3 | Desarrollador | ✅ Done |
| HU41 | Configurar parámetros del sistema (pesos de riesgo, umbrales) | 5 | Desarrollador | ✅ Done |
| HU42 | Ver estado de registro de asistencia de todos los docentes por fecha | 5 | Desarrollador | ✅ Done |

---

## Archivos involucrados

| Archivo | Descripción |
|---|---|
| [auditoria.php](../auditoria.php) | Punto de entrada del módulo de auditoría |
| [configuracion.php](../configuracion.php) | Punto de entrada de configuración del sistema |
| [descargar_plan_ia.php](../descargar_plan_ia.php) | Descarga del plan de mejora IA en PDF |
| [services/AIService.php](../services/AIService.php) | Llamada a API de IA (Claude) para generar planes |
| [models/AuthModel.php](../models/AuthModel.php) | Métodos: `buscarPlanCache`, `guardarPlanCache`, `getOrCreatePlanMejoraDocente`, `crearNotificacionPlanMejora`, `auditoriaSistema`, `accesosSistema`, `configuracionSistema`, `actualizarConfiguracion` |
| [controllers/AdminController.php](../controllers/AdminController.php) | Lógica de auditoría, configuración y asistencia |
| [views/auditoria_v.php](../views/auditoria_v.php) | Vista del log de auditoría y accesos |
| [views/configuracion_v.php](../views/configuracion_v.php) | Vista de configuración de parámetros y periodos |
| [views/padre_v.php](../views/padre_v.php) | Muestra el plan de mejora IA al Padre |
| [views/docente_v.php](../views/docente_v.php) | Botón para generar/enviar plan de mejora al Padre |

---

## Flujo del plan de mejora IA

```
1. Sistema detecta estudiante en Riesgo Medio o Alto
   ↓
2. Busca en caché: tabla planes_mejora_ia
   (WHERE id_estudiante + promedio + asistencia + area_critica)
   ↓
3a. Si existe caché → retorna acciones guardadas
3b. Si no existe   → llama a AIService::generarPlanMejora()
                       → guarda resultado en caché
   ↓
4. Plan se muestra en vista del Padre y/o del Docente
   ↓
5. Docente puede enviar plan al Padre vía:
   - Notificación interna
   - Correo (EmailService)
   - WhatsApp (URL generada)
   ↓
6. Padre puede descargar plan como PDF (descargar_plan_ia.php)
```

---

## Criterios de aceptación cumplidos

- [x] El plan de mejora incluye: área crítica, acciones específicas, fecha y estado.
- [x] El caché evita llamadas duplicadas a la API si los datos del estudiante no cambiaron.
- [x] El Padre ve el plan en su portal con las acciones recomendadas.
- [x] El Docente puede generar el plan y enviarlo al padre desde su panel.
- [x] La descarga del plan funciona como respuesta HTTP con cabecera `Content-Type: application/pdf`.
- [x] El log de auditoría registra módulo, acción, detalle e IP en cada operación sensible.
- [x] El historial de accesos distingue intentos exitosos y fallidos.
- [x] Los parámetros de riesgo (`peso_promedio_bajo`, `peso_falta`, `peso_tardanza`, `riesgo_alto`, `riesgo_medio`) son editables desde la interfaz.
- [x] El estado de registro de asistencia filtra por nivel, grado, sección, fecha y estado.

---

## Notas técnicas

- `AIService::generarPlanMejora()` llama a la API de Claude (Anthropic) con los datos del estudiante.
- La tabla `planes_mejora_ia` actúa como caché persistente entre sesiones.
- `registrarAuditoria()` y `registrarAcceso()` usan bloques `try/catch` para no interrumpir el flujo si el log falla.
- Los pesos de riesgo se leen de `configuracion_sistema` en cada cálculo para reflejar cambios en tiempo real.
