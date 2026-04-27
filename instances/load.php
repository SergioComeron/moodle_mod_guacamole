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
 * AJAX handler that creates or resumes a VM and returns the Guacamole URL.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

global $CFG, $DB;
require_once($CFG->dirroot . '/mod/guacamole/instances/lib.php');

$id         = optional_param('id', 0, PARAM_INT);
$cm         = get_coursemodule_from_id('guacamole', $id, 0, false, MUST_EXIST);
$gu         = optional_param('gu', 0, PARAM_INT);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$imageid    = optional_param('img', 0, PARAM_INT);
$computerid = optional_param('comp', 0, PARAM_INT);
$userid     = optional_param('usr', 0, PARAM_INT);

require_login($course, true, $cm);
require_sesskey();

if ($userid !== (int)$USER->id) {
    throw new moodle_exception('accessdenied', 'admin');
}

$PAGE->set_url('/mod/guacamole/start.php');

// createInstance() blocks waiting for GCP disk+VM operations (can take minutes).
set_time_limit(0);

try {
    $user      = $DB->get_record('user', ['id' => $userid]);
    $guacamole = $DB->get_record('guacamole', ['id' => $gu]);
    $image     = $DB->get_record('guacamole_images', ['id' => $imageid]);

    $guacamolecomputer = null;
    if ($computerid == 0) {
        $computername      = $image->cloudimage . '-' . $image->id . '-' . $user->id;
        $guacamolecomputer = $DB->get_record('guacamole_computers', ['imageid' => $imageid, 'userid' => $userid]);
        if ($guacamolecomputer != null && $guacamolecomputer->cloudimage != $image->cloudimage) {
            $guacamolecomputer->state = 'deleting';
            $DB->update_record('guacamole_computers', $guacamolecomputer);
            stopinstance($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
            $DB->delete_records('guacamole_computers', ['imageid' => $guacamolecomputer->imageid, 'userid' => $guacamolecomputer->userid]);
            $guacamolecomputer = null;
        }
    } else {
        $guacamolecomputer = $DB->get_record('guacamole_computers', ['id' => $computerid]);
        $computername      = $guacamolecomputer->cloudimage . '-' . $image->id . '-' . $user->id;
    }

    $oldstate = null;
    if (existDisk($computername) == false) {
        $oldstate     = 'stopped';
        $timecreated  = time();

        $guacamolecomputer                    = new stdClass();
        $guacamolecomputer->imageid           = $image->id;
        $guacamolecomputer->userid            = $user->id;
        $guacamolecomputer->cloudimage        = $image->cloudimage;
        $guacamolecomputer->state             = 'loading';
        $guacamolecomputer->timecreated       = $timecreated;
        $guacamolecomputer->timelaststart     = $timecreated;
        $guacamolecomputer->minutestoshutdown = $guacamole->minutestoshutdown;
        $guacamolecomputer->daystodelete      = $guacamole->daystodelete;
        $guacamolecomputer->timetodelete      = $timecreated + ($guacamole->daystodelete * 60 * 60 * 24);
        $guacamolecomputer->root              = $CFG->wwwroot;
        $DB->insert_record('guacamole_computers', $guacamolecomputer);

        createInstance($image->id, $user->id);
        $computername  = strtolower($computername);
        crearUsuario($user->username);
        $guaidconnection = crearConexion($image->id, $user->id, $computername);
        if (empty($guaidconnection)) {
            $guaidconnection = obtenerIdInstanciaGuacamole($computername);
        }
        darPermiso($guaidconnection, $user->username);

        $guacamolecomputer                  = $DB->get_record('guacamole_computers', ['imageid' => $image->id, 'userid' => $user->id]);
        $guacamolecomputer->guaidconnection = $guaidconnection;
        $DB->update_record('guacamole_computers', $guacamolecomputer);
    } else {
        $timestarted   = time();
        $computername  = strtolower($computername);
        $guacamolecomputer = $DB->get_record('guacamole_computers', ['imageid' => $image->id, 'userid' => $user->id]);

        if (!$guacamolecomputer) {
            // Disk exists in GCP but DB record is missing — recreate it.
            $guacamolecomputer                    = new stdClass();
            $guacamolecomputer->imageid           = $image->id;
            $guacamolecomputer->userid            = $user->id;
            $guacamolecomputer->cloudimage        = $image->cloudimage;
            $guacamolecomputer->state             = 'stopped';
            $guacamolecomputer->timecreated       = $timestarted;
            $guacamolecomputer->timelaststart     = $timestarted;
            $guacamolecomputer->minutestoshutdown = $guacamole->minutestoshutdown;
            $guacamolecomputer->daystodelete      = $guacamole->daystodelete;
            $guacamolecomputer->timetodelete      = $timestarted + ($guacamole->daystodelete * 60 * 60 * 24);
            $guacamolecomputer->root              = $CFG->wwwroot;
            $guacamolecomputer->guaidconnection   = '';
            $DB->insert_record('guacamole_computers', $guacamolecomputer);
            $guacamolecomputer = $DB->get_record('guacamole_computers', ['imageid' => $image->id, 'userid' => $user->id]);
        }

        $oldstate = $guacamolecomputer->state;
        if (!empty($guacamolecomputer->guaidconnection)) {
            $guaidconnection = $guacamolecomputer->guaidconnection;
        } else {
            $guaidconnection = crearConexion($image->id, $user->id, $computername);
            if (empty($guaidconnection)) {
                $guaidconnection = obtenerIdInstanciaGuacamole($computername);
            }
        }

        $guacamolecomputer->state          = ($oldstate === 'started') ? 'started' : 'loading';
        $guacamolecomputer->timelaststart  = $timestarted;
        $guacamolecomputer->guaidconnection = $guaidconnection;
        if ($guacamole->daystodelete > $guacamolecomputer->daystodelete) {
            $guacamolecomputer->daystodelete = $guacamole->daystodelete;
        }
        $guacamolecomputer->timetodelete = $timestarted + ($guacamolecomputer->daystodelete * 60 * 60 * 24);
        if ($guacamole->minutestoshutdown > $guacamolecomputer->minutestoshutdown) {
            $guacamolecomputer->minutestoshutdown = $guacamole->minutestoshutdown;
        }
        crearUsuario($user->username);
        if ($oldstate != 'started') {
            darPermiso($guacamolecomputer->guaidconnection, $user->username);
        }
        $DB->update_record('guacamole_computers', $guacamolecomputer);
    }

    // Determine wait time: new VM needs full wait, restart half, already running = 0.
    $isnew = ($oldstate === 'stopped' && !existInstance($computername));
    if ($oldstate === 'started') {
        $waitsecs = 0;
    } else if ($isnew) {
        $waitsecs = (int)$CFG->guacamole_seconds_wait;
    } else {
        $waitsecs = (int)($CFG->guacamole_seconds_wait / 2);
    }

    $type     = 'c';
    $database = 'mysql';
    $str      = $guaidconnection . "\0" . $type . "\0" . $database;
    $urlg     = $CFG->guacamole_domain . '/guacamole/#/client/' . base64_encode($str);

    if ($oldstate !== 'started') {
        startinstance($computername);
    }

    $varr             = [];
    $varr['urlG']     = $urlg;
    $varr['isNew']    = $isnew;
    $varr['waitSecs'] = $waitsecs;
    echo json_encode($varr);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['error' => get_class($e) . ': ' . $e->getMessage()]);
}
