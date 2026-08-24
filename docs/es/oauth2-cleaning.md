[← Volver al índice](README.md)

# Limpieza de OAuth2

Elimina registros de inicio de sesión vinculados con OAuth2 según los casos configurados.

## Casos de limpieza

| Caso | Descripción |
|---|---|
| Usuarios eliminados | Elimina vínculos OAuth2 de usuarios marcados como eliminados |
| Usuarios suspendidos | Elimina vínculos OAuth2 de usuarios marcados como suspendidos |
| Correo no coincide con el proveedor | Elimina vínculos automáticos cuando el correo del usuario en Moodle no coincide con el correo del proveedor OAuth2 |

Se pueden activar uno o varios casos a la vez.

## Cómo funciona

- La tarea programada lee los casos habilitados en la configuración del plugin.
- Para usuarios eliminados/suspendidos, elimina vínculos por id de usuario.
- Para correo no coincidente, elimina solo vínculos automáticos: si el vínculo fue creado por el sistema o si el usuario
que lo creó es el mismo usuario (es el caso común de vínculos creados al autenticarse).

## Registro

Cada vínculo eliminado se guarda en un registro interno con:

- Id del usuario.
- Tipo de caso aplicado.
- Detalles específicos del caso (por ejemplo banderas deleted/suspended o correos comparados).
