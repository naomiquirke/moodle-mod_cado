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
 * Classes that represent the backup steps added in define_my_steps within backup_cado_activity_task
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

 class backup_cado_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $cado = new backup_nested_element('cado', ['id'], [ //core information that can form CADO
            'name', 'timemodified', 'timegenerated', 'timeproposed', 'generateuser', 
            'approveuser', 'timeapproved', 'approvecomment', 'generatedpage','cadocomment', 'cadobiblio', 'cadointro']);
        
        // Build the tree

        // Define sources
        $cado -> set_source_table('cado', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        $cado->annotate_ids('user', 'generateuser');
        $cado->annotate_ids('user', 'approveuser');


        // Define file annotations
//        $cado->annotate_files('mod_cado', 'intro', null); // This file area hasn't itemid

        // Return the root element (resource), wrapped into standard activity structure
        return  $this->prepare_activity_structure($cado);
    }
 }