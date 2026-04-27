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
 * Admin page to manage all virtual machine instances.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/instances/lib.php');
require_once(__DIR__ . '/lib.php');

require_login();
$context = context_system::instance();
require_capability('mod/guacamole:manageimages', $context);

$deletecomputerid = optional_param('deletecomputerid', 0, PARAM_INT);
$filterstate      = optional_param('state', '', PARAM_ALPHA);

$baseurl = new moodle_url('/mod/guacamole/showimages.php');
$PAGE->set_url($baseurl);
$PAGE->set_context($context);

if ($deletecomputerid && confirm_sesskey()) {
    $guacamolecomputer = $DB->get_record('guacamole_computers', ['id' => $deletecomputerid]);
    if ($guacamolecomputer) {
        if (!empty($guacamolecomputer->guaidconnection)) {
            eliminarConexion($guacamolecomputer->guaidconnection);
        }
        $guacamolecomputer->state = 'deleting';
        $DB->update_record('guacamole_computers', $guacamolecomputer);
    }
    redirect($PAGE->url, get_string('deletingscheduled', 'guacamole'));
}

$strmanage = get_string('showimages', 'guacamole');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);
$PAGE->navbar->add(get_string('mod', 'guacamole'));
$PAGE->navbar->add(get_string('modulename', 'guacamole'));
$PAGE->navbar->add($strmanage, $baseurl);

echo $OUTPUT->header();
echo $OUTPUT->heading($strmanage);

// State filter tabs.
$states = ['', 'started', 'loading', 'stopped', 'deleting', 'shutdown'];
$statelabels = [
    ''         => get_string('all', 'moodle'),
    'started'  => get_string('state', 'guacamole') . ': started',
    'loading'  => get_string('state', 'guacamole') . ': loading',
    'stopped'  => get_string('state', 'guacamole') . ': stopped',
    'deleting' => get_string('state', 'guacamole') . ': deleting',
    'shutdown' => get_string('state', 'guacamole') . ': shutdown',
];
$tabs = [];
foreach ($states as $s) {
    $url = new moodle_url('/mod/guacamole/showimages.php', $s !== '' ? ['state' => $s] : []);
    $tabs[] = new tabobject($s === '' ? 'all' : $s, $url, $statelabels[$s]);
}
echo $OUTPUT->tabtree($tabs, $filterstate === '' ? 'all' : $filterstate);

// State badge styles.
$statebadge = [
    'started'  => 'badge badge-success',
    'loading'  => 'badge badge-warning',
    'stopped'  => 'badge badge-secondary',
    'deleting' => 'badge badge-danger',
    'shutdown' => 'badge badge-info',
];

// Fetch computers, optionally filtered by state.
$datetimefmt = 'd/m/Y H:i';
if ($filterstate !== '') {
    $computers = $DB->get_records('guacamole_computers', ['state' => $filterstate], 'timelaststart DESC');
} else {
    $computers = $DB->get_records('guacamole_computers', [], 'timelaststart DESC');
}

// Counters by state.
$allcomputers = $DB->get_records('guacamole_computers', []);
$counts = [];
foreach ($allcomputers as $c) {
    $counts[$c->state] = ($counts[$c->state] ?? 0) + 1;
}
$started  = $counts['started']  ?? 0;
$loading  = $counts['loading']  ?? 0;
$stopped  = $counts['stopped']  ?? 0;
$deleting = $counts['deleting'] ?? 0;

echo html_writer::start_div('alert alert-secondary d-flex gap-3 flex-wrap mb-3', ['style' => 'gap:1rem']);
echo html_writer::tag('span', '<strong>' . get_string('total') . ':</strong> ' . count($allcomputers));
echo html_writer::tag('span', '<span class="badge badge-success">' . $started  . ' started</span>');
echo html_writer::tag('span', '<span class="badge badge-warning">' . $loading  . ' loading</span>');
echo html_writer::tag('span', '<span class="badge badge-secondary">' . $stopped  . ' stopped</span>');
echo html_writer::tag('span', '<span class="badge badge-danger">'   . $deleting . ' deleting</span>');
echo html_writer::end_div();

if (empty($computers)) {
    echo $OUTPUT->notification(get_string('nothingtodisplay', 'moodle'), 'notifymessage');
} else {
    $table = new html_table();
    $table->head = [
        get_string('imagename', 'guacamole'),
        get_string('username', 'moodle'),
        get_string('state', 'guacamole'),
        get_string('datecreation', 'guacamole'),
        get_string('laststart', 'guacamole'),
        get_string('datetodelete', 'guacamole'),
        get_string('actions', 'moodle'),
    ];
    $table->attributes['class'] = 'generaltable table-sm';

    foreach ($computers as $computer) {
        $user = $DB->get_record('user', ['id' => $computer->userid], 'id,username,firstname,lastname');
        $username = $user
            ? html_writer::link(
                new moodle_url('/user/profile.php', ['id' => $user->id]),
                fullname($user) . ' (' . $user->username . ')'
              )
            : '(userid ' . $computer->userid . ')';

        $vmname = $computer->cloudimage . '-' . $computer->imageid . '-' . $computer->userid;

        $badge = $statebadge[$computer->state] ?? 'badge badge-light';
        $statecell = html_writer::tag('span', s($computer->state), ['class' => $badge]);

        $datecreated = $computer->timecreated
            ? userdate($computer->timecreated, $datetimefmt)
            : '-';
        $datelaststart = $computer->timelaststart
            ? userdate($computer->timelaststart, $datetimefmt)
            : '-';
        $datetodelete = $computer->timetodelete
            ? userdate($computer->timetodelete, $datetimefmt)
            : '-';

        $deleteurl = new moodle_url('/mod/guacamole/showimages.php', [
            'deletecomputerid' => $computer->id,
            'sesskey'          => sesskey(),
        ]);
        if ($filterstate !== '') {
            $deleteurl->param('state', $filterstate);
        }
        $deleteicon   = new pix_icon('t/delete', get_string('delete'));
        $deleteaction = $OUTPUT->action_icon(
            $deleteurl,
            $deleteicon,
            new confirm_action(get_string('deleteimageconfirm', 'guacamole'))
        );

        $table->data[] = [
            s($vmname),
            $username,
            $statecell,
            $datecreated,
            $datelaststart,
            $datetodelete,
            $deleteaction,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
