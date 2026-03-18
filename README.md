# Bulk cleaning

Tool to automate bulk cleaning data on the platform in operations through scheduled tasks.

Package tested in: Moodle 4.5+.

## Features

### Enrolments cleaning
Automatically unenrol users based on configurable cases:
- **Deleted users:** Remove enrolments for users that have been deleted.
- **Suspended users:** Remove enrolments for users that are suspended in the platform.
- **Expired enrolments:** Remove enrolments that have passed their end date.

An additional **user filter** can be applied to refine which enrolments are cleaned:
- No restriction
- No grades in the course
- Never accessed the course
- Completed the course
- Not completed the course

### Users cleaning
Automatically suspend users based on configurable cases:
- **No login:** Suspend users who have not logged in for a configured number of days (default: 365).

## Installation

Download zip package, extract the `bulkcleaning` folder and upload this folder into `admin/tool/`.

## Configuration

Navigate to: *Site administration > Plugins > Admin tools > Bulk cleaning*

The settings are organized in two tabs:
- **Enrolments cleaning:** Enable/disable, select cleaning cases, and choose user filters.
- **Users cleaning:** Enable/disable, select cleaning cases, and configure the inactivity threshold.

## Scheduled tasks

The plugin registers two scheduled tasks:
- **Enrollments cleaning task:** Runs daily at 3:00 AM.
- **Users cleaning task:** Runs daily at 4:00 AM.

## CLI

A CLI script is available to preview what would be cleaned without executing changes:

```bash
sudo -u www-data /usr/bin/php admin/tool/bulkcleaning/cli/check.php --all
sudo -u www-data /usr/bin/php admin/tool/bulkcleaning/cli/check.php --enrol --csv
sudo -u www-data /usr/bin/php admin/tool/bulkcleaning/cli/check.php --case=deletedusers --csv
```

## About

* **Developed by:** David Herney - david dot herney at gmail dot com
* **Powered by:** [BambuCo](https://bambuco.co) and [Engagement](https://engagement.com.co)

## IN VERSION

### 2026031700:
First release.

## License

2026 David Herney @ BambuCo

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
