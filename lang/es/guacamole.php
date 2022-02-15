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
$string['crontask'] = 'Parar laboratorios';
$string['crontaskdelete'] = 'Eliminar laboratorios';
$string['modulenameplural'] = 'Guacamoles';
$string['modulename_help'] = 'Usar el módulo guacamole para laboratorios virtuales';
$string['guacamole:addinstance'] = 'Añadir un nuevo guacamole';
$string['guacamole:configdaystodelete'] = 'Establecer el número de días para eliminar un laboratorio';
$string['guacamole:confignumberofinstances'] = 'Establecer el número de instancias de guacamole';
$string['guacamole:configtimetoshutdown'] = 'Establecer minutos para apagar un laboratorio';
$string['guacamole:submit'] = 'Enviar guacamole';
$string['guacamole:view'] = 'Ver guacamole';
$string['guacamolefieldset'] = 'Custom example fieldset';
$string['guacamolename'] = 'Nombre guacamole';
$string['guacamolename_help'] = 'This is the content of the help tooltip associated with the guacamolename field. Markdown syntax is supported.';
$string['guacamole'] = 'Guacamole';
$string['pluginadministration'] = 'Administración guacamole';
$string['pluginname'] = 'Guacamole';
$string['instruction'] = 'Click en el botón para acceder';
$string['access'] = 'Acceder';
$string['calendarstart'] = 'El laboratorio \'{$a}\' comienza';
$string['allow'] = 'Comienzo de un laboratorio';
$string['guacamoleinstance'] = 'Nombre laboratorio';
$string['guacamoinstance_help'] = 'This is the content of the help tooltip associated with the guacamolename field. Markdown syntax is supported.';
$string['daystodelete'] = 'Dias para eliminar';
$string['numberofinstances'] = 'Máximo número de instancias';
$string['sourceimage'] = 'Source image url from cloud';
$string['manageimages'] = 'Administrar imágenes';
$string['guacamole:manageimages'] = 'Administrar imágenes';
$string['addnewimage'] = 'Añadir nueva imagen';
$string['editaimage'] = 'Editar una imagen';
$string['mod'] = 'Modulos';
$string['image'] = 'Imagen';
$string['mindaystodelete'] = "Dias minimos para eliminar";
$string['maxdaystodelete'] = "Dias máximos para eliminar";
$string['defaultdaystodelete'] = "Días por defecto para eliminar";
$string['active'] = 'Activar';
$string['deleteimageconfirm'] = 'Estas seguro de eliminar esta imagen?';
$string['deleteimageconfirm'] = 'Estas seguro de eliminar esta máquina?';
$string['imagedeleted'] = 'Eliminar imagen';
$string['prueba'] = 'Prueba';
$string['mintimetoshutdown'] = 'Minutos mínimos para apagar';
$string['maxtimetoshutdown'] = 'Minutos máximos para apagar';
$string['defaulttimetoshutdown'] = 'Minutos por defecto para apagar';
$string['minutestoshutdown'] = 'Minutos para apagar';
$string['clicktoopen'] = 'Click {$a} para acceder al laboratorio.';
$string['numberfreelab'] = 'Número de laboratorios libres: ';
$string['here'] = 'aquí';
$string['labdesactivated'] = 'Laboratorio desactivado';
$string['busylab'] = 'Todos los laboratorios están ocupados';
$string['username'] = 'Nombre usuario guacamole';
$string['userpass'] = 'Contraseña usuario guacamole';
$string['projectcloud'] = 'Projecto del Cloud';
$string['domainserver'] = 'Dominio del servidor guacamole';
$string['proyectcloudzone'] = 'Zona del projecto en cloud';
$string['templatesgroup'] = 'Grupo plantillas';
$string['debug'] = 'Debug';
$string['secondswait'] = 'Segundos de espera';
$string['jsonfile'] = 'Archivo json google';
$string['projectcloudex'] = 'Nombre del proyecto en el cloud';
$string['domainserverex'] = 'Dominio del backend de guacamole';
$string['templatesgroupex'] = 'Grupo donde están las plantillas en guacamole';
$string['debugex'] = 'Muestra errores al lanzar un laboratorio';
$string['secondswaitex'] = 'Segundos de retardo al lanzar un laboratorio';
$string['showimages'] = 'Ver imágenes';
$string['guacamoleimagename'] = 'Nombre imagen disco';

$string['datecreation'] = 'Fecha creación';
$string['laststart'] = 'Último inicio';
$string['stophour'] = 'Hora parada';
$string['datetodelete'] = 'Fecha eliminación';
$string['availableinstances'] = 'Instancias disponibles';
$string['imagename'] = 'Nombre imagen';

$string['startvirtualmachine'] = 'Iniciar máquina virtual';
$string['notice'] = 'Aviso';
$string['machinenewbaseimage'] = 'Esta máquina tiene ahora una nueva imagen base disponible, probablemente porque hemos actualizado la versión de algún programa.';
$string['youcandeleteifyouwish'] = 'Si lo desea, puede eliminar su máquina actual que ha creado e iniciar una desde la nueva imagen, pero tenga en cuenta que todo lo que no esté guardado en la unidad de disco "Guacamole" se perderá.';
$string['createnewdeleting'] = 'Crear nueva máquina virtual eliminando la anterior';
$string['previousdisabled'] = 'La máquina anterior está desactivada, disculpe las molestias';
$string['createandstart'] = 'Crear e iniciar la máquina virtual';
$string['machinedesactivated'] = 'La máquina está desactivada. Disculpe las molestias';
$string['start'] = 'Iniciar la máquina virtual';
$string['noavailable'] = 'No puede crear e iniciar su máquina ahora porque hemos alcanzado el máximo permitido para máquinas como esta. Esto se debe a que hay otros estudiantes que ahora las están usando. Vuelva más tarde para ver si ya se han liberado recursos. Disculpe las molestias.';
$string['starting'] = 'Por favor espere, la máquina se está iniciando';
$string['redirectedfewminutes'] = 'Será redirigido aproximadamente en unos minutos';
$string['state'] = 'Estado';
$string['openvirtualmachine'] = 'Abrir la máquina virtual';
$string['openinnewtab'] = 'La máquina virtual se ha abierto en una nueva pestaña';
$string['ondeleting'] = 'No puede iniciar su máquina debido a que la máquina se está borrando. Inténtelo más tarde';
$string['trylater'] = 'No puede iniciar su máquina. Por favor, refresca la pestaña anterior e inténtelo más tarde';
