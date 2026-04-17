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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace guacamole with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = required_param('id', PARAM_INT);   // Course.

$PAGE->set_url('/mod/guacamole/index.php', ['id' => $id]);

if (! $course = $DB->get_record('course', ['id' => $id])) {
    throw new \moodle_exception('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$params = [
    'context' => context_course::instance($id),
];
$event = \mod_guacamole\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get all required strings.
$strguacamoles = get_string('modulenameplural', 'guacamole');
$strguacamole  = get_string('modulename', 'guacamole');

// Print the header.
$PAGE->navbar->add($strguacamoles);
$PAGE->set_title($strguacamoles);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strguacamoles, 2);

// Get all the appropriate data.
if (! $guacamoles = get_all_instances_in_course('guacamole', $course)) {
    notice(get_string('thereareno', 'moodle', $strguacamoles), "../../course/view.php?id=$course->id");
    die();
}

$usesections = course_format_uses_sections($course->format);

// Print the list of instances (your module will probably extend this).

$timenow  = time();
$strname  = get_string('name');

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head  = [$strsectionname, $strname];
    $table->align = ['center', 'left'];
} else {
    $table->head  = [$strname];
    $table->align = ['left'];
}

$currentsection = '';
foreach ($guacamoles as $guacamole) {
    if (!$guacamole->visible) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id=$guacamole->coursemodule\">" . format_string($guacamole->name, true) . "</a>";
    } else {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id=$guacamole->coursemodule\">" . format_string($guacamole->name, true) . "</a>";
    }
    $printsection = '';
    if ($guacamole->section !== $currentsection) {
        if ($guacamole->section) {
            $printsection = get_section_name($course, $guacamole->section);
        }
        if ($currentsection !== '') {
            $table->data[] = 'hr';
        }
        $currentsection = $guacamole->section;
    }
    if ($usesections) {
        $table->data[] = [$printsection, $link];
    } else {
        $table->data[] = [$link];
    }
}

echo '<br />';

echo html_writer::table($table);

// Finish the page.

echo $OUTPUT->footer();
