[← Volver al índice](README.md)

# Instalación y configuración

## Requisitos

- Moodle 4.5 o superior.

## Instalación

1. Copiar la carpeta del componente en `/admin/tool/bulkcleaning/`.
2. Ir a **Administración del sitio > Notificaciones** para completar la instalación.

## Configuración

Se encuentra en **Administración del sitio > Extensiones > Herramientas del administrador > Limpieza masiva**.

Los ajustes están organizados en dos pestañas:

- **Limpieza de matrículas:** Habilitar/deshabilitar, seleccionar casos de limpieza y elegir filtro de usuarios.
- **Limpieza de usuarios:** Habilitar/deshabilitar, seleccionar casos de limpieza, elegir la acción y configurar los días de inactividad.

## Tareas programadas

El plugin registra dos tareas programadas:

| Tarea | Horario por defecto |
|---|---|
| Limpieza de matrículas | Todos los días a las 3:00 AM |
| Limpieza de usuarios | Todos los días a las 4:00 AM |

Las tareas solo procesan datos si están habilitadas en la configuración.

Se pueden ajustar los horarios desde **Administración del sitio > Servidor > Tareas programadas**.
