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
 * Set up details re translation of CADO from HTML to JSON.
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
$urlparams = ['id' => $cmid];
$url = new moodle_url('/mod/cado/translate.php', $urlparams);
$PAGE->set_url($url);
$title = get_string('modulename', 'cado');
$PAGE->set_title($title);
$translatedcado = new mod_cado_translatecado($context, $cm, $course);

$PAGE->set_heading($translatedcado->instance->name);

$myrenderer = $PAGE->get_renderer('mod_cado');
$myrenderer->render_form_header();

echo $translatedcado->translate();

$myrenderer->render_form_footer();

