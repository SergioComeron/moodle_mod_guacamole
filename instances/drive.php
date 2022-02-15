<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once ($CFG->dirroot . '/mod/guacamole/instances/google-api-php-client-2.2.1/vendor/autoload.php');
// require_once ($CFG->dirroot . '/mod/guacamole/instances/google-api-php-client-2.2.1/src/');

function subirFileJson(){
  global $CFG;
  //Sacar el fichero json
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
$optParams = array(
  'pageSize' => 10,
  'fields' => 'nextPageToken, files(id, name)'
);
$results = $service->files->listFiles($optParams);
