[← Volver al índice](README.md)

# Limpieza de usuarios

Permite ejecutar acciones sobre usuarios inactivos de forma automática.

## Casos de limpieza

| Caso | Descripción |
|---|---|
| Sin inicio de sesión | Usuarios que no han ingresado a la plataforma en un número determinado de días |

## Configuración

- **Días sin inicio de sesión:** Número de días de inactividad para considerar a un usuario como candidato (por defecto: 365).
- **Acción de limpieza:** Qué hacer con los usuarios que cumplen la condición.

## Acciones disponibles

| Acción | Descripción |
|---|---|
| Suspender | Desactiva la cuenta del usuario. El usuario no podrá ingresar, pero sus datos se conservan |
| Eliminar | Borra la cuenta del usuario de la plataforma |

## Registro

Cada usuario procesado se guarda en un registro interno con el caso aplicado, la acción realizada y la fecha del último acceso.
