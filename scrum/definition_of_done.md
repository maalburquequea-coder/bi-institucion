# Definition of Done — BI Educativo

Una historia de usuario se considera **Terminada (Done)** cuando cumple todos estos criterios:

## Código

- [ ] El código está implementado y funciona en el entorno local (XAMPP).
- [ ] No hay errores de PHP (`error_reporting(E_ALL)` sin warnings ni notices críticos).
- [ ] Los queries SQL usan `prepare/execute` (no interpolación directa de variables).
- [ ] Las entradas del usuario pasan por la función `e()` o equivalente antes de mostrarse en HTML.
- [ ] No se exponen datos sensibles (contraseñas, tokens) en vistas ni logs.

## Funcionalidad

- [ ] La historia cumple todos sus criterios de aceptación definidos en el Sprint Backlog.
- [ ] El flujo principal funciona correctamente para el rol indicado (Admin / Docente / Padre).
- [ ] Los casos borde están manejados (campos vacíos, datos inexistentes, permisos incorrectos).

## Integración

- [ ] La vista carga sin errores de JavaScript en consola del navegador.
- [ ] Los gráficos Chart.js renderizan correctamente con datos reales y con datos vacíos.
- [ ] Los formularios POST validan el método HTTP y protegen contra CSRF donde aplica.
- [ ] El acceso a cada página verifica sesión activa y rol autorizado.

## Base de datos

- [ ] Los cambios en BD están documentados como migración SQL en la carpeta `database/`.
- [ ] Las transacciones usan `beginTransaction / commit / rollBack` para operaciones multi-tabla.

## Auditoría

- [ ] Las acciones sensibles (login, creación, eliminación, aprobación) registran entrada en `auditoria`.
- [ ] Los intentos de acceso (exitosos y fallidos) se registran en `accesos_sistema`.

---

## Roles del equipo Scrum

| Rol | Responsable |
|---|---|
| Product Owner | Institución Educativa / Director |
| Scrum Master | Líder del proyecto |
| Development Team | Equipo de desarrollo |

---

## Velocidad del equipo

| Sprint | Puntos planificados | Puntos entregados |
|---|---|---|
| Sprint 1 | 18 | 18 |
| Sprint 2 | 29 | 29 |
| Sprint 3 | 47 | 47 |
| Sprint 4 | 44 | 44 |
| Sprint 5 | 39 | 39 |
| **Total** | **177** | **177** |

---

## Estructura final del proyecto

```
bi_institucion/
├── scrum/                        ← Documentación Scrum
│   ├── product_backlog.md        ← Épicas e historias de usuario
│   ├── sprint_1.md               ← Autenticación
│   ├── sprint_2.md               ← Usuarios y roles
│   ├── sprint_3.md               ← Portal padre y docente
│   ├── sprint_4.md               ← Alertas y Dashboard BI
│   ├── sprint_5.md               ← IA, auditoría, configuración
│   └── definition_of_done.md    ← Este archivo
│
├── config/                       ← Conexión BD y funciones globales
├── controllers/                  ← Lógica de negocio por módulo
│   ├── AdminController.php
│   ├── AuthController.php
│   ├── DashboardController.php
│   └── EstudianteController.php
├── models/                       ← Acceso a base de datos
│   ├── AuthModel.php
│   └── EstudianteModel.php
├── views/                        ← Plantillas HTML/PHP
├── services/                     ← Servicios externos (IA, Email)
├── database/                     ← Scripts SQL de migración
├── assets/                       ← CSS e imágenes
├── uploads/                      ← Archivos subidos (Excel asistencia)
└── vendor/                       ← Dependencias Composer (PHPMailer)
```
