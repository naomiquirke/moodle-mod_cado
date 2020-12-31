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
 * This file contains the moodle hooks for the cado module.
 *
 * @package    mod_cado
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function cado_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
                return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
                return true;
        case FEATURE_GROUPINGS:
                return true;
        case FEATURE_MOD_INTRO:
                return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
                return true;
        case FEATURE_GRADE_HAS_GRADE:
                return false;
        case FEATURE_GRADE_OUTCOMES:
                return false;
        case FEATURE_BACKUP_MOODLE2:
                return true;
        case FEATURE_SHOW_DESCRIPTION:
                return false;
        default:
                return null;
    }
};
/**
 * Adds cado instance
 *
 * This is done by calling the add_instance() method of the cado type class
 * @param stdClass $data
 * @return int The instance id of the new assignment
 */
function cado_add_instance(stdClass $data) {
    // Passed cado edit settings form when created.
    return mod_cado_cado::add_instance($data);
};

/**
 * Updates cado instance
 *
 * This is done by calling the update_instance() method of the cado type class
 * @param stdClass $data
 * @return bool
 */
function cado_update_instance(stdClass $data) {
    return mod_cado_cado::update_instance($data);
};

/**
 * Deletes cado instance
 *
 * This is done by calling the delete_instance() method of the cado type class
 * @param int $id = cm->instance in course/lib.php.
 * @return bool
 */
function cado_delete_instance($id) {
    return mod_cado_cado::delete_instance($id);
};


/**
 * Adds cado specific settings to the cado settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $cadonode The node to add module settings to
 * @return void
 */
function cado_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $cadonode) {
    global $PAGE, $CFG;

    // Approve link.
    if (has_capability('mod/cado:approve', $PAGE->cm->context)) {
        $cadonode->add(get_string('approvelink', 'cado'),
                new moodle_url($CFG->wwwroot . '/mod/cado/approve.php', array('id' => $PAGE->cm->id)));
    }

    // Printview link.
    $cadonode->add(get_string('printview', 'cado'),
        new moodle_url($CFG->wwwroot . '/mod/cado/view.php', array('id' => $PAGE->cm->id, 'reportformat' => 'print')));

    // Compare link.
    if (has_capability('mod/cado:compare', $PAGE->cm->context)) {
          $cadonode->add(get_string('comparelink', 'cado'),
                new moodle_url($CFG->wwwroot . '/mod/cado/compare.php', array('id' => $PAGE->cm->id)));
    }
    // Send message to approvers.
    if (has_capability('mod/cado:generate', $PAGE->cm->context)) {
          $cadonode->add(get_string('propose', 'cado'),
                new moodle_url($CFG->wwwroot . '/mod/cado/propose.php', array('id' => $PAGE->cm->id)));
    }
}
/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function cado_get_view_actions() {
    return array('view', 'view all');
}


/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function cado_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array(), $filter);
    // We only need be concerned if there is a change of modified time.
    return $updates;
}
