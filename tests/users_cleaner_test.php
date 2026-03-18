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

namespace tool_bulkcleaning;

use tool_bulkcleaning\local\cleaners\users;

/**
 * Tests for the users cleaner.
 *
 * @package    tool_bulkcleaning
 * @category   test
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_bulkcleaning\local\cleaners\users
 */
final class users_cleaner_test extends \advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test that inactive users are suspended and a log record is created.
     */
    public function test_clean_nologin_suspends_inactive_users(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Simulate last access 60 days ago.
        $DB->set_field('user', 'lastaccess', time() - (60 * DAYSECS), ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(1, $updateduser->suspended);

        $logs = $DB->get_records('tool_bulkcleaning_users', ['userid' => $user->id]);
        $this->assertCount(1, $logs);

        $details = json_decode(reset($logs)->details);
        $this->assertEquals(users::CASE_NOLOGIN, $details->case);
        $this->assertEquals(30, $details->days);
    }

    /**
     * Test that recently active users are NOT suspended.
     */
    public function test_clean_nologin_preserves_active_users(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Simulate last access 5 days ago.
        $DB->set_field('user', 'lastaccess', time() - (5 * DAYSECS), ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(0, $updateduser->suspended);
        $this->assertFalse($DB->record_exists('tool_bulkcleaning_users', ['userid' => $user->id]));
    }

    /**
     * Test that already suspended users are NOT processed again.
     */
    public function test_clean_nologin_skips_already_suspended(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['suspended' => 1]);

        // Simulate last access 60 days ago.
        $DB->set_field('user', 'lastaccess', time() - (60 * DAYSECS), ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        // No log should be created for this user because it was already suspended.
        $this->assertFalse($DB->record_exists('tool_bulkcleaning_users', ['userid' => $user->id]));
    }

    /**
     * Test that deleted users are NOT processed.
     */
    public function test_clean_nologin_skips_deleted_users(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $DB->set_field('user', 'lastaccess', time() - (60 * DAYSECS), ['id' => $user->id]);

        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('tool_bulkcleaning_users', ['userid' => $user->id]));
    }

    /**
     * Test that users who never logged in (lastaccess=0) are suspended
     * if their account was created before the threshold.
     */
    public function test_clean_nologin_handles_never_logged_in(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Simulate: never logged in, account created 60 days ago.
        $DB->set_field('user', 'lastaccess', 0, ['id' => $user->id]);
        $DB->set_field('user', 'timecreated', time() - (60 * DAYSECS), ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(1, $updateduser->suspended);
        $this->assertEquals(1, $DB->count_records('tool_bulkcleaning_users', ['userid' => $user->id]));
    }

    /**
     * Test that users who never logged in but were recently created are preserved.
     */
    public function test_clean_nologin_preserves_recent_never_logged_in(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Never logged in, account created 5 days ago.
        $DB->set_field('user', 'lastaccess', 0, ['id' => $user->id]);
        $DB->set_field('user', 'timecreated', time() - (5 * DAYSECS), ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(0, $updateduser->suspended);
        $this->assertFalse($DB->record_exists('tool_bulkcleaning_users', ['userid' => $user->id]));
    }

    /**
     * Test that nothing happens when days config is not set.
     */
    public function test_clean_nologin_no_days_configured(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 0, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $DB->set_field('user', 'lastaccess', time() - (60 * DAYSECS), ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(0, $updateduser->suspended);
        $this->assertFalse($DB->record_exists('tool_bulkcleaning_users', ['userid' => $user->id]));
    }

    /**
     * Test the log details JSON structure.
     */
    public function test_clean_nologin_log_details_structure(): void {
        global $DB;

        set_config('userscleaning_nologin_days', 30, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $lastaccess = time() - (60 * DAYSECS);
        $DB->set_field('user', 'lastaccess', $lastaccess, ['id' => $user->id]);

        ob_start();
        users::clean_nologin();
        ob_end_clean();

        $log = $DB->get_record('tool_bulkcleaning_users', ['userid' => $user->id]);
        $details = json_decode($log->details, true);

        $this->assertArrayHasKey('case', $details);
        $this->assertArrayHasKey('days', $details);
        $this->assertArrayHasKey('lastaccess', $details);
        $this->assertEquals(users::CASE_NOLOGIN, $details['case']);
        $this->assertEquals(30, $details['days']);
        $this->assertEquals($lastaccess, $details['lastaccess']);
    }
}
