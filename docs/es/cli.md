[← Volver al índice](README.md)

# Comando CLI

El plugin incluye un comando de línea para previsualizar qué datos serían limpiados, sin ejecutar cambios.

## Uso

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php [opciones]
```

## Opciones

| Opción | Descripción |
|---|---|
| `--help`, `-h` | Muestra la ayuda |
| `--all`, `-a` | Muestra datos de todas las limpiezas |
| `--enrol`, `-e` | Muestra solo datos de limpieza de matrículas |
| `--users`, `-u` | Muestra solo datos de limpieza de usuarios |
| `--case=CASO`, `-s` | Filtra por un caso específico (ej: `deletedusers`, `suspendedusers`, `expiredenrols`, `nologin`) |
| `--csv`, `-c` | Exporta los resultados en formato CSV en vez de mostrar conteos |

## Ejemplos

Ver un resumen de todos los datos que serían limpiados:

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php --all
```

Ver un resumen de las matrículas que serían limpiadas:

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php -e
```

Ver solo un caso específico y llevarlo a CSV:

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php --case=deletedusers --csv
```
