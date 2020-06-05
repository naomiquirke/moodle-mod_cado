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
 * ALERT THIS CODE IS NOT IN USE $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
 */

defined('MOODLE_INTERNAL') || die;

require_once('../../config.php');
global $DB;
$id = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'cado');
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/cado:generate', $context);
$copied = mod_cado_cado::getcadorecord($recordid);
$copied->timegenerated = time();
$copied->timemodified = time();
$copied->timeproposed = null;
$copied->timeapproved = null;
$copied->approvecomment=null;
$copied->generatepage = null;
$success = mod_cado_cado::getnewcadorecord($copied);
if (!$success) {
    debugging('failed to update DB');
}