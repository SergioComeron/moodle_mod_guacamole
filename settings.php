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
 * Plugin settings for mod_guacamole.
 *
 * @package   mod_guacamole
 * @copyright  2019 Sergio Comerón (sergiocomeron@icloud.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/guacamole/lib.php');

    $settings->add(new admin_setting_configtext('guacamole_user', get_string('username', 'guacamole'), get_string('username', 'guacamole'), null));
    $settings->add(new admin_setting_configpasswordunmask('guacamole_password', get_string('userpass', 'guacamole'), get_string('userpass', 'guacamole'), null));
    $settings->add(new admin_setting_configtext('guacamole_project_cloud', get_string('projectcloud', 'guacamole'), get_string('projectcloudex', 'guacamole'), null));
    $settings->add(new admin_setting_configtext('guacamole_domain', get_string('domainserver', 'guacamole'), get_string('domainserverex', 'guacamole'), null));
    $settings->add(new admin_setting_configtext(
        'guacamole_zone_cloud',
        get_string('proyectcloudzone', 'guacamole'),
        get_string('proyectcloudzone', 'guacamole'),
        'europe-west1-b'
    ));
    $settings->add(new admin_setting_configtext('guacamole_template_group', get_string('templatesgroup', 'guacamole'), get_string('templatesgroupex', 'guacamole'), 'imagenes'));
    $settings->add(new admin_setting_configtext('guacamole_default_max_connections', get_string('numberofinstances', 'guacamole'), null, null));
    $settings->add(new admin_setting_configtext('guacamole_default_minutes_to_shutdown', get_string('defaulttimetoshutdown', 'guacamole'), null, null));
    $settings->add(new admin_setting_configtext('guacamole_default_days_to_delete', get_string('defaultdaystodelete', 'guacamole'), null, null));
    $settings->add(new admin_setting_configtext('guacamole_seconds_wait', get_string('secondswait', 'guacamole'), get_string('secondswaitex', 'guacamole'), '50'));
    $settings->add(new admin_setting_configtext('guacamole_machine_type', get_string('gcpmachinetype', 'guacamole'), get_string('gcpmachinetypeex', 'guacamole'), 'n2d-custom-2-6144'));
    $settings->add(new admin_setting_configtext('guacamole_disk_type', get_string('gcpdisktype', 'guacamole'), get_string('gcpdisktypeex', 'guacamole'), 'pd-ssd'));
    $settings->add(new admin_setting_confightmleditor('guacamole_help', get_string('help', 'guacamole'), get_string('helpex', 'guacamole'), null));

    $settings->add(new admin_setting_configstoredfile('mod_guacamole/jsonfile', 'json file', get_string('jsonfile', 'guacamole'), 'jsonfile'));

    $link = '<a href="' . $CFG->wwwroot . '/mod/guacamole/manageimages.php">' . get_string('manageimages', 'guacamole') . '</a>';
    $link2 = '<a href="' . $CFG->wwwroot . '/mod/guacamole/showimages.php">' . get_string('showimages', 'guacamole') . '</a>';

    $settings->add(new admin_setting_heading('guacamole_addimages', '', $link));
    $settings->add(new admin_setting_heading('guacamole_showimages', '', $link2));
}
