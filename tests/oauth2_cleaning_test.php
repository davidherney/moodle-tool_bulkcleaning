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

use tool_bulkcleaning\local\cleaners\oauth2;
use tool_bulkcleaning\task\oauth2_cleaning;

/**
 * Tests for the OAuth2 cleaning scheduled task.
 *
 * @package    tool_bulkcleaning
 * @category   test
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_bulkcleaning\task\oauth2_cleaning
 */
final class oauth2_cleaning_test extends \advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test that the task does not process anything when disabled.
     */
    public function test_execute_disabled_does_nothing(): void {
        global $DB;

        set_config('oauth2cleaning_enabled', 0, 'tool_bulkcleaning');
        set_config('oauth2cleaning_cases', oauth2::CASE_DELETEDUSERS, 'tool_bulkcleaning');

        $user = $this->getDataGenerator()->create_user();
        $issuerid = $this->create_test_issuer_id();
        $this->create_linked_login($user->id, $issuerid, $user->email, $user->id);
        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);

        $task = new oauth2_cleaning();
        ob_start();
        $task->execute();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('auth_oauth2_linked_login', ['userid' => $user->id]));
    }

    /**
     * Test that the task does not process anything when no cases are configured.
     */
    public function test_execute_without_cases_does_nothing(): void {
        global $DB;

        set_config('oauth2cleaning_enabled', 1, 'tool_bulkcleaning');
        set_config('oauth2cleaning_cases', '', 'tool_bulkcleaning');

        $user = $this->getDataGenerator()->create_user();
        $issuerid = $this->create_test_issuer_id();
        $this->create_linked_login($user->id, $issuerid, $user->email, $user->id);
        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);

        $task = new oauth2_cleaning();
        ob_start();
        $task->execute();
        ob_end_clean();

        $this->assertTrue($DB->record_exists('auth_oauth2_linked_login', ['userid' => $user->id]));
    }

    /**
     * Test that deleted and suspended users are cleaned when both cases are selected.
     */
    public function test_execute_cleans_deleted_and_suspended_users(): void {
        global $DB;

        set_config('oauth2cleaning_enabled', 1, 'tool_bulkcleaning');
        set_config(
            'oauth2cleaning_cases',
            oauth2::CASE_DELETEDUSERS . ',' . oauth2::CASE_SUSPENDEDUSERS,
            'tool_bulkcleaning'
        );

        $generator = $this->getDataGenerator();
        $issuerid = $this->create_test_issuer_id();
        $deleteduser = $generator->create_user();
        $suspendeduser = $generator->create_user(['suspended' => 1]);
        $activeuser = $generator->create_user();

        $this->create_linked_login($deleteduser->id, $issuerid, $deleteduser->email, $deleteduser->id);
        $this->create_linked_login($suspendeduser->id, $issuerid, $suspendeduser->email, $suspendeduser->id);
        $this->create_linked_login($activeuser->id, $issuerid, $activeuser->email, $activeuser->id);
        $DB->set_field('user', 'deleted', 1, ['id' => $deleteduser->id]);

        $task = new oauth2_cleaning();
        ob_start();
        $task->execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('auth_oauth2_linked_login', ['userid' => $deleteduser->id]));
        $this->assertFalse($DB->record_exists('auth_oauth2_linked_login', ['userid' => $suspendeduser->id]));
        $this->assertTrue($DB->record_exists('auth_oauth2_linked_login', ['userid' => $activeuser->id]));
    }

    /**
     * Test that email-not-match cleaning only removes automatic relations.
     */
    public function test_execute_cleans_email_not_match_automatic_relations_only(): void {
        global $DB;

        set_config('oauth2cleaning_enabled', 1, 'tool_bulkcleaning');
        set_config('oauth2cleaning_cases', oauth2::CASE_EMAILNOTMATCH, 'tool_bulkcleaning');

        $generator = $this->getDataGenerator();
        $issuerid = $this->create_test_issuer_id();
        $modifier = $generator->create_user();

        $automatic = $generator->create_user(['email' => 'automatic@example.com']);
        $manual = $generator->create_user(['email' => 'manual@example.com']);
        $matching = $generator->create_user(['email' => 'matching@example.com']);

        $automaticid = $this->create_linked_login(
            $automatic->id,
            $issuerid,
            'oauth2-automatic@example.com',
            $automatic->id
        );
        $manualid = $this->create_linked_login(
            $manual->id,
            $issuerid,
            'oauth2-manual@example.com',
            $modifier->id
        );
        $matchingid = $this->create_linked_login(
            $matching->id,
            $issuerid,
            $matching->email,
            $matching->id
        );

        $task = new oauth2_cleaning();
        ob_start();
        $task->execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('auth_oauth2_linked_login', ['id' => $automaticid]));
        $this->assertTrue($DB->record_exists('auth_oauth2_linked_login', ['id' => $manualid]));
        $this->assertTrue($DB->record_exists('auth_oauth2_linked_login', ['id' => $matchingid]));
    }

    /**
     * Create a linked login for a user.
     *
     * @param int $userid
     * @param int $issuerid
     * @param string $email
     * @param int $usermodified
     * @return int linked login id
     */
    private function create_linked_login(int $userid, int $issuerid, string $email, int $usermodified): int {
        global $DB;

        $now = time();
        return $DB->insert_record('auth_oauth2_linked_login', (object) [
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $usermodified,
            'userid' => $userid,
            'issuerid' => $issuerid,
            'username' => 'u' . $userid . '_' . substr(md5($email . $now), 0, 8),
            'email' => $email,
            'confirmtoken' => '',
            'confirmtokenexpires' => null,
        ]);
    }

    /**
     * Create a valid OAuth2 issuer and return its id.
     *
     * @return int
     */
    private function create_test_issuer_id(): int {
        $this->setAdminUser();

        $issuer = \core\oauth2\api::create_issuer((object) [
            'name' => 'Bulk cleaning issuer ' . uniqid('', true),
            'clientid' => 'clientid',
            'clientsecret' => 'secret',
            'image' => '',
            'showonloginpage' => 1,
        ]);

        return (int) $issuer->get('id');
    }
}
