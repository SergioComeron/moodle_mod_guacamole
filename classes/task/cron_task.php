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


class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'guacamole');
    }

    public function execute() {
        global $CFG, $DB;

        $ch = curl_init($CFG->guacamole_domain."/guacamole/api/tokens");
        $nombreDeUsuario=$CFG->guacamole_user;
        $parametros='username='.$nombreDeUsuario.'&password='.$CFG->guacamole_password;
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'username='.$nombreDeUsuario.'&password='.$CFG->guacamole_password);

        $res = curl_exec($ch);
        $var = json_decode($res, true);
        curl_close($ch);

        $tokens=$var['authToken'];

        $ch = curl_init($CFG->guacamole_domain."/guacamole/api/session/data/mysql/activeConnections?token=".$tokens);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $res=curl_exec($ch);
        curl_close($ch);

        $var = json_decode($res, true);
        $users = array_column($var, 'username');
        $connections = array_column($var, 'connectionIdentifier');

        $guacamolecomputers = $DB->get_records('guacamole_computers', array('state'=>'started', 'root'=>$CFG->wwwroot));
        foreach ($guacamolecomputers as $guacamolecomputer){
            echo ($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
            $timelaststart = $guacamolecomputer->timelaststart;
            $timedesconection = 0;
            $timedesconection = fechaDesconexion($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
            $timetostop = $timedesconection+$guacamolecomputer->minutestoshutdown*60;
            $today = time();
            $guacamolecomputer->timetodelete = $today + ($guacamolecomputer->daystodelete*60*60*24);
            $DB->update_record('guacamole_computers', $guacamolecomputer);
            if (!in_array($guacamolecomputer->guaidconnection, $connections)){
              if ($timedesconection<$timelaststart){
                $timetostop = $timelaststart+$guacamolecomputer->minutestoshutdown*60+120;
                $today = time();
                if ($timetostop<$today){
                  $guacamolecomputer->state = 'shutdown';
                  $DB->update_record('guacamole_computers', $guacamolecomputer);
                  $user=$DB->get_record('user', array('id'=>$guacamolecomputer->userid));
                  quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                  eliminarConexion($guacamolecomputer->guaidconnection);

                  stopvm($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
                  $guacamolecomputer->guaidconnection=null;
                  $guacamolecomputer->state='stopped';
                  $guacamolecomputer->timelaststop=$today;
                  $DB->update_record('guacamole_computers', $guacamolecomputer);
                  echo "....parada";
                }else{

                }
              }else{
                if ($timetostop<$today){
                  $guacamolecomputer->state = 'shutdown';
                  $DB->update_record('guacamole_computers', $guacamolecomputer);
                  $user=$DB->get_record('user', array('id'=>$guacamolecomputer->userid));
                  quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                  eliminarConexion($guacamolecomputer->guaidconnection);

                  stopvm($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
                  $guacamolecomputer->guaidconnection=null;
                  $guacamolecomputer->state='stopped';
                  $guacamolecomputer->timelaststop=$today;
                  $DB->update_record('guacamole_computers', $guacamolecomputer);
                  echo "....parada";
                }else{
                  if ($timedesconection==0){
                    $timetostop = $timelaststart+$guacamolecomputer->minutestoshutdown*60+120;
                    $today = time();

                    if ($timetostop<$today){
                      $guacamolecomputer->state = 'shutdown';
                      $DB->update_record('guacamole_computers', $guacamolecomputer);
                      $user=$DB->get_record('user', array('id'=>$guacamolecomputer->userid));
                      quitarPermiso($guacamolecomputer->guaidconnection, $user->username);
                      eliminarConexion($guacamolecomputer->guaidconnection);

                      stopvm($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
                      $guacamolecomputer->guaidconnection=null;
                      $guacamolecomputer->state='stopped';
                      $guacamolecomputer->timelaststop=$today;
                      $DB->update_record('guacamole_computers', $guacamolecomputer);
                      echo "....parada";
                    }else{

                    }

                  }
                }
              }

            }else{

            }
        }
    }
}
