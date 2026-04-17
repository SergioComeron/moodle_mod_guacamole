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
 * The main guacamole configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_guacamole
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_guacamole_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB, $PAGE;

        $courseid = optional_param('course', 0, PARAM_INT);
        if (!empty($courseid)) {
            $course = get_course($courseid);
            $idguacamole = '0';
            $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/guacamole/init.js'));
            $PAGE->requires->js_init_call('M.chargeOnLoad.init', []);
        } else {
            $course = get_course($this->_course->id);
            $guacamole = $DB->get_record('guacamole', ['id' => $this->current->id]);
            $idguacamole = $this->current->id;
        }
        $context = context_course::instance($course->id);
        $jsmodule = [
            'name'     => 'mod_guacamole',
            'fullpath' => '/mod/guacamole/guacamole.js',
        ];
        $opts = [];

        global $CFG, $DB, $PAGE;

        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('guacamolename', 'guacamole'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'guacamolename', 'guacamole');

        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $select = ['active' => 1];
        $opciones = $DB->get_records('guacamole_images');
        $images = [];
        foreach ($opciones as $opcion) {
            if ($opcion->active == 1) {
                $images[$opcion->id] = $opcion->name;
            }
        }
        $mform->addElement('select', 'imageid', get_string('guacamoleinstance', 'guacamole'), $images, ['onchange' => 'cargarOnChange()']);
        $mform->addRule('imageid', null, 'required', null, 'client');

        $defaultdaystodelete = '2';
        if (has_capability('mod/guacamole:configdaystodelete', $context)) {
            $mform->addElement('text', 'daystodelete', get_string('daystodelete', 'guacamole'), 'size="2"');
            $mform->setType('daystodelete', PARAM_INT);
            $mform->setDefault('daystodelete', $defaultdaystodelete);
        } else {
            $mform->addElement('text', 'daystodelete', get_string('daystodelete', 'guacamole'), 'size="2" disabled="disabled"');
            $mform->setType('daystodelete', PARAM_INT);
            $mform->setDefault('daystodelete', $defaultdaystodelete);
        }

        $defaultminutestoshutdown = '2';
        if (has_capability('mod/guacamole:configtimetoshutdown', $context)) {
            $mform->addElement('text', 'minutestoshutdown', get_string('minutestoshutdown', 'guacamole'), 'size="2"');
            $mform->setType('minutestoshutdown', PARAM_INT);
            $mform->setDefault('minutestoshutdown', $defaultminutestoshutdown);
        } else {
            $mform->addElement('text', 'minutestoshutdown', get_string('minutestoshutdown', 'guacamole'), 'size="2" disabled="disabled"');
            $mform->setType('minutestoshutdown', PARAM_INT);
            $mform->setDefault('minutestoshutdown', $defaultminutestoshutdown);
        }

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);

        $name = get_string('allow', 'guacamole');
        $options = ['optional' => true];

        $mform->addElement('date_time_selector', 'timeopen', $name, $options);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
        $PAGE->requires->jquery();

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/guacamole/guacamole.js'));
    }
}
