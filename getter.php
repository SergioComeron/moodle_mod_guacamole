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
 * Redirect the user to the appropriate submission related page
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
global $DB;

$idimagen = optional_param('idimagen', 0, PARAM_TEXT);
$imagen = $DB->get_record('guacamole_images', array('id'=>$idimagen));
$defaultdaystodelete = $imagen->defaultdaystodelete;
$defaultminutestoshutdown = $imagen->defaultminutestoshutdown;

echo "<script>";
echo "document.getElementById(\"id_daystodelete\").value=".$defaultdaystodelete.";";
echo "document.getElementById(\"id_minutestoshutdown\").value=".$defaultminutestoshutdown.";";
echo "</script>";
?>
