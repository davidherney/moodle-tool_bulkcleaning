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
 * Strings for component 'tool_bulkcleaning', language 'es'
 *
 * @package    tool_bulkcleaning
 * @category   string
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['docs_pagenotfound'] = 'Página no encontrada';
$string['docs_title'] = 'Documentación';
$string['enrolcleaning_case_deletedusers'] = 'Usuarios eliminados';
$string['enrolcleaning_case_expiredenrols'] = 'Matrículas expiradas';
$string['enrolcleaning_case_suspendedusers'] = 'Usuarios suspendidos en la plataforma';
$string['enrolcleaning_cases'] = 'Casos de limpieza';
$string['enrolcleaning_cases_desc'] = 'Seleccione qué casos de limpieza de matrículas debe procesar la tarea programada.';
$string['enrolcleaning_enabled'] = 'Habilitar limpieza de matrículas';
$string['enrolcleaning_enabled_desc'] = 'Si se habilita, la tarea programada limpiará las matrículas según los casos seleccionados.';
$string['enrolcleaning_userfilter'] = 'Filtro de usuarios';
$string['enrolcleaning_userfilter_completed'] = 'Completó el curso';
$string['enrolcleaning_userfilter_desc'] = 'Seleccione la condición que deben cumplir los usuarios para ser considerados en la limpieza de matrículas.';
$string['enrolcleaning_userfilter_noaccess'] = 'Nunca accedió al curso';
$string['enrolcleaning_userfilter_nogrades'] = 'Sin calificaciones en el curso';
$string['enrolcleaning_userfilter_none'] = 'Sin restricción';
$string['enrolcleaning_userfilter_notcompleted'] = 'No completó el curso';
$string['pluginname'] = 'Limpieza masiva';
$string['privacy:metadata:enrol'] = 'Registros de acciones de limpieza de matrículas realizadas sobre usuarios.';
$string['privacy:metadata:enrol:courseid'] = 'El ID del curso del cual se desmatriculó al usuario.';
$string['privacy:metadata:enrol:details'] = 'Detalles adicionales de la acción de limpieza (caso, plugin de matrícula, rol, fechas).';
$string['privacy:metadata:enrol:timecreated'] = 'La fecha en que se realizó la acción de limpieza.';
$string['privacy:metadata:enrol:userid'] = 'El ID del usuario cuya matrícula fue limpiada.';
$string['privacy:metadata:users'] = 'Registros de acciones de limpieza de usuarios (suspender o eliminar).';
$string['privacy:metadata:users:details'] = 'Detalles adicionales de la acción de limpieza (caso, días, acción realizada).';
$string['privacy:metadata:users:timecreated'] = 'La fecha en que se realizó la acción de limpieza.';
$string['privacy:metadata:users:userid'] = 'El ID del usuario que fue suspendido o eliminado.';
$string['privacy:path:enrolcleaning'] = 'Registros de limpieza de matrículas';
$string['privacy:path:userscleaning'] = 'Registros de limpieza de usuarios';
$string['tab_enrolcleaning'] = 'Limpieza de matrículas';
$string['tab_userscleaning'] = 'Limpieza de usuarios';
$string['task_enrolcleaning'] = 'Tarea de limpieza de matrículas';
$string['task_userscleaning'] = 'Tarea de limpieza de usuarios';
$string['userscleaning_action'] = 'Acción de limpieza';
$string['userscleaning_action_delete'] = 'Eliminar el usuario';
$string['userscleaning_action_desc'] = 'Seleccione la acción a realizar sobre los usuarios que cumplan los criterios de limpieza.';
$string['userscleaning_action_suspend'] = 'Suspender el usuario';
$string['userscleaning_case_nologin'] = 'Sin inicio de sesión en X días';
$string['userscleaning_cases'] = 'Casos de limpieza';
$string['userscleaning_cases_desc'] = 'Seleccione qué casos de limpieza de usuarios debe procesar la tarea programada.';
$string['userscleaning_enabled'] = 'Habilitar limpieza de usuarios';
$string['userscleaning_enabled_desc'] = 'Si se habilita, la tarea programada limpiará los usuarios según los casos seleccionados.';
$string['userscleaning_nologin_days'] = 'Días sin inicio de sesión';
$string['userscleaning_nologin_days_desc'] = 'Número de días sin inicio de sesión para considerar a un usuario como inactivo. Los usuarios que no hayan iniciado sesión en este número de días serán suspendidos/borrados.';
