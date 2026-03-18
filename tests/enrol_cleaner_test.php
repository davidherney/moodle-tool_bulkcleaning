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

use tool_bulkcleaning\local\cleaners\enrol;

/**
 * Tests for the enrol cleaner.
 *
 * @package    tool_bulkcleaning
 * @category   test
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_bulkcleaning\local\cleaners\enrol
 */
final class enrol_cleaner_test extends \advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test that deleted users are unenrolled and a log record is created.
     */
    public function test_clean_deleted_users_unenrols_and_logs(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($user->id, $course->id, $studentrole->id);

        // Confirm enrolment exists.
        $this->assertTrue($DB->record_exists('user_enrolments', ['userid' => $user->id]));

        // Simulate orphaned data: user marked deleted but enrolments remain.
        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);

        ob_start();
        enrol::clean_deleted_users();
        ob_end_clean();

        // The enrolment should be removed.
        $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $user->id]));

        // A log record should exist.
        $logs = $DB->get_records('tool_bulkcleaning_enrol', ['userid' => $user->id]);
        $this->assertCount(1, $logs);

        $log = reset($logs);
        $this->assertEquals($course->id, $log->courseid);

        $details = json_decode($log->details);
        $this->assertEquals(enrol::CASE_DELETEDUSERS, $details->case);
        $this->assertEquals('manual', $details->enrol);
    }

    /**
     * Test that active (non-deleted) users are NOT unenrolled by clean_deleted_users.
     */
    public function test_clean_deleted_users_preserves_active_users(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activeuser = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($activeuser->id, $course->id, $studentrole->id);

        ob_start();
        enrol::clean_deleted_users();
        ob_end_clean();

        // The active user must remain enrolled.
        $this->assertTrue($DB->record_exists('user_enrolments', ['userid' => $activeuser->id]));
        $this->assertEquals(0, $DB->count_records('tool_bulkcleaning_enrol'));
    }

    /**
     * Test that suspended users are unenrolled and a log record is created.
     */
    public function test_clean_suspended_users_unenrols_and_logs(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user(['suspended' => 1]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($user->id, $course->id, $studentrole->id);

        ob_start();
        enrol::clean_suspended_users();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $user->id]));

        $logs = $DB->get_records('tool_bulkcleaning_enrol', ['userid' => $user->id]);
        $this->assertCount(1, $logs);

        $details = json_decode(reset($logs)->details);
        $this->assertEquals(enrol::CASE_SUSPENDEDUSERS, $details->case);
    }

    /**
     * Test that active (non-suspended) users are NOT unenrolled by clean_suspended_users.
     */
    public function test_clean_suspended_users_preserves_active_users(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activeuser = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($activeuser->id, $course->id, $studentrole->id);

        ob_start();
        enrol::clean_suspended_users();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_enrolments', ['userid' => $activeuser->id]));
        $this->assertEquals(0, $DB->count_records('tool_bulkcleaning_enrol'));
    }

    /**
     * Test that expired enrolments are removed and logged.
     */
    public function test_clean_expired_enrolments_unenrols_and_logs(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Enrol with an end time in the past.
        $pasttime = time() - DAYSECS;
        $generator->enrol_user($user->id, $course->id, $studentrole->id, 'manual', 0, $pasttime);

        ob_start();
        enrol::clean_expired_enrolments();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $user->id]));

        $logs = $DB->get_records('tool_bulkcleaning_enrol', ['userid' => $user->id]);
        $this->assertCount(1, $logs);

        $details = json_decode(reset($logs)->details);
        $this->assertEquals(enrol::CASE_EXPIREDENROLS, $details->case);
        $this->assertEquals($pasttime, $details->timeend);
    }

    /**
     * Test that enrolments with future end time are NOT removed by clean_expired_enrolments.
     */
    public function test_clean_expired_enrolments_preserves_future(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Enrol with an end time in the future.
        $futuretime = time() + (30 * DAYSECS);
        $generator->enrol_user($user->id, $course->id, $studentrole->id, 'manual', 0, $futuretime);

        ob_start();
        enrol::clean_expired_enrolments();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_enrolments', ['userid' => $user->id]));
        $this->assertEquals(0, $DB->count_records('tool_bulkcleaning_enrol'));
    }

    /**
     * Test that enrolments with no end time (timeend=0) are NOT removed.
     */
    public function test_clean_expired_enrolments_preserves_no_endtime(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($user->id, $course->id, $studentrole->id);

        ob_start();
        enrol::clean_expired_enrolments();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('user_enrolments', ['userid' => $user->id]));
        $this->assertEquals(0, $DB->count_records('tool_bulkcleaning_enrol'));
    }

    /**
     * Test that the log details JSON contains all expected fields.
     */
    public function test_save_log_details_structure(): void {
        global $DB;

        enrol::save_log(999, 888, enrol::CASE_DELETEDUSERS, 'manual', 5, 1000, 2000);

        $log = $DB->get_record('tool_bulkcleaning_enrol', ['userid' => 999]);
        $this->assertNotFalse($log);

        $details = json_decode($log->details, true);
        $this->assertEquals(enrol::CASE_DELETEDUSERS, $details['case']);
        $this->assertEquals('manual', $details['enrol']);
        $this->assertEquals(5, $details['roleid']);
        $this->assertEquals(1000, $details['timestart']);
        $this->assertEquals(2000, $details['timeend']);
    }

    /**
     * Test that multiple enrolments in different courses for the same deleted user are all cleaned.
     */
    public function test_clean_deleted_users_multiple_courses(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $user = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $generator->enrol_user($user->id, $course1->id, $studentrole->id);
        $generator->enrol_user($user->id, $course2->id, $studentrole->id);

        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);

        ob_start();
        enrol::clean_deleted_users();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $user->id]));
        $this->assertEquals(2, $DB->count_records('tool_bulkcleaning_enrol', ['userid' => $user->id]));
    }
}
