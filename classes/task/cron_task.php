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

        $token = guacamole_get_token();
        $activeconnections = guacamole_api_request($token, '/guacamole/api/session/data/mysql/activeConnections');

        // activeConnections returns a JSON object (map), not an array — convert to sequential array.
        $activeconnections = array_values($activeconnections ?: []);
        $connections = array_column($activeconnections, 'connectionIdentifier');

        $guacamolecomputers = $DB->get_records('guacamole_computers', ['state' => 'started', 'root' => $CFG->wwwroot]);
        foreach ($guacamolecomputers as $guacamolecomputer) {
            $computername = strtolower($guacamolecomputer->cloudimage . '-' . $guacamolecomputer->imageid . '-' . $guacamolecomputer->userid);
            mtrace($computername);
            $timelaststart = $guacamolecomputer->timelaststart;
            $timedesconection = 0;
            $timedesconection = fechaDesconexion($computername);
            $timetostop = $timedesconection + $guacamolecomputer->minutestoshutdown * 60;
            $today = time();
            $guacamolecomputer->timetodelete = $today + ($guacamolecomputer->daystodelete * 60 * 60 * 24);
            $DB->update_record('guacamole_computers', $guacamolecomputer);
            if (!in_array($guacamolecomputer->guaidconnection, $connections)) {
                if ($timedesconection < $timelaststart) {
                    $timetostop = $timelaststart + $guacamolecomputer->minutestoshutdown * 60 + 120;
                    $today = time();
                    if ($timetostop < $today) {
                        $guacamolecomputer->state = 'shutdown';
                        $DB->update_record('guacamole_computers', $guacamolecomputer);
                        $user = $DB->get_record('user', ['id' => $guacamolecomputer->userid]);
                        quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                        eliminarConexion($guacamolecomputer->guaidconnection);

                        stopvm($computername);
                        $guacamolecomputer->guaidconnection = null;
                        $guacamolecomputer->state = 'stopped';
                        $guacamolecomputer->timelaststop = $today;
                        $DB->update_record('guacamole_computers', $guacamolecomputer);
                        mtrace('....parada');
                    }
                } else {
                    if ($timetostop < $today) {
                        $guacamolecomputer->state = 'shutdown';
                        $DB->update_record('guacamole_computers', $guacamolecomputer);
                        $user = $DB->get_record('user', ['id' => $guacamolecomputer->userid]);
                        quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                        eliminarConexion($guacamolecomputer->guaidconnection);

                        stopvm($computername);
                        $guacamolecomputer->guaidconnection = null;
                        $guacamolecomputer->state = 'stopped';
                        $guacamolecomputer->timelaststop = $today;
                        $DB->update_record('guacamole_computers', $guacamolecomputer);
                        mtrace('....parada');
                    } else {
                        if ($timedesconection == 0) {
                            $timetostop = $timelaststart + $guacamolecomputer->minutestoshutdown * 60 + 120;
                            $today = time();

                            if ($timetostop < $today) {
                                $guacamolecomputer->state = 'shutdown';
                                $DB->update_record('guacamole_computers', $guacamolecomputer);
                                $user = $DB->get_record('user', ['id' => $guacamolecomputer->userid]);
                                quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                                eliminarConexion($guacamolecomputer->guaidconnection);

                                stopvm($computername);
                                $guacamolecomputer->guaidconnection = null;
                                $guacamolecomputer->state = 'stopped';
                                $guacamolecomputer->timelaststop = $today;
                                $DB->update_record('guacamole_computers', $guacamolecomputer);
                                mtrace('....parada');
                            }
                        }
                    }
                }
            }
        }
    }
}
