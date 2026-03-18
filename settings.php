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
 * Settings for Bulk cleaning.
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_bulkcleaning\local\cleaners\enrol AS cleaner_enrol;
use tool_bulkcleaning\local\cleaners\users AS cleaner_users;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new theme_boost_admin_settingspage_tabs('tool_bulkcleaning', new lang_string('pluginname', 'tool_bulkcleaning'));
    $ADMIN->add('tools', $settings);

    if ($ADMIN->fulltree) {

        // Enrolments cleaning tab.
        $page = new admin_settingpage('tool_bulkcleaning_enrol',
            new lang_string('tab_enrolcleaning', 'tool_bulkcleaning'));

        $page->add(new admin_setting_configcheckbox(
            'tool_bulkcleaning/enrolcleaning_enabled',
            new lang_string('enrolcleaning_enabled', 'tool_bulkcleaning'),
            new lang_string('enrolcleaning_enabled_desc', 'tool_bulkcleaning'),
            0
        ));

        $page->add(new admin_setting_configmulticheckbox(
            'tool_bulkcleaning/enrolcleaning_cases',
            new lang_string('enrolcleaning_cases', 'tool_bulkcleaning'),
            new lang_string('enrolcleaning_cases_desc', 'tool_bulkcleaning'),
            [],
            [
                cleaner_enrol::CASE_DELETEDUSERS => new lang_string('enrolcleaning_case_deletedusers', 'tool_bulkcleaning'),
                cleaner_enrol::CASE_SUSPENDEDUSERS => new lang_string('enrolcleaning_case_suspendedusers', 'tool_bulkcleaning'),
                cleaner_enrol::CASE_EXPIREDENROLS => new lang_string('enrolcleaning_case_expiredenrols', 'tool_bulkcleaning'),
            ]
        ));

        $settings->add($page);

        // Users cleaning tab.
        $page = new admin_settingpage('tool_bulkcleaning_users',
            new lang_string('tab_userscleaning', 'tool_bulkcleaning'));

        $page->add(new admin_setting_configcheckbox(
            'tool_bulkcleaning/userscleaning_enabled',
            new lang_string('userscleaning_enabled', 'tool_bulkcleaning'),
            new lang_string('userscleaning_enabled_desc', 'tool_bulkcleaning'),
            0
        ));

        $page->add(new admin_setting_configmulticheckbox(
            'tool_bulkcleaning/userscleaning_cases',
            new lang_string('userscleaning_cases', 'tool_bulkcleaning'),
            new lang_string('userscleaning_cases_desc', 'tool_bulkcleaning'),
            [],
            [
                cleaner_users::CASE_NOLOGIN => new lang_string('userscleaning_case_nologin', 'tool_bulkcleaning'),
            ]
        ));

        $page->add(new admin_setting_configtext(
            'tool_bulkcleaning/userscleaning_nologin_days',
            new lang_string('userscleaning_nologin_days', 'tool_bulkcleaning'),
            new lang_string('userscleaning_nologin_days_desc', 'tool_bulkcleaning'),
            365,
            PARAM_INT
        ));

        $settings->add($page);
    }
}
