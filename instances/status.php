<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * AJAX endpoint that returns whether the user's GCP VM is RUNNING.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

global $CFG, $DB, $USER;
require_once($CFG->dirroot . '/mod/guacamole/instances/lib.php');

$id      = optional_param('id', 0, PARAM_INT);
$cm      = get_coursemodule_from_id('guacamole', $id, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$userid  = optional_param('usr', 0, PARAM_INT);

require_login($course, true, $cm);
require_sesskey();

if ($userid !== (int)$USER->id) {
    echo json_encode(['ready' => false, 'status' => 'forbidden']);
    exit;
}

$imageid = optional_param('img', 0, PARAM_INT);
$image   = $DB->get_record('guacamole_images', ['id' => $imageid]);
$user    = $DB->get_record('user', ['id' => $userid]);

$computername = strtolower($image->cloudimage . '-' . $image->id . '-' . $user->id);
$gcpstatus    = getinstancestatus($computername);

if ($gcpstatus === 'RUNNING') {
    $guacamolecomputer = $DB->get_record('guacamole_computers', ['imageid' => $imageid, 'userid' => $userid]);
    if ($guacamolecomputer && $guacamolecomputer->state !== 'started') {
        $guacamolecomputer->state = 'started';
        $DB->update_record('guacamole_computers', $guacamolecomputer);
    }
    echo json_encode(['ready' => true]);
} else {
    echo json_encode(['ready' => false, 'status' => $gcpstatus]);
}
