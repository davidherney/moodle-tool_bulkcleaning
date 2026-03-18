[← Back to index](README.md)

# Installation and configuration

## Requirements

- Moodle 4.5 or higher.

## Installation

1. Copy the plugin folder into `/admin/tool/bulkcleaning/`.
2. Go to **Site administration > Notifications** to complete the installation.

## Configuration

Navigate to **Site administration > Plugins > Admin tools > Bulk cleaning**.

The settings are organized in two tabs:

- **Enrolments cleaning:** Enable/disable, select cleaning cases and choose user filter.
- **Users cleaning:** Enable/disable, select cleaning cases, choose the action and configure the inactivity days.

## Scheduled tasks

The plugin registers two scheduled tasks:

| Task | Default schedule |
|---|---|
| Enrolments cleaning | Daily at 3:00 AM |
| Users cleaning | Daily at 4:00 AM |

Tasks only process data if they are enabled in the configuration.

Schedules can be adjusted from **Site administration > Server > Scheduled tasks**.
