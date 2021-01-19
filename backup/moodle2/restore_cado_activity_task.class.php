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
 * Resource restore task that provides all the settings and steps.
 *
 * @package    mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/cado/backup/moodle2/restore_cado_stepslib.php'); // Because it exists (must).

/**
 * Class that provides all the settings and steps to perform one complete restore of the activity.
 *
 * @package    mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_cado_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // CADO only has one structure step.
        $this->add_step(new restore_cado_activity_structure_step('cado_structure', 'cado.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('cado',
            ['approvecomment', 'generatedpage', 'generatedjson', 'cadocomment', 'cadobiblio', 'cadointro'], 'cado');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('CADOVIEWBYID', '/mod/cado/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CADOINDEX', '/mod/cado/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     *
     */
    static public function define_cado_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('cado', 'add', 'view.php?id={course_module}', '{cado}');
        $rules[] = new restore_log_rule('cado', 'update', 'view.php?id={course_module}', '{cado}');
        $rules[] = new restore_log_rule('cado', 'view', 'view.php?id={course_module}', '{cado}');

        return $rules;
    }

    /**
     * Define the restore log rules for course that will be applied
     *
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('cado', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
