[← Back to index](README.md)

# CLI command

The plugin includes a command line script to preview what data would be cleaned, without executing any changes.

## Usage

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php [options]
```

## Options

| Option | Description |
|---|---|
| `--help`, `-h` | Shows the help |
| `--all`, `-a` | Shows data for all cleanings |
| `--enrol`, `-e` | Shows only enrolment cleaning data |
| `--users`, `-u` | Shows only user cleaning data |
| `--case=CASE`, `-s` | Filters by a specific case (e.g.: `deletedusers`, `suspendedusers`, `expiredenrols`, `nologin`) |
| `--csv`, `-c` | Exports results in CSV format instead of showing counts |

## Examples

Show a summary of all data that would be cleaned:

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php --all
```

Show a summary of enrolments that would be cleaned:

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php -e
```

Show only a specific case and export to CSV:

```bash
/usr/bin/php admin/tool/bulkcleaning/cli/check.php --case=deletedusers --csv
```
