<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Google Drive helper for mod_guacamole.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

require_once($CFG->dirroot . '/mod/guacamole/instances/google-api-php-client-2.2.1/vendor/autoload.php');

/**
 * Uploads the JSON service-account key file to the Moodle temp directory.
 */
function subirfilejson() {
    global $CFG;
    // Sacar el fichero json
    $fs = get_file_storage();
    $file = $fs->get_file(1, 'mod_guacamole', 'jsonfile', 0, '/', get_config('mod_guacamole', 'jsonfile'));
    $file->copy_content_to($CFG->dataroot . '/temp/auth.json');
}

$client = new Google_Client();
$client->setApplicationName('Pruebas');
$client->useApplicationDefaultCredentials();
$client->addScope('https://www.googleapis.com/auth/drive');

subirFileJson();
$service = new Google_Service_Drive($client);

// Print the names and IDs for up to 10 files.
$optparams = [
  'pageSize' => 10,
  'fields' => 'nextPageToken, files(id, name)',
];
$results = $service->files->listFiles($optparams);
