[← Volver al índice](README.md)

# Limpieza de matrículas

Desmatricula usuarios automáticamente según los casos configurados.

## Casos de limpieza

| Caso | Descripción |
|---|---|
| Usuarios eliminados | Elimina matrículas de usuarios que han sido borrados de la plataforma |
| Usuarios suspendidos | Elimina matrículas de usuarios que están suspendidos |
| Matrículas expiradas | Elimina matrículas cuya fecha de fin ya pasó |

Se pueden activar uno o varios casos a la vez.

## Filtro de usuarios

Permite restringir qué matrículas se limpian según una condición adicional sobre el usuario en el curso.

| Filtro | Descripción |
|---|---|
| Sin restricción | Se limpian todas las matrículas que cumplan el caso |
| Sin calificaciones | Solo si el usuario no tiene calificaciones en el curso |
| Nunca accedió | Solo si el usuario nunca ingresó al curso |
| Completó el curso | Solo si el usuario ya completó el curso |
| No completó el curso | Solo si el usuario no ha completado el curso |

El filtro se aplica a todos los casos activos.

## Registro

Cada matrícula eliminada se guarda en un registro interno con los datos del usuario, el curso y el caso aplicado.
