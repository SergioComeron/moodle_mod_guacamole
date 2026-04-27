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

$title       = get_string('vm_title', 'guacamole');
$creating    = get_string('vm_creating', 'guacamole');
$restarting  = get_string('vm_restarting', 'guacamole');
$errmsg      = get_string('guacamoleautherror', 'mod_guacamole');
$trylater = get_string('trylater', 'guacamole');
$noavail  = get_string('noavailable', 'guacamole');

echo '<!DOCTYPE html>';
echo '<html lang="' . current_language() . '"><head>';
echo '<meta charset="utf-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>' . s($title) . '</title>';
echo '<style>';
echo '* { margin:0; padding:0; box-sizing:border-box; }';
echo 'body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;';
echo '       background:#f0f4f8; display:flex; align-items:center;';
echo '       justify-content:center; min-height:100vh; }';
echo '.card { background:#fff; border-radius:12px;';
echo '        box-shadow:0 4px 24px rgba(0,0,0,.10);';
echo '        padding:3rem 2.5rem; max-width:480px; width:90%; text-align:center; }';
echo '.icon { font-size:2.5rem; margin-bottom:1rem; }';
echo 'h1 { font-size:1.3rem; font-weight:600; color:#1a2b3c; margin-bottom:.5rem; }';
echo '.msg { color:#6b7c93; font-size:.95rem; margin-bottom:2rem;';
echo '       min-height:1.4em; transition:opacity .3s; }';
echo '.bar-wrap { background:#e8edf2; border-radius:999px; height:10px;';
echo '            overflow:hidden; margin-bottom:.75rem; }';
echo '.bar { height:100%; border-radius:999px; width:0%;';
echo '       background:linear-gradient(90deg,#0f6cbf,#29a3e4);';
echo '       transition:width .6s ease; }';
echo '.pct { font-size:.85rem; color:#a0aebb; }';
echo '.error { color:#c0392b; font-size:.95rem; margin-top:1.5rem; display:none; }';
echo '</style>';
echo '</head><body>';
echo '<div class="card">';
echo '<div class="icon">🖥️</div>';
echo '<h1>' . s($title) . '</h1>';

if ($stateblocked) {
    echo '<p class="msg">' . s($trylater) . '</p>';
} else if ($instancesavailables > 0 || computerStartedByUser($userid, $imageid) != null) {
    // Initial message shown before load.php responds (unknown if new or restart).
    echo '<p class="msg" id="status-msg">' . s($creating) . '</p>';
    echo '<div class="bar-wrap"><div class="bar" id="bar"></div></div>';
    echo '<div class="pct" id="pct">0%</div>';
    echo '<p class="error" id="error-msg">' . s($errmsg) . '</p>';

    $params = json_encode([
        'img'     => $imageid,
        'usr'     => $userid,
        'id'      => $id,
        'gu'      => $gu,
        'comp'    => $computerid,
        'sesskey' => sesskey(),
    ]);
    $statparams = json_encode([
        'id'      => $id,
        'img'     => $imageid,
        'usr'     => $userid,
        'sesskey' => sesskey(),
    ]);
    $loadurl   = json_encode($CFG->wwwroot . '/mod/guacamole/instances/load.php');
    $statusurl = json_encode($CFG->wwwroot . '/mod/guacamole/instances/status.php');
    $waittime  = (int)($CFG->guacamole_seconds_wait ?? 30);
    $readymsg  = get_string('vm_ready', 'guacamole');

    echo '<script>';
    echo 'var bar=document.getElementById("bar");';
    echo 'var msg=document.getElementById("status-msg");';
    echo 'var pct=document.getElementById("pct");';
    echo 'var urlG=null;';
    echo 'var progress=0;';
    echo 'var waitSecs=0;'; // Overridden by load.php response.
    echo 'var msgRestarting=' . json_encode($restarting) . ';';

    echo 'function setProgress(p,text){';
    echo '  progress=Math.min(p,100);';
    echo '  bar.style.width=progress+"%";';
    echo '  pct.textContent=Math.round(progress)+"%";';
    echo '  if(text){msg.textContent=text;}';
    echo '}';

    // Phase 1: animate 0→48% slowly over 90 seconds while load.php runs.
    echo 'var phase1=setInterval(function(){';
    echo '  if(progress<48){setProgress(progress+0.53);}';
    echo '  else{clearInterval(phase1);}';
    echo '},1000);';

    echo 'function showError(m){';
    echo '  clearInterval(phase1);';
    echo '  document.getElementById("error-msg").textContent=m||' . json_encode($errmsg) . ';';
    echo '  document.getElementById("error-msg").style.display="block";';
    echo '  msg.style.opacity="0";';
    echo '  bar.style.background="#e74c3c";';
    echo '}';

    echo 'var statusParams=new URLSearchParams(' . $statparams . ');';
    echo 'function pollStatus(){';
    echo '  fetch(' . $statusurl . ',{method:"POST",body:statusParams})';
    echo '    .then(function(r){return r.json();})';
    echo '    .then(function(d){';
    echo '      if(d.message){setProgress(Math.min(progress+3,92),d.message);}';
    echo '      if(d.ready){';
    echo '        setProgress(92,' . json_encode($readymsg) . ');';
    echo '        startCountdown();';
    echo '      } else {setTimeout(pollStatus,5000);}';
    echo '    })';
    echo '    .catch(function(){setTimeout(pollStatus,5000);});';
    echo '}';

    echo 'function startCountdown(){';
    echo '  var remaining=waitSecs;';
    echo '  var step=8/waitSecs;'; // fill from 92% to 100% over waitSecs seconds.
    echo '  var cd=setInterval(function(){';
    echo '    remaining--;';
    echo '    setProgress(Math.min(progress+step));';
    echo '    if(remaining<=0){clearInterval(cd);document.location.href=urlG;}';
    echo '  },1000);';
    echo '}';

    echo 'var loadParams=new URLSearchParams(' . $params . ');';
    echo 'fetch(' . $loadurl . ',{method:"POST",body:loadParams})';
    echo '  .then(function(r){if(!r.ok){throw new Error(r.status);}return r.json();})';
    echo '  .then(function(data){';
    echo '    if(data.error){throw new Error(data.error);}';
    echo '    clearInterval(phase1);';
    echo '    urlG=data.urlG;';
    echo '    waitSecs=data.waitSecs||0;';
    echo '    if(!data.isNew){setProgress(55,msgRestarting);}else{setProgress(55);}';
    echo '    if(data.waitSecs===0){setProgress(100);setTimeout(function(){document.location.href=urlG;},600);}';
    echo '    else{setTimeout(pollStatus,5000);}';
    echo '  })';
    echo '  .catch(function(e){showError(e&&e.message?e.message:null);});';
    echo '</script>';
} else {
    echo '<p class="msg">' . s($noavail) . '</p>';
}

echo '</div></body></html>';
