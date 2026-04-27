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
 * Opens in a new blank tab: minimal HTML, no Moodle chrome.
 * require_login() ensures the session is initialized before any output.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

global $CFG, $DB, $PAGE;
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

$guacamoleimage      = $DB->get_record('guacamole_images', ['id' => $imageid]);
$instancesavailables = $guacamoleimage->maxnuminstances - getComputersUsed($imageid);
$guacamolecomputer   = $DB->get_record('guacamole_computers', ['id' => $computerid]);

$stateblocked = $guacamolecomputer != null &&
    in_array($guacamolecomputer->state, ['deleting', 'loading', 'shutdown']);

echo '<!DOCTYPE html>';
echo '<html><head>';
echo '<meta charset="utf-8">';
echo '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/guacamole/styles.css" media="screen" />';
echo '</head><body>';

if ($stateblocked) {
    echo '<br><br><br><br>';
    echo '<h3 style="text-align:center;">' . get_string('trylater', 'guacamole') . '</h3>';
} else if ($instancesavailables > 0 || computerStartedByUser($userid, $imageid) != null) {
    $startstr    = get_string('starting', 'guacamole');
    $redirectstr = get_string('redirectedfewminutes', 'guacamole');
    $loadinghtml = '<p style="text-align:center;"><img src="' . $CFG->wwwroot . '/mod/guacamole/loading.gif"/></p>'
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

    echo '<div id="wait">' . $loadinghtml . '</div>';
    $errmsg = get_string('guacamoleautherror', 'mod_guacamole');
    echo '<div id="error-msg" style="display:none;text-align:center;color:red;padding:2em;">'
        . s($errmsg) . '</div>';
    $statusurl = json_encode($CFG->wwwroot . '/mod/guacamole/instances/status.php');
    $loadurl   = json_encode($CFG->wwwroot . '/mod/guacamole/instances/load.php');
    $statparams = json_encode([
        'id'      => $id,
        'img'     => $imageid,
        'usr'     => $userid,
        'sesskey' => sesskey(),
    ]);

    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '  var loadParams = new URLSearchParams(' . $params . ');';
    echo '  var statusParams = new URLSearchParams(' . $statparams . ');';
    echo '  var urlG = null;';
    echo '  function showError(msg) {';
    echo '    document.getElementById("wait").style.display = "none";';
    echo '    var el = document.getElementById("error-msg");';
    echo '    if (msg) { el.textContent = msg; }';
    echo '    el.style.display = "block";';
    echo '  }';
    echo '  function pollStatus() {';
    echo '    fetch(' . $statusurl . ', {method: "POST", body: statusParams})';
    echo '      .then(function(r) { return r.json(); })';
    echo '      .then(function(d) {';
    echo '        if (d.ready) { document.location.href = urlG; }';
    echo '        else { setTimeout(pollStatus, 5000); }';
    echo '      })';
    echo '      .catch(function() { setTimeout(pollStatus, 5000); });';
    echo '  }';
    echo '  fetch(' . $loadurl . ', {method: "POST", body: loadParams})';
    echo '    .then(function(r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })';
    echo '    .then(function(data) {';
    echo '      if (data.error) { throw new Error(data.error); }';
    echo '      urlG = data.urlG;';
    echo '      setTimeout(pollStatus, 5000);';
    echo '    })';
    echo '    .catch(function(e) { showError(e && e.message ? e.message : null); });';
    echo '});';
    echo '</script>';
} else {
    echo '<br><br><br><br>';
    echo '<h3 style="text-align:center;">' . get_string('noavailable', 'guacamole') . '</h3>';
}

echo '</body></html>';
