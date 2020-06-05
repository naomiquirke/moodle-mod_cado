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

require_once(__DIR__ . '/../../config.php');
 
$id = required_param('id', PARAM_INT);           // Course ID
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course); 
$PAGE->set_url('/mod/cado/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');

// Print the header.
$strplural = get_string('modulenameplural', 'cado');
$PAGE->navbar->add($strplural);
$PAGE->set_title($strplural);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($strplural));

$modinfo = get_fast_modinfo($course);
foreach ($modinfo->instances['cado'] as $cm) {
    if (!$cm->uservisible) { //only checking visibility not capability!
        continue;
    } else {
        $context = context_module::instance($cm->id);
        if (has_capability('mod/cado:view', $context)) {
            echo $OUTPUT->container($link = "<a href=\"view.php?id=$cm->id\">".format_string($cm->name,true)."</a>");
        }
    }
}

echo $OUTPUT->footer();

