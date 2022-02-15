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

$string['modulename'] = 'Guacamole';
$string['crontask'] = 'Stop labs';
$string['crontaskdelete'] = 'Delete labs';
$string['modulenameplural'] = 'Guacamoles';
$string['modulename_help'] = 'Use the guacamole module for virtual lab';
$string['guacamole:addinstance'] = 'Add a new guacamole';
$string['guacamole:configdaystodelete'] = 'Set number of days to delete a lab';
$string['guacamole:confignumberofinstances'] = 'Set number of instances of a lab';
$string['guacamole:configtimetoshutdown'] = 'Set minutes to shutdown a lab';
$string['guacamole:submit'] = 'Submit guacamole';
$string['guacamole:view'] = 'View guacamole';
$string['guacamolefieldset'] = 'Custom example fieldset';
$string['guacamolename'] = 'Session name';
$string['guacamolename_help'] = 'This is the content of the help tooltip associated with the guacamolename field. Markdown syntax is supported.';
$string['guacamole'] = 'Guacamole';
$string['pluginadministration'] = 'Guacamole administration';
$string['pluginname'] = 'Guacamole';
$string['instruction'] = 'Click the button to acces';
$string['access'] = 'Access';
$string['calendarstart'] = 'The lab \'{$a}\' start';
$string['allow'] = 'Start of lab';
$string['guacamoleinstance'] = 'Lab name';
$string['guacamoinstance_help'] = 'This is the content of the help tooltip associated with the guacamolename field. Markdown syntax is supported.';
$string['daystodelete'] = 'Days to delete';
$string['numberofinstances'] = 'Default max number of instances';
$string['sourceimage'] = 'Source image url from cloud';
$string['addnewimage'] = 'Add a new image';
$string['editaimage'] = 'Edit a image';
$string['mod'] = 'Modules';
$string['image'] = 'Image';
$string['mindaystodelete'] = "Min days to delete";
$string['maxdaystodelete'] = "Max days to delete";
$string['defaultdaystodelete'] = "Default days to delete";
$string['active'] = 'Active';
$string['deleteimageconfirm'] = 'Are you sure you want to delete this image?';
$string['deletecomputerconfirm'] = 'Are you sure you want to delete this computer?';
$string['imagedeleted'] = 'Image deleted';
$string['prueba'] = 'Prueba';
$string['mintimetoshutdown'] = 'Min number of minutes to shutdown';
$string['maxtimetoshutdown'] = 'Max number of minutes to shutdown';
$string['defaulttimetoshutdown'] = 'Default number of minutes to shutdown';
$string['minutestoshutdown'] = 'Minutes to shutdown';
$string['clicktoopen'] = 'Click {$a} to access laboratory.';
$string['numberfreelab'] = 'Number of free laboratories: ';
$string['here'] = 'here';
$string['labdesactivated'] = 'Lab desactivated';
$string['username'] = 'Guacamole user name';
$string['userpass'] = 'Guacamole user pass';
$string['projectcloud'] = 'Cloud project';
$string['domainserver'] = 'Guacamole domain server';
$string['proyectcloudzone'] = 'Zone cloud project';
$string['templatesgroup'] = 'Templates group';
$string['debug'] = 'Debug';
$string['secondswait'] = 'Seconds wait';
$string['manageimages'] = 'Manage images';
$string['guacamole:manageimages'] = 'Manage images';
$string['jsonfile'] = 'Google json file';
$string['projectcloudex'] = 'Project name at cloud';
$string['domainserverex'] = 'Server domain of guacamole backend';
$string['templatesgroupex'] = 'Group where are the templates';
$string['debugex'] = 'Show errors when launching lab';
$string['secondswaitex'] = 'Number of seconds that delays the start of a laboratory';
$string['showimages'] = 'Show images';
$string['guacamoleimagename'] = 'Disk image name';
$string['help'] = 'Help';
$string['helpex'] = 'Help shown to the user';
$string['imagename'] = 'Name';

$string['datecreation'] = 'Date creation';
$string['laststart'] = 'Last start';
$string['stophour'] = 'Stop hour';
$string['datetodelete'] = 'Date to delete';
$string['availableinstances'] = 'Available instances';
$string['imagename'] = 'Image name';

$string['startvirtualmachine'] = 'Start the virtual machine';
$string['notice'] = 'Notice';
$string['machinenewbaseimage'] = 'This machine now has a new base image available, probably because we have updated the version of some program.';
$string['youcandeleteifyouwish'] = 'If you wish you can delete the current machine that you have created and start a new one from the new image, but keep in mind that everything that is not saved in the disk drive "Guacamole" will be lost.';
$string['createnewdeleting'] = 'Create new machine by deleting previous one';
$string['previousdisabled'] = 'The previous machine is disabled. Sorry for the inconvenience';
$string['createandstart'] = 'Create and start the virtual machine';
$string['machinedesactivated'] = 'The machine is desactivated. Sorry for the inconvenience';
$string['start'] = 'Start virtual machine';
$string['noavailable'] = 'You can not create and start your machine now because we have reached the maximum allowed for machines like this. This is because there are other students who are now using them. Check back later to see if resources have already been released. Sorry for the disturbances.';
$string['starting'] = 'Please wait. The lab is starting';
$string['redirectedfewminutes'] = 'It will be redirected approximately in a few minutes';
$string['state'] = 'State';
$string['openvirtualmachine'] = 'Open the virtual machine';
$string['openinnewtab'] = 'The virtual machine is open in a new tab';
$string['ondeleting'] = 'You cannot start your machine because the machine is being deleted. Try again later';
$string['trylater'] = 'You cannot start your machine. Please refresh the previous tab and try again later';
