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

namespace tool_bulkcleaning\local\cleaners;

/**
 * Class enrol
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol {

    /** @var string Cleaning case: deleted users. */
    public const CASE_DELETEDUSERS = 'deletedusers';

    /** @var string Cleaning case: suspended users. */
    public const CASE_SUSPENDEDUSERS = 'suspendedusers';

    /** @var string Cleaning case: expired enrollments. */
    public const CASE_EXPIREDENROLS = 'expiredenrols';

    /**
     * Save a cleaning log record.
     *
     * @param int $userid
     * @param int $courseid
     * @param string $case
     * @param string $enrolplugin
     * @param int|null $roleid
     * @param int $timestart
     * @param int $timeend
     */
    public static function save_log(int $userid, int $courseid, string $case,
            string $enrolplugin, ?int $roleid, int $timestart, int $timeend): void {
        global $DB;

        $DB->insert_record('tool_bulkcleaning_enrol', (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'timecreated' => time(),
            'details' => json_encode([
                'case' => $case,
                'enrol' => $enrolplugin,
                'roleid' => $roleid,
                'timestart' => $timestart,
                'timeend' => $timeend,
            ]),
        ]);
    }

    /**
     * Get enrolments for deleted users.
     *
     * @return array
     */
    public static function get_deleted_users_enrolments(): array {
        global $DB;

        $sql = "SELECT ue.id AS ueid, ue.userid, e.id AS enrolid, e.enrol, e.courseid,
                       ue.timestart, ue.timeend, ra.roleid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid
                       AND ra.component = " . $DB->sql_concat("'enrol_'", 'e.enrol') . " AND ra.itemid = e.id
                 WHERE u.deleted = 1";

        return $DB->get_records_sql($sql);
    }

    /**
     * Clean enrolments for deleted users.
     */
    public static function clean_deleted_users(): void {
        global $DB;

        mtrace('Processing: deleted users enrolments.');

        $enrolments = self::get_deleted_users_enrolments();

        if (empty($enrolments)) {
            mtrace('  No enrolments found for deleted users.');
            return;
        }

        $plugins = [];

        foreach ($enrolments as $enrolment) {
            if (!isset($plugins[$enrolment->enrol])) {
                $plugins[$enrolment->enrol] = enrol_get_plugin($enrolment->enrol);
            }

            $plugin = $plugins[$enrolment->enrol];
            if (!$plugin) {
                mtrace("  Skipping: enrol plugin '{$enrolment->enrol}' not found.");
                continue;
            }

            $instance = $DB->get_record('enrol', ['id' => $enrolment->enrolid]);
            $plugin->unenrol_user($instance, $enrolment->userid);

            self::save_log(
                $enrolment->userid,
                $enrolment->courseid,
                self::CASE_DELETEDUSERS,
                $enrolment->enrol,
                $enrolment->roleid ? (int) $enrolment->roleid : null,
                (int) $enrolment->timestart,
                (int) $enrolment->timeend
            );

            mtrace("  Unenrolled user {$enrolment->userid} from course {$enrolment->courseid} (deleted user).");
        }
    }

    /**
     * Get enrolments for suspended users.
     *
     * @return array
     */
    public static function get_suspended_users_enrolments(): array {
        global $DB;

        $sql = "SELECT ue.id AS ueid, ue.userid, e.id AS enrolid, e.enrol, e.courseid,
                       ue.timestart, ue.timeend, ra.roleid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid
                       AND ra.component = " . $DB->sql_concat("'enrol_'", 'e.enrol') . " AND ra.itemid = e.id
                 WHERE u.suspended = 1 AND u.deleted = 0";

        return $DB->get_records_sql($sql);
    }

    /**
     * Clean enrolments for suspended users.
     */
    public static function clean_suspended_users(): void {
        global $DB;

        mtrace('Processing: suspended users enrolments.');

        $enrolments = self::get_suspended_users_enrolments();

        if (empty($enrolments)) {
            mtrace('  No enrolments found for suspended users.');
            return;
        }

        $plugins = [];

        foreach ($enrolments as $enrolment) {
            if (!isset($plugins[$enrolment->enrol])) {
                $plugins[$enrolment->enrol] = enrol_get_plugin($enrolment->enrol);
            }

            $plugin = $plugins[$enrolment->enrol];
            if (!$plugin) {
                mtrace("  Skipping: enrol plugin '{$enrolment->enrol}' not found.");
                continue;
            }

            $instance = $DB->get_record('enrol', ['id' => $enrolment->enrolid]);
            $plugin->unenrol_user($instance, $enrolment->userid);

            self::save_log(
                $enrolment->userid,
                $enrolment->courseid,
                self::CASE_SUSPENDEDUSERS,
                $enrolment->enrol,
                $enrolment->roleid ? (int) $enrolment->roleid : null,
                (int) $enrolment->timestart,
                (int) $enrolment->timeend
            );

            mtrace("  Unenrolled user {$enrolment->userid} from course {$enrolment->courseid} (suspended user).");
        }
    }

    /**
     * Get expired enrolments.
     *
     * @return array
     */
    public static function get_expired_enrolments(): array {
        global $DB;

        $sql = "SELECT ue.id AS ueid, ue.userid, e.id AS enrolid, e.enrol, e.courseid,
                       ue.timestart, ue.timeend, ra.roleid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid
                       AND ra.component = " . $DB->sql_concat("'enrol_'", 'e.enrol') . " AND ra.itemid = e.id
                 WHERE ue.timeend > 0 AND ue.timeend < :now
                       AND u.deleted = 0";

        return $DB->get_records_sql($sql, ['now' => time()]);
    }

    /**
     * Clean expired enrolments.
     */
    public static function clean_expired_enrolments(): void {
        global $DB;

        mtrace('Processing: expired enrolments.');

        $enrolments = self::get_expired_enrolments();

        if (empty($enrolments)) {
            mtrace('  No expired enrolments found.');
            return;
        }

        $plugins = [];

        foreach ($enrolments as $enrolment) {
            if (!isset($plugins[$enrolment->enrol])) {
                $plugins[$enrolment->enrol] = enrol_get_plugin($enrolment->enrol);
            }

            $plugin = $plugins[$enrolment->enrol];
            if (!$plugin) {
                mtrace("  Skipping: enrol plugin '{$enrolment->enrol}' not found.");
                continue;
            }

            $instance = $DB->get_record('enrol', ['id' => $enrolment->enrolid]);
            $plugin->unenrol_user($instance, $enrolment->userid);

            self::save_log(
                $enrolment->userid,
                $enrolment->courseid,
                self::CASE_EXPIREDENROLS,
                $enrolment->enrol,
                $enrolment->roleid ? (int) $enrolment->roleid : null,
                (int) $enrolment->timestart,
                (int) $enrolment->timeend
            );

            mtrace("  Unenrolled user {$enrolment->userid} from course {$enrolment->courseid} (expired enrolment).");
        }
    }
}
