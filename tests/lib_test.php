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
 * Unit tests for mod/guacamole/lib.php
 *
 * @package    mod_guacamole
 * @copyright  2024 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_guacamole;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/guacamole/lib.php');

/**
 * Tests for lib.php functions that interact with the database.
 *
 * @covers ::guacamole_add_instance
 * @covers ::guacamole_update_instance
 * @covers ::guacamole_delete_instance
 * @covers ::getComputersUsed
 * @covers ::computerStartedByUser
 */
final class lib_test extends \advanced_testcase {
    /**
     * @var \stdClass Course used across tests.
     */
    private $course;

    /**
     * @var \stdClass Guacamole image record used across tests.
     */
    private $image;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();

        // Create a guacamole_images record directly.
        global $DB;
        $this->image = new \stdClass();
        $this->image->name = 'Test image';
        $this->image->guaidconnection = '1';
        $this->image->cloudimage = 'test-image';
        $this->image->defaultminutestoshutdown = 60;
        $this->image->defaultdaystodelete = 7;
        $this->image->maxnuminstances = 5;
        $this->image->active = 1;
        $this->image->id = $DB->insert_record('guacamole_images', $this->image);
    }

    /**
     * Test that guacamole_add_instance saves a record and returns its id.
     */
    public function test_add_instance_returns_id(): void {
        global $DB;

        $moduleinfo = $this->getDataGenerator()->create_module('guacamole', [
            'course' => $this->course->id,
            'imageid' => $this->image->id,
            'minutestoshutdown' => 60,
            'daystodelete' => 7,
        ]);

        $this->assertNotEmpty($moduleinfo->id);
        $this->assertTrue($DB->record_exists('guacamole', ['id' => $moduleinfo->id]));
    }

    /**
     * Test that guacamole_delete_instance removes the record.
     */
    public function test_delete_instance_removes_record(): void {
        global $DB;

        $moduleinfo = $this->getDataGenerator()->create_module('guacamole', [
            'course' => $this->course->id,
            'imageid' => $this->image->id,
            'minutestoshutdown' => 60,
            'daystodelete' => 7,
        ]);

        guacamole_delete_instance($moduleinfo->id);

        $this->assertFalse($DB->record_exists('guacamole', ['id' => $moduleinfo->id]));
    }

    /**
     * Test getComputersUsed counts only started/loading/shutdown states.
     */
    public function test_get_computers_used_counts_active_states(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $base = [
            'imageid' => $this->image->id,
            'userid' => $user->id,
            'cloudimage' => 'test-image',
            'guaidconnection' => null,
            'timecreated' => $now,
            'timelaststart' => $now,
            'timelaststop' => 0,
            'minutestoshutdown' => 60,
            'daystodelete' => 7,
            'timetodelete' => $now + 7 * 86400,
            'root' => 'http://localhost',
        ];

        // Insert one computer per state.
        foreach (['started', 'loading', 'shutdown', 'stopped'] as $state) {
            $record = (object) array_merge($base, ['state' => $state]);
            // Use different userids to bypass the unique key (imageid, userid).
            $record->userid = $this->getDataGenerator()->create_user()->id;
            $DB->insert_record('guacamole_computers', $record);
        }

        // Started + loading + shutdown = 3; stopped should not count.
        $this->assertEquals(3, getComputersUsed($this->image->id));
    }

    /**
     * Test computerStartedByUser returns the record when state is started.
     */
    public function test_computer_started_by_user_returns_record(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = '42';
        $computer->state = 'started';
        $computer->timecreated = $now;
        $computer->timelaststart = $now;
        $computer->timelaststop = 0;
        $computer->minutestoshutdown = 60;
        $computer->daystodelete = 7;
        $computer->timetodelete = $now + 7 * 86400;
        $computer->root = 'http://localhost';
        $DB->insert_record('guacamole_computers', $computer);

        $result = computerStartedByUser($user->id, $this->image->id);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->userid);
        $this->assertEquals('started', $result->state);
    }

    /**
     * Test computerStartedByUser returns null when no started machine exists.
     */
    public function test_computer_started_by_user_returns_null_when_stopped(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $computer = new \stdClass();
        $computer->imageid = $this->image->id;
        $computer->userid = $user->id;
        $computer->cloudimage = 'test-image';
        $computer->guaidconnection = null;
        $computer->state = 'stopped';
        $computer->timecreated = $now;
        $computer->timelaststart = $now;
        $computer->timelaststop = $now;
        $computer->minutestoshutdown = 60;
        $computer->daystodelete = 7;
        $computer->timetodelete = $now + 7 * 86400;
        $computer->root = 'http://localhost';
        $DB->insert_record('guacamole_computers', $computer);

        $result = computerStartedByUser($user->id, $this->image->id);

        $this->assertNull($result);
    }
}
