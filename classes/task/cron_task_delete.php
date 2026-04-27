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
 * A scheduled task for guacamole cron.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_guacamole\task;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/instances/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

/**
 * Scheduled task to delete stopped virtual machines whose time-to-delete has passed.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task_delete extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontaskdelete', 'guacamole');
    }

    /**
     * Run guacamole cron.
     */
    public function execute() {
        global $CFG, $DB;

        // Process manually-deleted machines (marked 'deleting' by showimages.php).
        $pendingdelete = $DB->get_records('guacamole_computers', ['state' => 'deleting', 'root' => $CFG->wwwroot]);
        foreach ($pendingdelete as $guacamolecomputer) {
            $instancename = strtolower($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
            mtrace($instancename . '....eliminando (manual)');
            try {
                stopinstance($instancename);
            } catch (Throwable $e) {
                mtrace('....error GCP: ' . $e->getMessage());
            }
            $DB->delete_records('guacamole_computers', ['id' => $guacamolecomputer->id]);
        }

        // Process machines that have exceeded their time-to-delete.
        $guacamolecomputers = $DB->get_records('guacamole_computers', ['state' => 'stopped', 'root' => $CFG->wwwroot]);
        foreach ($guacamolecomputers as $guacamolecomputer) {
            $instancename = strtolower($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
            mtrace($instancename);
            if ($guacamolecomputer->timetodelete < time()) {
                $guacamolecomputer->state = 'deleting';
                $DB->update_record('guacamole_computers', $guacamolecomputer);
                try {
                    stopinstance($instancename);
                } catch (Throwable $e) {
                    mtrace('....error GCP: ' . $e->getMessage());
                }
                $DB->delete_records('guacamole_computers', ['imageid' => $guacamolecomputer->imageid, 'userid' => $guacamolecomputer->userid]);
                mtrace('....eliminada');
            } else {
                mtrace('....no eliminada');
            }
        }
    }
}
