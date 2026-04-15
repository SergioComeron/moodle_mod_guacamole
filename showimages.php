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
 * Script to let a user manage their RSS feeds.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once('./instances/lib.php');
require_once('./lib.php');

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$deletecomputerid = optional_param('deletecomputerid', 0, PARAM_INT);

if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

$managesharedfeeds = has_capability('block/rss_client:manageanyfeeds', $context);
if (!$managesharedfeeds) {
    require_capability('block/rss_client:manageownfeeds', $context);
}

$urlparams = [];
$extraparams = '';
if ($courseid) {
    $urlparams['courseid'] = $courseid;
    $extraparams = '&courseid=' . $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
    $extraparams = '&returnurl=' . $returnurl;
}
$baseurl = new moodle_url('/mod/guacamole/showimages.php', $urlparams);
$PAGE->set_url($baseurl);

if ($deletecomputerid && confirm_sesskey()) {
    $guacamolecomputer = $DB->get_record('guacamole_computers', ['id' => $deletecomputerid]);
    while ($guacamolecomputer->state == 'loading' || $guacamolecomputer->state == 'loading') {
        $guacamolecomputer = $DB->get_record('guacamole_computers', ['id' => $deletecomputerid]);
        sleep(1);
    }
    $guacamolecomputer->state = 'deleting';
    $DB->update_record('guacamole_computers', $guacamolecomputer);
    eliminarConexion($guacamolecomputer->guaidconnection);
    stopinstance($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
    $DB->delete_records('guacamole_computers', ['id' => $deletecomputerid]);
    redirect($PAGE->url, get_string('imagedeleted', 'guacamole'));
}

$strmanage = get_string('showimages', 'guacamole');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

$showimages = new moodle_url('/mod/guacamole/showimages.php', $urlparams);
$PAGE->navbar->add(get_string('mod', 'guacamole'));
$PAGE->navbar->add(get_string('modulename', 'guacamole'));
$PAGE->navbar->add(get_string('showimages', 'guacamole'), $showimages);
echo $OUTPUT->header();

$table = new html_table();
$table->head = ['Nombre', 'Estado', get_string('actions', 'moodle')];
$computers = $DB->get_records('guacamole_computers', []);

foreach ($computers as $computer) {
    $deleteurl = new moodle_url('/mod/guacamole/showimages.php?deletecomputerid=' . $computer->id . '&sesskey=' . sesskey());
    $deleteicon = new pix_icon('t/delete', get_string('delete'));
    $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('deleteimageconfirm', 'guacamole')));
    $imageicons = $deleteaction;


    $table->data[] = [$computer->cloudimage . '-' . $computer->imageid . '-' . $computer->userid, $computer->state, $imageicons];
}
echo html_writer::table($table);




echo $OUTPUT->footer();
