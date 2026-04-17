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
 * Data generator for mod_guacamole.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * mod_guacamole data generator class.
 */
class mod_guacamole_generator extends testing_module_generator {
    /**
     * Creates a new instance of the guacamole activity.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        if (!isset($record->imageid)) {
            $record->imageid = 0;
        }
        if (!isset($record->minutestoshutdown)) {
            $record->minutestoshutdown = 60;
        }
        if (!isset($record->daystodelete)) {
            $record->daystodelete = 7;
        }

        return parent::create_instance($record, $options);
    }
}
