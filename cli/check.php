<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI script to check what would be cleaned by bulk cleaning tasks.
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use tool_bulkcleaning\local\cleaners\enrol;
use tool_bulkcleaning\local\cleaners\users;

$allcases = implode(', ', [
    enrol::CASE_DELETEDUSERS,
    enrol::CASE_SUSPENDEDUSERS,
    enrol::CASE_EXPIREDENROLS,
    users::CASE_NOLOGIN,
]);

list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'enrol' => false,
        'users' => false,
        'all' => false,
        'csv' => false,
        'case' => '',
    ],
    [
        'h' => 'help',
        'e' => 'enrol',
        'u' => 'users',
        'a' => 'all',
        'c' => 'csv',
        's' => 'case',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help'] || (!$options['enrol'] && !$options['users'] && !$options['all'] && empty($options['case']))) {
    $help = "Check what would be cleaned by bulk cleaning tasks.

Options:
-h, --help          Print out this help
-e, --enrol         Show enrolment cleaning data
-u, --users         Show users cleaning data
-a, --all           Show all cleaning data
-c, --csv           Export data as CSV instead of counts
-s, --case=CASE     Filter by a specific case: {$allcases}

Example:
\$ sudo -u www-data /usr/bin/php admin/tool/bulkcleaning/cli/check.php --all
\$ sudo -u www-data /usr/bin/php admin/tool/bulkcleaning/cli/check.php -e --csv
\$ sudo -u www-data /usr/bin/php admin/tool/bulkcleaning/cli/check.php --case=deletedusers --csv
";
    echo $help;
    exit(0);
}

$csv = $options['csv'];
$casefilter = $options['case'];

// Map cases to their group.
$enrolcases = [enrol::CASE_DELETEDUSERS, enrol::CASE_SUSPENDEDUSERS, enrol::CASE_EXPIREDENROLS];
$userscases = [users::CASE_NOLOGIN];

$showenrol = $options['enrol'] || $options['all'];
$showusers = $options['users'] || $options['all'];

if (!empty($casefilter)) {
    if (in_array($casefilter, $enrolcases)) {
        $showenrol = true;
    } else if (in_array($casefilter, $userscases)) {
        $showusers = true;
    } else {
        cli_error("Unknown case: {$casefilter}. Available: {$allcases}");
    }
}

if ($showenrol) {
    check_enrol($csv, $casefilter);
}

if ($showusers) {
    check_users($csv, $casefilter);
}

/**
 * Check enrolment cleaning data.
 *
 * @param bool $csv
 * @param string $casefilter
 */
function check_enrol(bool $csv, string $casefilter): void {
    $cases = [
        enrol::CASE_DELETEDUSERS => 'get_deleted_users_enrolments',
        enrol::CASE_SUSPENDEDUSERS => 'get_suspended_users_enrolments',
        enrol::CASE_EXPIREDENROLS => 'get_expired_enrolments',
    ];

    if (!empty($casefilter)) {
        if (!isset($cases[$casefilter])) {
            return;
        }
        $cases = [$casefilter => $cases[$casefilter]];
    }

    if ($csv) {
        $fp = fopen('php://stdout', 'w');
        fputcsv($fp, ['case', 'userid', 'courseid', 'enrol', 'roleid', 'timestart', 'timeend']);

        foreach ($cases as $case => $method) {
            $enrolments = enrol::$method();
            foreach ($enrolments as $e) {
                fputcsv($fp, [$case, $e->userid, $e->courseid, $e->enrol, $e->roleid ?? '', $e->timestart, $e->timeend]);
            }
        }

        fclose($fp);
        return;
    }

    cli_heading('Enrolment cleaning');

    $enabled = get_config('tool_bulkcleaning', 'enrolcleaning_enabled');
    echo "Status: " . ($enabled ? 'Enabled' : 'Disabled') . "\n\n";

    foreach ($cases as $case => $method) {
        $count = count(enrol::$method());
        echo "  $case: $count enrolments\n";
    }

    echo "\n";
}

/**
 * Check users cleaning data.
 *
 * @param bool $csv
 * @param string $casefilter
 */
function check_users(bool $csv, string $casefilter): void {
    if (!empty($casefilter) && $casefilter !== users::CASE_NOLOGIN) {
        return;
    }

    $days = (int) get_config('tool_bulkcleaning', 'userscleaning_nologin_days');

    if ($csv) {
        $fp = fopen('php://stdout', 'w');
        fputcsv($fp, ['case', 'userid', 'lastaccess', 'days']);

        if ($days > 0) {
            $nologinusers = users::get_nologin_users();
            foreach ($nologinusers as $u) {
                fputcsv($fp, [users::CASE_NOLOGIN, $u->id, $u->lastaccess, $days]);
            }
        }

        fclose($fp);
        return;
    }

    cli_heading('Users cleaning');

    $enabled = get_config('tool_bulkcleaning', 'userscleaning_enabled');
    echo "Status: " . ($enabled ? 'Enabled' : 'Disabled') . "\n";
    echo "Configured days without login: " . ($days > 0 ? $days : 'Not configured') . "\n\n";

    if ($days <= 0) {
        echo "  " . users::CASE_NOLOGIN . ": N/A (days not configured)\n\n";
        return;
    }

    $count = count(users::get_nologin_users());
    echo "  " . users::CASE_NOLOGIN . ": $count users\n\n";
}
