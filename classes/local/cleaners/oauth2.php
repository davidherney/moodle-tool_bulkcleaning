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
 * Class oauth2
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth2 {
    /** @var string Cleaning case: deleted users. */
    const CASE_DELETEDUSERS = 'oauthdeletedusers';

    /** @var string Cleaning case: suspended users. */
    const CASE_SUSPENDEDUSERS = 'oauthsuspendedusers';

    /** @var string Cleaning case: email not match in automatic relations. */
    const CASE_EMAILNOTMATCH = 'oauthemailnotmatch';

    /**
     * Save a cleaning log record.
     *
     * @param int $userid
     * @param string $type
     * @param array $extra
     */
    public static function save_log(int $userid, string $type, array $extra = []): void {
        global $DB;

        $DB->insert_record('tool_bulkcleaning_oauth2', (object) [
            'userid' => $userid,
            'type' => $type,
            'timecreated' => time(),
            'details' => json_encode($extra),
        ]);
    }

    /**
     * Clean OAuth2 linked login for deleted users.
     *
     * @param bool|null $deleted Whether to clean deleted users.
     * @param bool|null $suspended Whether to clean suspended users.
     * @return void
     */
    public static function clean_users(?bool $deleted = false, ?bool $suspended = false): void {
        global $DB;

        mtrace('Processing: deleted users.');

        $conditions = [];
        if ($deleted) {
            $conditions[] = 'u.deleted = 1';
        }

        if ($suspended) {
            $conditions[] = 'u.suspended = 1';
        }

        if (empty($conditions)) {
            mtrace('  No cleaning conditions specified.');
            return;
        }

        $sql = "SELECT u.id, u.suspended, u.deleted FROM {user} u
                INNER JOIN {auth_oauth2_linked_login} l ON u.id = l.userid
                WHERE " . implode(' OR ', $conditions);
        $users = $DB->get_records_sql($sql);

        if (!empty($users)) {
            $userids = array_map(fn($u) => $u->id, $users);
            [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('auth_oauth2_linked_login', "userid $insql", $params);

            try {
                foreach ($users as $user) {
                    $extra = [
                        'deleted' => $user->deleted,
                        'suspended' => $user->suspended,
                    ];
                    self::save_log($user->id, $user->deleted ? self::CASE_DELETEDUSERS : self::CASE_SUSPENDEDUSERS, $extra);
                }
            } catch (\Exception $e) {
                mtrace('  Error saving log for deleted/suspended users: ' . $e->getMessage());
            }

            $count = count($userids);
            mtrace("  Deleted users with linked login ($count) have been cleaned.");
        } else {
            mtrace('  No deleted users found with linked login.');
        }
    }

    /**
     * Get users with deleted status and linked login.
     *
     * @return array
     */
    public static function get_deleted_users(): array {
        global $DB;

        $sql = "SELECT u.id, u.email AS useremail, l.email AS oauth2email
                FROM {user} u
                INNER JOIN {auth_oauth2_linked_login} l ON u.id = l.userid
                WHERE u.deleted = 1";

        return $DB->get_records_sql($sql);
    }

    /**
     * Get users with suspended status and linked login.
     *
     * @return array
     */
    public static function get_suspended_users(): array {
        global $DB;

        $sql = "SELECT u.id AS userid, u.email AS useremail, l.email AS oauth2email
                FROM {user} u
                INNER JOIN {auth_oauth2_linked_login} l ON u.id = l.userid
                WHERE u.suspended = 1";

        return $DB->get_records_sql($sql);
    }

    /**
     * Clean OAuth2 linked login for users with email not match in automatic relations.
     *
     * @return void
     */
    public static function clean_email_not_match(): void {
        global $DB;

        mtrace('Processing: email not match in automatic accounts.');

        $linkedlogins = self::get_email_not_match_users();

        if (!empty($linkedlogins)) {
            $linkedloginids = array_map(fn($l) => $l->id, $linkedlogins);

            [$insql, $params] = $DB->get_in_or_equal($linkedloginids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('auth_oauth2_linked_login', "id $insql", $params);

            try {
                foreach ($linkedlogins as $linkedlogin) {
                    $extra = [
                        'useremail' => $linkedlogin->useremail,
                        'oauth2email' => $linkedlogin->oauth2email,
                    ];
                    self::save_log($linkedlogin->userid, self::CASE_EMAILNOTMATCH, $extra);
                }
            } catch (\Exception $e) {
                mtrace('  Error saving log for email not match: ' . $e->getMessage());
            }

            $count = count($linkedloginids);
            mtrace("  Users with email not match ($count) have been cleaned.");
        } else {
            mtrace('  No users found with email not match.');
        }
    }

    /**
     * Get users with email not match in automatic relations.
     *
     * @return array
     */
    public static function get_email_not_match_users(): array {
        global $DB;

        // Assuming usermodified is 0 OR equal to userid for automatic relations.
        $sql = "SELECT l.id, l.userid, u.email AS useremail, l.email AS oauth2email
                FROM {auth_oauth2_linked_login} l
                INNER JOIN {user} u ON l.userid = u.id
                WHERE u.email <> l.email AND (l.usermodified = 0 OR l.usermodified = l.userid)";

        return $DB->get_records_sql($sql);
    }
}
