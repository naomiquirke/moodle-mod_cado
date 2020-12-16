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
 * CADO module external API
 *
 * @package    mod_cado
 * @category   external
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/cado/lib.php');

/**
 * CADO module external functions
 *
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cado_external extends external_api {

    /**
     * Describes the parameters for view_cado.
     *
     * @return external_function_parameters
     */
    public static function view_cado_parameters() {
        return new external_function_parameters(
            array(
                'cadoid' => new external_value(PARAM_INT, 'CADO instance id')
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $cadoid The cado id.
     * @return array of warnings and status result
     * @throws moodle_exception
     */
    public static function view_cado($cadoid) {
        global $DB;
        $params = ['cadoid' => $cadoid];
        $params = self::validate_parameters(self::view_cado_parameters(), $params);
        $warnings = array();

        // Request and permission validation.
        $cado = $DB->get_record('cado', ['id' => $params['cadoid']], '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($cado, 'cado');

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/cado:view', $context);
        // Make event for viewing approved CADO.
        $eventdata = ['objectid' => $cado->id, 'context' => $context, 'courseid' => $course->id];

        $event = \mod_cado\event\approved_cado_viewed::create($eventdata);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('cado', $cado);
        $event->trigger();

        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        $result = ['status' => true, 'warnings' => $warnings];
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function view_cado_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'Status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

}
