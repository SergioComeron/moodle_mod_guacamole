<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(__FILE__)).'/lib.php');

global $CFG, $DB;
require_once($CFG->dirroot . '/mod/guacamole/instances/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$cm = get_coursemodule_from_id('guacamole', $id, 0, false, MUST_EXIST);
$gu = optional_param('gu', 0, PARAM_INT);
$course  = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$imageid = optional_param('img', 0, PARAM_INT);
$computerid = optional_param('comp', 0, PARAM_INT);
$userid  = optional_param('usr', 0, PARAM_INT);

require_login($course, true, $cm);

$PAGE->set_url('/mod/guacamole/start.php');

$user = $DB->get_record('user', array('id'=>$userid));
$guacamole = $DB->get_record('guacamole', array('id'=>$gu));
$image = $DB->get_record('guacamole_images', array('id'=>$imageid));

$guacamolecomputer = null;
if ($computerid==0){
    $computername=$image->cloudimage.'-'.$image->id.'-'.$user->id;
    $guacamolecomputer = $DB->get_record('guacamole_computers', array('imageid'=>$imageid, 'userid'=>$userid));
    if ($guacamolecomputer !=null&&$guacamolecomputer->cloudimage!=$image->cloudimage){
        $guacamolecomputer->state = 'deleting';
        $DB->update_record('guacamole_computers', $guacamolecomputer);
        stopinstance($guacamolecomputer->cloudimage.'-'.$guacamolecomputer->imageid.'-'.$guacamolecomputer->userid);
        $DB->delete_records('guacamole_computers', array('imageid' => $guacamolecomputer->imageid, 'userid' => $guacamolecomputer->userid));
    }
}else{
    $guacamolecomputer = $DB->get_record('guacamole_computers', array('id'=>$computerid));
    $computername = $guacamolecomputer->cloudimage.'-'.$image->id.'-'.$user->id;
}


$oldState=null;
if (existDisk($computername)==false){
    $oldstate='stopped';

    $timecreated = time();
    $guacamolecomputer = new stdClass;
    $guacamolecomputer->imageid = $image->id;
    $guacamolecomputer->userid = $user->id;
    $guacamolecomputer->cloudimage = $image->cloudimage;
  //  $guacamolecomputer->guaidconnection = $guaidconnection;
    $guacamolecomputer->state = "loading";
    $guacamolecomputer->timecreated = $timecreated;
    $guacamolecomputer->timelaststart = $timecreated;
    $guacamolecomputer->minutestoshutdown = $guacamole->minutestoshutdown;
    $guacamolecomputer->daystodelete = $guacamole->daystodelete;
    $guacamolecomputer->timetodelete = $timecreated + ($guacamole->daystodelete*60*60*24);
    $DB->insert_record('guacamole_computers', $guacamolecomputer);
    createInstance($image->id,$user->id);
    $computername=strtolower($computername);
    crearUsuario($user->username);
    $guaidconnection= crearConexion($image->id, $user->id, $computername);
    darPermiso($guaidconnection, $user->username);
    $guacamolecomputer = $DB->get_record('guacamole_computers', array('imageid'=>$image->id, 'userid'=>$user->id));
    $guacamolecomputer->guaidconnection = $guaidconnection;
    $DB->update_record('guacamole_computers', $guacamolecomputer);
}else{
    $timestarted = time();
    $computername=strtolower($computername);
    $guacamolecomputer = $DB->get_record('guacamole_computers', array('imageid'=>$image->id, 'userid'=>$user->id));
    $oldState = $guacamolecomputer->state;
    if ($guacamolecomputer->state != "started"){
      $guaidconnection= crearConexion($image->id, $user->id, $computername);
    }else{
      $guaidconnection = $guacamolecomputer->guaidconnection;
    }
    $guacamolecomputer->state = "loading";
    $guacamolecomputer->timelaststart = $timestarted;
    $guacamolecomputer->guaidconnection = $guaidconnection;
    if ($guacamole->daystodelete>$guacamolecomputer->daystodelete){
      $guacamolecomputer->daystodelete = $guacamole->daystodelete;
    }
    $guacamolecomputer->timetodelete = $timestarted + ($guacamolecomputer->daystodelete*60*60*24);
    if ($guacamole->minutestoshutdown>$guacamolecomputer->minutestoshutdown){
      $guacamolecomputer->minutestoshutdown = $guacamole->minutestoshutdown;
    }
    crearUsuario($user->username);
    if ($oldState != "started"){
      darPermiso($guacamolecomputer->guaidconnection, $user->username);
    }
    $DB->update_record('guacamole_computers', $guacamolecomputer);
}
if (strcmp($oldState, 'started') == 0){
  $espera=1;
}else{
  $espera = $CFG->guacamole_seconds_wait;
}
$client = new Google_Client();
$client->setApplicationName('Pruebas');
$client->useApplicationDefaultCredentials();
$client->addScope('https://www.googleapis.com/auth/cloud-platform');
subirFileJson();

$client->setAuthConfig($CFG->dataroot . '/temp/auth.json');
$service = new Google_Service_Compute($client);

$project = $CFG->guacamole_project_cloud;
$zone = $CFG->guacamole_zone_cloud;
$idConnect = obtenerIdInstanciaGuacamole($computername);
$type='c';
$dataBase='mysql';
$nullChar="\0";
$str=$idConnect."\0".$type."\0".$dataBase;
$urlG=$CFG->guacamole_domain.'/guacamole/#/client/'.base64_encode($str);
startinstance($computername);

$datos = array('url'=>$urlG);

sleep($espera);
$guacamolecomputer = $DB->get_record('guacamole_computers', array('imageid'=>$image->id, 'userid'=>$user->id));
$guacamolecomputer->state = "started";
$DB->update_record('guacamole_computers', $guacamolecomputer);
$varR = array();
$varR["urlG"] = $urlG;
echo json_encode($varR);

?>
