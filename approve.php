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
 * Version 0.1
 *
 * @package    mod_CADO
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $DB, $PAGE, $USER;
$cmid = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'cado');
$context = context_module::instance($cmid);
require_login($course, true, $cm);
require_capability('mod/cado:approve', $context);

$urlparams = ['id' => $cmid, 'sesskey' => sesskey()];

$url = new moodle_url('/mod/cado/approve.php', $urlparams);
$PAGE->set_url($url);

$title = get_string('pluginname', 'cado');
$PAGE->set_title($title);

$approvecado = new mod_cado_cado($context, $cm, $course);
$mform_cado = new mod_cado_approve_form($url);
$nexturl = new moodle_url('/mod/cado/view.php', array('id' => $cmid));

if ($mform_cado->is_cancelled()) {
    redirect($nexturl); 
    
} else if (($fromform = $mform_cado->get_data()) && confirm_sesskey()) {
    $approvecado->approveupdate($fromform);
    if ($fromform->approved) {
        $event = \mod_cado\event\approve_cado::create(['context' => $context, 'objectid' => $cm->instance, 
            'other' =>['courseid' => $course->id , 'groupmode' => $cm->groupingid ] ] );
    } else {
        $event = \mod_cado\event\notapprove_cado::create(['context' => $context, 'objectid' => $cm->instance, 
            'other' =>['courseid' => $course->id , 'groupmode' => $cm->groupingid ] ] );
    }
    $event->trigger();
    $approvecado->workflownotify('approve',$nexturl,$fromform->approved);
        //redirect is included in workflownotify

} else {
    $formrenderer = $PAGE->get_renderer('mod_cado');
    $formrenderer->render_form_header();
    $mform_cado->set_last_data($approvecado);
    $mform_cado->display();
    $formrenderer->render_form_footer();
 
}
