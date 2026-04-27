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
 * Scheduled task to shut down virtual machines that have been idle beyond their allowed time.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'guacamole');
    }

    /**
     * Run guacamole shutdown cron.
     */
    public function execute() {
        global $CFG, $DB;

        $now = time();

        // Clean up machines stuck in 'loading' for more than 30 minutes.
        // This happens when the user closes the loading tab before status.php
        // can update the state to 'started'.
        $loadingstale = $now - 1800;
        $stalloading = $DB->get_records_select(
            'guacamole_computers',
            'state = ? AND root = ? AND timelaststart < ?',
            ['loading', $CFG->wwwroot, $loadingstale]
        );
        foreach ($stalloading as $stalled) {
            $stalled->state = 'stopped';
            $stalled->timelaststop = $now;
            $DB->update_record('guacamole_computers', $stalled);
            $computername = strtolower($stalled->cloudimage . '-' . $stalled->imageid . '-' . $stalled->userid);
            mtrace($computername . '....loading huerfana, marcada stopped');
        }

        $token = guacamole_get_token();
        $activeconnections = guacamole_api_request($token, '/guacamole/api/session/data/mysql/activeConnections');

        // activeConnections returns a JSON object (map), not an array — convert to sequential array.
        $activeconnections = array_values($activeconnections ?: []);
        $connections = array_column($activeconnections, 'connectionIdentifier');

        $guacamolecomputers = $DB->get_records('guacamole_computers', ['state' => 'started', 'root' => $CFG->wwwroot]);
        foreach ($guacamolecomputers as $guacamolecomputer) {
            $computername = strtolower($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
            mtrace($computername);

            $now = time();
            $guacamolecomputer->timetodelete = $now + ($guacamolecomputer->daystodelete * 60 * 60 * 24);
            $DB->update_record('guacamole_computers', $guacamolecomputer);

            // Skip machines with an active Guacamole session.
            if (
                !empty($guacamolecomputer->guaidconnection) &&
                    in_array($guacamolecomputer->guaidconnection, $connections)
            ) {
                continue;
            }

            $timelaststart    = $guacamolecomputer->timelaststart;
            $timedesconection = fechaDesconexion($computername);

            // Use disconnect time when it is more recent than the last start;
            // otherwise fall back to last-start + 2-minute grace period.
            if ($timedesconection > $timelaststart) {
                $timetostop = $timedesconection + $guacamolecomputer->minutestoshutdown * 60;
            } else {
                $timetostop = $timelaststart + $guacamolecomputer->minutestoshutdown * 60 + 120;
            }

            if ($timetostop >= $now) {
                continue;
            }

            $guacamolecomputer->state = 'shutdown';
            $DB->update_record('guacamole_computers', $guacamolecomputer);

            $user = $DB->get_record('user', ['id' => $guacamolecomputer->userid]);
            if (!empty($guacamolecomputer->guaidconnection)) {
                quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                eliminarConexion($guacamolecomputer->guaidconnection);
            }

            stopvm($computername);
            $guacamolecomputer->guaidconnection = null;
            $guacamolecomputer->state = 'stopped';
            $guacamolecomputer->timelaststop = $now;
            $DB->update_record('guacamole_computers', $guacamolecomputer);
            mtrace('....parada');
        }
    }
}
