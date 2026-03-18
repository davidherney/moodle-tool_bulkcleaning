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

namespace tool_bulkcleaning\reportbuilder\local\systemreports;

use context_system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use tool_bulkcleaning\reportbuilder\local\entities\users_log;

/**
 * Users cleaning log system report.
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_cleaning_report extends system_report {
    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters.
     */
    protected function initialise(): void {
        $entity = new users_log();
        $tablealias = $entity->get_table_alias('tool_bulkcleaning_users');

        $this->set_main_table('tool_bulkcleaning_users', $tablealias);
        $this->add_entity($entity);

        $entityuser = new user();
        $useralias = $entityuser->get_table_alias('user');
        $this->add_entity($entityuser
            ->add_join("LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$tablealias}.userid"));

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(true, get_string('tab_userscleaning', 'tool_bulkcleaning'));
    }

    /**
     * Validates access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report.
     */
    protected function add_columns(): void {
        $columns = [
            'user:fullnamewithpicturelink',
            'users_log:timecreated',
            'users_log:details',
        ];

        $this->add_columns_from_entities($columns);
        $this->set_initial_sort_column('users_log:timecreated', SORT_DESC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'users_log:timecreated',
            'users_log:details',
        ];

        $this->add_filters_from_entities($filters);
    }
}
