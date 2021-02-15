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
 * Set up the form to select the CADO for the comparison, and return this information to View
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$cmid = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'cado');
$context = context_module::instance($cmid);
require_login($course, true, $cm);
// If a person has capability compare, then they can compare course cado to any cados on the site.
require_capability('mod/cado:compare', $context);

$origincado = new mod_cado_cado($context, $cm, $course);

$url = new moodle_url('/mod/cado/compare.php', ['id' => $cmid]);
$PAGE->set_url($url);

$title = get_string('modulename', 'cado');
$PAGE->set_title($title);

$compareform = new mod_cado_compare_form($url, ['instance' => $cm->instance]);

if ($compareform->is_cancelled()) {
    redirect(new moodle_url('/mod/cado/view.php', array('id' => $cmid)));

} else if ($fromform = $compareform->get_data()) {
    if ($fromform->cadoid == 0) { // Also include "0".
        redirect($url);
    }
    $comparecmid = $DB->get_record('course_modules', ['instance' => $fromform->cadoid, 'module' => $cm->module]);
    redirect(new moodle_url('/mod/cado/view.php', ['id' => $cmid, 'compareid' => $comparecmid->id]));
} else {
    $formrenderer = $PAGE->get_renderer('mod_cado');
    $formrenderer->render_form_header();
    $compareform->display();
    $formrenderer->render_form_footer();

}
