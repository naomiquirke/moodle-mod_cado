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
 * Set up the form to enable a user with generation capability to propose the CADO for approval, and trigger the appropriate events
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$cmid = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'cado');
$context = context_module::instance($cmid);
require_login($course, true, $cm);
require_capability('mod/cado:generate', $context);

$viewedcado = new mod_cado_cado($context, $cm, $course);

$url = new moodle_url('/mod/cado/propose.php', ['id' => $cmid]);
$PAGE->set_url($url, ['id' => $cmid]);

$title = get_string('modulename', 'cado');
$PAGE->set_title($title);

$nexturl = new moodle_url('/mod/cado/view.php', array('id' => $cmid));
$proposeform = new mod_cado_get_recipients_form($url, ['purpose' => 'propose', 'context' => $context]);

if ($proposeform->is_cancelled()) {
    redirect($nexturl);
} else if (($fromform = $proposeform->get_data())) {
    $proposeid = $fromform->possiblelist;
    $viewedcado->proposeupdate($proposeid);
    $event = \mod_cado\event\propose_cado::create(['context' => $context, 'objectid' => $cm->instance,
        'other' => ['courseid' => $course->id , 'groupmode' => $cm->groupingid , 'proposeid' => $proposeid] ] );
    $event->trigger();
    $viewedcado->workflownotify('propose', $nexturl, null, $proposeid);
    // Note redirect is included in workflownotify.
} else {
    $formrenderer = $PAGE->get_renderer('mod_cado');
    $formrenderer->render_form_header();
    $proposeform->display();
    $formrenderer->render_form_footer();
}
