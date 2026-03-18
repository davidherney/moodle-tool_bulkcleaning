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

namespace tool_bulkcleaning\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for tool_bulkcleaning.
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_bulkcleaning_enrol',
            [
                'userid' => 'privacy:metadata:enrol:userid',
                'courseid' => 'privacy:metadata:enrol:courseid',
                'timecreated' => 'privacy:metadata:enrol:timecreated',
                'details' => 'privacy:metadata:enrol:details',
            ],
            'privacy:metadata:enrol'
        );

        $collection->add_database_table(
            'tool_bulkcleaning_users',
            [
                'userid' => 'privacy:metadata:users:userid',
                'timecreated' => 'privacy:metadata:users:timecreated',
                'details' => 'privacy:metadata:users:details',
            ],
            'privacy:metadata:users'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {tool_bulkcleaning_users} bu ON ctx.instanceid = bu.userid AND ctx.contextlevel = :contextlevel
                 WHERE bu.userid = :userid";
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'contextlevel' => CONTEXT_USER]);

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {tool_bulkcleaning_enrol} be ON ctx.instanceid = be.userid AND ctx.contextlevel = :contextlevel
                 WHERE be.userid = :userid";
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'contextlevel' => CONTEXT_USER]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $params = ['userid' => $context->instanceid];

        $sql = "SELECT userid FROM {tool_bulkcleaning_users} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT userid FROM {tool_bulkcleaning_enrol} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export data for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $context = \context_user::instance($userid);

        $records = $DB->get_records('tool_bulkcleaning_users', ['userid' => $userid], 'timecreated ASC');
        foreach ($records as $record) {
            $data = (object) [
                'userid' => $record->userid,
                'timecreated' => transform::datetime($record->timecreated),
                'details' => $record->details,
            ];
            writer::with_context($context)->export_data(
                [get_string('privacy:path:userscleaning', 'tool_bulkcleaning'), $record->id],
                $data
            );
        }

        $records = $DB->get_records('tool_bulkcleaning_enrol', ['userid' => $userid], 'timecreated ASC');
        foreach ($records as $record) {
            $data = (object) [
                'userid' => $record->userid,
                'courseid' => $record->courseid,
                'timecreated' => transform::datetime($record->timecreated),
                'details' => $record->details,
            ];
            writer::with_context($context)->export_data(
                [get_string('privacy:path:enrolcleaning', 'tool_bulkcleaning'), $record->id],
                $data
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_USER) {
            $DB->delete_records('tool_bulkcleaning_users', ['userid' => $context->instanceid]);
            $DB->delete_records('tool_bulkcleaning_enrol', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user to delete data for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('tool_bulkcleaning_users', ['userid' => $userid]);
        $DB->delete_records('tool_bulkcleaning_enrol', ['userid' => $userid]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete data for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('tool_bulkcleaning_users', "userid {$insql}", $inparams);
        $DB->delete_records_select('tool_bulkcleaning_enrol', "userid {$insql}", $inparams);
    }
}
