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
 * Library of interface functions and constants for module guacamole
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the guacamole specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_guacamole
 * @copyright  2019 Sergio Comerón Sánchez-Paniagua <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('GUACAMOLE_ULTIMATE_ANSWER', 42);
require_once($CFG->dirroot . '/mod/guacamole/instances/lib.php');
/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function guacamole_supports($feature) {
    global $CFG;
    if ($CFG->branch >= 400) {
        switch ($feature) {
            case FEATURE_MOD_INTRO:
                return true;
            case FEATURE_SHOW_DESCRIPTION:
                return true;
            case FEATURE_GRADE_HAS_GRADE:
                return true;
            case FEATURE_BACKUP_MOODLE2:
                return true;
            case FEATURE_MOD_PURPOSE:
                return MOD_PURPOSE_INTERFACE;
            default:
                return null;
        }
    } else {
        switch ($feature) {
            case FEATURE_MOD_INTRO:
                return true;
            case FEATURE_SHOW_DESCRIPTION:
                return true;
            case FEATURE_GRADE_HAS_GRADE:
                return true;
            case FEATURE_BACKUP_MOODLE2:
                return true;
            default:
                return null;
        }
    }
}

/**
 * Saves a new instance of the guacamole into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $guacamole Submitted data from the form in mod_form.php
 * @param mod_guacamole_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted guacamole record
 */
function guacamole_add_instance($guacamole, $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/guacamole/locallib.php');

    $guacamole->timecreated = time();
    $cmid       = $guacamole->coursemodule;
    $guacamole->instancesinuse = 0;

    $guacamole->id = $DB->insert_record('guacamole', $guacamole);
    guacamole_update_calendar($guacamole, $cmid);

    return $guacamole->id;
}

/**
 * Increments the in-use counter for a guacamole instance and its image.
 *
 * @param stdClass $guacamole The guacamole instance record.
 */
function guacamole_add_instance_in_use($guacamole) {
    global $DB;
    $guacamoleimage = $DB->get_record('guacamole_imagenes', ['name' => $guacamole->nombreinstance]);

    $guacamole->timemodified = time();
    $guacamole->instancesinuse = $guacamole->instancesinuse + 1;
    $guacamoleimage->instancesinuse = $guacamoleimage->instancesinuse + 1;
    $DB->update_record('guacamole', $guacamole);
    $DB->update_record('guacamole_imagenes', $guacamoleimage);
}

/**
 * Decrements the in-use counter for a guacamole instance and its image.
 *
 * @param stdClass $guacamole The guacamole instance record.
 */
function guacamole_remove_instance_in_use($guacamole) {
    global $DB;
    $guacamoleimage = $DB->get_record('guacamole_imagenes', ['name' => $guacamole->nombreinstance]);

    $guacamole->timemodified = time();
    $guacamole->instancesinuse = $guacamole->instancesinuse - 1;
    $guacamoleimage->instancesinuse = $guacamoleimage->instancesinuse - 1;
    $DB->update_record('guacamole', $guacamole);
    $DB->update_record('guacamole_imagenes', $guacamoleimage);
}

/**
 * Returns a guacamole instance record by ID.
 *
 * @param int $id The guacamole record ID.
 * @return stdClass|false The record, or false if not found.
 */
function get_instance($id) {
    global $DB;
    return $DB->get_record('guacamole', ['id' => $id]);
}

/**
 * Updates an instance of the guacamole in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $guacamole An object from the form in mod_form.php
 * @param mod_guacamole_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function guacamole_update_instance($guacamole, $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/guacamole/locallib.php');

    $guacamole->timemodified = time();
    $guacamole->id = $guacamole->instance;
    $cmid       = $guacamole->coursemodule;

    $result = $DB->update_record('guacamole', $guacamole);
    guacamole_update_calendar($guacamole, $cmid);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every guacamole event in the site is checked, else
 * only guacamole events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function guacamole_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/guacamole/locallib.php');

    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('guacamole', ['id' => $instance], '*', MUST_EXIST);
        }
        if (isset($cm)) {
            if (!is_object($cm)) {
                $cm = (object)['id' => $cm];
            }
        } else {
            $cm = get_coursemodule_from_instance('guacamole', $instance->id);
        }
        guacamole_update_calendar($instance, $cm->id);
        return true;
    }

    if ($courseid) {
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$scorms = $DB->get_records('guacamole', ['guacamole' => $courseid])) {
            return true;
        }
    } else {
        if (!$guacamoles = $DB->get_records('guacamole', ['course' => $courseid])) {
            return true;
        }
    }

    foreach ($guacamoles as $guacamole) {
        // Create a function such as the one below to deal with updating calendar events.
        $cm = get_coursemodule_from_instance('guacamole', $guacamole->id);
        guacamole_update_calendar($guacamole, $cm->id);
    }
    return true;
}

/**
 * Removes an instance of the guacamole from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function guacamole_delete_instance($id) {
    global $CFG, $DB;

    if (! $guacamole = $DB->get_record('guacamole', ['id' => $id])) {
        return false;
    }
    $result = true;
    if (! $DB->delete_records('guacamole', ['id' => $guacamole->id])) {
        $result = false;
    }
    return $result;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $guacamole The guacamole instance record
 * @return stdClass|null
 */
function guacamole_user_outline($course, $user, $mod, $guacamole) {
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $guacamole the module instance record
 */
function guacamole_user_complete($course, $user, $mod, $guacamole) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in guacamole activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function guacamole_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link guacamole_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function guacamole_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
}

/**
 * Prints single activity item prepared by {@link guacamole_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function guacamole_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Authenticates against the Guacamole API and returns an auth token.
 *
 * @return string The Guacamole auth token.
 * @throws moodle_exception If authentication fails.
 */
function guacamole_get_token() {
    global $CFG;
    $ch = curl_init($CFG->guacamole_domain . '/guacamole/api/tokens');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . urlencode($CFG->guacamole_user) .
        '&password=' . urlencode($CFG->guacamole_password));
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    if (!is_array($data) || empty($data['authToken'])) {
        throw new moodle_exception('guacamoleautherror', 'mod_guacamole');
    }
    return $data['authToken'];
}

/**
 * Makes an authenticated request to the Guacamole REST API.
 *
 * @param string $token The Guacamole auth token.
 * @param string $endpoint The API endpoint path (without domain or token).
 * @param string $method HTTP method: GET, POST, PATCH, DELETE.
 * @param string|null $body Request body for POST/PATCH.
 * @param string|null $contenttype Content-Type header value.
 * @return array Decoded JSON response.
 */
function guacamole_api_request($token, $endpoint, $method = 'GET', $body = null, $contenttype = null) {
    global $CFG;
    $url = $CFG->guacamole_domain . $endpoint . '?token=' . urlencode($token);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } else if ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . ($contenttype ?? 'application/json')]);
    } else if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        if ($contenttype) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $contenttype]);
        }
    }
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Checks whether the configured Guacamole user has permission on a connection.
 *
 * @param string $idconnection The Guacamole connection identifier.
 * @param string $usuario The Guacamole username to check.
 */
function tiene_permiso($idconnection, $usuario) {
    $token = guacamole_get_token();
    $data = guacamole_api_request($token, '/guacamole/api/session/data/mysql/users/' . urlencode($usuario) . '/permissions');
    $permisos = array_column($data, $idconnection);
    return !empty($permisos);
}

/**
 * Returns the Unix timestamp of the last disconnection event for a named Guacamole connection.
 *
 * @param string $instancia The Guacamole connection name (instance name).
 * @return int Unix timestamp of last disconnect, or 0 if never disconnected.
 */
function fechadesconexion($instancia) {
    $token = guacamole_get_token();
    $history = guacamole_api_request($token, '/guacamole/api/session/data/mysql/history/connections?order=-startDate');
    foreach ($history as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (($entry['connectionName'] ?? '') === $instancia && !empty($entry['endDate'])) {
            return (int) ($entry['endDate'] / 1000);
        }
    }
    return 0;
}

/**
 * Checks whether a Guacamole connection with the given name exists.
 *
 * @param string $idconnection The connection name to look for.
 * @return bool True if the connection exists, false otherwise.
 */
function existeinstanciaguacamole($idconnection) {
    $token = guacamole_get_token();
    $tree = guacamole_api_request($token, '/guacamole/api/session/data/mysql/connectionGroups/ROOT/tree');
    foreach ($tree['childConnections'] ?? [] as $conn) {
        if ($conn['name'] === $idconnection) {
            return true;
        }
    }
    return false;
}

/**
 * Returns the Guacamole identifier for a named connection.
 *
 * @param string $nombreinstancia The connection name.
 * @return string|null The Guacamole identifier, or null if not found.
 */
function obteneridinstanciaguacamole($nombreinstancia) {
    $token = guacamole_get_token();
    $tree = guacamole_api_request($token, '/guacamole/api/session/data/mysql/connectionGroups/ROOT/tree');
    foreach ($tree['childConnections'] ?? [] as $conn) {
        if ($conn['name'] === $nombreinstancia) {
            return $conn['identifier'];
        }
    }
    return null;
}

/**
 * Returns the Guacamole identifier of a template connection matching the given image name.
 *
 * @param string $image The image (template connection) name.
 * @return string|null The Guacamole identifier, or null if not found.
 */
function obtainidimage($image) {
    global $CFG;
    $token = guacamole_get_token();
    $tree = guacamole_api_request($token, '/guacamole/api/session/data/mysql/connectionGroups/ROOT/tree');
    foreach ($tree['childConnectionGroups'] ?? [] as $group) {
        if ($group['name'] === $CFG->guacamole_template_group) {
            foreach ($group['childConnections'] ?? [] as $conn) {
                if (strcasecmp($conn['name'], $image) === 0) {
                    return $conn['identifier'];
                }
            }
        }
    }
    return null;
}

/**
 * Returns an array of connection names from the configured template group.
 *
 * @return array List of connection names.
 */
function obtenerlaboratorios() {
    global $CFG;
    $token = guacamole_get_token();
    $tree = guacamole_api_request($token, '/guacamole/api/session/data/mysql/connectionGroups/ROOT/tree');
    foreach ($tree['childConnectionGroups'] ?? [] as $group) {
        if ($group['name'] === $CFG->guacamole_template_group) {
            return array_column($group['childConnections'] ?? [], 'name');
        }
    }
    return [];
}

/**
 * Returns the number of computers in active states (started, loading, shutdown) for an image.
 *
 * @param int $image The guacamole_images record ID.
 * @return int Count of active computers.
 */
function getcomputersused($image) {
    global $DB;
    return (int) $DB->count_records_select(
        'guacamole_computers',
        'imageid = ? AND state IN (?,?,?)',
        [$image, 'started', 'loading', 'shutdown']
    );
}

/**
 * Returns an associative array of identifier => name for template connections.
 *
 * @return array Map of Guacamole identifier to lowercase connection name.
 */
function obtenerlaboratoriosname() {
    global $CFG;
    $token = guacamole_get_token();
    $tree = guacamole_api_request($token, '/guacamole/api/session/data/mysql/connectionGroups/ROOT/tree');
    foreach ($tree['childConnectionGroups'] ?? [] as $group) {
        if ($group['name'] === $CFG->guacamole_template_group) {
            $opciones = [];
            foreach ($group['childConnections'] ?? [] as $conn) {
                $opciones[$conn['identifier']] = strtolower($conn['name']);
            }
            return $opciones;
        }
    }
    return [];
}

/**
 * Creates a new Guacamole connection cloned from the image template and returns its identifier.
 *
 * @param int $imageid The guacamole_images record ID.
 * @param int $userid The Moodle user ID.
 * @param string $compname The hostname/connection name to assign.
 * @return string The new Guacamole connection identifier.
 */
function crearconexion($imageid, $userid, $compname) {
    global $DB;
    $image = $DB->get_record('guacamole_images', ['id' => $imageid]);
    $token = guacamole_get_token();

    $originalparameters = getinstanceparameters($image->guaidconnection);
    $originalinfo = getinfoinstance($image->guaidconnection);
    unset($originalinfo['identifier']);
    $originalinfo['name'] = $compname;
    $originalinfo['parentIdentifier'] = 'ROOT';
    $originalparameters['hostname'] = $compname;
    $originalinfo['parameters'] = $originalparameters;

    $result = guacamole_api_request(
        $token,
        '/guacamole/api/session/data/mysql/connections',
        'POST',
        json_encode($originalinfo),
        'application/json'
    );
    return $result['identifier'];
}

/**
 * Returns the connection parameters for a Guacamole connection.
 *
 * @param string $instance The Guacamole connection identifier.
 * @return array The connection parameters as an associative array.
 */
function getinstanceparameters($instance) {
    $token = guacamole_get_token();
    return guacamole_api_request($token, '/guacamole/api/session/data/mysql/connections/' . $instance . '/parameters');
}

/**
 * Returns the connection info (name, protocol, etc.) for a Guacamole connection.
 *
 * @param string $instance The Guacamole connection identifier.
 * @return array The connection info as an associative array.
 */
function getinfoinstance($instance) {
    $token = guacamole_get_token();
    return guacamole_api_request($token, '/guacamole/api/session/data/mysql/connections/' . $instance);
}

/**
 * Deletes a Guacamole connection via the REST API.
 *
 * @param string $instance The Guacamole connection identifier to delete.
 */
function eliminarconexion($instance) {
    $token = guacamole_get_token();
    guacamole_api_request($token, '/guacamole/api/session/data/mysql/connections/' . $instance, 'DELETE');
}

/**
 * Grants READ permission on a Guacamole connection to a user.
 *
 * @param string $instance The Guacamole connection identifier.
 * @param string $user The Guacamole username.
 */
function darpermiso($instance, $user) {
    $token = guacamole_get_token();
    $body = '[{"op":"add","path":"/connectionPermissions/' . $instance . '","value":"READ"}]';
    guacamole_api_request($token, '/guacamole/api/session/data/mysql/users/' . urlencode($user) . '/permissions', 'PATCH', $body);
}

/**
 * Revokes READ permission on a Guacamole connection from a user.
 *
 * @param string $instance The Guacamole connection identifier.
 * @param string $user The Guacamole username.
 */
function quitarpermiso($instance, $user) {
    $token = guacamole_get_token();
    $body = '[{"op":"remove","path":"/connectionPermissions/' . $instance . '","value":"READ"}]';
    guacamole_api_request($token, '/guacamole/api/session/data/mysql/users/' . urlencode($user) . '/permissions', 'PATCH', $body);
}

/**
 * Creates a new Guacamole user via the REST API.
 *
 * @param string $user The username to create in Guacamole.
 */
function crearusuario($user) {
    $token = guacamole_get_token();
    $body = json_encode([
        'username' => $user,
        'attributes' => [
            'disabled' => '',
            'expired' => '',
            'access-window-start' => '',
            'valid-from' => '',
            'valid-until' => '',
            'timezone' => '',
        ],
    ]);
    guacamole_api_request($token, '/guacamole/api/session/data/mysql/users', 'POST', $body, 'application/json');
}

/**
 * Returns the number of currently-in-use instances for an image (stub).
 *
 * @param int $image The image identifier.
 * @return int Always 0 (stub implementation).
 */
function getinstancesinuse($image) {
    $res = 0;
    return $res;
}

/**
 * Returns the computer record in state 'started' for the given user and image, or null.
 *
 * @param int $userid The Moodle user ID.
 * @param int $imageid The guacamole_images record ID.
 * @return stdClass|null The computer record, or null if none is started.
 */
function computerstartedbyuser($userid, $imageid) {
    global $DB;
    $guacamoleimage = $DB->get_record('guacamole_computers', ['userid' => $userid, 'imageid' => $imageid, 'state' => 'started']);
    if ($guacamoleimage != null) {
        return $guacamoleimage;
    } else {
        return null;
    }
}


/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function guacamole_get_extra_capabilities() {
    return [];
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of guacamole?
 *
 * This function returns if a scale is being used by one guacamole
 * if it has support for grading and scales.
 *
 * @param int $guacamoleid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given guacamole instance
 */
function guacamole_scale_used($guacamoleid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('guacamole', ['id' => $guacamoleid, 'grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of guacamole.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any guacamole instance
 */
function guacamole_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('guacamole', ['grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given guacamole instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $guacamole instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function guacamole_grade_item_update(stdClass $guacamole, $reset = false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = [];
    $item['itemname'] = clean_param($guacamole->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($guacamole->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $guacamole->grade;
        $item['grademin']  = 0;
    } else if ($guacamole->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$guacamole->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update(
        'mod/guacamole',
        $guacamole->course,
        'mod',
        'guacamole',
        $guacamole->id,
        0,
        null,
        $item
    );
}

/**
 * Delete grade item for given guacamole instance
 *
 * @param stdClass $guacamole instance object
 * @return grade_item
 */
function guacamole_grade_item_delete($guacamole) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        'mod/guacamole',
        $guacamole->course,
        'mod',
        'guacamole',
        $guacamole->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Update guacamole grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $guacamole instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function guacamole_update_grades(stdClass $guacamole, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = [];

    grade_update('mod/guacamole', $guacamole->course, 'mod', 'guacamole', $guacamole->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function guacamole_get_file_areas($course, $cm, $context) {
    return [];
}

/**
 * File browsing support for guacamole file areas
 *
 * @package mod_guacamole
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function guacamole_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the guacamole file areas
 *
 * @package mod_guacamole
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the guacamole's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function guacamole_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = []) {
    // global $DB, $CFG;

    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'jsonfile')) {
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_guacamole', 'jsonfile', 0, '/', get_config('mod_guacamole', 'jsonfile'));

        return send_stored_file($file, 0, 0, $forcedownload);
    }
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding guacamole nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the guacamole module instance
 * @param stdClass $course current course record
 * @param stdClass $module current guacamole instance record
 * @param cm_info $cm course module information
 */
function guacamole_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the guacamole settings
 *
 * This function is called when the context for the page is a guacamole module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $guacamolenode guacamole administration node
 */
function guacamole_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $guacamolenode = null) {
    // TODO Delete this function and its docblock, or implement it.
}
