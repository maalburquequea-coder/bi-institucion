# Sprint 1 — Autenticación y base del sistema

| Campo | Detalle |
|---|---|
| Sprint | 1 de 5 |
| Duración | 2 semanas |
| Objetivo | Tener un sistema funcional de login, registro y redirección por roles |
| Puntos comprometidos | 18 |
| Estado | ✅ Completado |

---

## Sprint Backlog

| ID | Historia de usuario | Puntos | Responsable | Estado |
|---|---|---|---|---|
| HU01 | Login con correo y contraseña | 5 | Desarrollador | ✅ Done |
| HU02 | Registro de usuario (Docente / Padre) | 5 | Desarrollador | ✅ Done |
| HU03 | Verificación de correo por token | 3 | Desarrollador | ✅ Done |
| HU04 | Redirección por rol al hacer login | 3 | Desarrollador | ✅ Done |
| HU05 | Cierre de sesión seguro | 2 | Desarrollador | ✅ Done |

---

## Archivos involucrados

| Archivo | Descripción |
|---|---|
| [login.php](../login.php) | Punto de entrada del login |
| [registro.php](../registro.php) | Formulario de registro |
| [logout.php](../logout.php) | Cierre de sesión y limpieza de sesión |
| [verificar_correo.php](../verificar_correo.php) | Verificación del token de correo |
| [controllers/AuthController.php](../controllers/AuthController.php) | Lógica de autenticación y registro |
| [models/AuthModel.php](../models/AuthModel.php) | Acceso a BD: usuarios, tokens, roles |
| [views/login_v.php](../views/login_v.php) | Vista del formulario de login |
| [views/registro_v.php](../views/registro_v.php) | Vista del formulario de registro |
| [config/conexion.php](../config/conexion.php) | Conexión PDO y funciones globales |
| [services/EmailService.php](../services/EmailService.php) | Envío de correo de verificación |

---

## Criterios de aceptación cumplidos

- [x] El usuario puede iniciar sesión con correo y contraseña.
- [x] Se valida que la contraseña sea correcta con `password_verify`.
- [x] El sistema redirige a `admin.php`, `docente.php`, `padre.php` o `portal.php` según el rol.
- [x] El usuario puede registrarse seleccionando rol Docente o Padre.
- [x] Al registrarse se envía un correo con enlace de verificación (token único).
- [x] Si el correo no está verificado o la cuenta está pendiente, se muestra mensaje apropiado.
- [x] El logout destruye la sesión y redirige al login.

---

## Notas técnicas

- La conexión a la BD usa PDO con `prepare/execute` en todos los queries (prevención de SQL injection).
- La contraseña se hashea con `password_hash(..., PASSWORD_DEFAULT)`.
- El token de verificación se guarda en `usuarios.token_verificacion` y se limpia al verificar.
- La función `usuarioActual()` en `config/conexion.php` centraliza la validación de sesión.
