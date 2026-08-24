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

namespace tool_bulkcleaning\local;

use tool_bulkcleaning\local\cleaners\oauth2 as cleaner_oauth2;

/**
 * Class observer
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Event observer for core\event\user_updated
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event): void {
        global $DB;

        $enabled = get_config('tool_bulkcleaning', 'oauth2cleaning_enabled');
        if (!$enabled) {
            return;
        }

        $enabled = get_config('tool_bulkcleaning', 'oauth2cleaning_observer');
        if (!$enabled) {
            return;
        }

        $cases = get_config('tool_bulkcleaning', 'oauth2cleaning_cases');
        if (empty($cases)) {
            return;
        }

        $linkedlogins = $DB->get_records('auth_oauth2_linked_login', ['userid' => $event->objectid]);
        if (empty($linkedlogins)) {
            return;
        }

        $user = \core_user::get_user($event->objectid);
        $cases = explode(',', $cases);
        foreach ($cases as $case) {
            if ($case === cleaner_oauth2::CASE_SUSPENDEDUSERS && $user->suspended) {
                $DB->delete_records('auth_oauth2_linked_login', ['userid' => $user->id]);

                try {
                    $extra = [
                        'deleted' => $user->deleted,
                        'suspended' => $user->suspended,
                    ];
                    cleaner_oauth2::save_log($user->id, cleaner_oauth2::CASE_SUSPENDEDUSERS, $extra);
                } catch (\Exception $e) {
                    debugging('Error saving log for user ' . $user->id . ': ' . $e->getMessage());
                }

                return;
            } else if ($case === cleaner_oauth2::CASE_EMAILNOTMATCH) {
                foreach ($linkedlogins as $linkedlogin) {
                    if ($linkedlogin->email !== $user->email) {
                        $DB->delete_records('auth_oauth2_linked_login', ['id' => $linkedlogin->id]);
                        try {
                            $extra = [
                                'useremail' => $user->email,
                                'oauth2email' => $linkedlogin->email,
                            ];
                            cleaner_oauth2::save_log($user->id, cleaner_oauth2::CASE_EMAILNOTMATCH, $extra);
                        } catch (\Exception $e) {
                            debugging('Error saving log for user ' . $user->id . ': ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * Event observer for core\event\user_deleted
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;

        $enabled = get_config('tool_bulkcleaning', 'oauth2cleaning_enabled');
        if (!$enabled) {
            return;
        }

        $enabled = get_config('tool_bulkcleaning', 'oauth2cleaning_observer');
        if (!$enabled) {
            return;
        }

        $cases = get_config('tool_bulkcleaning', 'oauth2cleaning_cases');
        if (empty($cases)) {
            return;
        }

        $linkedlogins = $DB->get_records('auth_oauth2_linked_login', ['userid' => $event->objectid]);
        if (empty($linkedlogins)) {
            return;
        }

        $cases = explode(',', $cases);
        foreach ($cases as $case) {
            if ($case === cleaner_oauth2::CASE_DELETEDUSERS) {
                $DB->delete_records('auth_oauth2_linked_login', ['userid' => $event->objectid]);

                try {
                    $extra = [
                        'deleted' => 1,
                        'suspended' => null,
                    ];
                    cleaner_oauth2::save_log($event->objectid, cleaner_oauth2::CASE_DELETEDUSERS, $extra);
                } catch (\Exception $e) {
                    debugging('Error saving log for user ' . $event->objectid . ': ' . $e->getMessage());
                }

                return;
            }
        }
    }
}
