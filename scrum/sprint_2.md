# Sprint 2 — Gestión de usuarios y roles

| Campo | Detalle |
|---|---|
| Sprint | 2 de 5 |
| Duración | 2 semanas |
| Objetivo | Permitir al Admin gestionar cuentas, aprobar usuarios y vincular padres con estudiantes |
| Puntos comprometidos | 29 |
| Estado | ✅ Completado |

---

## Sprint Backlog

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU06 | Panel de Admin con KPIs (usuarios activos, alertas, cargas del día) | 5 | Desarrollador | ✅ Done |
| HU07 | Aprobar o rechazar cuentas de Docentes y Padres | 5 | Desarrollador | ✅ Done |
| HU08 | Editar datos de usuarios (nombre, correo, rol, estado) | 3 | Desarrollador | ✅ Done |
| HU09 | Eliminar usuario con limpieza completa de datos asociados | 3 | Desarrollador | ✅ Done |
| HU10 | Vincular cuenta del Padre al DNI del estudiante al registrarse | 5 | Desarrollador | ✅ Done |
| HU11 | Aprobar o rechazar solicitudes de vinculación padre-estudiante | 5 | Desarrollador | ✅ Done |
| HU12 | Gestionar periodos académicos (crear, activar, desactivar) | 3 | Desarrollador | ✅ Done |

---

## Archivos involucrados

| Archivo | Descripción |
|---|---|
| [admin.php](../admin.php) | Punto de entrada del panel de administrador |
| [usuarios.php](../usuarios.php) | Gestión de lista de usuarios registrados |
| [aprobaciones.php](../aprobaciones.php) | Aprobación/rechazo de cuentas y solicitudes |
| [configuracion.php](../configuracion.php) | Configuración del sistema y periodos académicos |
| [controllers/AdminController.php](../controllers/AdminController.php) | Lógica del panel Admin (CRUD usuarios, vinculaciones, periodos) |
| [models/AuthModel.php](../models/AuthModel.php) | Métodos: `usuariosPendientes`, `aprobarUsuario`, `rechazarUsuario`, `eliminarUsuario`, `aprobarSolicitudVinculacion`, `usuariosRegistrados`, `actualizarUsuarioAdmin`, `guardarPeriodo` |
| [views/admin_v.php](../views/admin_v.php) | Vista del panel de administrador |
| [views/usuarios_v.php](../views/usuarios_v.php) | Vista de lista de usuarios |
| [views/aprobaciones_v.php](../views/aprobaciones_v.php) | Vista de aprobaciones pendientes |
| [views/configuracion_v.php](../views/configuracion_v.php) | Vista de configuración y periodos |

---

## Criterios de aceptación cumplidos

- [x] El Admin puede ver KPIs: usuarios activos, alertas generadas, cargas de hoy.
- [x] Las cuentas nuevas quedan en estado `pendiente` hasta ser aprobadas por el Admin.
- [x] El Admin puede aprobar o rechazar cuentas desde la vista de aprobaciones.
- [x] Al aprobar un Padre, el sistema vincula automáticamente al estudiante por DNI.
- [x] El Admin puede editar nombre, correo, rol y estado de cualquier usuario.
- [x] Al eliminar un usuario se limpian: calificaciones (si Docente), vínculos (si Padre), notificaciones y logs de auditoría.
- [x] El Admin puede gestionar solicitudes de vinculación independientes.
- [x] El Admin puede crear y activar periodos académicos.

---

## Notas técnicas

- La eliminación de usuario usa transacción PDO para garantizar atomicidad.
- La vinculación padre-estudiante se puede hacer automáticamente en la aprobación o manualmente vía solicitud.
- Solo un periodo puede estar activo a la vez: al activar uno se desactivan los demás.
- Los roles disponibles para registro público son `Docente` y `Padre`; el rol `Admin` solo lo asigna el Admin.
