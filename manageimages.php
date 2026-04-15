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

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$deleteimageid = optional_param('deleteimageid', 0, PARAM_INT);

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
$baseurl = new moodle_url('/mod/guacamole/manageimages.php', $urlparams);
$PAGE->set_url($baseurl);

if ($deleteimageid && confirm_sesskey()) {
    $DB->delete_records('guacamole_images', ['id' => $deleteimageid]);

    redirect($PAGE->url, get_string('imagedeleted', 'guacamole'));
}

$images = $DB->get_records_select('guacamole_images', null, null, $DB->sql_order_by_text('name'));

$strmanage = get_string('manageimages', 'guacamole');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

$manageimages = new moodle_url('/mod/guacamole/manageimages.php', $urlparams);
$PAGE->navbar->add(get_string('mod', 'guacamole'));
$PAGE->navbar->add(get_string('modulename', 'guacamole'));
$PAGE->navbar->add(get_string('manageimages', 'guacamole'), $manageimages);
echo $OUTPUT->header();

$table = new flexible_table('guacamole-display-images');

$table->define_columns(['images', 'actions']);
$table->define_headers(["Images", get_string('actions', 'moodle')]);
$table->define_baseurl($baseurl);

$table->setup();

foreach ($images as $image) {
    $imagename = $image->name;

    $imageinfo = '<div class="title">' . $imagename . '</div>';

    $editurl = new moodle_url('/mod/guacamole/editimage.php?imageid=' . $image->id . $extraparams);
    $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

    $deleteurl = new moodle_url('/mod/guacamole/manageimages.php?deleteimageid=' . $image->id . '&sesskey=' . sesskey() . $extraparams);
    $deleteicon = new pix_icon('t/delete', get_string('delete'));
    $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('deleteimageconfirm', 'guacamole')));

    $imageicons = $editaction . ' ' . $deleteaction;

    $table->add_data([$imageinfo, $imageicons]);
}

$table->print_html();

$url = $CFG->wwwroot . '/mod/guacamole/editimage.php?' . substr($extraparams, 1);
echo '<div class="actionbuttons">' . $OUTPUT->single_button($url, get_string('addnewimage', 'guacamole'), 'get') . '</div>';

if ($returnurl) {
    echo '<div class="backlink">' . html_writer::link($returnurl, get_string('back')) . '</div>';
}

echo $OUTPUT->footer();
