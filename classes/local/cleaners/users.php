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
 * Class users
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users {

    /** @var string Cleaning case: no login since X days. */
    public const CASE_NOLOGIN = 'nologin';

    /**
     * Save a cleaning log record.
     *
     * @param int $userid
     * @param string $case
     * @param array $extra
     */
    public static function save_log(int $userid, string $case, array $extra = []): void {
        global $DB;

        $DB->insert_record('tool_bulkcleaning_users', (object) [
            'userid' => $userid,
            'timecreated' => time(),
            'details' => json_encode(array_merge(['case' => $case], $extra)),
        ]);
    }

    /**
     * Get users who have not logged in for a configured number of days.
     *
     * @return array
     */
    public static function get_nologin_users(): array {
        global $DB;

        $days = (int) get_config('tool_bulkcleaning', 'userscleaning_nologin_days');
        if ($days <= 0) {
            return [];
        }

        $threshold = time() - ($days * DAYSECS);

        $sql = "SELECT u.id, u.lastaccess
                FROM {user} u
                 WHERE u.deleted = 0 AND u.suspended = 0
                       AND (u.lastaccess > 0 AND u.lastaccess < :threshold
                            OR u.lastaccess = 0 AND u.timecreated < :threshold2)";

        return $DB->get_records_sql($sql, ['threshold' => $threshold, 'threshold2' => $threshold]);
    }

    /**
     * Clean users who have not logged in for a configured number of days.
     */
    public static function clean_nologin(): void {
        global $DB;

        mtrace('Processing: users with no login.');

        $days = (int) get_config('tool_bulkcleaning', 'userscleaning_nologin_days');
        if ($days <= 0) {
            mtrace('  No login days not configured.');
            return;
        }

        $users = self::get_nologin_users();

        if (empty($users)) {
            mtrace('  No users found with no login since ' . $days . ' days.');
            return;
        }

        $now = time();

        foreach ($users as $user) {
            $user->suspended = 1;
            $DB->update_record('user', $user);

            self::save_log($user->id, self::CASE_NOLOGIN, [
                'days' => $days,
                'lastaccess' => (int) $user->lastaccess,
            ]);

            mtrace("  Suspended user {$user->id} (no login since {$days} days).");
        }
    }
}
