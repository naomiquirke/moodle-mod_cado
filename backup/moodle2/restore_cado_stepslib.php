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
 * Define all the restore steps that will be used by the restore_cado_activity_task
 *
 * @package    mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step class to restore cado
 *
 * @package    mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_cado_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure step to restore cado
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('cado', '/activity/cado');
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Define structure step to restore cado
     *
     * @param object $data describing cado to be restored
     */
    protected function process_cado($data) {
        global $DB;
        global $USER;
        $userinfo = $this->get_setting_value('userinfo');

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

        // If not keeping userdata, then we need to force a regeneration on first view.
        if (!$userinfo) {
            $data->timemodified = time();
            $data->timegenerated = 0;
            $data->timeproposed = null;
            $data->generateuser = $USER->id;
            $data->approveuser = null;
            $data->timeapproved = null;
        }
        // Keep approval comments in case they are of use for a new version of course based on old version, but add a note to them.
        if ($data->approvecomment) {
            $data->approvecomment = '<div class="prevapprovecomment">' . $data->approvecomment
                . '</div><p class="approvecommentreviewed">'
                . get_string('restore', 'cado')
                . '</p>';
        }
        // Insert the resource record.
        $newitemid = $DB->insert_record('cado', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Define after_execute
     */
    protected function after_execute() {
        // Add related files, no need to match by itemname (just internally handled context).
    }
}
