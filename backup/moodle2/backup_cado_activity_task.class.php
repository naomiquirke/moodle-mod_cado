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
 * Version 1.1
 * Backup cado module class
 *
 * @package   mod_cado
 * @copyright 2020 Naomi Quirke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/cado/backup/moodle2/backup_cado_stepslib.php'); // Because it exists (must).

class backup_cado_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the cado.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_cado_activity_structure_step('cado_structure', 'cado.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of cados.
        $search = "/(".$base."\/mod\/cado\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@CADOINDEX*$2@$', $content);

        // Link to cado view by moduleid.
        $search = "/(".$base."\/mod\/cado\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@CADOVIEWBYID*$2@$', $content);

        return $content;
    }
}
