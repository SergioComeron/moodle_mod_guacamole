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
require_once ($CFG->dirroot . '/mod/guacamole/instances/google-api-php-client-2.2.1/vendor/autoload.php');

function subirFileJson(){
  global $CFG;
  //Sacar el fichero json
  $fs = get_file_storage();
  $file = $fs->get_file(1, 'mod_guacamole', 'jsonfile', 0, '/', get_config('mod_guacamole', 'jsonfile'));
  $file->copy_content_to($CFG->dataroot . '/temp/auth.json');
}

//function createInstance($instancia){
function createInstance($imageid, $userid){
  global $CFG, $DB;

  $user = $DB->get_record('user', array('id'=>$userid));
  $image = $DB->get_record('guacamole_images', array('id'=>$imageid));
  $computername=$image->cloudimage.'-'.$image->id.'-'.$user->id;
  $instancia=strtolower($computername);

  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');

  subirFileJson();

  $client->setAuthConfig($CFG->dataroot . '/temp/auth.json');

  $service = new Google_Service_Compute($client);

  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;

  //Declaro la instancia
  $instance = new Google_Service_Compute_Instance();
  //Le doy un nombre
  $instance->setName($instancia);
  //Le pongo un tipo de instancia
  $instance->setMachineType('projects/'.$CFG->guacamole_project_cloud.'/zones/'.$CFG->guacamole_zone_cloud.'/machineTypes/n1-standard-1');
  //Le pongo una interfaz de red
  $googleNetworkInterface = new Google_Service_Compute_NetworkInterface();
  $googleNetworkInterface->setNetwork('projects/'.$CFG->guacamole_project_cloud.'/global/networks/default');
  $googleNetworkInterface->setName('interfaz'.$instancia);
    //Interfaz de acceso
    $accessConfig = new Google_Service_Compute_AccessConfig();
    $accessConfig->setKind('compute#accessConfig');
    $accessConfig->setName('External NAT');
    $accessConfig->setType('ONE_TO_ONE_NAT');
  $googleNetworkInterface->setAccessConfigs(array($accessConfig));

  $instance->setNetworkInterfaces(array($googleNetworkInterface));
  //Le pongo un disco

  //Creo un disco
  $new_disk = new Google_Service_Compute_Disk();
  //Le doy un nombre al disco
  $new_disk->setName($instancia);

  $imageDisk = $image->cloudimage;
  $new_disk->setSourceImage('https://www.googleapis.com/compute/v1/projects/'.$CFG->guacamole_project_cloud.'/global/images/'.$imageDisk);

  //añado el disco al proyecto
  try{
    $insertDiskOperation = $service->disks->insert($project, $zone, $new_disk);
    if (waitForZoneOperationCompletion($service, $project, $zone,
          $insertDiskOperation->getName())>0) {
        exit('Error inserting disk.');
      }
  }catch (Exception $e){
    $admins = get_admins();
    foreach ($admins as $admin){
        email_to_user($admin, $admin, "Error añadiendo el disco ".$disk, "Exception: ".$e);
    }
  }


  //Le digo que es booteable
  $bootDisk = $service->disks->get($project, $zone, $instancia);
  if (!("READY"==$bootDisk->getStatus())) {
    exit("Disk creation didn't succeed.");
  }

  $primaryDisk = new Google_Service_Compute_AttachedDisk();
  $primaryDisk->setBoot("TRUE");
  $primaryDisk->setDeviceName("primary");
  $primaryDisk->setMode("READ_WRITE");
  $primaryDisk->setSource($bootDisk->getSelfLink());
  $primaryDisk->setType("PERSISTENT");
  $instance->setDisks(array($primaryDisk));

  try{
    $response = $service->instances->insert($project, $zone, $instance);
    if (waitForZoneOperationCompletion($service, $project, $zone,
          $response->getName())>0) {
        exit('Error inserting instance.');
      }
  }catch (Exception $e){
    $admins = get_admins();
    foreach ($admins as $admin){
        email_to_user($admin, $admin, "Error creando la instancia: ".$instance, "Exception: ".$e);
    }
  }

}

function existDisk($diskName){
  global $CFG;
  $diskName=strtolower($diskName);
  $exist = false;
  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');
  subirFileJson();

  $client->setAuthConfig($CFG->dataroot  . '/temp/auth.json');

  $service = new Google_Service_Compute($client);
  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;

  $optParams = [];

  $response = $service->disks->listDisks($project, $zone, $optParams);
  $array = $response['items'];
  foreach ($array as $instance => $valor) {
    $diskInstance=$array[$instance]['name'];
    if (strcmp($diskName, $diskInstance) === 0){
      $exist = true;
    }
  }
  return $exist;
}

function deleteInstance($instance){
  global $CFG;
  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');
  subirFileJson();

  $client->setAuthConfig($CFG->dataroot  . '/temp/auth.json');

  $service = new Google_Service_Compute($client);
  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;
  try{
      $response = $service->instances->delete($project, $zone, $instance);
      if (waitForZoneOperationCompletion($service, $project, $zone,
            $response->getName())>0) {
          exit('Error deleting instance.');
        }
  }catch (Exception $e){
    $admins = get_admins();
    foreach ($admins as $admin){
        email_to_user($admin, $admin, "Error eliminando la instancia: ".$instance, "Exception: ".$e);
    }
  }
}

function deleteDisk($disk){
  global $CFG;
  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');
  subirFileJson();

  $client->setAuthConfig($CFG->dataroot  . '/temp/auth.json');

  $service = new Google_Service_Compute($client);
  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;
  try{
    $response = $service->disks->delete($project, $zone, $disk);
    if (waitForZoneOperationCompletion($service, $project, $zone,
          $response->getName())>0) {
        exit('Error deleting disk.');
      }
  }catch (Exception $e){
    $admins = get_admins();
    foreach ($admins as $admin){
      email_to_user($admin, $admin, "Error eliminando el disco: ".$instance, "Exception: ".$e);
    }
  }
}

function stopinstance($instancia){
  require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
  global $CFG, $DB;
  deleteInstance($instancia);
  deleteDisk($instancia);
}

function stopvm($instance){
  global $CFG;
  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');
  subirFileJson();

  $client->setAuthConfig($CFG->dataroot  . '/temp/auth.json');

  $service = new Google_Service_Compute($client);
  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;

  try{
    $response = $service->instances->stop($project, $zone, $instance);
  }catch (Exception $e){
    $admins = get_admins();
    foreach ($admins as $admin){
        email_to_user($admin, $admin, "Error parando la instancia: ".$instance, "Exception: ".$e);
    }
  }
}

function existInstance($instance){
  global $CFG;
  $exist = false;

  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');
  subirFileJson();

  $client->setAuthConfig($CFG->dataroot  . '/temp/auth.json');

  $service = new Google_Service_Compute($client);
  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;

  $optParams = [];

  $response = $service->instances->listInstances($project, $zone, $optParams);
  $array = $response['items'];
  foreach ($array as $instanceArray => $valor) {
    $diskInstance=$array[$instanceArray]['name'];
    if (strcmp($instance, $diskInstance) === 0){
      $exist = true;
    }
  }
  return $exist;
}

function obtainimagename($instance){
  return substr($instance,0, strrpos($instance, '-'));
}

function startinstance($instance){
  global $CFG;
  $client = new Google_Client();
  $client->setApplicationName('Pruebas');
  $client->useApplicationDefaultCredentials();
  $client->addScope('https://www.googleapis.com/auth/cloud-platform');
  subirFileJson();

  $client->setAuthConfig($CFG->dataroot  . '/temp/auth.json');

  $service = new Google_Service_Compute($client);
  $project = $CFG->guacamole_project_cloud;
  $zone = $CFG->guacamole_zone_cloud;

  try{
    $response = $service->instances->start($project, $zone, $instance);
  }catch (Exception $e){
    $admins = get_admins();
    foreach ($admins as $admin){
        email_to_user($admin, $admin, "Error iniciando la instancia: ".$instance, "Exception: ".$e);
    }
  }
  return $response;
}

/**
 * Queries the Google Compute Engine API to determine if the zone operation has
 * completed.
 * @param Google_Service_Compute $computeService The service handle used to
 *        make Google Compute Engine API calls.
 * @param string $project The project the operation is executing within.
 * @param string $zone The The zone the operation is executing within.
 * @param string $operation The auto-generated name of the operation instance.
 * @return 0 = success, 1 = error.
 */
function waitForZoneOperationCompletion($computeService, $project, $zone,
    $operation) {
  for ($x=0; $x<=20; $x++) {
    $operationStatus = $computeService->zoneOperations->get($project,
      $zone, $operation);
    if ("DONE"==$operationStatus->getStatus()) {
      return 0;
    }
    sleep((2*$x));
  }
  return 1;
}

?>
