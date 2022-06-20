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

require_once(dirname(dirname(dirname(__FILE__))).'/instances/lib.php');
require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');

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

        $guacamolecomputers = $DB->get_records('guacamole_computers', array('state'=>'stopped', 'root'=>$CFG->wwwroot));

        foreach ($guacamolecomputers as $guacamolecomputer){
          echo $guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid;
          if ($guacamolecomputer->timetodelete<time()){
            $guacamolecomputer->state = 'deleting';
            $DB->update_record('guacamole_computers', $guacamolecomputer);
            echo "....eliminada";
            stopinstance($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
            $DB->delete_records('guacamole_computers', array('imageid' => $guacamolecomputer->imageid, 'userid' => $guacamolecomputer->userid));
          }else{
            echo "....no eliminada";
          }
          echo "<br>";
        }
    }
}
