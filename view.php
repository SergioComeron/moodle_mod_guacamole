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
 * Prints a particular instance of guacamole
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once('../../config.php');
require_once('./instances/lib.php');


global $USER;

$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n', 0, PARAM_INT);
if ($id) {
    $cm         = get_coursemodule_from_id('guacamole', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $guacamole  = $DB->get_record('guacamole', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $guacamole  = $DB->get_record('guacamole', ['id' => $n], '*', MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $guacamole->course], '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('guacamole', $guacamole->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_guacamole\event\course_module_viewed::create([
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
]);
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $guacamole);
$event->trigger();

$PAGE->set_url('/mod/guacamole/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($guacamole->name));
$PAGE->set_heading(format_string($course->fullname));
echo "<script src=\"//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js\"></script>";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"./styles.css\" media=\"screen\" />";

echo $OUTPUT->header();
echo $OUTPUT->heading($guacamole->name);
if ($guacamole->intro) {
    echo $OUTPUT->box(format_module_intro('guacamole', $guacamole, $cm->id), 'generalbox mod_introbox', 'guacamoleintro');
}

$imageid = $guacamole->imageid;
$userid = $USER->id;

$guacamoleimage = $DB->get_record('guacamole_images', ['id' => $imageid]);
$instancesavailables = $guacamoleimage->maxnuminstances - getComputersUsed($imageid);
$guacamolecomputer = null;
$url = null;
$urlant = null;
$vmisstarted = false;
$computerstarted = computerStartedByUser($userid, $imageid);
if ($computerstarted != null) {
    // Su máquina ya se está ejecutando
    if ($computerstarted->state != 'deleting') {
        $imageidant = $computerstarted;
        echo "<div id=box style=\"border-style:solid; border-width: 1px; padding: 10px; border: 1px solid Gainsboro; border-radius: 5px; line-height: 0.7;\">";
        $datetimeformat = 'd-m-Y H:i:s';
        $hourtimeformat = 'H:i:s';
        $datec = new \DateTime();
        $datec->setTimestamp($computerstarted->timecreated);
        echo "<p><b>" . get_string('datecreation', 'guacamole') . "</b>: " . $datec->format($datetimeformat) . "</p>";
        $dateu = new \DateTime();
        $dateu->setTimestamp($computerstarted->timelaststart);
        $datep = new \DateTime();
        $datep->setTimestamp($computerstarted->timetodelete);
        echo "<p><b>" . get_string('laststart', 'guacamole') . "</b>: " . $dateu->format($datetimeformat) . "</p>";
        echo "<p><b>" . get_string('datetodelete', 'guacamole') . "</b>: " . $datep->format($datetimeformat) . "</p>";
        echo "<p><b>" . get_string('imagename', 'guacamole') . "</b>: " . $computerstarted->cloudimage . "</p>";
        echo "<p><b>" . get_string('state', 'guacamole') . "</b>: " . $computerstarted->state . "</p>";
        echo "<button type=\"button\" class=\"btn btn-primary button\">" . get_string('openvirtualmachine', 'guacamole') . "</button>";
        $vmisstarted = true;
        $guacamolecomputer = $computerstarted;
        $imageid = $guacamolecomputer->imageid;
        $url = './instances/start.php?img=' . $imageid . '&usr=' . $userid . '&id=' . $id . '&gu=' . $guacamole->id . '&comp=' . $guacamolecomputer->id;
        echo "</div>";
    } else {
        // La máquina se está borrando
        echo "<div class=\"alert alert-danger\" role=\"alert\">";
        echo "<p>" . get_string('ondeleting', 'guacamole') . "</p>";
        echo "</div>";
    }
} else {
    if ($instancesavailables > 0) {
        $guacamolecomputer = $DB->get_record('guacamole_computers', ['userid' => $USER->id, 'imageid' => $guacamoleimage->id]);
        if ($guacamolecomputer == null) {
            // NO hay ninguna creada
            if ($guacamoleimage->active == 1) {
                echo "<button type=\"button\" class=\"btn btn-primary button\">" . get_string('createandstart', 'guacamole') . "</button>";
                $url = './instances/start.php?img=' . $imageid . '&usr=' . $userid . '&id=' . $id . '&gu=' . $guacamole->id . '&comp=0';
            } else {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo get_string('machinedesactivated', 'guacamole');
                echo "</div>";
            }
        } else {
            // Hay una creada
            if ($guacamolecomputer->state == 'loading') {
                echo "<div class=\"alert alert-info\" role=\"alert\">";
                echo get_string('trylater', 'guacamole');
                echo "</div>";
            } else if ($guacamolecomputer->state != 'deleting') {
                if ($guacamoleimage->active == 1) {
                    if ($guacamoleimage->cloudimage == $guacamolecomputer->cloudimage) {
                        echo "<div id=box style=\"border-style:solid; border-width: 1px; padding: 10px; border: 1px solid Gainsboro; border-radius: 5px; line-height: 0.7;\">";
                        $datetimeformat = 'd-m-Y H:i:s';
                        $hourtimeformat = 'H:i:s';
                        $datec = new \DateTime();
                        $datec->setTimestamp($guacamolecomputer->timecreated);
                        echo "<p><b>" . get_string('datecreation', 'guacamole') . "</b>: " . $datec->format($datetimeformat) . "</p>";
                        $timedesconection = fechaDesconexion($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
                        $dateu = new \DateTime();
                        $dateu->setTimestamp($guacamolecomputer->timelaststart);
                        echo "<p><b>" . get_string('laststart', 'guacamole') . "</b>: " . $dateu->format($datetimeformat) . "</p>";
                        $dates = new \DateTime();
                        $dates->setTimestamp($guacamolecomputer->timetodelete);
                        echo "<p><b>" . get_string('datetodelete', 'guacamole') . "</b>: " . $dates->format($datetimeformat) . "</p>";
                        echo "<p><b>" . get_string('imagename', 'guacamole') . "</b>: " . $guacamolecomputer->cloudimage . "</p>";
                        echo "<p><b>" . get_string('state', 'guacamole') . "</b>: " . $guacamolecomputer->state . "</p>";

                        echo "<button type=\"button\" class=\"btn btn-primary button\">" . get_string('start', 'guacamole') . "</button>";
                        $url = './instances/start.php?img=' . $imageid . '&usr=' . $userid . '&id=' . $id . '&gu=' . $guacamole->id . '&comp=' . $guacamolecomputer->id;
                        echo "</div>";
                    } else {
                        // las cloudimage son distintas
                        echo "<div id=box style=\"border-style:solid; border-width: 1px; padding: 10px; border: 1px solid Gainsboro; border-radius: 5px; line-height: 0.7;\">";
                        $datetimeformat = 'd-m-Y H:i:s';
                        $hourtimeformat = 'H:i:s';
                        $datec = new \DateTime();
                        $datec->setTimestamp($guacamolecomputer->timecreated);
                        echo "<p><b>" . get_string('datecreation', 'guacamole') . "</b>: " . $datec->format($datetimeformat) . "</p>";
                        $timedesconection = fechaDesconexion($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
                        $datep = new \DateTime();
                        $datep->setTimestamp($timedesconection + $guacamolecomputer->minutestoshutdown * 60);
                        echo "<td class=\"text-center\">" . get_string('laststart', 'guacamole') . ": " . $datep->format($hourtimeformat) . "</p>";
                        $dates = new \DateTime();
                        $dates->setTimestamp($guacamolecomputer->timetodelete);
                        echo "<p><b>" . get_string('datetodelete', 'guacamole') . "</b>: " . $dates->format($datetimeformat) . "</p>";
                        echo "<p><b>" . get_string('imagename', 'guacamole') . "</b>: " . $guacamolecomputer->cloudimage . "</p>";
                        echo "<p><b>" . get_string('state', 'guacamole') . "</b>: " . $guacamolecomputer->state . "</p>";

                        echo "<button type=\"button\" class=\"btn btn-primary button\">" . get_string('start', 'guacamole') . "</button>";
                        $url = './instances/start.php?img=' . $imageid . '&usr=' . $userid . '&id=' . $id . '&gu=' . $guacamole->id . '&comp=' . $guacamolecomputer->id;
                        echo "</div>";

                        echo "<br><br>";
                        echo "<div id=box style=\"border-style:solid; border-width: 1px; padding: 10px; border: 1px solid Gainsboro; border-radius: 5px;\">";
                        echo "<div class=\"alert alert-danger\" role=\"alert\">";
                        echo "<h4 class=\"alert-heading\">" . get_string('notice', 'guacamole') . "</h4>";
                        echo "<p>" . get_string('machinenewbaseimage', 'guacamole') . "</p>";
                        echo "<hr>";
                        echo "<p class\"mb-0\">" . get_string('youcandeleteifyouwish', 'guacamole') . "</p>";
                        echo "</div>";

                        echo "<button type=\"button\" class=\"btn btn-primary button2\">" . get_string('createnewdeleting', 'guacamole') . "</button>";
                        $urlant = './instances/start.php?img=' . $imageid . '&usr=' . $userid . '&id=' . $id . '&gu=' . $guacamole->id . '&comp=0';
                        echo "</div>";
                    }
                } else {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo get_string('machinedesactivated', 'guacamole');
                    echo "</div>";
                }
            } else {
                // La máquina se está borrando
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo get_string('ondeleting', 'guacamole');
                echo "</div>";
            }
        }
    } else {
        // No se puede crear, máximo alcanzado
        echo "<div class=\"alert alert-danger\" role=\"alert\">";
        echo get_string('noavailable', 'guacamole');
        echo "</div>";
    }
}
echo "<br>";
echo "<br>";
echo "<h2>" . get_string('help', 'moodle') . "</h2>";
echo $CFG->guacamole_help;

$noticemsg     = get_string('openinnewtab', 'guacamole');
$loadingmsg    = get_string('trylater', 'guacamole');
$reopenmsg     = get_string('openvirtualmachine', 'guacamole');
$vmisstartedjson = $vmisstarted ? 'true' : 'false';

echo '<div id="guac-notice" style="display:none;margin-top:1rem;" class="alert alert-info" role="alert">';
echo '  <span id="guac-notice-text"></span>';
echo '  <a id="guac-reopen" href="#" target="_blank" class="alert-link" style="display:none;margin-left:.5rem;">';
echo '    ' . s($reopenmsg) . ' ↗';
echo '  </a>';
echo '</div>';
echo "<script>";
echo "var vmIsStarted=" . $vmisstartedjson . ";";
echo "var noticemsg=" . json_encode($noticemsg) . ";";
echo "var loadingmsg=" . json_encode($loadingmsg) . ";";
echo "function guacOpenTab(u){";
echo "  var notice=document.getElementById('guac-notice');";
echo "  var noticeText=document.getElementById('guac-notice-text');";
echo "  var reopen=document.getElementById('guac-reopen');";
echo "  if(vmIsStarted){";
echo "    noticeText.textContent=noticemsg;";
echo "    reopen.href=u;";
echo "    reopen.style.display='inline';";
echo "  } else {";
echo "    noticeText.textContent=loadingmsg;";
echo "    reopen.style.display='none';";
echo "    var btn=document.querySelector('.button');";
echo "    if(btn){btn.disabled=true;btn.classList.add('disabled');}";
echo "  }";
echo "  notice.style.display='block';";
echo "  window.open(u,'_blank');";
echo "}";
echo "document.querySelector('.button') && document.querySelector('.button').addEventListener('click',function(){guacOpenTab(" . json_encode($url) . ");});";
echo "document.querySelector('.button2') && document.querySelector('.button2').addEventListener('click',function(){guacOpenTab(" . json_encode($urlant) . ");});";
echo "</script>";

echo $OUTPUT->footer();
