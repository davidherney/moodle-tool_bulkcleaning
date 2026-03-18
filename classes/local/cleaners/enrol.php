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

    /** @var string User filter: no restriction. */
    public const USERFILTER_NONE = 'none';

    /** @var string User filter: no grades in the course. */
    public const USERFILTER_NOGRADES = 'nogrades';

    /** @var string User filter: never accessed the course. */
    public const USERFILTER_NOACCESS = 'noaccess';

    /** @var string User filter: completed the course. */
    public const USERFILTER_COMPLETED = 'completed';

    /** @var string User filter: not completed the course. */
    public const USERFILTER_NOTCOMPLETED = 'notcompleted';

    /**
     * Get SQL fragments for the user filter setting.
     *
     * @return array With keys 'joins' and 'where'.
     */
    private static function get_userfilter_sql(): array {
        $filter = get_config('tool_bulkcleaning', 'enrolcleaning_userfilter');
        $joins = '';
        $where = '';

        switch ($filter) {
            case self::USERFILTER_NOGRADES:
                $joins = "LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid AND gi.itemtype <> 'course'
                          LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = ue.userid
                                                         AND gg.finalgrade IS NOT NULL";
                $where = "AND gg.id IS NULL";
                break;
            case self::USERFILTER_NOACCESS:
                $joins = "LEFT JOIN {user_lastaccess} ul ON ul.userid = ue.userid AND ul.courseid = e.courseid";
                $where = "AND ul.id IS NULL";
                break;
            case self::USERFILTER_COMPLETED:
                $joins = "INNER JOIN {course_completions} cc ON cc.userid = ue.userid
                            AND cc.course = e.courseid AND cc.timecompleted IS NOT NULL";
                break;
            case self::USERFILTER_NOTCOMPLETED:
                $joins = "LEFT JOIN {course_completions} cc ON cc.userid = ue.userid
                            AND cc.course = e.courseid AND cc.timecompleted IS NOT NULL";
                $where = "AND cc.id IS NULL";
                break;
        }

        return ['joins' => $joins, 'where' => $where];
    }

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
    public static function save_log(
        int $userid,
        int $courseid,
        string $case,
        string $enrolplugin,
        ?int $roleid,
        int $timestart,
        int $timeend
    ): void {
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

        $userfilter = self::get_userfilter_sql();

        $sql = "SELECT DISTINCT ue.id AS ueid, ue.userid, e.id AS enrolid, e.enrol, e.courseid,
                       ue.timestart, ue.timeend, ra.roleid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid
                       AND ra.component = " . $DB->sql_concat("'enrol_'", 'e.enrol') . " AND ra.itemid = e.id
                {$userfilter['joins']}
                 WHERE u.deleted = 1 {$userfilter['where']}";

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

        $userfilter = self::get_userfilter_sql();

        $sql = "SELECT DISTINCT ue.id AS ueid, ue.userid, e.id AS enrolid, e.enrol, e.courseid,
                       ue.timestart, ue.timeend, ra.roleid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid
                       AND ra.component = " . $DB->sql_concat("'enrol_'", 'e.enrol') . " AND ra.itemid = e.id
                {$userfilter['joins']}
                 WHERE u.suspended = 1 AND u.deleted = 0 {$userfilter['where']}";

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

        $userfilter = self::get_userfilter_sql();

        $sql = "SELECT DISTINCT ue.id AS ueid, ue.userid, e.id AS enrolid, e.enrol, e.courseid,
                       ue.timestart, ue.timeend, ra.roleid
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON e.id = ue.enrolid
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid
                       AND ra.component = " . $DB->sql_concat("'enrol_'", 'e.enrol') . " AND ra.itemid = e.id
                {$userfilter['joins']}
                 WHERE ue.timeend > 0 AND ue.timeend < :now
                       AND u.deleted = 0 {$userfilter['where']}";

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
