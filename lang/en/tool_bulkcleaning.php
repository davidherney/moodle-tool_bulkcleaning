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
 * English language pack for Bulk cleaning
 *
 * @package    tool_bulkcleaning
 * @category   string
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['enrolcleaning_case_deletedusers'] = 'Deleted users';
$string['enrolcleaning_case_expiredenrols'] = 'Expired enrollments';
$string['enrolcleaning_case_suspendedusers'] = 'Suspended users in the platform';
$string['enrolcleaning_cases'] = 'Cleaning cases';
$string['enrolcleaning_cases_desc'] = 'Select which enrollment cleaning cases should be processed by the scheduled task.';
$string['enrolcleaning_enabled'] = 'Enable enrollments cleaning';
$string['enrolcleaning_enabled_desc'] = 'If enabled, the scheduled task will clean enrollments based on the selected cases.';
$string['enrolcleaning_userfilter'] = 'User filter';
$string['enrolcleaning_userfilter_completed'] = 'Completed the course';
$string['enrolcleaning_userfilter_desc'] = 'Select the condition that users must meet to be considered for enrollment cleaning.';
$string['enrolcleaning_userfilter_noaccess'] = 'Never accessed the course';
$string['enrolcleaning_userfilter_nogrades'] = 'No grades in the course';
$string['enrolcleaning_userfilter_none'] = 'No restriction';
$string['enrolcleaning_userfilter_notcompleted'] = 'Not completed the course';
$string['pluginname'] = 'Bulk cleaning';
$string['privacy:metadata'] = 'The Bulk cleaning plugin doesn\'t store any personal data.';
$string['tab_enrolcleaning'] = 'Enrolments cleaning';
$string['tab_userscleaning'] = 'Users cleaning';
$string['task_enrolcleaning'] = 'Enrollments cleaning task';
$string['task_userscleaning'] = 'Users cleaning task';
$string['userscleaning_action'] = 'Cleaning action';
$string['userscleaning_action_delete'] = 'Delete the user';
$string['userscleaning_action_desc'] = 'Select the action to perform on users that meet the cleaning criteria.';
$string['userscleaning_action_suspend'] = 'Suspend the user';
$string['userscleaning_case_nologin'] = 'No login since X days';
$string['userscleaning_cases'] = 'Cleaning cases';
$string['userscleaning_cases_desc'] = 'Select which user cleaning cases should be processed by the scheduled task.';
$string['userscleaning_enabled'] = 'Enable users cleaning';
$string['userscleaning_enabled_desc'] = 'If enabled, the scheduled task will clean users based on the selected cases.';
$string['userscleaning_nologin_days'] = 'Days without login';
$string['userscleaning_nologin_days_desc'] = 'Number of days without login to consider a user inactive. Users who have not logged in for this number of days will be suspended/deleted.';
