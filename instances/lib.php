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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/guacamole/instances/google-api-php-client-2.2.1/vendor/autoload.php');

/**
 * Returns a Google_Client configured with auth and a hard HTTP timeout.
 *
 * Every GCP API call goes through Guzzle — without a timeout the PHP process
 * can hang indefinitely if the network stalls. 60 s per request is generous
 * enough for any single Compute Engine operation while still bounding the
 * worst-case hang time.
 *
 * @return Google_Client
 */
function guacamole_gcp_client() {
    global $CFG;
    subirFileJson();
    $client = new Google_Client();
    $client->setApplicationName('mod_guacamole');
    $client->setAuthConfig($CFG->dataroot . '/temp/auth.json');
    $client->addScope('https://www.googleapis.com/auth/cloud-platform');
    $client->setHttpClient(new \GuzzleHttp\Client([
        'connect_timeout' => 10,
        'timeout'         => 60,
    ]));
    return $client;
}

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

/**
 * Creates a new GCP Compute Engine instance cloned from the image for the given user.
 *
 * @param int $imageid The guacamole_images record ID.
 * @param int $userid The Moodle user ID.
 */
function createinstance($imageid, $userid) {
    global $CFG, $DB;

    $user = $DB->get_record('user', ['id' => $userid]);
    $image = $DB->get_record('guacamole_images', ['id' => $imageid]);
    $computername = $image->cloudimage . '-' . $image->id . '-' . $user->id;
    $instancia = strtolower($computername);

    $service = new Google_Service_Compute(guacamole_gcp_client());

    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;

    // Declaro la instancia
    $instance = new Google_Service_Compute_Instance();
    // Le doy un nombre
    $instance->setName($instancia);
    // Le pongo un tipo de instancia
    $machinetype = $CFG->guacamole_machine_type ?? 'n2d-custom-2-6144';
    $instance->setMachineType('projects/' . $CFG->guacamole_project_cloud . '/zones/' . $CFG->guacamole_zone_cloud . '/machineTypes/' . $machinetype);
    // Le pongo una interfaz de red
    $googlenetworkinterface = new Google_Service_Compute_NetworkInterface();
    $googlenetworkinterface->setNetwork('projects/' . $CFG->guacamole_project_cloud . '/global/networks/default');
    $googlenetworkinterface->setName('interfaz' . $instancia);
    // Interfaz de acceso
    $accessconfig = new Google_Service_Compute_AccessConfig();
    $accessconfig->setKind('compute#accessConfig');
    $accessconfig->setName('External NAT');
    $accessconfig->setType('ONE_TO_ONE_NAT');
    $googlenetworkinterface->setAccessConfigs([$accessconfig]);

    $instance->setNetworkInterfaces([$googlenetworkinterface]);
    // Le pongo un disco

    // Creo un disco
    $newdisk = new Google_Service_Compute_Disk();
    // Le doy un nombre al disco
    $newdisk->setName($instancia);

    // Cambia el tipo de disco aquí
    $disktype = $CFG->guacamole_disk_type ?? 'pd-ssd';
    $newdisk->setType('projects/' . $CFG->guacamole_project_cloud . '/zones/' . $CFG->guacamole_zone_cloud . '/diskTypes/' . $disktype);

    $imagedisk = $image->cloudimage;
    $newdisk->setSourceImage('https://www.googleapis.com/compute/v1/projects/' . $CFG->guacamole_project_cloud . '/global/images/' . $imagedisk);

    $insertdiskoperation = $service->disks->insert($project, $zone, $newdisk);
    if (waitForZoneOperationCompletion($service, $project, $zone, $insertdiskoperation->getName()) > 0) {
        throw new moodle_exception('gcperror', 'mod_guacamole', '', 'Error inserting disk: ' . $instancia);
    }

    $bootdisk = $service->disks->get($project, $zone, $instancia);
    if ("READY" !== $bootdisk->getStatus()) {
        throw new moodle_exception('gcperror', 'mod_guacamole', '', "Disk not ready after insert: " . $instancia);
    }

    $primarydisk = new Google_Service_Compute_AttachedDisk();
    $primarydisk->setBoot("TRUE");
    $primarydisk->setDeviceName("primary");
    $primarydisk->setMode("READ_WRITE");
    $primarydisk->setSource($bootdisk->getSelfLink());
    $primarydisk->setType("PERSISTENT");
    $instance->setDisks([$primarydisk]);

    $response = $service->instances->insert($project, $zone, $instance);
    if (waitForZoneOperationCompletion($service, $project, $zone, $response->getName()) > 0) {
        throw new moodle_exception('gcperror', 'mod_guacamole', '', 'Error inserting instance: ' . $instancia);
    }
}

/**
 * Checks whether a GCP persistent disk with the given name exists in the configured zone.
 *
 * @param string $diskname The disk name to check.
 * @return bool True if the disk exists, false otherwise.
 */
function existdisk($diskname) {
    global $CFG;
    $diskname = strtolower($diskname);
    $exist = false;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;

    $optparams = [];

    $response = $service->disks->listDisks($project, $zone, $optparams);
    $array = $response['items'];
    foreach ($array as $instance => $valor) {
        $diskinstance = $array[$instance]['name'];
        if (strcmp($diskname, $diskinstance) === 0) {
            $exist = true;
        }
    }
    return $exist;
}

/**
 * Deletes a GCP Compute Engine instance.
 *
 * @param string $instance The instance name to delete.
 */
function deleteinstance($instance) {
    global $CFG;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;
    $response = $service->instances->delete($project, $zone, $instance);
    if (waitForZoneOperationCompletion($service, $project, $zone, $response->getName()) > 0) {
        throw new moodle_exception('gcperror', 'mod_guacamole', '', 'Error deleting instance: ' . $instance);
    }
}

/**
 * Deletes a GCP persistent disk.
 *
 * @param string $disk The disk name to delete.
 */
function deletedisk($disk) {
    global $CFG;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;
    $response = $service->disks->delete($project, $zone, $disk);
    if (waitForZoneOperationCompletion($service, $project, $zone, $response->getName()) > 0) {
        throw new moodle_exception('gcperror', 'mod_guacamole', '', 'Error deleting disk: ' . $disk);
    }
}

/**
 * Stops (deletes instance and disk) the named VM.
 *
 * @param string $instancia The instance name to stop.
 */
function stopinstance($instancia) {
    deleteInstance($instancia);
    deleteDisk($instancia);
}

/**
 * Sends a stop (power-off) request to a running GCP Compute Engine instance.
 *
 * @param string $instance The instance name to stop.
 */
function stopvm($instance) {
    global $CFG;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;

    try {
        $response = $service->instances->stop($project, $zone, $instance);
    } catch (Exception $e) {
        $admins = get_admins();
        foreach ($admins as $admin) {
            email_to_user($admin, $admin, "Error parando la instancia: " . $instance, "Exception: " . $e);
        }
    }
}

/**
 * Checks whether a GCP Compute Engine instance with the given name exists.
 *
 * @param string $instance The instance name to check.
 * @return bool True if the instance exists, false otherwise.
 */
function existinstance($instance) {
    global $CFG;
    $exist = false;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;

    $optparams = [];

    $response = $service->instances->listInstances($project, $zone, $optparams);
    $array = $response['items'];
    foreach ($array as $instancearray => $valor) {
        $diskinstance = $array[$instancearray]['name'];
        if (strcmp($instance, $diskinstance) === 0) {
            $exist = true;
        }
    }
    return $exist;
}

/**
 * Extracts the image name prefix from an instance name (strips the last -suffix).
 *
 * @param string $instance The full instance name (e.g. image-123-456).
 * @return string The image name portion.
 */
function obtainimagename($instance) {
    return substr($instance, 0, strrpos($instance, '-'));
}

/**
 * Returns the GCP status string of an instance (RUNNING, STAGING, TERMINATED…)
 * or empty string if the instance does not exist.
 *
 * @param string $instance The instance name to query.
 * @return string GCP status string, or '' if not found.
 */
function getinstancestatus($instance) {
    global $CFG;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    try {
        $inst = $service->instances->get(
            $CFG->guacamole_project_cloud,
            $CFG->guacamole_zone_cloud,
            $instance
        );
        return $inst->getStatus();
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Starts a stopped GCP Compute Engine instance.
 *
 * @param string $instance The instance name to start.
 * @return \Google_Service_Compute_Operation The start operation response.
 */
function startinstance($instance) {
    global $CFG;
    $service = new Google_Service_Compute(guacamole_gcp_client());
    $project = $CFG->guacamole_project_cloud;
    $zone = $CFG->guacamole_zone_cloud;

    try {
        $response = $service->instances->start($project, $zone, $instance);
    } catch (Exception $e) {
        $admins = get_admins();
        foreach ($admins as $admin) {
            email_to_user($admin, $admin, "Error iniciando la instancia: " . $instance, "Exception: " . $e);
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
 * @package mod_guacamole
 */
function waitforzoneoperationcompletion(
    $computeservice,
    $project,
    $zone,
    $operation
) {
    $deadline = time() + 600; // 10-minute wall-clock limit.
    for ($x = 0; $x <= 20 && time() < $deadline; $x++) {
        $operationstatus = $computeservice->zoneOperations->get(
            $project,
            $zone,
            $operation
        );
        if ("DONE" == $operationstatus->getStatus()) {
            return 0;
        }
        sleep(min(2 * $x, $deadline - time()));
    }
    return 1;
}
