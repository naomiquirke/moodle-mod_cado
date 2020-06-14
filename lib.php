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
 *
 * @package    mod_CADO
 * @copyright  2020 Naomi Quirke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function cado_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return false;

        default: return null;
    }
};

function cado_add_instance(stdClass $data) { //passed cado edit settings form when created
    return mod_cado_cado::add_instance($data);
};

function cado_update_instance(stdClass $data) {
    return mod_cado_cado::update_instance($data);
};

function cado_delete_instance($id) { //$id = cm->instance in course/lib.php
    return mod_cado_cado::delete_instance($id);
};


/**
 * Adds cado specific settings to the cado settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $cadonode The node to add module settings to
 * @return void
 */
function cado_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $cadonode){
    global $PAGE, $USER, $CFG;

//approve link
    if(has_capability('mod/cado:approve', $PAGE->cm->context)){
        $cadonode ->add(get_string('approvelink', 'cado'), new moodle_url($CFG->wwwroot . '/mod/cado/approve.php', array('id'=>$PAGE->cm->id)));
   }

//printview link
   $cadonode ->add(get_string('printview', 'cado'), new moodle_url($CFG->wwwroot . '/mod/cado/view.php', array('id'=>$PAGE->cm->id, 'reportformat' => 'print')));

 //compare link
 if(has_capability('mod/cado:compare', $PAGE->cm->context)){
    $cadonode ->add(get_string('comparelink', 'cado'), new moodle_url($CFG->wwwroot . '/mod/cado/compare.php', array('id'=>$PAGE->cm->id)));
    }
//send message to approvers
 if(has_capability('mod/cado:generate', $PAGE->cm->context)){
    $cadonode ->add(get_string('propose', 'cado'), new moodle_url($CFG->wwwroot . '/mod/cado/propose.php', array('id'=>$PAGE->cm->id)));
    }

}
