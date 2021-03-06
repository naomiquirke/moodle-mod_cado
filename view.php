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
 * Basic view page, starting point to every CADO type user interaction
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$cmid = required_param('id', PARAM_INT);
$reportformat = optional_param('reportformat', null, PARAM_ALPHA);
$compareid = optional_param('compareid', null, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'cado');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/cado:view', $context);

$viewedcado = new mod_cado_cado($context, $cm, $course);
if (empty($viewedcado->instance->generatedjson) && $viewedcado->instance->timegenerated) {
    // Then CADO must have been generated prior to version 3.0 upgrade, so needs to be translated to JSON.
    $newinstance = $viewedcado->instance;
    $viewedcado = new mod_cado_translatecado($newinstance, $cm->groupingid);
    $viewedcado->translate();
}

if ($compareid && has_capability('mod/cado:compare', $context)) {
    $compared = new mod_cado_comparecado();
    $getcompared = $compared->compare($viewedcado->instance, $compareid);
} else {
    $compareid = null;
}

$urlparams = ['id' => $cmid,
                'reportformat' => $reportformat,
                'compareid' => $compareid ];

$url = new moodle_url('/mod/cado/view.php', $urlparams);
$PAGE->set_url($url);
$reportformat ? $PAGE->set_pagelayout($reportformat) : $PAGE->set_pagelayout('standard');
$title = get_string('modulename', 'cado');
$PAGE->set_title($title);
$PAGE->set_heading($viewedcado->instance->name);

$myrenderer = $PAGE->get_renderer('mod_cado');
if ($reportformat == 'print') {
    echo '<div class = "cado-outer-print">';  // Note try to get this looking ok on printout try: 19cm; 670px.
}

// This tests whether a report has yet been generated, if not and capability exists then it will generate.
// It also tests whether generation and approval status statements should be included, and if so, it works them out.
$showcentral = 1;
$cs = new stdClass();
$cs->statecomment = null;
$cs->showtime = null;
$cs->approvecomment = null;
if (!$viewedcado->instance->timeapproved) {  // If not approved.
    if (!has_capability('mod/cado:generate', $context)) { // Note if can't generate then simply display status state.
        $cs->statecomment = has_capability('mod/cado:approve', $context) ?
            get_string('notgenerated', 'cado') : get_string('notavailable', 'cado');
        $showcentral = 0;
    } else { // Able to generate.
        // If not yet generated, then generate.
        if ($viewedcado->instance->timegenerated == 0) {
            $viewedcado->cadogenerate($myrenderer);
        }

        // Now display appropriate status state.
        if ($viewedcado->instance->timeproposed == 0) { // Note status state for not yet proposed.
            $cs->statecomment = get_string('generateddraft', 'cado',
                ['genuser' => mod_cado_check::getusername($viewedcado->instance->generateuser)]);
            $cs->showtime = $viewedcado->instance->timegenerated;
            // Note add time separately so that it can be formatted by user specification.
        } else { // Note status state for has been proposed.
            $cs->statecomment = get_string('generatedproposed', 'cado',
                [ 'genuser' => mod_cado_check::getusername($viewedcado->instance->generateuser),
                  'approver' => mod_cado_check::getusername($viewedcado->instance->approveuser) ] );
            $cs->showtime = $viewedcado->instance->timeproposed;
            // Note add time separately so that it can be formatted by user specification.
        }

        if ($viewedcado->instance->approvecomment) {
            // If there is an approve comment available, even if not currently approved, add here.
            $cs->notapprovedcomment = get_string('notapproved', 'cado');
            // Note cannot add who didn't approve because it may show someone to whom the cado was subsequently proposed.
            // So add this information automatically to approve/not approved comment.
            $cs->approvecomment = format_text($viewedcado->instance->approvecomment, $viewedcado->instance->approvecommentformat);
        }
    }
} else { // This is when CADO is approved.
    // First the situation when user can view the workflow status.
    if (has_capability('mod/cado:generate', $context) || has_capability('mod/cado:approve', $context)) {
        $cs->statecomment = get_string('approvedon', 'cado',
            ['approver' => mod_cado_check::getusername($viewedcado->instance->approveuser)]);
        $cs->approvecomment = format_text($viewedcado->instance->approvecomment, $viewedcado->instance->approvecommentformat);
        $cs->showtime = $viewedcado->instance->timeapproved;
        // Note add time separately so that it can be formatted by user specification.
    } else { // Now for when reader only has view rights to CADO, not to approval information.
        $cs = null;
    }
    // Make event for viewing approved CADO.
    $eventdata = array('objectid' => $cmid, 'context' => $context, 'courseid' => $course->id);
    $event = \mod_cado\event\approved_cado_viewed::create($eventdata);
    $event->trigger();
}

// Now set view completion and then output.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$myrenderer->render_form_header();
$myrenderer->render_state($cs);
if ($showcentral) { // Now outputting the main report.
    if ($compareid) {
        $myrenderer->render_compare($getcompared);
    } else {
        $coursegenerated = (object) json_decode($viewedcado->instance->generatedjson, true);
        // Now add the bits that are in the table or are independent to the individual course details.
        $coursegenerated->logourl = get_config('cado')->showlogo ? $myrenderer->get_logo_url() : null;
        $coursegenerated->cadointro = format_text($viewedcado->instance->cadointro, $viewedcado->instance->cadointroformat);
        // Include these based on site settings at time of generation.
        $coursegenerated->cadocomment = $coursegenerated->commentexists ?
            format_text($viewedcado->instance->cadocomment, $viewedcado->instance->cadocommentformat) : null;
        $coursegenerated->cadobiblio = $coursegenerated->biblioexists ?
            format_text($viewedcado->instance->cadobiblio, $viewedcado->instance->cadobiblioformat) : null;

        // Finally output.
        echo $myrenderer->render_cado($coursegenerated);
    }
}
if ($reportformat == 'print') {
    echo '</div>';
}
$myrenderer->render_form_footer();
