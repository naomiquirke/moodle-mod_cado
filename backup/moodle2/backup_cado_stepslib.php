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
 * Classes that represent the backup steps added in define_my_steps within backup_cado_activity_task
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class that represents the backup steps added in define_my_steps within backup_cado_activity_task
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_cado_activity_structure_step extends backup_activity_structure_step {

    /**
     * Definition of structure
     *
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        // TODO  $userinfo = $this->get_setting_value('userinfo'); At present we ignore this because
        // I haven't decided whether we should keep all this information or not.
        // At present when we restore, if it userinfo is turned on then it keeps the approval turned on, otherwise it turns it off.

        // Define each element separated.
        $cado = new backup_nested_element('cado', ['id'], [ // Core information that can form CADO.
            'name', 'timemodified', 'timegenerated', 'timeproposed', 'generateuser', 'approveuser', 'timeapproved'
            , 'approvecomment', 'generatedpage', 'generatedjson', 'cadocomment', 'cadobiblio', 'cadointro'
            , 'cadocommentformat', 'cadobiblioformat', 'cadointroformat']);

        // Build the tree.

        // Define sources.
        $cado->set_source_table('cado', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations.
        $cado->annotate_ids('user', 'generateuser');
        $cado->annotate_ids('user', 'approveuser');

        // Define file annotations.

        // Return the root element (resource), wrapped into standard activity structure.
        return  $this->prepare_activity_structure($cado);
    }
}
