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
 * Script to let a user edit the properties of a particular RSS feed.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir .'/simplepie/moodle_simplepie.php');
require_once($CFG->dirroot.'/mod/guacamole/lib.php');

class image_edit_form extends moodleform {
    protected $isadding;
    protected $caneditshared;
    protected $title = '';
    protected $description = '';

    function __construct($actionurl, $isadding, $caneditshared) {
        $this->isadding = $isadding;
        $this->caneditshared = $caneditshared;
        parent::__construct($actionurl);
    }

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'guacamoleeditimageheader', get_string('image', 'guacamole'));
        $mform->addElement('text', 'name', get_string('imagename', 'guacamole'));
        $mform->setType('name', PARAM_RAW);
        $opciones=obtenerLaboratoriosName();
        $mform->addElement('select', 'guaidconnection', get_string('guacamoleinstance', 'guacamole'), $opciones);
        $mform->addElement('advcheckbox', 'active', get_string('active', 'guacamole'));
        $mform->addElement('text', 'cloudimage', get_string('guacamoleimagename', 'guacamole'));
        $mform->setType('cloudimage', PARAM_RAW);
      //  if (!$this->isadding){
      //    $mform->disabledIf('cloudimage', null);
      //  }
        $mform->addElement('text', 'maxnuminstances', get_string('numberofinstances', 'guacamole'), 'size="2"');
        $mform->setDefault('maxnuminstances', $CFG->guacamole_default_max_connections);
        $mform->setType('maxnuminstances', PARAM_INT);
        $mform->addElement('text', 'defaultminutestoshutdown', get_string('defaulttimetoshutdown', 'guacamole'), 'size="2"');
        $mform->setType('defaultminutestoshutdown', PARAM_INT);
        $mform->setDefault('defaultminutestoshutdown', $CFG->guacamole_default_minutes_to_shutdown);
        $mform->addElement('text', 'defaultdaystodelete', get_string('defaultdaystodelete', 'guacamole'), 'size="2"');
        $mform->setType('defaultdaystodelete', PARAM_INT);
        $mform->setDefault('defaultdaystodelete', $CFG->guacamole_default_days_to_delete);

        $submitlabal = null;
        if ($this->isadding) {
            $submitlabal = get_string('addnewimage', 'guacamole');
        }
        $this->add_action_buttons(true, $submitlabal);
    }

    function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->title = '';
            $data->description = '';
            if($this->title){
                $data->title = $this->title;
            }
            if($this->description){
                $data->description = $this->description;
            }
        }
        return $data;
    }
}

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$imageid = optional_param('imageid', 0, PARAM_INT); // 0 mean create new.

if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

$urlparams = array('imageid' => $imageid);
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$manageimages = new moodle_url('/mod/guacamole/manageimages.php', $urlparams);

$PAGE->set_url('/mod/guacamole/editimage.php', $urlparams);
$PAGE->set_pagelayout('admin');

if ($imageid) {
    $isadding = false;
    $imagerecord = $DB->get_record('guacamole_images', array('id' => $imageid), '*', MUST_EXIST);
} else {
    $isadding = true;
    $imagerecord = new stdClass;
}
$manageimagescap = has_capability('mod/guacamole:manageimages', $context);

$mform = new image_edit_form($PAGE->url, $isadding, $manageimages);
$mform->set_data($imagerecord);

if ($mform->is_cancelled()) {
    redirect($manageimages);

} else if ($data = $mform->get_data()) {
    if (!$manageimagescap) {
        $data->shared = 0;
    }

    if ($isadding) {
        $DB->insert_record('guacamole_images', $data);
    } else {
        $data->id = $imageid;
        $DB->update_record('guacamole_images', $data);
    }

    redirect($manageimages);

} else {
    if ($isadding) {
        $strtitle = get_string('addnewimage', 'guacamole');
    } else {
        $strtitle = get_string('editaimage', 'guacamole');
    }

    $PAGE->set_title($strtitle);
    $PAGE->set_heading($strtitle);

    $PAGE->navbar->add(get_string('mod', 'guacamole'));
    $PAGE->navbar->add(get_string('pluginname', 'guacamole'));
    $PAGE->navbar->add(get_string('manageimages', 'guacamole'), './manageimages.php' );
    $PAGE->navbar->add($strtitle);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle, 2);

    $mform->display();

    echo $OUTPUT->footer();
}
