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
 * Unit tests for guacamole_images CRUD (backing editimage.php).
 *
 * @package    mod_guacamole
 * @copyright  2024 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_guacamole;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the guacamole_images table operations performed by editimage.php.
 */
class image_form_test extends \advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Inserting a new image record persists all fields correctly.
     */
    public function test_insert_image_record(): void {
        global $DB;

        $data = new \stdClass();
        $data->name = 'Ubuntu 22.04 lab';
        $data->guaidconnection = '5';
        $data->cloudimage = 'ubuntu-2204-base';
        $data->defaultminutestoshutdown = 120;
        $data->defaultdaystodelete = 14;
        $data->maxnuminstances = 10;
        $data->active = 1;

        $id = $DB->insert_record('guacamole_images', $data);

        $saved = $DB->get_record('guacamole_images', ['id' => $id]);
        $this->assertEquals('Ubuntu 22.04 lab', $saved->name);
        $this->assertEquals('ubuntu-2204-base', $saved->cloudimage);
        $this->assertEquals(120, $saved->defaultminutestoshutdown);
        $this->assertEquals(14, $saved->defaultdaystodelete);
        $this->assertEquals(10, $saved->maxnuminstances);
        $this->assertEquals(1, $saved->active);
    }

    /**
     * Updating an existing image record reflects the new values.
     */
    public function test_update_image_record(): void {
        global $DB;

        $data = new \stdClass();
        $data->name = 'Original name';
        $data->guaidconnection = '3';
        $data->cloudimage = 'old-image';
        $data->defaultminutestoshutdown = 60;
        $data->defaultdaystodelete = 7;
        $data->maxnuminstances = 5;
        $data->active = 1;
        $data->id = $DB->insert_record('guacamole_images', $data);

        $data->name = 'Updated name';
        $data->defaultminutestoshutdown = 90;
        $data->active = 0;
        $DB->update_record('guacamole_images', $data);

        $updated = $DB->get_record('guacamole_images', ['id' => $data->id]);
        $this->assertEquals('Updated name', $updated->name);
        $this->assertEquals(90, $updated->defaultminutestoshutdown);
        $this->assertEquals(0, $updated->active);
    }

    /**
     * An inactive image (active=0) is distinguishable from an active one.
     */
    public function test_inactive_image_is_retrievable(): void {
        global $DB;

        $data = new \stdClass();
        $data->name = 'Inactive image';
        $data->guaidconnection = '7';
        $data->cloudimage = 'inactive-image';
        $data->defaultminutestoshutdown = 30;
        $data->defaultdaystodelete = 3;
        $data->maxnuminstances = 2;
        $data->active = 0;
        $DB->insert_record('guacamole_images', $data);

        $activeImages = $DB->get_records('guacamole_images', ['active' => 1]);
        $inactiveImages = $DB->get_records('guacamole_images', ['active' => 0]);

        $this->assertCount(0, $activeImages);
        $this->assertCount(1, $inactiveImages);
    }

    /**
     * maxnuminstances limits how many computers can be created for one image.
     * This test verifies the field is stored and readable correctly.
     */
    public function test_max_instances_field(): void {
        global $DB;

        $data = new \stdClass();
        $data->name = 'Limited image';
        $data->guaidconnection = '9';
        $data->cloudimage = 'limited-image';
        $data->defaultminutestoshutdown = 60;
        $data->defaultdaystodelete = 7;
        $data->maxnuminstances = 3;
        $data->active = 1;
        $id = $DB->insert_record('guacamole_images', $data);

        $saved = $DB->get_record('guacamole_images', ['id' => $id]);
        $this->assertEquals(3, (int) $saved->maxnuminstances);
    }
}
