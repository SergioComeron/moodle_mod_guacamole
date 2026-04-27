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
 * Loading page that triggers VM startup via AJAX and redirects to Guacamole.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->dirroot . '/mod/guacamole/instances/lib.php');

$id         = optional_param('id', 0, PARAM_INT);
$gu         = optional_param('gu', 0, PARAM_INT);
$computerid = optional_param('comp', 0, PARAM_INT);
$imageid    = optional_param('img', 0, PARAM_INT);
$userid     = optional_param('usr', 0, PARAM_INT);

$cm     = get_coursemodule_from_id('guacamole', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/guacamole/instances/start.php', [
    'id' => $id, 'img' => $imageid, 'usr' => $userid, 'gu' => $gu, 'comp' => $computerid,
]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('modulename', 'guacamole'));

$guacamoleimage    = $DB->get_record('guacamole_images', ['id' => $imageid]);
$instancesavailables = $guacamoleimage->maxnuminstances - getComputersUsed($imageid);
$guacamolecomputer = $DB->get_record('guacamole_computers', ['id' => $computerid]);

echo $OUTPUT->header();

$stateblocked = $guacamolecomputer != null &&
    in_array($guacamolecomputer->state, ['deleting', 'loading', 'shutdown']);
if ($stateblocked) {
    echo '<br><br><br><br>';
    echo '<h3 style="text-align:center;">' . get_string('trylater', 'guacamole') . '</h3>';
} else if ($instancesavailables > 0 || computerStartedByUser($userid, $imageid) != null) {
    $startstr    = get_string('starting', 'guacamole');
    $redirectstr = get_string('redirectedfewminutes', 'guacamole');
    $loadinghtml = '<p style="text-align:center;"><img src="../loading.gif"/></p>'
        . '<h3 style="text-align:center;">' . $startstr . '</h3>'
        . '<p style="text-align:center;">' . $redirectstr . '</p>';

    $params = json_encode([
        'img'     => $imageid,
        'usr'     => $userid,
        'id'      => $id,
        'gu'      => $gu,
        'comp'    => $computerid,
        'sesskey' => sesskey(),
    ]);

    echo '<link rel="stylesheet" type="text/css" href="../styles.css" media="screen" />';
    echo '<div id="wait">' . $loadinghtml . '</div>';
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '  var params = new URLSearchParams(' . $params . ');';
    echo '  fetch("./load.php", {method: "POST", body: params})';
    echo '    .then(function(r) { return r.json(); })';
    echo '    .then(function(data) { document.location.href = data.urlG; });';
    echo '});';
    echo '</script>';
} else {
    echo '<br><br><br><br>';
    echo '<h3 style="text-align:center;">' . get_string('noavailable', 'guacamole') . '</h3>';
}

echo $OUTPUT->footer();
