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
 * English strings for guacamole
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(__FILE__)).'/lib.php');

global $CFG, $DB;
require_once($CFG->dirroot . '/mod/guacamole/instances/lib.php');
$id = optional_param('id', 0, PARAM_INT);
echo "<script src=\"https://code.jquery.com/jquery-3.4.1.js\"></script>";

$gu = optional_param('gu', 0, PARAM_INT);
$computerid = optional_param('comp', 0, PARAM_INT);
$imageid = optional_param('img', 0, PARAM_INT);
$userid  = optional_param('usr', 0, PARAM_INT);

$guacamoleimage = $DB->get_record('guacamole_images', array('id'=>$imageid));
$instancesavailables = $guacamoleimage->maxnuminstances-getComputersUsed($imageid);
$guacamolecomputer = null;
$guacamolecomputer = $DB->get_record('guacamole_computers', array('id'=>$computerid));
if ($guacamolecomputer!=null&&($guacamolecomputer->state=='deleting'||$guacamolecomputer->state=='loading'||$guacamolecomputer->state=='shutdown')){
    //La máquina no esta ni started ni stopped
    echo ('<br><br><br><br>');
    echo "<h3 style=\"text-align:center;\">".get_string('trylater', 'guacamole')."</h3>";
}else{
  if ($instancesavailables >0 || computerStartedByUser($userid, $imageid)!=null){
    echo "<div id = wait></div>";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../styles.css\" media=\"screen\" />";


    echo "<script type=\"text/javascript\">";
    echo "$(document).ready(function() {";
                  //Añadimos la imagen de carga en el contenedor
    echo          "$('#wait').html('<br><br><br><br>');";
    echo          "$('#wait').html('<p style=\"text-align:center;\"><img src=\"../loading.gif\"/></p><h3 style=\"text-align:center;\">".get_string('starting', 'guacamole')."</h3><p style=\"text-align:center;\">".get_string('redirectedfewminutes', 'guacamole')."</p>');";

    echo "$('#content').html('<p style=\"text-align:center;\"><img src=\"../loading.gif\"/></p><h3 style=\"text-align:center;\">".get_string('starting', 'guacamole')."</h3><p style=\"text-align:center;\">".get_string('redirectedfewminutes', 'guacamole')."</p>');";
    echo          "var parametros = {\"img\" : \"".$imageid."\",";
    echo                            "\"usr\" : \"".$userid."\",";
    echo                            "\"id\" : \"".$id."\",";
    echo                            "\"gu\" : \"".$gu."\",";
    echo                            "\"comp\" : \"".$computerid."\",";
    echo                            "};";
    echo          "$.ajax({";
    echo              "data: parametros,";
    echo              "type: \"POST\",";
    echo              "url: \"./load.php\",";
    echo              "success: function(data) {";
    echo                  "var respuesta = JSON.parse(data);";
    echo                    "document.location.href = respuesta.urlG";
    echo              "}";
    echo          "});";
    echo          "return false;";
    echo  "});";
    echo "</script>";
  }else{
    //No se puede crear, máximo alcanzado
    echo ('<br><br><br><br>');
    echo "<h3 style=\"text-align:center;\">".get_string('noavailable', 'guacamole')."</h3>";
  }
}


?>
