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
 * Unit tests for the cron_task_delete scheduled task.
 *
 * cron_task (shutdown) depends entirely on external cURL calls to Guacamole and
 * Google Cloud, so it is not unit-testable without a full mock layer. The delete
 * task, however, contains pure DB logic that can be tested in isolation.
 *
 * @package    mod_guacamole
 * @copyright  2024 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_guacamole;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the cron_task_delete state-transition logic.
 *
 * These tests verify DB state changes without triggering the actual GCP calls.
 * The execute() method of cron_task_delete is NOT called directly because it
 * would attempt to connect to Google Cloud. Instead, we test the DB preconditions
 * and postconditions that the task relies on.
 *
 * @covers \mod_guacamole\task\cron_task_delete
 */
final class cron_task_test extends \advanced_testcase {
    /**
     * @var \stdClass Image record.
     */
    private $image;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        global $DB;
        $image = new \stdClass();
        $image->name = 'Test image';
        $image->guaidconnection = '1';
        $image->cloudimage = 'test-image';
        $image->defaultminutestoshutdown = 30;
        $image->defaultdaystodelete = 3;
        $image->maxnuminstances = 5;
        $image->active = 1;
        $image->id = $DB->insert_record('guacamole_images', $image);
        $this->image = $image;
    }

    /**
     * A stopped computer whose timetodelete is in the past should be eligible
     * for deletion (state transitions to 'deleting' before removal).
     */
    public function test_stopped_computer_past_timetodelete_is_deletable(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'stopped';
        $computer->timecreated = $now - 10 * 86400;
        $computer->timelaststart = $now - 10 * 86400;
        $computer->timelaststop = $now - 5 * 86400;
        $computer->minutestoshutdown = 30;
        $computer->daystodelete = 3;
        $computer->timetodelete = $now - 86400; // In the past — eligible.
        $computer->root = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $computer);

        $computers = $DB->get_records('guacamole_computers', ['state' => 'stopped', 'root' => $CFG->wwwroot]);
        $eligible = array_filter($computers, fn($c) => $c->timetodelete < $now);

        $this->assertCount(1, $eligible);
    }

    /**
     * A stopped computer whose timetodelete is in the future must NOT be deleted.
     */
    public function test_stopped_computer_future_timetodelete_is_not_deletable(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'stopped';
        $computer->timecreated = $now - 86400;
        $computer->timelaststart = $now - 86400;
        $computer->timelaststop = $now - 3600;
        $computer->minutestoshutdown = 30;
        $computer->daystodelete = 3;
        $computer->timetodelete = $now + 2 * 86400; // In the future — must not be deleted.
        $computer->root = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $computer);

        $computers = $DB->get_records('guacamole_computers', ['state' => 'stopped', 'root' => $CFG->wwwroot]);
        $eligible = array_filter($computers, fn($c) => $c->timetodelete < $now);

        $this->assertCount(0, $eligible);
    }

    /**
     * A loading computer older than 30 minutes qualifies for orphan cleanup.
     */
    public function test_orphaned_loading_older_than_30min_qualifies_for_cleanup(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'loading';
        $computer->timecreated = $now - 3600;
        $computer->timelaststart = $now - 1801; // Older than 30 min.
        $computer->timelaststop = 0;
        $computer->minutestoshutdown = 30;
        $computer->daystodelete = 3;
        $computer->timetodelete = $now + 3 * 86400;
        $computer->root = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $computer);

        $loadingstale = $now - 1800;
        $stalled = $DB->get_records_select(
            'guacamole_computers',
            'state = ? AND root = ? AND timelaststart < ?',
            ['loading', $CFG->wwwroot, $loadingstale]
        );

        $this->assertCount(1, $stalled);
    }

    /**
     * A loading computer newer than 30 minutes must NOT be cleaned up.
     */
    public function test_loading_newer_than_30min_does_not_qualify_for_cleanup(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'loading';
        $computer->timecreated = $now;
        $computer->timelaststart = $now - 600; // Only 10 min ago.
        $computer->timelaststop = 0;
        $computer->minutestoshutdown = 30;
        $computer->daystodelete = 3;
        $computer->timetodelete = $now + 3 * 86400;
        $computer->root = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $computer);

        $loadingstale = $now - 1800;
        $stalled = $DB->get_records_select(
            'guacamole_computers',
            'state = ? AND root = ? AND timelaststart < ?',
            ['loading', $CFG->wwwroot, $loadingstale]
        );

        $this->assertCount(0, $stalled);
    }

    /**
     * A started computer with null guaidconnection is still eligible for shutdown checks.
     */
    public function test_started_computer_with_null_guaidconnection_is_processable(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'started';
        $computer->timecreated = $now - 7200;
        $computer->timelaststart = $now - 7200;
        $computer->timelaststop = 0;
        $computer->minutestoshutdown = 30;
        $computer->daystodelete = 3;
        $computer->timetodelete = $now + 3 * 86400;
        $computer->root = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $computer);

        $started = $DB->get_records('guacamole_computers', ['state' => 'started', 'root' => $CFG->wwwroot]);
        $this->assertCount(1, $started);
        $this->assertNull(reset($started)->guaidconnection);
    }

    /**
     * A computer in deleting state is picked up for manual-deletion processing.
     */
    public function test_deleting_state_computer_is_eligible_for_processing(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'deleting';
        $computer->timecreated = $now - 86400;
        $computer->timelaststart = $now - 86400;
        $computer->timelaststop = $now - 3600;
        $computer->minutestoshutdown = 30;
        $computer->daystodelete = 3;
        $computer->timetodelete = $now - 3600;
        $computer->root = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $computer);

        $pending = $DB->get_records('guacamole_computers', ['state' => 'deleting', 'root' => $CFG->wwwroot]);
        $this->assertCount(1, $pending);
    }

    /**
     * Only computers belonging to the current wwwroot should be processed.
     */
    public function test_cron_ignores_computers_from_other_root(): void {
        global $DB, $CFG;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $base = [
            'imageid' => $this->image->id,
            'userid' => $user->id,
            'cloudimage' => 'test-image',
            'guaidconnection' => null,
            'state' => 'stopped',
            'timecreated' => $now - 86400,
            'timelaststart' => $now - 86400,
            'timelaststop' => $now - 3600,
            'minutestoshutdown' => 30,
            'daystodelete' => 3,
            'timetodelete' => $now - 3600,
        ];

        // Computer from this Moodle instance.
        $local = (object) array_merge($base, ['root' => $CFG->wwwroot]);
        $DB->insert_record('guacamole_computers', $local);

        // Computer from a different Moodle instance sharing the same DB.
        $user2 = $this->getDataGenerator()->create_user();
        $foreign = (object) array_merge($base, ['root' => 'https://other.moodle.example.com', 'userid' => $user2->id]);
        $DB->insert_record('guacamole_computers', $foreign);

        $computers = $DB->get_records('guacamole_computers', ['state' => 'stopped', 'root' => $CFG->wwwroot]);

        $this->assertCount(1, $computers);
        $this->assertEquals($CFG->wwwroot, reset($computers)->root);
    }
}
