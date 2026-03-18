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
 * Scheduled task for users cleaning.
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkcleaning\task;

use tool_bulkcleaning\local\cleaners\users;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for users cleaning.
 *
 * @package    tool_bulkcleaning
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_cleaning extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_userscleaning', 'tool_bulkcleaning');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        $enabled = get_config('tool_bulkcleaning', 'userscleaning_enabled');
        if (!$enabled) {
            mtrace('Users cleaning is disabled.');
            return;
        }

        $cases = get_config('tool_bulkcleaning', 'userscleaning_cases');
        if (empty($cases)) {
            mtrace('No cleaning cases selected.');
            return;
        }

        $cases = explode(',', $cases);

        foreach ($cases as $case) {
            switch ($case) {
                case users::CASE_NOLOGIN:
                    users::clean_nologin();
                    break;
            }
        }
    }
}
