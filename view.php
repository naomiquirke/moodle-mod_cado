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
 * Version 1.0
 * Basic view page, starting point to every CADO type user interaction
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$cmid = required_param('id', PARAM_INT);
$reportformat= optional_param('reportformat', null, PARAM_ALPHA);
$compareid = optional_param('compareid', null, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'cado');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/cado:view', $context);

$viewedcado = new mod_cado_cado($context, $cm, $course);
if ($compareid && has_capability('mod/cado:compare', $context)) {
    $getcompared = $viewedcado->compare($compareid);
} else {$compareid = null; }

$urlparams = ['id' => $cmid,
                'reportformat' => $reportformat,
                'compareid' => $compareid ];

$url = new moodle_url('/mod/cado/view.php', $urlparams);
$PAGE->set_url($url);
$reportformat ? $PAGE->set_pagelayout($reportformat) : $PAGE->set_pagelayout('standard') ;
$title = get_string('modulename', 'cado');
$PAGE->set_title($title);
$PAGE->set_heading($viewedcado->instance->name);

$myrenderer = $PAGE->get_renderer('mod_cado');
if ($reportformat=='print') {
    echo '<div style = "max-width:98%;">';  //try to get this looking ok on printout try: 19cm; 670px
}

//This tests whether a report has yet been generated, if not and capability exists then it will generate
//It also tests whether generation and approval status statements should be included, and if so, it works them out
$showcentral = 1;
$cs = new stdClass();
$cs->statecomment = null;
$cs->showtime = null;
$cs->approvecomment = null;
if (!$viewedcado->instance->timeapproved) {  //If not approved
    if (!has_capability('mod/cado:generate', $context)) { //if can't generate then simply display status state
        $cs->statecomment = has_capability('mod/cado:approve', $context) ? get_string('notgenerated','cado') : get_string('notavailable','cado');
        $showcentral = 0;
    } else { //if can generate then ensure generation then display appropriate status state
        if ($viewedcado->instance->timegenerated == 0) {  //if not generated, then first generate
            $viewedcado->cadogenerate($myrenderer);
        }

        if ($viewedcado->instance->timeproposed == 0) { //status state for not yet proposed
            $cs->statecomment = get_string('generateddraft','cado',['genuser'=>mod_cado_check::getusername($viewedcado->instance->generateuser)]);
            $cs->showtime = $viewedcado->instance->timegenerated; //add time separately so that it can be formatted by user specification
        } else { //status state for has been proposed
            $cs->statecomment = get_string('generatedproposed','cado',
                [ 'genuser'=>mod_cado_check::getusername($viewedcado->instance->generateuser), 
                  'approver'=>mod_cado_check::getusername($viewedcado->instance->approveuser) ] );
            $cs->showtime = $viewedcado->instance->timeproposed; //add time separately so that it can be formatted by user specification
        }

        if ($viewedcado->instance->approvecomment) { //if there is an approve comment available, even if it is not currently approved, then add here
            $cs->notapprovedcomment = get_string('notapproved','cado'); //cannot add who didn't approve because it may show someone to whom the cado was subsequently proposed.  So add this information automatically to approve/not approved comment
            $cs->approvecomment = $viewedcado->instance->approvecomment;
        }
    }
} else { // is approved
    if (has_capability('mod/cado:generate', $context) || has_capability('mod/cado:approve', $context)) { //can show workflow status 
        $cs->statecomment = get_string('approvedon','cado',['approver'=>mod_cado_check::getusername($viewedcado->instance->approveuser)]);
        $cs->approvecomment = $viewedcado->instance->approvecomment;
        $cs->showtime = $viewedcado->instance->timeapproved; //add time separately so that it can be formatted by user specification
    } else {$cs = null;} //else only has view rights, no need to give approval information
} 

//Now set view completion and then output
$completion = new completion_info($course);
$completion->set_module_viewed($cm);
$myrenderer->render_form_header();
$myrenderer->render_state($cs);
if ($showcentral) { //now outputting the main report
    if ($compareid) {
        $myrenderer->render_compare($getcompared);
    } else {
        $myrenderer->rendered_already($viewedcado->instance->generatedpage); 
    }
}
if ($reportformat=='print') {
    echo '</div>';
}
$myrenderer->render_form_footer();